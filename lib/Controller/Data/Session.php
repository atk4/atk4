<?php
/* 
Implements connectivity between Model and Session 
*/
class Controller_Data_Session extends Controller_Data_Array {
    function setSource($model,$data=undefined){
        if($data===undefined)return $this;

        $this->model->_table[$this->name] =& $_SESSION['ctl_data'][$data];
    }
}
