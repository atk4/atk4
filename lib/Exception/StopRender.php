<?php
/**
 * Undocumented.
 */
class Exception_StopRender extends Exception_Stop
{
    public function __construct($r)
    {
        $this->result = $r;
    }
}
