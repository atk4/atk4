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

class Controller_Data_Array extends Controller_Data{

    /**
     * By default new records are added with the ID being sequential. If you set
     * this to false, then model IDs will be assigned using unique identifiers.
     */
    public $sequential_id = true;
    
    /**
     * Maximum ID value. Used when $sequential_id = true.
     */
    protected $max_id = 0;



    function setSource($model,$data=undefined){
        if(!$data || $data===undefined)$data=array();
        parent::setSource($model,$data);

        if(!$model->hasElement($model->id_field)) {
            $model->addField($model->id_field)->system(true);
        }

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
            if ( !isset($model->_table[$this->short_name][$id]) 
                || $model->_table[$this->short_name][$id][$model->id_field]!=$id) {
                
                return $this->tryLoadBy($model,$model->id_field,$id);
            }
            // ID key exists and it points to record with a matching id_field.
            // Lucky! Can save some time loading it.
        }
        if(!isset($model->_table[$this->short_name][$id]))return $this;
        $model->data=$model->_table[$this->short_name][$id];
        $model->dirty=array();
        $model->id=$id;
        return $this;
    }
    function load($model,$id=null){
        $this->tryLoad($model,$id);
        if (! $model->loaded()) {
            throw $this->exception('Unable to load data')
                ->addMoreInfo('id',$id);
        }
        return $this;
    }
    function save($model,$id=null){
        $id = $id?:$model->id;
        
        if(is_null($id)){
            if($this->sequential_id){
                // calculate initial max_id in case we already have some initial
                // data somehow set, but not with save().
                if (!$this->max_id && !empty($model->_table[$this->short_name])) {
                    $this->max_id = max(array_keys($model->_table[$this->short_name]));
                }
                
                $id = ++$this->max_id;
            }else{
                $id = uniqid();
            }
            if($model->id_field){
                $model->data[$model->id_field]=$id;
            }
        }
        $model->_table[$this->short_name][$id]=$model->data;
        
        return $id;
    }
    function delete($model,$id=null){
        $id = $id?:$model->id;
        
        unset($model->_table[$this->short_name][$id]);
        $model->unload();
        
        // Imants: if we delete last element, then roll back sequence ID by one.
        // Disabled because sequence should only go forward and no backwards.
        // It's like AutoIncrement in MySQL, or Sequence in Oracle.
        // if ($this->sequential_id && $this->max_id == $id) {
        //     $this->max_id--;
        // }
        
        return $this;
    }
    function deleteAll($model){
        $model->_table[$this->short_name]=array();
        
        // Reset max id. Works like Truncate in MySQL or Oracle.
        if ($this->sequential_id) {
            $this->max_id = 0;
        }
        
        return $this;
    }
    function getRows($model){
        return $model->_table[$this->short_name];
    }
    function count($model){
        return count($model->_table[$this->short_name]);
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
        if(@$model->id_field && isset($model->data[$model->id_field])) {
            $model->id=$model->data[$model->id_field];
        }
        return $model->data;
    }
    function next($model){
        list($model->id,$model->data)=each($model->_table[$this->short_name]);
        if(@$model->id_field && isset($model->data[$model->id_field])) {
            $model->id=$model->data[$model->id_field];
        }
        $model->set("id", $model->id); // romans, revise please - otherwise, array based source not working properly
        return $model;
    }
    function getActualFields(){
        return array();
    }
}
