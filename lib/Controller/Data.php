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

    /**
     * Conditions can be set on non-primary field. This capability of
     * data controller allows your model to use methods such as 
     * addCondition, loadBy, tryLoadBy etc. Each of those methods can
     * be supplied with two arguments - the field and value and 
     * exact matching will be used. For three-argument use, see
     * property $supportOperators
     */
    public $supportConditions = false;


    /**
     * Limit makes it possible to specify count / skip for the
     * model, which is necessary for pagination.
     */
    public $supportLimit = false;

    /**
     * Controller allows re-ordering data set by non-primary field
     * in either ascending or descending order. Models support
     * ordering on multiple fields. If controller does not
     * support multiple ordering fields, then the last
     * field to be used for ordering must take precedence
     */
    public $supportOrder = false;

    /**
     * Controller can load and store data based on ID field.
     * This would be supported by most data controllers
     */
    public $supportRef = false;

    /**
     * Operators add ability to use fuzzy-match on conditions.
     * Controller is required to support '>', '<', '=', '!=',
     * '<=', '>=' operators. If the underlying data storage
     * supports other operators, controller should make them
     * available too.
     */
    public $supportOperators = null;

    /**
     * When controller is added into model, the model will
     * automatically keep track of it and will not dispose
     * of it.
     */
    public $auto_track_element = true;

    function setSource($model, $data) {
        $model->_table[$this->short_name] = $data;
        return $this;
    }

	abstract function save($model, $id, $data);
	abstract function delete($model, $id);

	abstract function loadById($model, $id);

    // Those methods may not be available in a model
    // abstract function loadByConditions($model);
    // abstract function deleteAll($model);

    /** Create a new cursor and load model with the first entry. Returns cursor */
    abstract function prefetchAll($model);

    /** Load next data row from cursor */
    function loadCurrent($model,&$cursor) {
        $model->data=array_shift($cursor);
        $model->id=$model->data[$model->id_field];
    }

}

