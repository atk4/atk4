<?php
class Controller_Data_Mongo extends Controller_Data {
    function setSource($model,$table){

        if(@!$this->api->mongoclient){
            $m=new MongoClient();


            $this->api->mongoclient=$m->ven;

        }

        parent::setSource($model,array(
            'db'=>$this->api->mongoclient->$table,
            'conditions'=>array(),
            'collection'=>$model->table
        ));

        //$model->data=$model->_table[$this->short_name]['db']->get($id);
    }

    /** Implemetns access to our private storage inside model */
    protected function _get($model,$key){
        return $model->_table[$this->short_name][$key];
    }
    protected function _set($model,$key,$val){
        $model->_table[$this->short_name][$key]=$val;
    }

    function save($model,$id=null){

        $data=$model->data;
        unset($data[$model->id_field]);
        foreach ($model->_references as $our_field=>$junk) {
            if(isset($data[$our_field]) && $data[$our_field]){

                $deref=str_replace('_id','',$our_field);
                if($deref == $our_field)continue;

                $m=$model->ref($our_field)->load($data[$our_field]);

                $data[$deref]=$m[$m->title_field];

                $data[$our_field]=new MongoID($data[$our_field]);
            }

        }

        if($model->loaded()){
            $data[$model->id_field] = new MongoID($model->id);
        }

        $db=$this->_get($model,'db')->save($data);
        $model->id=(string)$data[$model->id_field]?:null;
        return $model->id;
    }
    function tryLoad($model,$id){
    }
    function load($model,$id=null){
        $this->tryLoadBy($model,$model->id_field,new MongoID($id));
    }
    function getBy($model,$field,$cond=undefined,$value=undefined){
    }
    function tryLoadBy($model,$field,$cond=undefined,$value=undefined){

        if ($value===undefined) {
            $value=$cond;
            $cond='=';
        }

        $model->data=$this->_get($model,'db')->findOne(
            array_merge(
                $model->_table[$this->short_name]['conditions'],
                array($field=>$value)
            )
        );
        $model->id=(string)$model->data[$model->id_field]?:null;
        return $model->id;
    }
    function tryLoadAny($model){
        $model->data=$this->_get($model,'db')->findOne(
            $model->_table[$this->short_name]['conditions']
        );
        $model->id=(string)$model->data[$model->id_field]?:null;
        return $model->id;
    }
    function loadBy($model,$field,$cond=undefined,$value=undefined){
    }
    function delete($model,$id=null){
        $id=new MongoID($id?:$this->id);
        $model->data=$this->_get($model,'db')->remove(
            array($model->id_field=>$id),
            array('justOne'=>true)
        );
        $model->unload();
        return $this;
    }
    function deleteAll($model){}
    function getRows($model){}
    function setOrder($model,$field,$desc=false){}
    function setLimit($model,$count,$offset=0){}
    function rewind($model){
        $c=$this->_get($model,'db')->find(
            $model->_table[$this->short_name]['conditions']
        );
        $this->_set($model,'cur',$c);
        $model->data=$c->getNext();
        $model->id=(string)$model->data[$model->id_field]?:null;
        return $model->data;
    }
    function next($model){
        $c=$this->_get($model,'cur');
        $model->data=$c->getNext();
        $model->id=(string)$model->data[$model->id_field]?:null;
        return $model->data;
    }

    function addCondition($model,$field,$value){
        if($model->_table[$this->short_name]['conditions'][$field]){
            throw $this->exception('Multiple conditions on same field not supported yet')
                ;
        }
        if ($f=$model->hasElement($field)) {
            // TODO: properly convert to Mongo presentation
            if($f->type()=='boolean' && is_bool($value)) {
                $value=(int)$value;
            }

            if($f->type()=='reference_id' && $value) {
                $value = new MongoID($value);
            }

            $f->defaultValue($value);
        }
        $model->_table[$this->short_name]['conditions'][$field]=$value;
    }
}
