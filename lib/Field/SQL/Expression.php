<?php
/**
 * Undocumented.
 */
class Field_SQL_Expression extends Field_Calculated
{
    public function getValue($model, $data)
    {
        return $model->data[$this->short_name];
    }

    public function getExpr()
    {
        return $this->getExpression();
    }

    public function getExpression()
    {
        return $this->expression;
    }

    public function setForeignFieldName($name)
    {
        $this->name = $name;

        return $this;
    }
}
