<?php
/**
 * Reference implementation for data controller.
 *
 * Data controllers are used by "Model" class to access their data source.
 * Additionally SQL_Model can use data controllers for caching their data
 */
abstract class Controller_Data extends AbstractController
{
    public $default_exception = 'Exception_DB';

    /**
     * Conditions can be set on non-primary field. This capability of
     * data controller allows your model to use methods such as
     * addCondition, loadBy, tryLoadBy etc. Each of those methods can
     * be supplied with two arguments - the field and value and
     * exact matching will be used. For three-argument use, see
     * property $supportOperators.
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
     * field to be used for ordering must take precedence.
     */
    public $supportOrder = false;

    /**
     * Controller can load and store data based on ID field.
     * This would be supported by most data controllers.
     */
    public $supportRef = false;

    /**
     * Operators add ability to use fuzzy-match on conditions.
     * Controller is required to support '>', '<', '=', '!=',
     * '<=', '>=' operators even if database uses diffirent
     * notation, due to consistency.
     *
     * If database supports additional operators, you can list
     * them as an array in this attribute, e.g.: ['like','is','is not'].
     *
     * You may also set $supportOperators='all' which will indicate
     * that this controller supports all possible operators, although
     * this is not advised.
     */
    public $supportOperators = null;

    /**
     * When controller is added into model, the model will
     * automatically keep track of it and will not dispose
     * of it.
     */
    public $auto_track_element = true;

    /**
     * Associates model with the collection/table in this data store identified
     * by second argument. For instance for Controller_Data_SQL the second
     * argument would be a table.
     *
     * All necessary data must be stored in $model->_table[$this->short_name]
     * to avoid conflicts between different controllers and to allow user to
     * use one controller with multiple models.
     */
    public function setSource($model, $data)
    {
        $model->_table[$this->short_name] = $data;

        return $this;
    }

    public function &d($model)
    {
        return $model->_table[$this->short_name];
    }

    /**
     * Writes record containing $data into the data store under id=$id.
     * If the ID field can vary, consult $model->id_field. You do not have
     * to worry about default fileds, because their values will automatically
     * be in $data.
     *
     * If $id is null then new record must be created. If $id is specified,
     * then record must be overwritten. Method must return $id of stored record.
     * This method may throw exceptions.
     */
    abstract public function save($model, $id, $data);

    /**
     * Locate and remove record in the storade with specified $id. Return
     * true if record was deleted or false if it wasn't found. In most
     * cases, the record data will first be loaded and then deleted, just
     * to make sure the record is accessible with specified conditions,
     * so you can silently ignore error.
     */
    abstract public function delete($model, $id);

    /**
     * Locate and load data for a specified record. If data backend supports
     * selective loading of fields, you may call model->getActualFields
     * to get a list of required fields for a model. When non-array is
     * returned, you should load all fields.
     */
    abstract public function loadById($model, $id);

    // Those methods may not be available in a model
    // abstract function loadByConditions($model);
    // abstract function deleteAll($model);

    /**
     * Create a new cursor and load model with the first entry. Returns cursor,
     * which will then be passed to loadCurrent() for loading more results.
     *
     * If the data store does not support cursors, then fetch all data
     * and return array. loadCurrent will automatically array_shift one record
     * on each call.
     */
    abstract public function prefetchAll($model);

    /**
     * Load next data row from cursor.
     */
    public function loadCurrent($model, &$cursor)
    {
        $model->data = array_shift($cursor);
        $model->id = $model->data[$model->id_field];
    }
}
