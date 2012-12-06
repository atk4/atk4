<?php // vim:ts=4:sw=4:et:fdm=marker
/*
 * Undocumented
 *
 * @link http://agiletoolkit.org/
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2012 Romans Malinovskis <romans@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
class Controller_Data_ArrayAssoc extends Controller_Data_Array {
    function setSource($model,$data){
        $newdata=array();
        foreach($data as $id=>$name){
            $newdata[]=array('id'=>$id,'name'=>$name);
        }
        return parent::setSource($model,$newdata);
    }
    function load($model,$id){
        $model->unload();
        if(!isset($model->table[$id]))return $model;
        $model->set('id',$id);
        $model->set('name',$model->table[$id]);
        return $model;
    }
    function save($model,$id=null){
        if(is_null($id))$id=$model->id;
        $model->table[$id]=$model->get('name');
        return $model;
    }
}
