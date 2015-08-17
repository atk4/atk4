<?php
class Controller_Data_Clusterpoint extends Controller_Data {
    public $supportConditions = true;
    public $supportLimit = true;
    public $supportOrder = true;
    public $supportRef = true;
    public $supportOperators = array(); //'=' => true, '>' => true, '>=' => true, '<=' => true, '<' => true, '!=' => true, 'like' => true);

    public $transport_class = 'PestJSON';


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

        $model->addMethod('cp', function($m)use($pest){
            return $pest;
        });

        parent::setSource($model, $api_endpoint);
    }

    function _search($model,$query){
        if(!is_array($query)){
            $query = [$query];
        }

        return $this->_advancedCommand($model,'/_search',['query'=>$this->xml_encode($query)]);
    }

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

    function xml_encode($mixed, $domElement=null, $DOMDocument=null) {
        if (is_null($DOMDocument)) {
            $DOMDocument =new DOMDocument;
            $DOMDocument->formatOutput = true;
            $this->xml_encode($mixed, $DOMDocument, $DOMDocument);
            $str = $DOMDocument->saveXML();
            list(,$str) = explode("\n",$str,2);
            return $str;
        } else {
            if (is_array($mixed)) {
                foreach ($mixed as $index => $mixedElement) {
                    if (is_int($index)) {
                        if ($index === 0) {
                            $node = $domElement;
                        } else {
                            $node = $DOMDocument->createElement($domElement->tagName);
                            $domElement->parentNode->appendChild($node);
                        }
                    } else {
                        $plural = $DOMDocument->createElement($index);
                        $domElement->appendChild($plural);
                        $node = $plural;
                        if (!(rtrim($index, 's') === $index)) {
                            $singular = $DOMDocument->createElement(rtrim($index, 's'));
                            $plural->appendChild($singular);
                            $node = $singular;
                        }
                    }

                    return $this->xml_encode($mixedElement, $node, $DOMDocument);
                }
            } else {
                $mixed = is_bool($mixed) ? ($mixed ? 'true' : 'false') : $mixed;
                $domElement->appendChild($DOMDocument->createTextNode($mixed));
            }
        }
    }

    function loadByid($model, $id){
        // TODO: add conditions
        $data = $this->_search($model, ['id'=>$id]);
        $data = @$data['documents'][0];
        if(is_array($data)) {
            $model->id = $id;
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
        $data = $this->_search($model, '*');
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
