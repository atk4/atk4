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
    
    /**
     * Variables to hold record count and offset for setLimit functionality
     */
    protected $limited = false;
    protected $limit_count;
    protected $limit_offset;
    protected $current_offset;



    function setSource($model,$data=undefined){
        if(!$data || $data===undefined)$data=array();
        parent::setSource($model,$data);

        if(!$model->hasElement($model->id_field)) {
            $model->addField($model->id_field)->system(true);
        }

        return $this;
    }
    function getBy($model,$field,$cond=undefined,$value=undefined){
        foreach($model->_table[$this->short_name] as $row){
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
        $t =& $model->_table[$this->short_name];
        if(!is_array($t))return null;
        reset($t);
        list($id,$row)=each($t);

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
        $t =& $model->_table[$this->short_name];
        if(is_object($id))return;

        if(@$model->id_field){
            if ( !isset($t[$id]) 
                || $t[$id][$model->id_field]!=$id) {
                
                return $this->tryLoadBy($model,$model->id_field,$id);
            }
            // ID key exists and it points to record with a matching id_field.
            // Lucky! Can save some time loading it.
        }
        if(!isset($t[$id]))return $this;
        $model->data=$t[$id];
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
        $t =& $model->_table[$this->short_name];
        $id = $id?:$model->id;
        
        if(is_null($id)){
            if($this->sequential_id){
                // calculate initial max_id in case we already have some initial
                // data somehow set, but not with save().
                if (!$this->max_id && !empty($t)) {
                    $this->max_id = max(array_keys($t));
                }
                
                $id = ++$this->max_id;
            }else{
                $id = uniqid();
            }
            if($model->id_field){
                $model->data[$model->id_field]=$id;
            }
        }
        $t[$id]=$model->data;
        
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
        $t =& $model->_table[$this->short_name];
        if ($this->limited) {
            return array_slice($t, $this->limit_offset, $this->limit_count, true);
        } else {
            return $t;
        }
    }
    function count($model, $alias = null){
        return count($model->_table[$this->short_name]);
    }
    function setOrder($model,$field,$desc=false){
        if (is_bool($desc)) {
            $desc=$desc?'desc':'';
        } elseif (is_callable($field)) {
            // do nothinc
            $model->_table[$this->short_name]['order']=$field;
        } elseif (strtolower($desc)==='asc') {
            $desc='';
        } elseif ($desc && strtolower($desc)!='desc') {
            throw $this->exception('Incorrect ordering keyword')
                ->addMoreInfo('order by', $desc);
        }

        $model->_table[$this->short_name.'__options']['order']=
            function($a,$b)use($field,$desc){
                $r = strtolower($a[$field]) < strtolower($b[$field]) ? -1 : 1;
                return $desc==='desc' ? -$r : $r;
            };

        // this physically change order of array elements, so be aware of that !
        //uasort($model->_table[$this->short_name], 
        return $this;
    }
    
    function setLimit($model,$count,$offset=0){
        if ($count!==null) {
            $this->limited = true;
            $this->limit_count = $count;
            $this->limit_offset = $offset;
        } else {
            $this->limited = false;
        }
    }

    /**
     * This method applies conditions, orders etc then returns
     * a subset of records within the array
     */
    function createCursor($model){
        $t = $model->_table[$this->short_name];
        
        if ($this->limited) {
            // Imants: probably some kind of magic can be used here to move
            // array internal pointer instantly to specific offset.
            // Maybe something with ArrayIterator->seek($pos) or
            // LimitIterator(ArrayIterator)->getInnerIterator()->seek($pos)
            
            // Following if just a simple implementation with looping.
            // if limit_offset is closer to beginning of array then loop from
            // start else loop from the end of array till we reach it.
            // That way we offset as fast as possible.
            $cnt = $this->count($model);
            if ($this->limit_offset < $cnt/50) {
                $this->current_offset = 0;
                reset($t);
                while($this->current_offset < $this->limit_offset && next($t)) {
                    $this->current_offset++;
                }
            } else {
                $this->current_offset = $cnt-1;
                end($t);
                while($this->current_offset > $this->limit_offset && prev($t)) {
                    $this->current_offset--;
                }
            }
        } else {
            reset($t);
        }


        if($model->_table[$this->short_name.'__options']['order']){
            uasort($t, $model->_table[$this->short_name.'__options']['order']);
        }

        return $t;

    }
    
    function rewind($model){

        $model->_table[$this->short_name.'__options']['cursor']
            =$this->createCursor($model);

        $t =& $model->_table[$this->short_name.'__options']['cursor'];

        list($model->id,$model->data) = each($t);
        if (@$model->id_field && isset($model->data[$model->id_field])) {
            $model->id = $model->data[$model->id_field];
        }
        
        return $model->data;
    }
    function next($model){
        $t =& $model->_table[$this->short_name.'__options']['cursor'];
        
        list($model->id,$model->data) = each($t);
        
        if ($this->limited) {
            $this->current_offset++;
            if($this->current_offset >= $this->limit_offset + $this->limit_count) {
                $model->id = $model->data = null;
            }
        }
        
        if (@$model->id_field && isset($model->data[$model->id_field])) {
            $model->id = $model->data[$model->id_field];
        }
        $model->set("id", $model->id); // romans, revise please - otherwise, array based source not working properly
        return $model;
    }
    function getActualFields(){
        return array();
    }
}
