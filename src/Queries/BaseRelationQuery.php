<?php


namespace Arrilot\BitrixModels\Queries;


use Arrilot\BitrixModels\Models\BaseBitrixModel;
use Illuminate\Support\Collection;

/**
 * BaseRelationQuery содержит основные методы и свойства для загрузки релейшенов
 *
 * @method BaseBitrixModel first()
 * @method Collection|BaseBitrixModel[] getList()
 * @property array $select
 */
trait BaseRelationQuery
{
    /**
     * @var bool - когда запрос представляет связь с один-ко-многим. Если true, вернуться все найденные модели, иначе только первая
     */
    public $multiple;
    /**
     * @var string - настройка связи моделей. ключ_у_связанной_модели
     */
    public $foreignKey;
    /**
     * @var string - настройка связи моделей. ключ_у_текущей_модели
     */
    public $localKey;
    /**
     * @var BaseBitrixModel - модель, для которой производится загрузка релейшена
     */
    public $primaryModel;
    /**
     * @var array - список связей, которые должны быть подгружены при выполнении запроса
     */
    public $with;

    /**
     * Найти связанные записи для определенной модели [[$this->primaryModel]]
     * Этот метод вызывается когда релейшн вызывается ленивой загрузкой $model->relation
     * @return Collection|BaseBitrixModel[]|BaseBitrixModel - связанные модели
     * @throws \Exception
     */
    public function findFor()
    {
        return $this->multiple ? $this->getList() : $this->first();
    }

    /**
     * Определяет связи, которые должны быть загружены при выполнении запроса
     *
     * Передавая массив можно указать ключем - название релейшена, а значением - коллбек для кастомизации запроса
     *
     * @param array|string $with - связи, которые необходимо жадно подгрузить
     *  // Загрузить Customer и сразу для каждой модели подгрузить orders и country
     * Customer::query()->with(['orders', 'country'])->getList();
     *
     *  // Загрузить Customer и сразу для каждой модели подгрузить orders, а также для orders загрузить address
     * Customer::find()->with('orders.address')->getList();
     *
     *  // Загрузить Customer и сразу для каждой модели подгрузить country и orders (только активные)
     * Customer::find()->with([
     *     'orders' => function (BaseQuery $query) {
     *         $query->filter(['ACTIVE' => 'Y']);
     *     },
     *     'country',
     * ])->all();
     *
     * @return $this
     */
    public function with($with)
    {
        $with = is_string($with) ? func_get_args() : $with;

        if (empty($this->with)) {
            $this->with = $with;
        } elseif (!empty($with)) {
            foreach ($with as $name => $value) {
                if (is_int($name)) {
                    // дубликаты связей будут устранены в normalizeRelations()
                    $this->with[] = $value;
                } else {
                    $this->with[$name] = $value;
                }
            }
        }

        return $this;
    }

    /**
     * Добавить фильтр для загрзуки связи относительно моделей
     * @param Collection|BaseBitrixModel[] $models
     */
    protected function filterByModels($models)
    {
        $values = [];
        foreach ($models as $model) {
            if (($value = $model[$this->foreignKey]) !== null) {
                if (is_array($value)) {
                    $values = array_merge($values, $value);
                } else {
                    $values[] = $value;
                }
            }
        }

        if (empty($values)) {
            $this->stopQuery();
        }

        $primary = $this->localKey;
        if (preg_match('/^PROPERTY_(.*)_VALUE$/', $primary, $matches) && !empty($matches[1])) {
            $primary = 'PROPERTY_' . $matches[1];
        }

        $this->filter([$primary => array_unique($values, SORT_REGULAR)]);
        $this->select[] = $primary;
    }

    /**
     * Подгрузить связанные модели для уже загруденных моделей
     * @param array $with - массив релейшенов, которые необходимо подгрузить
     * @param Collection|BaseBitrixModel[] $models модели, для которых загружать связи
     */
    public function findWith($with, &$models)
    {
        // --- получаем модель, на основании которой будем брать запросы релейшенов
        $primaryModel = reset($models);
        if (!$primaryModel instanceof BaseBitrixModel) {
            $primaryModel = $this->model;
        }

        $relations = $this->normalizeRelations($primaryModel, $with);
        /* @var $relation BaseQuery */
        foreach ($relations as $name => $relation) {
            $relation->populateRelation($name, $models);
        }
    }

