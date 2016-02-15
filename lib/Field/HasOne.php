<?php

/**
 * Denormalized field implementation for foreign table for non-relational
 * models.
 */
class Field_HasOne extends Field_Calculated {
    private $foreignName;

    function getValue($model, $data) {
        $model = $this->add($this->getModel());
        $id = $data[$this->foreignName];

        $this->hook('beforeForeignLoad', array($model, $id));

        $model->load($id);
        $titleField = $model->getTitleField();
        return $model->get($titleField) ?  : 'Ref#' . $id;
    }

    function getForeignFieldName() {
        return $this->foreignName;
    }

    function setForeignFieldName($name) {
        $this->foreignName = $name;
        return $this;
    }
}
