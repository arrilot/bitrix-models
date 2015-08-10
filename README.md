[![Latest Stable Version](https://poser.pugx.org/arrilot/bitrix-models/v/stable.svg)](https://packagist.org/packages/arrilot/bitrix-models/)
[![Total Downloads](https://img.shields.io/packagist/dt/arrilot/bitrix-models.svg?style=flat)](https://packagist.org/packages/Arrilot/bitrix-models)
[![Build Status](https://img.shields.io/travis/arrilot/bitrix-models/master.svg?style=flat)](https://travis-ci.org/arrilot/bitrix-models)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/arrilot/bitrix-models/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/arrilot/bitrix-models/)

#Bitrix models (in development)

*Данный пакет представляет собой надстройку над традиционным API Битрикса для работы с элементами инфоблоков и пользователями. Достигается это при помощи создания моделей.*

## Установка

1)```composer require arrilot/bitrix-models```

2) подключаем composer к Битриксу.

Теперь можно создавать свои модели, наследуя их либо от 
```php
Arrilot\BitrixModels\Models\ElementModel
``` 
либо от
```php
Arrilot\BitrixModels\Models\UserModel
```

## Использование

Везде будем рассматривать модель для элемента инфоблока. Для пользователей отличия минимальны.

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
    public static function iblockId()
    {
        return PRODUCT_IB_ID;
    }
}
```

Для работы модели необходимо лишь реализовать `iblockId()` возвращающий ID информационного блока.
Для юзеров не требуется и этого.

Рассмотрим примеры работы API моделей.

1) Инстанцирование модели без получения информации из базы.
```php
$product = new Product($productId);
//теперь есть возможно работать с моделью, допустим
$product->deactivate();
//или
$product->delete();
```

1) Получение элемента инфоблока из базы.
```php
$product = Product::getById($productId);
// $product  - объект модели Product (как в примере 1), однако он реализует ArrayAccess
// и поэтому с ним во многом можно работать как с массивом, полученным из битриксового getById();
if ($product['CODE'] === 'test') {
    $product->deactivate();
}

// элемент выбирается из базы вместе со свойствами.
echo $product['PROPERTY_VALUES']['GUID'];
```

2) Инстанцирование модели без запросов к базе.
Зачастую нет необходимости в получении информации из БД, достаточно лишь ID объекта.
В этом случае можно просто инстанцировать объект модели.
```php

$product = new Product($productId);
//теперь есть возможно работать с моделью, допустим
$product->deactivate();

//объект для текущего пользователя можно получить так:
$user = User::current();
```

3) Если поля нужны, их потом дополучить
```php
$product = new Product($productId);
// последующие методы обращаются к базе только если нужная 
// информация еще не была получена
$arProps = $product->getProps(); // только свойства
$arFields = $product->getFields(); // только поля
$arProduct = $product->get(); // и то и то

// последующие методы принудительно перегружают информацию из 
// базы даже если она есть
$arProps = $product->refreshProps(); // только свойства
$arFields = $product->refreshFields(); // только поля
$arProduct = $product->refresh(); // и то и то
```

4) Преобразование модели в чистый массив.
```php
$product = Product::getById($productId);
$arProduct = $product->toArray();
$json = $product->toJson();
```

5) Обновление элемента инфоблока.
```php
$product = Product::getById($productId);

// вариант 1
$product['NAME'] = 'Новое имя продукта';
$product->save();

// вариант 2
$product['NAME'] = 'Новое имя продукта';
$product->update(['NAME' => 'Новое имя продукта']);
```

6) Добавление элемента инфоблока.
```php
// $fields - массив аналогичный передаваемому в CIblockElement::Add()
$product = Product::create($fields);
```

7) Подсчёт количества элементов инфоблока с учётом фильтра
```php
$count = Product::count(['ACTIVE'=>'Y']);
```

8) Получения списка элементов
```php
$products = Product::getList([
    'filter' => ['ACTIVE'=>'N'],
    'select' => ['ID', 'NAME', 'CODE'],
    'sort => ['NAME' => 'ASC'],
    'keyBy' => 'ID'
]);
foreach ($products as $id => $product) {
    if ($product['CODE'] === 'test') {
        $product->deactivate();
    }
}
```

### "Fluent API" для запросов.

Для `getById`, `getList` и `count` можно также использовать fluent API.

9) Пример 7 можно также реализовать и так:
```php
$count = Product::query()->filter(['ACTIVE'=>'Y'])->count();
```

10) Получения списка элементов (7) через query
```php
$products = Product::query()
                    ->filter($filter)
                    ->navigation(['nTopCount'=>100])
                    ->select('ID','NAME')
                    ->getList();
```

Опущенные модификаторы будут заполнены дефолтными значениями.
В значения модификаторов можно передавать прямо такие же значения как и в соответсвующие параметры CIblockElement::getList
Некоторые дополнительные моменты:

1. Как видно из примера `select()` поддерживает не только массив но и произвольное число аргументов
`select('ID', 'NAME')`  равнозначно `select(['ID', 'NAME'])` 

2. `select()` поддерживает два дополнительных значения - `'FIELDS'` (выбрать все поля), `'PROPS'` (выбрать все свойства).
Для пользователей также можно указать `'GROUPS'` (добавить группы пользователя в выборку)
Значение по-умолчанию - `['FIELDS', 'PROPS']`

3.  Есть возможность указать `keyBy()` - именно указанное тут поле станет ключом в списке-результате.
Значение по-умолчанию - `ID`. При установки `false` будет использован обычный автоинкрементирующий integer

4. В некоторых местах API более дружелюбный чем в самом Битриксе. Допустим в фильтре по пользователям не обязательно использовать
`'GROUP_IDS'`. При передаче `'GROUP_ID'` (именно такой ключ требует Битрикс допустим при создании пользователя) или `'GROUPS'` 
результат будет аналогичен.

5. В фильтр автоматически подставляется ID инфоблока.

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

$products = Product::query()
                    ->filter(['SECTION_ID' => $secId])
                    ->active()
                    ->navigation(['nTopCount'=>100])
                    ->getList();
```

В "query scopes" можно также передавать дополнительные параметры.
```php

    /**
     * Scope to get users only from specific group.
     *
     * @param UserQuery $query
     * @param int|array $groupId
     *
     * @return UserQuery
     */
    public function scopeFromGroup($query, $groupId)
    {
        $query->filter['GROUPS_ID'] = $groupId;
    
        return $query;
    }

...
$users = $query->sort(["ID" => "ASC"])->filter(['NAME'=>'John'])->fromGroup(7)->getList();
```

### Accessors

Временами возникает потребность как то модифицировать данные между выборкой их из базы и получением их из модели.
Для этого используются акссессоры.
Также как и для "query scopes", для добавления аксессора необходимо добавить метод в соответсвующую модель.

Правило именования метода - $methodName = "get".camelCase($field)."field".
Пример акссессора который принудительно делает первую букву имени заглавной:
```php

    public function getNameField($value)
    {
        return ucfirst($value);  
    }
    
    // теперь в $product['NAME'] будет модифицированное значение
    
```

Аксессоры можно создавать также и для несуществущих полей, например:
```php
    public function getFullNameField()
    {
        return $this['NAME']." ".$this['LAST_NAME'];
    }
    
    ...
    
    echo $user['NAME']; // John
    echo $user['LAST_NAME']; // Doe
    echo $user['FULL_NAME']; // John Doe
```

Для того чтобы такие аксессоры отображались в toArray() и toJson() их указать в поле $appends модели.
```php
    protected $appends = ['ACCESSOR_THREE'];
```

