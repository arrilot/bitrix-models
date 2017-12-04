<?php

namespace Arrilot\BitrixModels\Adapters;

/**
 * Class D7Adapter
 *
 * @method \Bitrix\Main\DB\Result getList(array $parameters = [])
 * @method int getCount(array $filter = [])
 * @method \Bitrix\Main\Entity\UpdateResult update(int $id, array $fields)
 * @method \Bitrix\Main\Entity\DeleteResult delete(int $id)
 * @method \Bitrix\Main\Entity\AddResult add(array $fields)
 */
class D7Adapter
{
    /**
     * Bitrix Class FQCN.
     *
     * @var string
     */
    protected $className;
    
    /**
     * D7Adapter constructor.
     *
     * @param $className
     */
    public function __construct($className)
    {
        $this->className = $className;
    }

    /**
     * Getter for class name.
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * Handle dynamic method calls into a static calls on bitrix entity class.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $className = $this->className;

        return $className::$method(...$parameters);
    }
}
