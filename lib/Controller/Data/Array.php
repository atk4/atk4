<?php // vim:ts=4:sw=4:et:fdm=marker
/*
 * Implementation of array access controller for Agile Toolkit Models.
 *
 * $m=$this->add('Model');
 * $m->addField('test');
 * $m->table='test_table';
 *
 * $storage=array();
 *
 * $m->setSource('Array', $storage);
 * $m['test']=123;
 * $m->save(1);
 *
 * $m['test']=321;
 * $m->save(2);
 *
 * $m->load(1);
 * echo $m['test'];
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/

class Controller_Data_Array extends Controller_Data {

    /* By default new records are added with the ID being sequential. If you set this to false, then model IDs will be assigned using unique identifiers */
    public $sequential_id=true;

    /* If your model is using id_field and the record with key==id was not found, controller will scan array for a matching record based on the field. 
     * When you are using data blob from external source which does not have key associations, you should keep this "true". If you save records using
     * save() only, it will maintain keys automatically and you can set this to false for extra speed.
     *
     * This setting have no effect on models without id_field property, as those would always rely on keys */
    public $search_on_load=true;

    function setSource($model,$data=undefined){
        if(!$data || $data===undefined)$data=array();
        parent::setSource($model,$data);

        if(!$model->hasElement($model->id_field))$model->addField($model->id_field)->system(true);


        return $this;
    }
    function getBy($model,$field,$cond=undefined,$value=undefined){
        $t =& $model->_table[$this->short_name];
        foreach($t as $row){
            if($row[$field]==$value){
                return $row;
            }
        }
    }
    function tryLoadBy($model,$field,$cond=undefined,$value=undefined){
        if($value===undefined){
            $value=$cond;
            $cond='=';
        }
        foreach($model->_table[$this->short_name] as $row){
            if($row[$field]==$value){
                $model->data=$row;
                $model->dirty=array();
                $model->id=$row[$model->id_field];
                return $this;
            }
        }
        return $this;
    }
    function tryLoadAny($model){
        if(!is_array($model->_table[$this->short_name]))return null;
        reset($model->_table[$this->short_name]);
        list($id,$row)=each($model->_table[$this->short_name]);

        $model->data=$row;
        $model->dirty=array();
        $model->id=$model->id_field?$row[$model->id_field]:$id;

        return $this;
    }
    function loadBy($model,$field,$cond=undefined,$value=undefined){
        if($value===undefined){
            $value=$cond;
            $cond='=';
        }
        $this->tryLoadBy($model,$field,$cond,$value);
        if(!$model->loaded())throw $this->exception('Unable to load data')
            ->addMoreInfo('field',$field)
            ->addMoreInfo('condition',$cond)
            ->addMoreInfo('value',$value);
        return $this;
    }
    function tryLoad($model,$id){
        if(is_object($id))return;

        if(@$model->id_field){
            if( !isset($model->_table[$this->short_name][$id]) || $model->_table[$this->short_name][$id][$model->id_field]!=$id){
                return $this->tryLoadBy($model,$model->id_field,$id);
            }
            // ID key exists and it points to record with a matching id_field. Lucky! Can save some time loading it.
        }
        if(!isset($model->_table[$this->short_name][$id]))return $this;
        $model->data=$model->_table[$this->short_name][$id];
        $model->dirty=array();
        $model->id=$id;
        return $this;
    }
    function load($model,$id=null){
        $this->tryLoad($model,$id);
        if(!$model->loaded())throw $this->exception('Unable to load data')
            ->addMoreInfo('id',$id);
        return $this;
    }
    function save($model,$id=null){
        if(is_null($id)){
            if($this->sequential_id){
                // Imants: This fail if array is not sorted in ascending order by its keys
                //end($model->_table[$this->short_name]);
                //list($id)=each($model->_table[$this->short_name]);
                //$id++;
                if(!empty($model->_table[$this->short_name])) {
                    $id = max(array_keys($model->_table[$this->short_name])) + 1;
                } else {
                    $id = 1;
                }
            }else{
                $id=uniqid();
            }
            if($model->id_field){
                $model->data[$model->id_field]=$id;
            }
            $model->_table[$this->short_name][$id]=$model->data;
        }else{
            $model->_table[$this->short_name][$id]=$model->data;
        }
        return $id;
    }
    function delete($model,$id=null){
        unset($model->_table[$this->short_name][$id?:$model->id]);
        return $this;
    }
    function deleteAll($model){
        $model->_table[$this->short_name]=array();
        return $this;
    }
    function getRows($model){
        return $model->_table[$this->short_name];
    }
    function setOrder($model,$field,$desc=false){
        if (is_bool($desc)) {
            $desc=$desc?'desc':'';
        } elseif (strtolower($desc)==='asc') {
            $desc='';
        } elseif ($desc && strtolower($desc)!='desc') {
            throw $this->exception('Incorrect ordering keyword')
                ->addMoreInfo('order by', $desc);
        }
        // this physically change order of array elements, so be aware of that !
        uasort($model->_table[$this->short_name], function($a,$b)use($field,$desc){
            $r = strtolower($a[$field]) < strtolower($b[$field]) ? -1 : 1;
            return $desc==='desc' ? -$r : $r;
        });
    }
    function setLimit($model,$count,$offset=0){
        // TODO: splice
    }

    function rewind($model){
        reset($model->_table[$this->short_name]);

        list($model->id,$model->data)=each($model->_table[$this->short_name]);
        if(@$model->id_field && isset($model->data[$model->id_field]))$model->id=$model->data[$model->id_field];
        return $model->data;
    }
    function next($model){
        list($model->id,$model->data)=each($model->_table[$this->short_name]);
        if(@$model->id_field && isset($model->data[$model->id_field]))$model->id=$model->data[$model->id_field];
        $model->set("id", $model->id); // romans, revise please - otherwise, array based source not working properly
        return $model;
    }
    function setAssoc($data){
        $this->array_data=array();
        foreach($data as $id=>$name){
            $this->array_data[]=array('id'=>$id,'name'=>$name);
        }
        return $this;
    }
    function getActualFields(){
        return array();
    }
}
