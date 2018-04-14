<?php


namespace Arrilot\BitrixModels\Queries;


use Arrilot\BitrixModels\Models\BaseBitrixModel;
use Illuminate\Support\Collection;

/**
 * BaseRelationQuery implements the common methods and properties for relational queries.
 *
 * @method BaseBitrixModel first()
 * @method Collection|BaseBitrixModel[] getList()
 */
trait BaseRelationQuery
{
    /**
     * @var bool whether this query represents a relation to more than one record.
     * This property is only used in relational context. If true, this relation will
     * populate all query results. If false, only the first row.
     */
    public $multiple;
    /**
     * @var array
     */
    public $link;
    /**
     * @var BaseBitrixModel the primary model of a relational query.
     * This is used only in lazy loading with dynamic query options.
     */
    public $primaryModel;

    /**
     * Finds the related records for the specified primary record.
     * This method is invoked when a relation of an ActiveRecord is being accessed in a lazy fashion.
     * @param string $name the relation name
     * @param BaseBitrixModel $model the primary model
     * @return mixed the related record(s)
     * @throws \Exception
     */
    public function findFor($name, $model)
    {
        if (method_exists($model, 'get' . $name)) {
            $method = new \ReflectionMethod($model, 'get' . $name);
            $realName = lcfirst(substr($method->getName(), 3));
            if ($realName !== $name) {
                throw new \InvalidArgumentException('Relation names are case sensitive. ' . get_class($model) . " has a relation named \"$realName\" instead of \"$name\".");
            }
        }

        return $this->multiple ? $this->getList() : $this->first();
    }

    /**
     * @param array $models
     */
    private function filterByModels($models)
    {
        $attributes = array_keys($this->link);

        if (count($attributes) != 1) {
            throw new \LogicException('Массив link может содержать только один элемент.');
        }

        $values = [];
        $primary = current($attributes);
        $attribute = reset($this->link);
        foreach ($models as $model) {
            if (($value = $model[$attribute]) !== null) {
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

        $this->filter([$primary => array_unique($values, SORT_REGULAR)]);
    }
}