<?php

namespace Arrilot\BitrixModels;

use Illuminate\Support\Collection as BaseCollection;

class Collection extends BaseCollection
{
    /**
     * Create a new collection consisting of even elements.
     *
     * @return $this
     */
    public function even()
    {
        return $this->every(2);
    }

    /**
     * Create a new collection consisting of odd elements.
     *
     * @return $this
     */
    public function odd()
    {
        return $this->every(2, 1);
    }

    /**
     * Create a new collection consisting of every n-th element.
     *
     * @param  int  $step
     * @param  int  $offset
     * @return $this
     */
    public function every($step, $offset = 0)
    {
        $new = [];
        $position = 0;
        foreach ($this->items as $key => $item) {
            if ($position % $step === $offset) {
                $new[] = $item;
            }
            $position++;
        }

        return new static($new);
    }
}