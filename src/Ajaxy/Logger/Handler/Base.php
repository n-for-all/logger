<?php

namespace Ajaxy\Logger\Handler;

use Ajaxy\Logger\HandlerInterface;

abstract class Base implements HandlerInterface{

    /** @var resource|null */
    protected $bubble = true;

    /**
     * Return the currently active stream if it is open
     *
     * @return resource|null
     */
    public function getBubble()
    {
        return $this->bubble;
    }

    /**
     * Sets the bubble value
     *
     * @return resource|null
     */
    public function setBubble($bubble)
    {
        $this->bubble = $bubble;
        return $this;
    }
}


 ?>
