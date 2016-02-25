<?php
/**
 * Undocumented.
 */
abstract class Field_Calculated extends Field_Base
{
    protected $expression = null;

    public function setExpression($expression)
    {
        $this->expression = $expression;

        return $this;
    }

    public function updateSelectQuery()
    {
        return $this;
    }

    abstract public function getValue($model, $data);
}
