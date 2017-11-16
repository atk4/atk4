<?php
/**
 * Implements RESTful interface access as per specification
 * http://en.wikipedia.org/wiki/Representational_state_transfer.
 *
 * RESTful access is typically implemented through two URL patterns:
 *
 * Collection URI such as: 'resources.json', listing and adding new records.
 * Element URI such as: 'resources/28.json', updating and deleting existing records.
 *
 * You must define both properties in your model:
 *
 *  public $collection_uri = 'resources.json';
 *  public $element_uri    = 'resources/{$id}.json';
 *
 * This Data Controller uses Pest as transport:
 * https://github.com/educoder/pest
 *
 * If you use XML API, change $transport_class to 'PestXML';
 */
class Controller_Data_RESTful extends Controller_Data
{
    public $transport_class = 'PestJSON'; // PestJSON | PestXML

    protected $update_mode = 'PUT';
    protected $insert_mode = 'POST';

    public function setSource($model, $table)
    {
        if (!$model->collection_uri) {
            throw $this->exception('Define $collection_uri in your model');
        }
        if (!$model->element_uri) {
            throw $this->exception('Define $element_uri in your model');
        }

        $pest = new $this->transport_class($table);

        if ($auth = $model->auth ?: $this->app->getConfig('restapi/auth', null)) {

            // Per-URL authentication is defined
            if (is_array($auth) && isset($auth[$table])) {
                $auth = $auth [$table];
            }

            if (!is_array($auth)) {
                throw $this->exception('auth must be array("user","pass")');
            }

            $pest->setupAuth($auth[0], $auth[1], @$auth[2] ?: 'basic');
        }

        $model->addMethod('pest', function () use ($pest) {
            return $pest;
        });

        parent::setSource($model, $pest);
    }

    /**
     * Sends request to specified URL with specified method and data.
     */
    public function sendRawRequest($model, $url, $method = 'GET', $data = null)
    {
        if ($model->debug) {
            echo '<font color="blue">'.
                htmlspecialchars("$method $url (".json_encode($data).')').
                '</font>';
        }
        $method = strtolower($method);

        $pest = $model->_table[$this->short_name];
        if ($data) {
            return $pest->$method($url, $data);
        } else {
            return $pest->$method($url);
        }
    }

    public function sendCollectionRequest($model, $method = 'GET', $data = null)
    {
        return $this->sendRawRequest($model, $model->collection_uri, $method, $data);
    }
    public function sendItemRequest($model, $id, $method = 'GET', $data = null)
    {
        return $this->sendRawRequest(
            $model,
            str_replace('{$id}', $id, $model->element_uri),
            $method,
            $data
        );
    }

    // Implement loadBy
    public function loadById($model, $id)
    {
        $data = $this->sendItemRequest($model, $id);
        if (is_array($data)) {
            $model->id = $id;
            $model->data = $data;
        }
    }

    // Implement iteration
    public function prefetchAll($model)
    {
        return $this->sendCollectionRequest($model);
    }
    public function loadCurrent($model, &$cursor)
    {
        if (!$cursor) {
            return $model->unload();
        }
        $model->data = array_shift($cursor);
        $model->id = $model->data[$model->id_field];
    }

    // Saving, updating and deleting data
    public function save($model, $id, $data)
    {
        if (is_null($id)) { // insert
            $model->data = $this->sendCollectionRequest($model, $this->insert_mode, $data);
            $model->id = $model->data ? $model->data[$model->id_field] : null;
        } else { // update
            $model->data = $this->sendItemRequest($model, $id, $this->update_mode, $data);
            $model->id = $model->data ? $model->data[$model->id_field] : null;
        }

        return $model->id;
    }

    public function delete($model, $id)
    {
        $this->sendItemRequest($model, $id, 'DELETE');
        $model->unload();
    }
}
