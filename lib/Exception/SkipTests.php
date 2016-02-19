<?php
/**
 * Stops testing process. For example if you see that database failed to
 * connect on our testing environment.
 */
class Exception_SkipTests extends Exception_Stop
{
    public function __construct($msg = null)
    {
        parent::__construct($msg ?: 'No reason specified');
    }
}
