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
/* This controller will simply output data as it's being passed through */
class Controller_Data_Dumper extends Controller_Data {
    protected $log = array();
    protected $watchedController = null;

    function setWatchedControllerData($model, $controller) {
        $controller = $this->app->normalizeClassName($controller,'Data');
        $this->watchedController = $model->setController($controller);
    }
    function getLog($toClean=true) {
        $log = $this->log;
        if ($toClean) {
            $this->log = array();
        }
        return $log;
    }
    function printLog($toClean=true) {
        $log = $this->getLog($toClean);
        echo '<pre>';
        foreach($log as $l) {
            echo "$l\n";
        }
        echo '</pre>';
    }
    function __destruct(){
        if(!$this->log) { return; }
        $this->printLog();
    }
    private function log($method, $args, $isCalling=true) {
        $s = '';
        foreach ($args as $arg) {
            if ($arg instanceof AbstractObject) {
                $s .= ', ' . get_class($arg) . ' ' . $arg->short_name;
            } else {
                $s .= ', ' . $arg;
            }
        }
        $s = substr($s, 2);
        if ($isCalling) {
            $this->log[] = $this->watchedController->short_name . '::' . $method . " with ($s)";
        } else {
            $this->log[] = $this->watchedController->short_name . '::' . $method . " return $s";
        }
    }

    // {{{ Override all Controller_Data methods
    function setSource($model, $data) {
        return $this->__call('setSource', array($model, $data));
    }
    function save($model, $id, $data) {
        return $this->__call('save', array($model, $id, $data));
    }
    function delete($model,$id) {
        return $this->__call('delete', array($model, $id));
    }
    function loadById($model,$id) {
        return $this->__call('loadById', array($model, $id));
    }
    function loadByConditions($model) {
        return $this->__call('loadByConditions', array($model));
    }
    function deleteAll($model) {
        return $this->__call('deleteAll', array($model));
    }
    function prefetchAll($model) {
        return $this->__call('prefetchAll', array($model));
    }
    function loadCurrent($model) {
        return $this->__call('loadCurrent', array($model));
    }
    // }}}
    function __call($method, $args) {
        $this->log($method, $args, true);
        if ($this->watchedController) {
            $ret = call_user_func_array(array($this->watchedController, $method), $args);
            $this->log($method, array($ret), false);
        }
        return $ret;
    }
}
