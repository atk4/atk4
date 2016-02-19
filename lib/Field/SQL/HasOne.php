<?php
/**
 * Undocumented.
 */
class Field_SQL_HasOne extends Field_SQL_Expression
{
    public function getExpression($model)
    {
        $refModel = $this->getModel();
        if (is_string($refModel)) {
            $refModel = $this->app->normalizeClassName($refModel, 'Model');
        }
        $refModel = $this->add($refModel);

        $other = $model->dsql()->getField($this->getForeignFieldName());
        if ($this->table()) {
            $other = $model->dsql()->expr($this->table().'.'.$this->getForeignFieldName());
        }

        return $refModel->dsql()
            ->field($refModel->title_field)
            ->where($refModel->id_field, $other);
    }

    public function getValue($model, $data)
    {
        return $model->data[$this->short_name];
    }

    public function getForeignFieldName()
    {
        return $this->foreignName;
    }

    public function setForeignFieldName($name)
    {
        $this->foreignName = $name;

        return $this;
    }
}
