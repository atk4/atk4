<?php
/* 
Implements connectivity between Model and Session 
*/
class Controller_Data_Session extends Controller_Data_Array {

    public $search_on_load=false;

    function setSource($model,&$data=undefined){
        if($data===undefined)return $this;

        if(!$_SESSION['ctl_data'][$data]){
            $_SESSION['ctl_data'][$data]=array();
        }


        $this->model->_table[$this->name] =& $_SESSION['ctl_data'][$data];
    }
}
