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
Reference implementation for data controller

Data controllers are used by "Model" class to access their data source.
Additionally SQL_Model can use data controllers for caching their data

*/

abstract class Controller_Data extends AbstractController {
    public $default_exception = 'Exception_DB';
    public $supportConditions = false;
    public $supportLimit = false;
    public $supportOrder = false;
    public $supportRef = false;
    public $supportOperators = null;

    public $auto_track_element = true;

    function setSource($model, $data) {
        $model->_table[$this->short_name] = $data;
        return $this;
    }

	abstract function save($model, $id, $data);
	abstract function delete($model, $id);

	abstract function loadById($model, $id);
    abstract function loadByConditions($model);

    abstract function deleteAll($model);

    /** Create a new cursor and load model with the first entry */
    abstract function prefetchAll($model);

    /** Provided that rewind was called before, load next data entry */
    abstract function loadCurrent($model);
}

