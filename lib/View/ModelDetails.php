<?php
/**
 * Shows contents of particular model records
 */
class View_ModelDetails extends Grid_Basic
{
    public $default_controller=null;

    /**
     * The view will have 3 columns instead of just 2 by also showing the
     * ID of each field. Uneful for debug purposes but unnecessary for
     * general use.
     */
    public $include_id_column=false;

    function init(){
        parent::init();
        if($this->include_id_column)$this->addColumn('text', 'id');
        $this->addColumn('text', 'name');
        $this->addColumn('text', 'value');
    }
    public $source_set=false;
    function setSource($data){
        if(!isset($data[0]) && !is_array($data[0])){
            // associative array
            $newdata=array();
            foreach($data as $key=>$value){
                $newdata[]=array(
                    'id'=>$key,
                    'value'=>$value
                );
            }
            $data=$newdata;
        }
        $this->source_set=true;
        return parent::setSource($data);
    }
    function render(){
        if(!$this->source_set){
            if (!$this->model->loaded()) {
                throw $this->exception('Specified model must be loaded');
            }
            $data = array();
            foreach ($this->model->elements as $key => $field) {
                if ($field instanceof Field || $field instanceof Field_Base) {
                    $data[]=array(
                        'id'=>$key,
                        'name'=>$field->caption(),
                        'value'=>$field->get()
                    );
                }
            }

            parent::setSource($data);
        }

        return parent::render();
    }
}
