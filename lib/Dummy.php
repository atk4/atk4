<?php
/**
 * Dummy is a class ignoring all the calls. It is used to
 * substitute some other classes when their functionality
 * is not needed.
 */
// @codingStandardsIgnoreStart
class Dummy
{
    public function __call($foo, $bar)
    {
        return $this;
    }
    public function __set($foo, $bar)
    {
        return $this;
    }
    public function __toString()
    {
        return '##Dummy Object';
    }
    public function __get($foo)
    {
        return;
    }
}
// @codingStandardsIgnoreEnd
