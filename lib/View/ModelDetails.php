<?php
/**
 * Shows contents of particular model records
 */
class View_ModelDetails extends Grid_Basic
{
    public $default_controller=null;

    function init(){
        parent::init();
        $this->addColumn('text', 'id');
        $this->addColumn('text', 'name');
        $this->addColumn('text', 'value');
    }

    function render(){
        if (!$this->model->loaded()) {
            throw $this->exception('Specified model must be loaded');
        }
        $data = array();
        foreach ($this->model->elements as $key => $field) {
            if ($field instanceof Field) {
                $data[]=array(
                    'id'=>$key,
                    'name'=>$field->caption(),
                    'value'=>$field->get()
                );
            }
        }

        $this->setSource($data);

        return parent::render();
    }
}
