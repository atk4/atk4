<?php
/**
 * Undocumented.
 */
class Field_SQL_HasOne extends Field_SQL_Expression
{
    /** @var string */
    private $foreignName;

    /**
     * @param SQL_Model $model
     * @return DB_dsql
     */
    public function getExpression($model)
    {
        /** @type SQL_Model */
        $refModel = $this->getModel();

        if (is_string($refModel)) {
            $refModel = $this->app->normalizeClassName($refModel, 'Model');
        }
        /** @type SQL_Model */
        $refModel = $this->add($refModel);

        $other = $model->dsql()->getField($this->getForeignFieldName());
        if ($this->table()) {
            $other = $model->dsql()->expr($this->table().'.'.$this->getForeignFieldName());
        }

        return $refModel->dsql()
            ->field($refModel->title_field)
            ->where($refModel->id_field, $other);
    }

    /**
     * @todo Unused method parameter $data
     *
     * @param SQL_Model $model
     * @return mixed
     *
     */
    public function getValue($model, $data)
    {
        return $model->data[$this->short_name];
    }

    /**
     * @return string
     */
    public function getForeignFieldName()
    {
        return $this->foreignName;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setForeignFieldName($name)
    {
        $this->foreignName = $name;

        return $this;
    }
}
