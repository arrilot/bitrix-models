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
     * @var string.
     */
    public $link_primary_key;
    /**
     * @var string.
     */
    public $link_foreign_key;

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

        $this->filter([$this->link_primary_key => $model[$this->link_foreign_key]]);

        return $this->multiple ? $this->getList() : $this->first();
    }
}