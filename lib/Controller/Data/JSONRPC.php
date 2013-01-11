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
 * Implementation of Rest-full API connectivity using JSON. Define your 
 * model with the methods you wish and then set this controller. You 
 * need to specify URL as a table. When you perform interaction with 
 * your model, it will automatically communicate with the remote server.
 *
 * You can call custom methods through $controller->request($model,'method',arguments);
 * Those will not change the state of your model and will simply return 
 * decoded JSON back.
 *
 * NOTE: This is pretty awesome controller!
 */
class Controller_Data_JSONRPC extends Controller_Data {
    /* Sends Generic Request */

    public $last_request=null;

    function request($model,$method,$params=null,$id=undefined){
        // if method is array, then the batch is executed. It must be in 
        // format:  TODO: IMPLEMENT
        // array( 
        //  array('method'=>$method, 'params'=>$params),
        //  array('method'=>$method, 'params'=>$params),
        // )

        // Generate ID, to match with response
        if($id===undefined){
            $id=uniqid();
        }

        // Prepare Request
        $request=array();
        $request["jsonrpc"] = "2.0";
        $request["method"] = $method;

        if(!is_null($params))$request["params"] = $params;
        if(!is_null($id))$request['id']=$id;
        $request["ts"] = time();

        // Send Request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url=$model->_table[$this->name]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $this->last_request=$request;
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($request,'','&'));

        $result = curl_exec($ch);
        curl_close($ch);

        // TODO: check for errors

        $result=json_decode($result);


        if($result->id != $id){
            // ERROR
            //$e=$this->exception($result->error->message,$result->error->code);
        }

        // Convert error into exception
        if($result->error){
            $e=$this->exception($result->error->message,$result->error->code);
            if($result->error->data);
            $e->addMoreInfo('data',$result->error->data);
            throw $e;
        }

        // Decode Request
        return (array)$result->result;
    }
    function getBy($model,$field,$cond=undefined,$value=undefined){
    }
    function tryLoadBy($model,$field,$cond=undefined,$value=undefined){
    }
    function tryLoadAny($model){
    }
    function loadBy($model,$field,$cond=undefined,$value=undefined){
    }
    function tryLoad($model,$id){
    }
    function load($model,$id=null){
        $model->data=$this->request($model,'load',array($id));
        $model->dirty=array();
        $model->id=$id;
    }
    function save($model,$id=null,$data=array()){
        $model->data=$this->request($model,'save',array($id,$data));
    }
    function delete($model,$id=null){
        $this->request($model,'delete',array($id));
    }
    function deleteAll($model){
        $this->request($model,'deleteAll');
    }
    function getRows($model){
        $this->request($model,'getRows');
    }
    function setOrder($model,$field,$desc=false){
        $this->stickyRequest($model,'setOrder',array($field,$desc));
    }
    function setLimit($model,$count,$offset=0){
        $this->stickyRequest($model,'setLimit',array($field,$desc));
    }

    function rewind($model){
        if(isset($model->_table[$this->name.'/list'])){
            reset($model->_table[$this->name.'/list']);
        }else{
            $this->request($model,'getRows');
        }
        $this->log($model,"rewind");
        if($this->sh)return $sh->rewind($model);
    }
    function next($model){
        $this->log($model,"next");
        if($this->sh)return $sh->next($model);
    }
    function __call($method,$arg){
        $this->log($model,"$method");
        if($this->sh)return call_user_func_array(array($sh,$method),$arg);
    }
}
