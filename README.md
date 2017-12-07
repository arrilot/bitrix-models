[![Latest Stable Version](https://poser.pugx.org/arrilot/bitrix-models/v/stable.svg)](https://packagist.org/packages/arrilot/bitrix-models/)
[![Total Downloads](https://img.shields.io/packagist/dt/arrilot/bitrix-models.svg?style=flat)](https://packagist.org/packages/Arrilot/bitrix-models)
[![Build Status](https://img.shields.io/travis/arrilot/bitrix-models/master.svg?style=flat)](https://travis-ci.org/arrilot/bitrix-models)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/arrilot/bitrix-models/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/arrilot/bitrix-models/)

# Bitrix models

## Вступление 

Данный пакет привносит Model Layer в Битрикс.
Этот слой логически состоит из двух частей:

1. Модели для сущностей битрикса, в дальнейшем будем называть их "Битрикс-модели".
Они представляют собой надстройку над традиционным API Битрикса (`CIBlockElement` и т д).
С внешней же стороны эта надстройка напоминает `Eloquent`.
2. Модели для произвольных таблицы - интеграция `illuminate/support` в целом и `Eloquent` в частности.

## Установка

1. ```composer require arrilot/bitrix-models```
2. Регистрируем пакет в `init.php` - `Arrilot\BitrixModels\ServiceProvider::register();`

## Использование моделей Bitrix

Для наследования доступны следующие модели:

```php
Arrilot\BitrixModels\Models\ElementModel
Arrilot\BitrixModels\Models\SectionModel
Arrilot\BitrixModels\Models\UserModel
Arrilot\BitrixModels\Models\D7Model
```

Для пример далее везде будем рассматривать модель для элемента инфоблока (ElementModel). 
Для других сущностей API практически идентичен.

> ElementModel полноценно поддерживает только инфоблоки второй версии (та что хранит свойства в отдельных таблицах).
С первой версией некоторые моменты могут не работать должным образом из-за особенностей работы CIBlockElement::GetList().
Самая большая проблема: Если в инфоблоке есть множественные свойства, то запросы с limit(), take(), и first() будут отрабатывать неверно и получать меньше элементов чем нужно и не с полным набором множественных свойств.
Если вы всё же собираетесь использовать ElementModel с инфоблоками первой версии, то обязательно выставите const IBLOCK_VERSION = 1; внутри класса модели.

Создадим, модель для инфоблока Товары.
```php
<?php

use Arrilot\BitrixModels\Models\ElementModel;

class Product extends ElementModel
{
    /**
     * Corresponding iblock id.
     *
     * @return int
     */
    const IBLOCK_ID = 1;
}
```

Для работы модели необходимо лишь задать ID инфоблока в константе.
Для юзеров не требуется и этого.

> Если вы не хотите привязываться к ID инфоблоков, можно переопределить в моделе метод `public static iblockId()` и получать в нём ID инфоблока например по коду. Это дает большую гибкость, но скорее всего вам потребуются дополнительные запросы в БД.

В дальнейшем мы будем использовать наш класс `Product` как в статическом, так и в динамическом контексте.

### Добавление продукта

```php
// $fields - массив, аналогичный передаваемому в CIblockElement::Add(), но IBLOCK_ID в нём можно не указывать.
$product = Product::create($fields);
```

> Заметка:
В случае если поля $product в дальнейшем как-то используются, рекомендуется сразу же обновить объект новым запросом в БД .
Нужно это из-за того, что форматы полей в CIblockElement::Add() и CIblockElement::GetList() не совпадают на 100%
Делается это так: `$product = Product::create($fields)->refresh()`

### Обновление

```php
// вариант 1
$product['NAME'] = 'Новое имя продукта';
$product->save();

// вариант 2
$product->update(['NAME' => 'Новое имя продукта']);
```

### Инстанцирование модели без запросов к базе.

Для ряда операций нет необходимости в получении информации из БД, достаточно лишь ID объекта.
В этом случае достаточно инстанцировать объект модели, передав в конструктор идентификатор.
```php
$product = new Product($id);

//теперь есть возможно работать с моделью, допустим
$product->deactivate();
```

### Получение полей объекта из базы

```php
$product = new Product($id);

// метод `load` обращается к базе, только если информация еще не была получена.
$product->load();

// Если мы хотим принудительно обновить информацию из базы даже если она уже была получена ранее
$product->refresh();

// После любого из этих методов, мы можем работать с полученными полями (`echo $product['CODE'];`)

//Для текущего пользователья есть отдельный хэлпер
$user = User::current();
// В итоге мы получаем инстанс User с заполненными полями. 
// Сколько бы раз мы не вызывали `User::current()` в рамках работы скрипта, запрос в базу происходит только один раз - первый.
// `User::freshCurrent()` - то же самое, но получает данные из базы каждый раз.
```

Описанные методы сохраняют данные из БД внутри экземпляра класса-модели.
Объекты-модели реализуют ArrayAccess, поэтому с ними можно во многом работать как с массивами.
```php
$product->load();
if ($product['CODE'] === 'test') {
    $product->deactivate();
}
```

### Преобразование моделей в массив/json.

```php
$array = $product->toArray();
$json = $product->toJson();
```

### Получение информации из базы

Наиболее распостраненный сценарий работы с моделями - получение элементов/списков из БД.
Для построение запроса используется "Fluent API", который использует в недрах себя стандартный Битриксовый API.

Для начала построения запроса используется статический метод `::query()`.
Данный метод возвращает объект-построитель запроса (`ElementQuery`, `SectionQuery` или `UserQuery`), через который уже строится цепочка запроса.

Простейший пример:
```php
$products = Product::query()->select('ID')->getList();
```

На самом деле данная форма приведена больше для понимания, есть более удобный вид, который использует `__callStatic` для передачи управления в объект-запрос.
```php
$products = Product::select('ID')->getList();
```

Любая цепочка запросов должна заканчиваться одним из следующих методов:

1. `->getList()` - получение коллекции (см. http://laravel.com/docs/master/collections) объектов. По умолчанию ключом каждого элемента является его ID.
2. `->getById($id)` - получение объекта по его ID.
3. `->first()` - получение одного (самого первого) объекта удовлетворяющего параметрам запроса.
4. `->count()` - получение количества объектов.
5. `->paginate() или ->simplePaginate()` - получение спагинированного списка с мета-данными (см. http://laravel.com/docs/master/pagination)
6. Методы для отдельных сущностей:
 `->getByLogin($login)` и `->getByEmail($email)` - получение первого попавшегося юзера с данным логином/email.
 `->getByCode($code)` и `->getByExternalId($id)` - получение первого попавшегося элемента или раздела ИБ по CODE/EXTERNAL_ID

#### Управление выборкой

1. `->sort($array)` - аналог `$arSort` (первого параметра `CIBlockElement::GetList`)

 Примеры:
 
 `->sort(['NAME' => 'ASC', 'ID => 'DESC'])`

 `->sort('NAME', 'DESC') // = ->sort(['NAME' => 'DESC'])`

 `->sort('NAME') // = ->sort(['NAME' => 'ASC'])`


2. `->filter($array)` - аналог `$arFilter`
3. `->navigation($array)`
4. `->select(...)` - аналог `$arSelect`

 Примеры:

 `->select(['ID', 'NAME'])`
 
 `->select('ID', 'NAME')`

 `select()` поддерживает два дополнительных значения - `'FIELDS'` (выбрать все поля), `'PROPS'` (выбрать все свойства).
 Для пользователей также можно указать `'GROUPS'` (добавить группы пользователя в выборку).
 Значение по-умолчанию для `ElementModel` - `['FIELDS', 'PROPS']`

5. `->limit($int)`, `->take($int)`, `->page($int)`, `->forPage($page, $perPage)` - для навигации 

### Fetch и GetNext

По-умолчанию, внутри моделей для итерации по полученным из базы элементам/разделам/юзерам используется производительный метод `->Fetch()`
В отличии от `->GetNext()`, он не приводит данные в html безопасный вид и не преобразует DETAIL_PAGE_URL, SECTION_PAGE_URL в реальные урлы элементов и категорий.
Если в результате выборки вам нужны эти преобразования, то можно переключиться на этот метод.

1. Можно переключить сразу всю модель задав ей свойство

    ```php
        public static $fetchUsing = 'GetNext';
    
        // полная форма, если нужно менять параметры.
        public static $fetchUsing = [
            'method' => 'GetNext',
            'params' => [true, true],
        ];
    ```

2. Можно переключить для одного единственного запроса.

    ```php
         Products::query()->filter(['ACTIVE' => 'Y'])->fetchUsing('GetNext')->getList()`
         // вместо строки `'GetNext'` можно как и в первом случае использовать массив.
    ```


#### Некоторые дополнительные моменты:

1. Для ограничения выборки добавлены алиасы `limit($value)` (соответсвует `nPageSize`) и `page($num)` (соответсвует `iNumPage`)
2. В некоторых местах API более дружелюбный чем в самом Битриксе. Допустим в фильтре по пользователям не обязательно использовать
`'GROUP_IDS'`. При передаче `'GROUP_ID'` (именно такой ключ требует Битрикс допустим при создании пользователя) или `'GROUPS'` 
результат будет аналогичен.


### Query Scopes

Построитель запросов можно расширять добавляя "query scopes" в модель.
Для этого необходимо создать публичный метод начинаюзищися со `scope`.

Пример "query scope"-a уже присутсвующего в пакете.
```php
    /**
     * Scope to get only active items.
     *
     * @param BaseQuery $query
     *
     * @return BaseQuery
     */
    public function scopeActive($query)
    {
        $query->filter['ACTIVE'] = 'Y';
    
        return $query;
    }

...

$products = Product::filter(['SECTION_ID' => $secId])
                    ->active()
                    ->getList();
```

В "query scopes" можно также передавать дополнительные параметры.
```php

    /**
     * @param ElementQuery $query
     * @param string|array $category
     *
     * @return ElementQuery
     */
    public function scopeFromSectionWithCode($query, $category)
    {
        $query->filter['SECTION_CODE'] = $category;

        return $query;
    }

...
$users = Product::fromSectionWithCode('sale')->getList();
```

Данные скоупы уже присутсвуют в пакете, ими можно пользоваться.

#### Остановка действия

Иногда требуется остановить выборку из базы из query scope. 
Для этого достаточно вернуть false.
Пример:
```php
    public function scopeFromCategory($query, $category)
    {
        if (!$category) {
            return false;
        }
        
        $query->filter['SECTION_CODE'] = $category;

        return $query;
    }
...
```
В результате запрос в базу не будет сделан - `getList()` вернёт пустую коллекцию, `getById()` - false, а `count()` - 0.

> Того же самого эффекта можно добиться вызвав вручную метод `->stopQuery()` 

### Кэширование запросов

Для всех вышеупомнянутых Битрикс-моделей есть простой встроенный механизм кэширования.
Достаточно лишь добавитьв цепочку вызовов `->cache($minutes)->` и результат выборки из базы будет закэширован на указанное количество минут.
Пример: `$products = Products::query()->cache(30)->filter(['ACTIVE' => 'Y'])->getList()`
Под капотом кэширование происходит используя стандартный механизм из d7 Битрикса. Клоюч кэширования зависит от модели и всех параметров запроса.

### Accessors

Временами возникает потребность как то модифицировать данные между выборкой их из базы и получением их из модели.
Для этого используются акссессоры.
Также как и для "query scopes", для добавления аксессора необходимо добавить метод в соответсвующую модель.

Правило именования метода - `$methodName = "get".camelCase($field)."Attribute"`.
Пример акссессора который принудительно делает первую букву имени заглавной:
```php

    public function getXmlIdAttribute($value)
    {
        return (int) $value;  
    }
    
    // теперь в $product['XML_ID'] всегда будет целочисленное значение
    
```
Этим надо пользоваться с осторожностью, потому оригинальное значение становится недоступным.

Аксессоры можно создавать также и для несуществущих, виртуальных полей, например:
```php
    public function getFullNameAttribute()
    {
        return $this['NAME']." ".$this['LAST_NAME'];
    }
    
    ...
    
    echo $user['NAME']; // John
    echo $user['LAST_NAME']; // Doe
    echo $user['FULL_NAME']; // John Doe
```

Для того чтобы такие виртуальные аксессоры отображались в toArray() и toJson(), их необходимо явно указать в поле $appends модели.
```php
    protected $appends = ['FULL_NAME'];
```

### События моделей (Model Events)

События позволяют вклиниваться в различные точки жизненного цикла модели и выполнять в них произвольный код.
Например, автоматически проставлять символьный код при создании элемента.
События моделей не используют событийную модель Битрикса (ни старого ядра, ни D7) и касаются лишь того, что происходит внутри моделей.
Использование Битриксовых событий в большинстве случаев более удобно потому что покрывает больше кейсов.

Обработчик события задается переопределением соответсвующего метода в классе-модели.

```php
class News extends ElementModel
{
    /**
     * Hook into before item create or update.
     *
     * @return mixed
     */
    protected function onBeforeSave()
    {
        $this['CODE'] = CUtil::translit($this['NAME'], "ru");
    }

    /**
     * Hook into after item create or update.
     *
     * @param bool $result
     *
     * @return void
     */
    protected function onAfterSave($result)
    {
        //
    }
}
```

Сигнатуры обработчиков других событий совпадают с приведенными выше.

Список доступных эвентов:

1. `onBeforeCreate` - перед добавлением записи
2. `onAfterCreate(bool $result)` - после добавления записи
3. `onBeforeUpdate` - перед обновлением записи
4. `onAfterUpdate(bool $result)` - после обновления записи
5. `onBeforeSave` - перед добавлением или обновлением записи
6. `onAfterSave(bool $result)` - после добавления или обновления записи
7. `onBeforeDelete` - перед удалением записи
8. `onAfterDelete(bool $result)` - после удаления записи

Если сделать `return false;` из обработчика `onBefore...()` то последующее действие будет отменено.
В обработчиках можно получить дополнительную информацию используя свойства объекта текущей модели.
Например, в `onBefore...()` обработчиках доступны все поля через `$this->fields`
Во всех `onAfter...()` доступен массив ошибок через `$this->eventErrors`;
В `onBeforeUpdate()` и `onBeforeSave()` доступен массив `$this->fieldsSelectedForSave`, в котором содержатся ключи полей которые мы собираемся обновлять.

## D7 Model

Немного особняком стоит `D7Model`
В отличии от предыдущих моделей она вместо старых GetList-ов и т д использует в качестве бэкэнда D7
Через неё можно работать как с обычными сущностями D7, так и с хайлоадблоками

Пример для хайлоадблока:

```php
class Subscriber extends D7Model
{
    public static function tableClass()
    {
        $hlBlock = HighloadBlockTable::getRowById(1);
    
        return HighloadBlockTable::compileEntity($hlBlock)->getDataClass();
    }
}
```

Понятно, что логика получения класса хайлоадблока может быть любой, но важно не забыть скомпилировать его, иначе он не будет работать.
Пожалуй самый удобный вариант - использовать вспомогательный пакет [https://github.com/arrilot/bitrix-iblock-helper/](https://github.com/arrilot/bitrix-iblock-helper/)
С ним мы получаем следующее:

```php
class Subscriber extends D7Model
{
    public static function tableClass()
    {
        return highloadblock_class('app_subscribers');
    }
}
```

Если мы работаем не с хайлоадблоком, а с полноценной сущностью D7 ORM, то просто возвращаем в этом методе полное название класса этой сущности.
Цепочки вызовов и названия методов для D7Model такие же как и для предыдущих моделей. Всё что мы передаем в эти методы будет передано далее в D7.

Пример получения всех подписчиков с именем John и с кэшированием на 5 минут:
```php
$subscribers = Subscriber::query()->cache(5)->filter(['=NAME'=>'John])->getList();
```
Полный список методов следующий
 
```php
/**
 * static int count()
 *
 * D7Query methods
 * @method static D7Query runtime(array|\Bitrix\Main\Entity\ExpressionField $fields)
 * @method static D7Query enableDataDoubling()
 * @method static D7Query disableDataDoubling()
 * @method static D7Query cacheJoins(bool $value)
 *
 * BaseQuery methods
 * @method static Collection getList()
 * @method static D7Model first()
 * @method static D7Model getById(int $id)
 * @method static D7Query sort(string|array $by, string $order='ASC')
 * @method static D7Query order(string|array $by, string $order='ASC') // same as sort()
 * @method static D7Query filter(array $filter)
 * @method static D7Query addFilter(array $filters)
 * @method static D7Query resetFilter()
 * @method static D7Query navigation(array $filter)
 * @method static D7Query select($value)
 * @method static D7Query keyBy(string $value)
 * @method static D7Query limit(int $value)
 * @method static D7Query offset(int $value)
 * @method static D7Query page(int $num)
 * @method static D7Query take(int $value) // same as limit()
 * @method static D7Query forPage(int $page, int $perPage=15)
 * @method static \Illuminate\Pagination\LengthAwarePaginator paginate(int $perPage = 15, string $pageName = 'page')
 * @method static \Illuminate\Pagination\Paginator simplePaginate(int $perPage = 15, string $pageName = 'page')
 * @method static D7Query stopQuery()
 * @method static D7Query cache(float|int $minutes)
 */
```

За подробностями смотрите  `vendor/arrilot/bitrix-models/src/Models/D7Model.php` и  `vendor/arrilot/bitrix-models/src/Queries/D7Query.php`


## Использование моделей Eloquent

Вторая часть пакета - интеграция ORM [Eloquent](https://laravel.com/docs/master/eloquent) для пользовательских таблиц в Битриксе, то есть созданных при разработке вручную, а не поставляемых вместе системой.
По сути это альтернатива прямым запросам в базу, D7 ORM и моделям D7Model из этого пакета.

Через `Eloquent` можно работать не только с пользовательскими таблицами, но и с Highload-блоками, что очень удобно.
При этом мы работаем с таблицей Highload-блока минуя какое-бы то ни было API Битрикса.

Стоит учитывать что в отличии от Битрикса `Eloquent` использует в качестве расширения PHP для доступа к mysql не mysql/mysqli а PDO
А это значит что во-первых необходимо чтобы PDO был установлен и настроен, а во-вторых что у вас будут создаваться два подключения к базе на запрос.

> Заметка:
Вопрос: Зачем в одном пакете Eloquent если уже есть D7Model? Что лучше выбрать?
Ответ: Выбор между ними зависит от условий проекта и личных предпочтений.
Eloquent удобнее и сильно функциональнее чем всё что есть в Битриксе и в D7Model 
Например там есть полноценные связи между моделями.
С другой стороны это большая внешняя зависимость со своими требованиями

Недостатки

### Установка

Первым делом нужно поставить еще одну зависимость - `composer require illuminate/database`
После этого добавляем в `init.php` еще одну строку - `Arrilot\BitrixModels\ServiceProvider::registerEloquent();`
Теперь уже можно создавать Eloquent модели наследуясь от `EloquentModel`

```php
<?php

use Arrilot\BitrixModels\Models\EloquentModel;

class Product extends EloquentModel
{
    protected $table = 'app_products';
}
```
Если таблица называется `products` (множественная форма названия класса), то `protected $table = 'products';` можно опустить - это стандартное для Eloquent поведение.
Из нестандартного
 1. Первичным ключом является `ID`, а не `id`
 2. Поля для времени создания и обновления записи называются `CREATED_AT` и `UPDATED_AT`, а не `created_at` и `updated_at`

> Если вы решили не добавлять в таблицу поля CREATED_AT и UPDATED_AT, то в модели нужно задать `public $timestamps = false;`

### Работа с хайлоадблоком через Eloquent

Представим что мы создали Highload-блок для брендов `Brands`, задали для него таблицу `brands` и добавили в него свойство `UF_NAME`.
Тогда класс-модель для него будет выглядеть так:

```php
<?php

use Arrilot\BitrixModels\Models\EloquentModel;

class Brand extends EloquentModel
{
    public $timestamps = false;
}
```

А для добавления новой записи в него достаточно следующего кода:
```php
$brand = new Brand();
$brand['UF_NAME'] = 'Nike';
$brand->save();

// либо даже такого если настроены $fillable поля.
$brand = Brand::create(['UF_NAME' => 'Nike']);
```

Для полноценной работой с Eloquent-моделями важно ознакомиться с официальной документацией данной ORM [(ссылка еще раз)](http://laravel.com/docs/master/eloquent)

В заключение обращу внимание на то что, несмотря на то что API моделей Битрикса и моделей Eloquent очень похожи (во многом из-за того что bitrix-models разрабатывались под влияением Eloquent)
это всё же разные вещи и внутри они совершенно независимые. Нельзя допустим сделать связь (relation) Eloquent модели и Битриксовой модели.

### Query Builder

При подключении Eloquent мы бесплатно получаем и Query Builder от Laravel [https://laravel.com/docs/master/queries](https://laravel.com/docs/master/queries), который очень полезен если необходима прямая работа с БД минуя уровень абстракции моделей.
Он удобнее и несравнимо безопаснее чем `$DB->Query()` и прочее.

Работа с билдером проводится через глобально доступный класс `DB`.
Например добавление элемента бренда в HL-блок будет выглядеть так:
```php
DB::table('brands')->insert(['UF_NAME' => 'Nike']);
```

## Постраничная навигация (pagination)

И Битрикс-модели и Eloquent-модели поддерживают `->paginate()` и `->simplePaginate()` (см. http://laravel.com/docs/master/pagination)
Для того чтобы затем отобразить постраничную навигацию через `->links()` нужно

 1. Установить [https://github.com/arrilot/bitrix-blade/](https://github.com/arrilot/bitrix-blade/)
 2. Скопировать дефолтные вьюшки из [https://github.com/laravel/framework/tree/master/src/Illuminate/Pagination/resources/views](https://github.com/laravel/framework/tree/master/src/Illuminate/Pagination/resources/views) в `local/views/pagination`

После этого вьюшки можно модифицировать или создавать новые.
