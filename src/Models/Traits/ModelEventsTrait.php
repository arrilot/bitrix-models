<?php

namespace Arrilot\BitrixModels\Models\Traits;

trait ModelEventsTrait
{
    /**
     * Hook into before item create.
     *
     * @return mixed
     */
    protected function onBeforeCreate()
    {
        //
    }

    /**
     * Hook into after item create.
     *
     * @param bool $result
     *
     * @return void
     */
    protected function onAfterCreate($result)
    {
        //
    }

    /**
     * Hook into before item update.
     *
     * @return mixed
     */
    protected function onBeforeUpdate()
    {
        //
    }

    /**
     * Hook into after item update.
     *
     * @param bool $result
     *
     * @return void
     */
    protected function onAfterUpdate($result)
    {
        //
    }

    /**
     * Hook into before item create or update.
     *
     * @return mixed
     */
    protected function onBeforeSave()
    {
        //
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

    /**
     * Hook into before item delete.
     *
     * @return mixed
     */
    protected function onBeforeDelete()
    {
        //
    }

    /**
     * Hook into after item delete.
     *
     * @param bool $result
     *
     * @return void
     */
    protected function onAfterDelete($result)
    {
        //
    }
}
