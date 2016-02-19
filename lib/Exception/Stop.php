<?php
/**
 * To stop process flow, debug purposes.
 *
 * @author Camper (cmd@adevel.com) on 04.08.2009
 */
class Exception_Stop extends BaseException
{
    public function __construct($msg = null)
    {
        parent::__construct($msg ?: 'This exception must be ignored in APP');
    }
}
