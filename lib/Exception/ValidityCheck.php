<?php
/**
 * Thrown by Model on validity check fail.
 *
 * @author Camper (cmd@adevel.com) on 07.09.2009
 */
class Exception_ValidityCheck extends Exception_ForUser
{
    private $field;
    public function __construct($msg, $field = null)
    {
        parent::__construct($msg);
        if ($field) {
            $this->setField($field);
        }
    }
    public function setField($field)
    {
        $this->field = $field;

        return $this;
    }
    public function getField()
    {
        return $this->field;
    }
}
