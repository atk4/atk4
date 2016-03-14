<?php
/**
 * Denormalized field implementation for foreign table for non-relational
 * models.
 */
class Field_HasOne extends Field_Calculated
{
    /** @var string */
    protected $foreignName;

    /**
     * @todo Useless method parameter $model
     *
     * @param  Model $model
     * @param  array $data
     * @return mixed
     */
    public function getValue($model, $data)
    {
        /** @type Model $model */
        $model = $this->add($this->getModel());
        $id = $data[$this->foreignName];

        try {

            $this->hook('beforeForeignLoad', array($model, $id));

            $model->load($id);
            $titleField = $model->getTitleField();

            return $model->get($titleField) ?: 'Ref#'.$id;
        } catch (BaseException $e) {
            // record is no longer there it seems
            return null;
        }
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
