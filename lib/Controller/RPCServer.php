<?php
/**
 * KISS implementation of JSON RPC 2.0 server.
 */
class Controller_RPCServer extends AbstractController {

    public $interface_prefix='interface_';

    public $default_exception='Exception_Api_Protocol';


    /**
     * Reads JSONRPC 2.0 compatible request and executes methods inside 
     * specified objects. All executable methods must be defined as:
     *
     *   function interface_test($arg1, $arg2, ..){
     *       ...
     *       return ..;
     *   }
     *
     * Your method may throw exception. You may also override RPCServer to
     * change how methods are being processed.
     *
     * If object you specify is instance of AbstractObject, then you'll have
     * proper access to API and other standard methods. If you specify string,
     * the class will be initalized instead (it will be prefixed with Controller_Api_)
     */

    function process($interface_object){
        $io = $this->api->normalizeClassName($interface_object, 'Contoller_Api');

        // Read input and decode
        if(!$this->debug){
            $input = $this->getInput();
            $reqs = $this->decodeInput($input);
        }else{
            $reqs = $this->getDebugInput();
        }

        // Handle all requests
        foreach($reqs as &$data){
            try {
                $data = $this->processInput($data);
            }catch (Exception $e){
                $data = $e;
            }
        }

        $result = $this->encodeResult($reqs);
        $this->sendOutput($result);

        exit;
    }
    function getInput(){
        return @file_get_contents('php://input');
    }
    function decodeInput($input){
        $input = json_decode($input,true);
        return is_array($input)?$input:array($input);
    }

}
