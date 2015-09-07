<?php
class Controller_Data_Clusterpoint extends Controller_Data {
    public $supportConditions = true;
    public $supportLimit = true;
    public $supportOrder = true;
    public $supportRef = true;
    public $supportOperators = 'all'; //'=' => true, '>' => true, '>=' => true, '<=' => true, '<' => true, '!=' => true, 'like' => true);

    public $transport_class = 'PestJSON';

    function &d($model){
        return $model->_table[$this->short_name];
    }

    /**
     * This method is called by a model to initialize Clusterpoint connection
     */
    function setSource($model,$api_endpoint=null){
        if(!is_array($api_endpoint)){
            $api_endpoint = [$api_endpoint];
        }

        if(substr($api_endpoint[0],0,4) != 'http'){
            $api_endpoint[0] =
                $pest->setupAuth($this->app->getConfig('cp/endpoint')).
                '/'.
                $api_endpoint[0];
        }

        $pest = new $this->transport_class($api_endpoint[0]);

        if (isset($api_endpoint['user'])?: $this->app->getConfig('restapi/auth',null)) {
            $pest->setupAuth($api_endpoint['user'], $api_endpoint['pass']);
        }elseif($this->app->getConfig('cp/auth',false)){
            $pest->setupAuth($this->app->getConfig('cp/auth/user'), $this->app->getConfig('cp/auth/pass'));
        }

        $model->addMethod('cp,search', function($m)use($pest){
            return $pest;
        });

        parent::setSource($model, $api_endpoint);
    }


    function search($model,$query,$args = []){
        $d = $this->d($model);
        $d['search'] = $query;
    }


    function _search($model,$query){
        if(!is_array($query)){
            $query = [$query];
        }

        $d = $this->d($model);

        return $this->_advancedCommand($model,'/_search',['query'=>$d['search'].$this->xml_encode($query)]);
    }

    /**
     * Some methods in Clusterpoint are specified through XML/POST data
     *
     * @param  [type] $model [description]
     * @param  [type] $url   [description]
     * @param  [type] $data  [description]
     * @return [type]        [description]
     */
    function _advancedCommand($model,$url,$data=null){
        if($model->debug) {
            echo '<font color="blue">'.
                htmlspecialchars("$url == (".json_encode($data).")").
                '</font>';
        }
        $method=$data?'post':'get';

        $cp = $model->cp();
        if($data){
            return $cp->$method($url,$data);
        }else{
            return $cp->$method($url);
        }
    }

    function xml_encode($mixed, $xml = null, $debug = false) {
        if(is_null($xml)){
           $xml = new SimpleXMLElement("<x></x>");
           $this->xml_encode($mixed, $xml);

           if($debug){
               $dom = dom_import_simplexml($xml)->ownerDocument;
               $dom->formatOutput = true;


               $s=explode("\n",$dom->saveXML(),2);

           }else{
               $s=explode("\n",$xml->asXML(),2);
           }

           $s = $s[1];
           return trim(substr($s,3,-5));
        }


        foreach($mixed as $key => $value) {

            if(is_array($value)) {
                if(is_numeric($key)){
                    //$subnode = $xml->addChild("item$key");
                    $this->xml_encode($value, $xml);
                } else {
                    $subnode = $xml->addChild("$key");
                    $this->xml_encode($value, $subnode);
                }
            } else {
                if(is_numeric($key)){
                    $xml[0]=$value;
                }else{
                    $xml->addChild("$key",htmlspecialchars("$value"));
                }
            }
        }
    }

    function loadByid($model, $id){
        // TODO: add conditions
        $data = $this->_search($model, array_merge($this->convertConditions($model->conditions),['id'=>$id]));
        $data = @$data['documents'][0];
        if(is_array($data)) {
            $model->id = $id;
            $model->data = $data;
        }
    }

    function convertConditions($model){
        $conditions = $model->conditions;


        $res = [];

        $res[0] = 'gowrav';
        foreach($conditions as $rule){
            list($field, $op, $value) = $rule;
            switch($op){
                case'=':
                case'>':
                case'<':
                case'>=':
                case'<=':
                case'!=':
                    $res[$field]=$op.$value;
            }
        }

        return $res;
    }


    function loadByConditions($model){

        $data = $this->_search($model, $this->convertConditions($model));
        $data = @$data['documents'][0];
        if(is_array($data)) {
            $model->id = $data[$model->id_field];
            $model->data = $data;
        }
    }


    function save($model, $id, $data){
        if(is_null($id)){
            $id = uniqid();
            $method = 'post';
        }else{
            $method = 'put';
        }

        $cp = $model->cp();
        $res = $cp->$method('/'.$id, $data);

        //$newid = $res['documents'][0];
        //if($newid != $id)throw $this->exception('Save failed');
        $model->id = $id;
    }

    function delete($model, $id) {
        $res = $model->cp()->delete('/'.$id);
        $model->unload();
    }

    function prefetchAll($model) {
        $data = $this->_search($model, array_merge($this->convertConditions($model)));
        return $data['documents'];
    }

    function loadCurrent($model,&$cursor) {
        if(!$cursor){
            return $model->unload();
        }
        $model->data = array_shift($cursor);
        $model->id = $model->data[$model->id_field];
    }

}
