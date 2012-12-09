<?php
/* 
Implements connectivity between Model and Session 
*/
class Controller_Data_Session extends Controller_Data_Array {

    public $search_on_load=false;

    function setSource($model,$data=undefined){
        $this->api->initializeSession();
        if($data===undefined || $data === null)$data='-';

        if(!$_SESSION['ctl_data'][$data]){
            $_SESSION['ctl_data'][$data]=array();
        }


        $model->_table[$this->short_name] =& $_SESSION['ctl_data'][$data];
    }
}
