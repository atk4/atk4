<?php
/**
 * Denormalized field implementation for foreign table for non-relational
 * models.
 */
class Field_HasOne extends Field_Calculated
{
    protected $foreignName;

    public function getValue($model, $data)
    {
        $model = $this->add($this->getModel());
        $id = $data[$this->foreignName];

        try {

            $this->hook('beforeForeignLoad', array($model, $id));

            $model->load($id);
            $titleField = $model->getTitleField();

            return $model->get($titleField) ?: 'Ref#'.$id;
        }catch(BaseException $e){
            // record is no longer there it seems
            return null;
        }
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
