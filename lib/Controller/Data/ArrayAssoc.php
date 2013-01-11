<?php // vim:ts=4:sw=4:et:fdm=marker
/*
 * Undocumented
 *
 * @link http://agiletoolkit.org/
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
class Controller_Data_ArrayAssoc extends Controller_Data_Array {

    function setSource($m,$s){
        $d=array();
        foreach($s as $key=>$val){
            $d[$key]=array('name'=>$val,'id'=>$key);
        }
        return parent::setSource($m,$d);
    }

    /*
    function load($model,$id=null){
        $model->data=$model->table[$id];
    }

    function save($model,$id=null){
        $model->table[$id]=$model->data;
    }

    function delete($model,$id=null){
        unset($model->table[$id]);
    }

    function tryLoad($model,$id){
        if(!isset($table[$id]))return;
        $this->load($model,$id);
    }

    function loadBy($model,$field,$cond,$value){
        $model->data=$this->getBy($model,$field,$cond,$value);
    }

    function tryLoadBy($model,$field,$cond,$value){
        $data=$this->getBy($model,$field,$cond,$value);
        if($data)$model->data=$data;
    }

    function deleteAll($model){
        $model->table=array();
    }
    function getRows($model){
        return $model->table;
    }
    function getBy($model,$field,$cond,$value){
        if($cond!='=')return $this->exception('Unsupported condition','NotImplemented');

        // load by id field
        if($field===$model->id_field)
            return $this->load($model,$value);

        // load by other field
        foreach($model->table as $key=>$data){
            if($data[$field]==$value){
                return $data;
            }
        }
        return null;
    }

    function setOrder($model,$field,$desc=false){
        return $this->exception('','NotSupported');
    }

    function setLimit($model,$count,$offset=0){
        return $this->exception('','NotSupported');
    }
     */
}
