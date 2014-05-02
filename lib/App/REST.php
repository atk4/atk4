<?php
class App_REST extends App_CLI {

    public $page;
    function init(){
        parent::init();
        try {
            // Extra 24-hour protection
            parent::init();

            $this->add('Logger');
            $this->add('Controller_PageManager')
                ->parseRequestedURL();

            list($this->version,$junk)=explode('_',$this->page,2);
            $this->pathfinder->base_location->defineContents(['endpoint'=>'endpoint']);

        } catch (Exception $e) {
            $this->caughtException($e);
        }
    }
    function main(){
        try {
            $file = $this->api->locatePath('endpoint', str_replace('_','/',$this->page) . '.php');

            include_once($file);

            $this->pm->base_path = '/';

            $class = "endpoint_" . $this->page;
            $this->endpoint = new $class();
            $this->endpoint->api = $this;

            $raw_post = file_get_contents("php://input");
            try {
                header('Content-type: application/json');
                switch($_SERVER['REQUEST_METHOD']){
                    case'GET':
                        if($mm=$_GET['method']){
                            echo json_encode($this->endpoint->$mm());
                        }else{
                            echo json_encode($this->endpoint->get());
                        }
                        exit;

                    case'POST':
                        echo json_encode($this->endpoint->save($_POST));
                        exit;
                    case'DELETE':
                        echo json_encode($this->endpoint->delete());
                        exit;
                }
            } catch (Exception $e) {
                header('HTTP/1.1 500 Internal Server Error');
                $error = array(
                    'error'=>$e->getMessage(),
                    'type'=>get_class($e),
                    'more_info'=>$e instanceof BaseException ? $e->more_info:null
                );
                echo json_encode($error);
                exit;
            }


        } catch (Exception $e) {
            $this->caughtException($e);
        }
    }
}
