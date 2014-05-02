<?php
/**
 * Implementation of RESTful endpoint for App_REST
 */
class Endpoint_REST {

    public $model_class=null;
    public $user_id_field='user_id';
    public $user=null;  // authenticated user
    public $authenticate=true;

    public $allow_list=true;
    public $allow_list_one=true;
    public $allow_add=false;
    public $allow_edit=false;
    public $allow_delete=false;

    public $app;
    public $api;


    public function _authenticate() {
        // Verifies user authentication data
        if(!$this->authenticate)return;

        if($t=$_GET['token']){
            $guid= $this->_hubid_auth($t);
            $m=$this->app->add('Model_User')->loadBy('hub_guid',$guid);
            return $this->user = $m;

        }elseif ($u=$_GET['username']) {
            $p=$_GET['password'];
        }else{

            if (!isset($_SERVER['PHP_AUTH_USER'])) {

                //throw $this->exception('Use Authenticaiton');

                header('WWW-Authenticate: Basic realm="ATK4 REST API"');
                header('HTTP/1.0 401 Unauthorized');
                echo 'Please use authentication authentication';
                exit;
            }

	    $u=$_SERVER['PHP_AUTH_USER'];
	    $p=$_SERVER['PHP_AUTH_PW'];

        }

        if($_SERVER['PHP_X_VEN_AUTH']) {
            throw $this->exception('Not Implemented');
        }

        $auth=$this->app->add('Controller_VenAuth');
        $result = $auth->verifyCredentials($u,$p);

        if($result === false){
            //throw $this->exception('Authentication wrong');

            header('WWW-Authenticate: Basic realm="ATK4 REST API"');
            header('HTTP/1.0 401 Unauthorized');
            echo 'Authenticaiton wrong';
            exit;
        }

        $this->user = $auth->model;
    }

    protected function _model(){
        // Based od authentication data, return a valid model
        if(!$this->model_class)throw $this->exception('Must have model');

        $m=$this->app->add('Model_'.$this->model_class);
        if($this->user_id_field && $this->authenticate){
            // if not authenticated, blow up
            $m->addCondition($this->user_id_field,$this->user->id);
        }

        $id=$_GET['id'];
        if(!is_null($id)){
            $m->load($id);
        }

        return $m;

    }

    protected function _outputOne($data) {

        if($data['_id']) {
            $data = array('id'=>(string)$data['_id'])+$data;
        }
        unset($data['_id']);

        foreach($data as $key=>$val){
            if($val instanceof MongoID) {
                $data[$key]=(string)$val;
            }
        }

        return $data;
    }

    protected function _outputMany($data) {
        $output = array();
        foreach($data as $row) {
            $output[] = $this->_outputOne($row);
        }
        return $output;
    }

    protected function _input($data,$filter=true){
        // validates input
        if(is_array($filter)) {
            $data = array_intersect_key($my_array, array_flip($allowed));
        }

        unset($data['id']);
        unset($data['_id']);
        unset($data['user_id']);
        unset($data['user']);

        return $data;
    }


    function get(){
        $m=$this->_model();
        if($m->loaded()){
            if(!$this->allow_list_one)throw $this->exception('Loading is not allowed');
            return $this->_outputOne($m->get());
        }

        if(!$this->allow_list)throw $this->app->exception('Listing is not allowed');
        return $this->_outputMany($m->setLimit(100)->getRows());
    }

    function insert($data){
        $m=$this->_model();

        if(!$this->allow_add)throw $this->exception('Adding is not allowed');

        if($m->loaded()) throw $this->exception('Not a valid request for this resource URL');

        $data=$this->_input($data,$this->allow_add);

        return $this->_outputOne($m->set($data)->save()->get());
    }
    function save($data){
        $m=$this->_model();

        if(!$m->loaded())throw $this->exception('Replacing of the whole collection is not supported. element URI');

        if(!$this->allow_edit)throw $this->exception('Editing is not allowed');

        $data=$this->_input($data,$this->allow_edit);

        return $this->_outputOne($m->set($data)->save()->get());
    }

    function delete(){
        if(!$this->allow_delete)throw $this->exception('Deleting is not allowed');

        $m=$this->_model();
        if(!$m->loaded())throw $this->exception('Cowardly refusing to delete all records');

        $m->delete();

        return true;
    }
}
