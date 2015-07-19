<?php
class Relation_ContainsMany extends Field {
    public $auto_track_element=true;
    public $json = false;
    public $encode = null;
    public $decode = null;

    function init(){
        parent::init();

        if($this->json){
            $this->encode = function($t){ return json_encode($t); };
            $this->decode = function($t){ return json_decode($t,true); };
        }
    }

    function ref(){

        // create new instance
        $model = $this->getModel();

        if(is_callable($model)){
            call_user_func($model, $model = $this->add('Model'));
        }elseif(is_string($model)){
            $model = $this->app->normalizeClassName($model,'Model');

            $model = $this->add($model);
        }

        $data = $this->owner[$this->short_name]?:[];

        if($this->decode)$data = call_user_func($this->decode,$data);

        $model->setSource('Array',$data);

        $self = $this;
        $model->addHook('afterSave', function($m)use($self,$data){
            $e = $m->_table[$m->controller->short_name];
            if($this->encode)$e = call_user_func($this->encode,$e);
            $self->owner[$self->short_name] = $e;
            $self->owner->saveLater();
        });


        return $model;
    }

}