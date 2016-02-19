<?php
/**
 * Implements connectivity between Model and Session.
 */
class Controller_Data_Session extends Controller_Data_Array
{
    public function setSource($model, $data)
    {
        $this->app->initializeSession();
        if ($data === undefined || $data === null) {
            $data = '-';
        }

        if (!$_SESSION['ctl_data'][$data]) {
            $_SESSION['ctl_data'][$data] = array();
        }
        if (!$_SESSION['ctl_data'][$data][$model->table]) {
            $_SESSION['ctl_data'][$data][$model->table] = array();
        }

        $model->_table[$this->short_name] = &$_SESSION['ctl_data'][$data][$model->table];
    }
}