    /**
     * @param BaseBitrixModel $model - модель пустышка, чтобы получить запросы
     * @param array $with
     * @return BaseQuery[]
     */
    private function normalizeRelations($model, $with)
    {
        $relations = [];
        foreach ($with as $name => $callback) {
            if (is_int($name)) { // Если ключ - число, значит в значении написано название релейшена
                $name = $callback;
                $callback = null;
            }

            if (($pos = strpos($name, '.')) !== false) { // Если есть точка, значит указан вложенный релейшн
                $childName = substr($name, $pos + 1); // Название дочернего релейшена
                $name = substr($name, 0, $pos); // Название текущего релейшена
            } else {
                $childName = null;
            }

            if (!isset($relations[$name])) { // Указываем новый релейшн
                $relation = $model->getRelation($name); // Берем запрос
                $relation->primaryModel = null;
                $relations[$name] = $relation;
            } else {
                $relation = $relations[$name];
            }

            if (isset($childName)) {
                $relation->with[$childName] = $callback;
            } elseif ($callback !== null) {
                call_user_func($callback, $relation);
            }
        }

        return $relations;
    }
    /**
     * Находит связанные записи и заполняет их в первичных моделях.
     * @param string $name - имя релейшена
     * @param array $primaryModels - первичные модели
     * @return Collection|BaseBitrixModel[] - найденные модели
     */
    public function populateRelation($name, &$primaryModels)
    {
        $this->filterByModels($primaryModels);

        $models = $this->getList();
        $buckets = $this->buildBuckets($models, $this->foreignKey);

        foreach ($primaryModels as $i => $primaryModel) {
            if ($this->multiple && is_array($keys = $primaryModel[$this->foreignKey])) {
                $value = [];
                foreach ($keys as $key) {
                    $key = $this->normalizeModelKey($key);
                    if (isset($buckets[$key])) {
                        $value = array_merge($value, $buckets[$key]);
                    }
                }
            } else {
                $key = $this->getModelKey($primaryModel, $this->foreignKey);
                $value = isset($buckets[$key]) ? $buckets[$key] : ($this->multiple ? [] : null);
            }

            $primaryModel->populateRelation($name, is_array($value) ? new Collection($value) : $value);
        }

        return $models;
    }

    /**
     * Сгруппировать найденные модели
     * @param array $models
     * @param array|string $linkKeys
     * @param bool $checkMultiple
     * @return array
     */
    private function buildBuckets($models, $linkKeys, $checkMultiple = true)
    {
        $buckets = [];

        foreach ($models as $model) {
            $key = $this->getModelKey($model, $linkKeys);
            $buckets[$key][] = $model;
        }

        if ($checkMultiple && !$this->multiple) {
            foreach ($buckets as $i => $bucket) {
                $buckets[$i] = reset($bucket);
            }
        }

        return $buckets;
    }

    /**
     * Получить значение атрибутов в виде строки
     * @param BaseBitrixModel $model
     * @param array|string $attributes
     * @return string
     */
    private function getModelKey($model, $attributes)
    {
        $key = [];
        foreach ((array)$attributes as $attribute) {
            $key[] = $this->normalizeModelKey($model[$attribute]);
        }
        if (count($key) > 1) {
            return serialize($key);
        }
        $key = reset($key);
        return is_scalar($key) ? $key : serialize($key);
    }

    /**
     * @param mixed $value raw key value.
     * @return string normalized key value.
     */
    private function normalizeModelKey($value)
    {
        if (is_object($value) && method_exists($value, '__toString')) {
            // ensure matching to special objects, which are convertable to string, for cross-DBMS relations, for example: `|MongoId`
            $value = $value->__toString();
        }

        return $value;
    }
}