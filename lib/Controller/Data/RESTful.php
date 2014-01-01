<?php
/**
 * Implements RESTful interface access as per specification
 * http://en.wikipedia.org/wiki/Representational_state_transfer
 *
 * RESTful access is typically implemented through two URL patterns:
 *
 * Collection URI such as: 'resources.json', listing and adding new records.
 * Element URI such as: 'resources/28.json', updating and deleting existing records.
 *
 * You must define both properties in your model:
 *
    public $collection_uri = 'resources.json';
    public $element_uri    = 'resources/{$id}.json';
 *
 * This Data Controller uses Pest as transport:
 * https://github.com/educoder/pest
 *
 * If you use XML API, change $transport_class to 'PestXML';
 */
class Controller_Data_RESTFul extends Controller_Data {
    public $transport_class = 'PestJSON';

    function setSource($model, $table) {

        if(!$model->collection_uri)throw $this->exception('Define $collection_uri in your model');
        if(!$model->element_uri)throw $this->exception('Define $element_uri in your model');

        parent::setSource($model, new $this->transport_class($table));
    }

    /**
     * Sends request to specified URL with specified method and data
     */
    function sendRawRequest($model,$url,$method='GET',$data=null){
        $method=strtolower($method);

        $pest = $model->_table[$this->short_name];
        if($data){
            return $pest->$method($url,$data);
        }else{
            return $pest->$method($url);
        }
    }

    function sendCollectionRequest($model,$method='GET',$data=null){
        return $this->sendRawRequest($model,$model->collection_uri,$method,$data);
    }
    function sendItemRequest($model,$id,$method='GET',$data=null){
        return $this->sendRawRequest(
            $model,
            str_replace('{$id}',$id,$model->element_uri),
            $method,
            $data
        );
    }

    // Implement loadBy
    function loadById($model, $id) {
        $data = $this->sendItemRequest($model,$id);
        if(is_array($data)) {
            $model->id = $id;
            $model->data = $data;
        }
    }

    // Implement iteration
    function prefetchAll($model) {
        return $this->sendCollectionRequest($model);
    }
    function loadCurrent($model,&$cursor) {
        if(!$cursor){
            return $model->unload();
        }
        $model->data = array_shift($cursor);
        $model->id = $model->data[$model->id_field];
    }


    // Saving, updating and deleting data
    function save($model, $id, $data) {
        if (is_null($id)) { // insert
            $model->data = $this->sendCollectionRequest($model, 'POST', $data);
            $model->id = $model->data?$model->data[$model->id_field]:null;
        } else { // update
            $model->data = $this->sendItemRequest($model, $id, 'PUT', $data);
            $model->id = $model->data?$model->data[$model->id_field]:null;
        }
    }

    function delete($model, $id) {
        $this->sendItemRequest($model, $id, 'DELETE');
        $model->unload();
    }
}
