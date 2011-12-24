<?php
class Controller_Data_ArrayAssoc extends Controller_Data_Array {
    function setSource($model,$data){
        $newdata=array();
        foreach($data as $id=>$name){
            $newdata[]=array('id'=>$id,'name'=>$name);
        }
        return parent::setSource($model,$newdata);
    }
}
