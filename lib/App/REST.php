<?php
/**
 * REST Server implementation for Agile Toolkit
 *
 * This class takes advantage of the tight integration for Agile Toolkit
 * to enhance and make it super simple to create an awesome API for
 * your existing application.
 */
class App_REST extends App_CLI {

    public $doc_page='app/rest';

    public $page;
    function init(){
        parent::init();
        try {
            // Extra 24-hour protection
            parent::init();

            $this->add('Logger');
            $this->add('Controller_PageManager')
                ->parseRequestedURL();

            // It's recommended that you use versioning inside your API,
            // for example http://api.example.com/v1/user
            //
            // This way version is accessible anywhere from $this->app->version
            list($this->version,$junk)=explode('_',$this->page,2);

            // Add-ons may define additional endpoints for your API, but
            // you must activate them explicitly.
            $this->pathfinder->base_location->defineContents(['endpoint'=>'endpoint']);

        } catch (Exception $e) {
            $this->caughtException($e);
        }
    }
    function encodeOutput($data){
        switch($_GET['format']){
            case 'xml':
                throw $this->excception('only JSON format is supported');
            case 'json':
            default:
                header('Content-type: application/json');
                echo json_encode($data);
                exit;
        }
    }
    function main(){
        try {
            $file = $this->api->locatePath('endpoint', str_replace('_','/',$this->page) . '.php');
            include_once($file);

            $this->pm->base_path = '/';

            $class = "endpoint_" . $this->page;
            $this->endpoint = new $class();
            $this->endpoint->app = $this;
            $this->endpoint->api = $this; // compatibility

            $raw_post = file_get_contents("php://input");
            try {

                $method=strtolower($_SERVER['REQUEST_METHOD']);
                if($_GET['method'])$method.='_'.$_GET['method'];
                if(!$this->endpoint->methodExists($method)){
                    throw $this->exception('Method does not exist for this endpoint')
                        ->addMoreInfo('method',$method)
                        ->addMoreInfo('endpoint',$this->endpoint)
                        ;
                }

                // Perform the desired action
                $this->encodeOutput($this->endpoint->$method($_POST));

            } catch (Exception $e) {
                header('HTTP/1.1 500 Internal Server Error');
                var_Dump($e->getMessage());
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
