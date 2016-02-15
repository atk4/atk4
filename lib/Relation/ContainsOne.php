<?php
class Relation_ContainsOne extends Field {
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

        $this->system(true);
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

        $model->data = $this->owner[$this->short_name];
        $model->id = $model->data?1:null;
        if($this->decode)$model->data = call_user_func($this->decode,$model->data);

        $self = $this;
        $model->addHook('beforeSave', function($m)use($self){
            $e = $m->data;
            if($this->encode)$e = call_user_func($this->encode,$e);
            $self->owner[$self->short_name] = $e;
            $self->owner->saveLater();
        });


        return $model;
    }

}