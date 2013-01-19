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
        if(!$_SESSION['ctl_data'][$data][$model->table]){
            $_SESSION['ctl_data'][$data][$model->table]=array();
        }

        $model->_table[$this->short_name] =& $_SESSION['ctl_data'][$data][$model->table];
    }
}
