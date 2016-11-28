<?php
/**
 * Creates field as selectable grid. Saves JSON encoded list of ids of related model.
 *
 * Usage example:
 *
 * In model define field like:
 *  $this->addField('products', [
 *       'type'      => null,
 *       'mandatory' => true,
 *       'serialize' => 'json',
 *       'ui' => [
 *           'display' => ['form' => 'JSONArraySelectable', 'grid' => 'json'],
 *       ],
 *   ]);
 *
 * In form you should set model of this field/grid:
 *  $form->getElement('products')
 *      ->setModel(new Model_Product($this->api->db));
 */
class Form_Field_JSONArraySelectable extends Form_Field_JSONArray
{
    public function getInput($attr = array())
    {
        // create selectable grid
        $g = $this->add('Grid', ['show_header' => false], 'field_input');
        $g->setModel($this->model, [$this->model->title_field]);
        $g->addSelectable($this);
        $g->template->tryDel('Pannel');

        // output hidden field and grid
        return parent::getInput(array_merge(array(
                'type' => 'hidden',
                'name' => $this->name,
                'data-shortname' => $this->short_name,
                'id' => $this->name,
                'value' => json_encode($this->value),
            ), $attr)) .
            $g->getHTML();
    }
}
