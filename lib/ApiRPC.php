<?
class ApiRPC extends ApiCLI {
    /*
     * In order to use this API, all you have to do is to call setHandler
     * right after initialization. All requests received over the web must
     * be sent from RPC class.
     */

    var $handler;
    var $security_key=null;

    function setHandler($class_name){
        try{
            $this->handler=$this->add($class_name);
            if(!isset($_POST['data']))
                throw new RPCException('No "data" specified in POST');

            @$data = unserialize($_POST['data']);
            if($data===false || !is_array($data))
                throw new RPCException('Data was received, but was corrupted');

            if($this->security_key && count($data)!=3){
                throw new RPCException('This handler requires security key');
            }

            if(count($data)==3){
                list($method,$args,$checksum)=$data;

                $rechecksum=serialize(array($method,$args,$this->security_key));
                if($rechecksum!=$checksum)
                    throw new RPCException('Specified security key was not correct');
            }else{
                list($method,$args)=$data;
            }

            $result = call_user_func(array($this->handler,$method),$args);

            echo serialize($result);
        }catch(BaseException $e){
            echo serialize($e);
        }
    }
}
