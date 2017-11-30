<?php

namespace Arrilot\BitrixModels\Models;

use Arrilot\BitrixModels\Queries\D7Query;
use Bitrix\Highloadblock\HighloadBlockTable;
use Exception;

class D7Model extends BaseBitrixModel
{
    /**
     * Constructor.
     *
     * @param $id
     * @param $fields
     */
    public function __construct($id = null, $fields = null)
    {
        $this->id = $id;
        
        $this->fill($fields);
    }

    /**
     * Instantiate a query object for the model.
     *
     * @return D7Query
     */
    public static function query()
    {
        return new D7Query(static::tableClass(), get_called_class());
    }
    
    /**
     * @return \Bitrix\Main\Entity\DataManager
     */
    public static function tableClass()
    {
        return HighloadBlockTable::compileEntity(HighloadBlockTable::getRowById(2))->getDataClass();
    }

    /**
     * Internal part of create to avoid problems with static and inheritance
     *
     * @param $fields
     *
     * @throws Exception
     *
     * @return static|bool
     */
    protected static function internalCreate($fields)
    {
        $model = new static(null, $fields);
        
        if ($model->onBeforeSave() === false || $model->onBeforeCreate() === false) {
            return false;
        }

        $bxObject = static::tableClass();
        $resultObject = $bxObject::add($fields);
        $result = $resultObject->isSuccess();
        if ($result) {
            $model->setId($resultObject->getId());
        }

        $model->onAfterCreate($result);
        $model->onAfterSave($result);

        if (!$result) {
            throw new Exception(implode('; ', $resultObject->getErrorMessages()));
        }

        return $model;
    }

    /**
     * Delete model
     *
     * @return bool
     */
    public function delete()
    {
        if ($this->onBeforeDelete() === false) {
            return false;
        }

        $bxObject = static::tableClass();
        $resultObject = $bxObject::delete($this->id);
        $result = $resultObject->isSuccess();

        $this->onAfterDelete($result);

        return $result;
    }

    /**
     * Save model to database.
     *
     * @param array $selectedFields save only these fields instead of all.
     *
     * @return bool
     */
    public function save($selectedFields = [])
    {
        $selectedFields = is_array($selectedFields) ? $selectedFields : func_get_args();

        if ($this->onBeforeSave() === false || $this->onBeforeUpdate() === false) {
            return false;
        }

        $fields = $this->normalizeFieldsForSave($selectedFields);
        $bxObject = static::tableClass();
        $resultObject = $bxObject::update($this->id, $fields);
        $result = $resultObject->isSuccess();

        $this->onAfterUpdate($result);
        $this->onAfterSave($result);

        return $result;
    }
    
    /**
     * Determine whether the field should be stopped from passing to "update".
     *
     * @param string $field
     * @param mixed  $value
     * @param array  $selectedFields
     *
     * @return bool
     */
    protected function fieldShouldNotBeSaved($field, $value, $selectedFields)
    {
        return (!empty($selectedFields) && !in_array($field, $selectedFields)) || $field === 'ID';
    }
}
