<?php
class Controller_Data_Mongo extends Controller_Data {
    function setSource($model,$table){

        if(@!$this->api->mongoclient){
            $m=new MongoClient();

            $db=$this->api->getConfig('mongo/db');


            $this->api->mongoclient=$m->$db;

        }

        parent::setSource($model,array(
            'db'=>$this->api->mongoclient->$table,
            'conditions'=>array(),
            'collection'=>$model->table
        ));

        $model->addMethod('incr,decr',$this);

        //$model->data=$model->_table[$this->short_name]['db']->get($id);
    }

    /** Implemetns access to our private storage inside model */
    public function _get($model,$key){
        return $model->_table[$this->short_name][$key];
    }
    public function _set($model,$key,$val){
        $model->_table[$this->short_name][$key]=$val;
    }

    function incr($m,$field,$amount){
        if(!$m->loaded())throw $this->exception('Can only increment loaded model');
        $m->db()->update(array($m->id_field=>new MongoID($m->id)), array('$inc'=>array($field=>$amount)));
    }

    function save($model,$id=null){

        $data=array();

        foreach($model->elements as $name=>$f)if($f instanceof Field){
            if(!$f->editable() && !$f->system())continue;
            if(!isset($model->dirty[$name]) && $f->defaultValue()===null)continue;

            $value=$f->get();

            if($f->type()=='boolean' && is_bool($value)) {
                $value=(bool)$value;
            }
            if($f->type()=='int'){
                $value=(int)$value;
            }
            if($f->type()=='money' || $f->type()=='float'){
                $value=(float)$value;
            }

            $data[$name]=$value;

        }
        unset($data[$model->id_field]);

        foreach ($model->_references as $our_field=>$junk) {
            if(isset($data[$our_field]) && $data[$our_field] && 
                $our_field!=$model->id_field) {

                $deref=str_replace('_id','',$our_field);
                if($deref == $our_field)continue;

                $m=$model->ref($our_field);
                if(!$m->loaded())continue;

                $data[$deref]=$m[$m->title_field];
                if($m instanceof Mongo_Model){
                    $data[$our_field]=new MongoID($data[$our_field]);
                }
            }
        }

        if($model->loaded()){
            if (!$data){
                if ($model->debug) echo '<font style="color: blue">db.'.$model->table.' is not dirty</font>';
                return $model->id;
            }
            if ($model->debug) echo '<font style="color: blue">db.'.$model->table.'.update({_id: '.(new MongoID($model->id)).'},{"$set":'.json_encode($data).'})</font>';
            $db=$this->_get($model,'db')->update(array($model->id_field=>new MongoID($model->id)), array('$set'=>$data));
            return $model->id;
        }

        if ($model->debug) echo '<font style="color: blue">db.'.$model->table.'.save('.json_encode($data).')</font>';
        $db=$this->_get($model,'db')->save($data);
        $model->id=(string)$data[$model->id_field]?:null;
        $model->data=$data;  // will grab defaults here
        if ($model->debug) echo '<font style="color: blue">='.$model->id.'</font><br/>';
        $model->dirty=array();
        return $model->id;
    }
    function tryLoad($model,$id){
        $this->tryLoadBy($model,$model->id_field,new MongoID($id)); // TODO thow exception
    }
    function load($model,$id){
        $this->tryLoadBy($model,$model->id_field,new MongoID($id));
    }
    function getBy($model,$field,$cond=undefined,$value=undefined){
    }
    function tryLoadBy($model,$field,$cond=undefined,$value=undefined){

        if ($value===undefined) {
            $value=$cond;
            $cond='=';
        }

        $cond=array_merge(
                $model->_table[$this->short_name]['conditions'],
                array($field=>$value));

        if ($model->debug) echo '<font style="color: blue">db.'.$model->table.'.findOne('.json_encode($cond).')</font><br/>';
        $model->data=$this->_get($model,'db')->findOne($cond);
        $model->id=(string)$model->data[$model->id_field]?:null;
        return $model->id;
    }
    function tryLoadAny($model){
        if ($model->debug) echo '<font style="color: blue">db.'.$model->table.'.findOne('.
            json_encode($model->_table[$this->short_name]['conditions']).')</font><br/>';
        $model->data=$this->_get($model,'db')->findOne(
            $model->_table[$this->short_name]['conditions']
        );
        $model->id=(string)$model->data[$model->id_field]?:null;
        return $model->id;
    }
    function loadAny($model){
        $this->tryLoadAny($model);
        if(!$model->loaded())throw $this->exception('No records for this model');
        return $model->id;
    }
    function loadBy($model,$field,$cond=undefined,$value=undefined){
        $this->tryLoadBy($model,$field,$cond,$value);
        if(!$model->loaded())throw $this->exception('No records matching criteria');
        return $model->id;
    }
    function delete($model,$id){
        $id=new MongoID($id);
        if ($model->debug) echo '<font style="color: blue">db.'.$model->table.'.remove('.
            json_encode(array($model->id_field=>$id)).',{justOne:true})</font><br/>';
        $model->data=$this->_get($model,'db')->remove(
            array($model->id_field=>$id),
            array('justOne'=>true)
        );
        $model->unload();
        return $this;
    }
    function deleteAll($model){
        if ($model->debug) echo '<font style="color: blue">db.'.$model->table.'.remove('.
            json_encode($model->_table[$this->short_name]['conditions']).')</font><br/>';

        $model->data=$this->_get($model,'db')->remove(
            $model->_table[$this->short_name]['conditions']
        );
        $model->unload();
        return $this;
    }
    function getRows($model){}
    function setOrder($model,$field,$desc=false){
        $this->_set($model,'order',array($field=>$desc?-1:1));
        // TODO: allow setting order multiple times
        // TODO: extend syntax to be compatible with SQL_Model
    }
    function setLimit($model,$count,$offset=0){
        $this->_set($model,'limit',array($count,$offset));
    }
    function selectQuery($model){
        $c=$this->_get($model,'db')->find(
            $model->_table[$this->short_name]['conditions']
        );

        // sort
        if($s=$this->_get($model,'order'))$c->sort($s);
        $this->_set($model,'cur',$c);

        // skip
        if($l=$this->_get($model,'limit')){
            list($count,$skip)=$l;
            if($skip)$c->skip($skip);
            if($count)$c->limit($count);
        }

        return $c;
    }
    function count($model){
        return $this->selectQuery($model)->count();
    }
    function rewind($model){
        if ($model->debug) echo '<font style="color: blue">db.'.$model->table.'.find('.json_encode($model->_table[$this->short_name]['conditions']).')</font>';
        $c=$this->selectQuery($model);

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
            throw $this->exception('Multiple conditions on same field not supported yet');
        }
        if ($f=$model->hasElement($field)) {
            // TODO: properly convert to Mongo presentation
            if($f->type()=='boolean' && is_bool($value)) {
                $value=(bool)$value;
            }
            if($f->type()=='int'){
                $value=(int)$value;
            }
            if($f->type()=='money' || $f->type()=='float'){
                $value=(float)$value;
            }

            if(
                ($f->type()=='reference_id' && $value && !is_array($value)) ||
                $field == $model->id_field
            ) {
                $value = new MongoID($value);
            }
            $f->defaultValue($value)->system(true);
        }else{
            if($field[0]!='$'){
                throw $this->exception('Condition on undefined field. Does not '.
                    'look like expression either')
                    ->addMoreInfo('model',$model)
                    ->addMoreInfo('field',$field);
            }
        }
        $model->_table[$this->short_name]['conditions'][$field]=$value;
    }
}
