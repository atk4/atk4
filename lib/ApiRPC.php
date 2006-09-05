<?
class ApiRPC extends ApiCLI {
    /*
     * In order to use this API, all you have to do is to call setHandler
     * right after initialization. All requests received over the web must
     * be sent from RPC class.
     */

    var $handler;
    var $security_key=null;

    function setSecurityKey($key){
        $this->security_key=$key;
        return $this;
    }
    function setHandler($class_name){
        try{
            $this->handler=$this->add($class_name);
            if(!isset($_POST['data']))
                throw new RPCException('No "data" specified in POST');

            @$data = unserialize(stripslashes($_POST['data']));
            if($data===false || !is_array($data))
                throw new RPCException('Data was received, but was corrupted');

            if($this->security_key && count($data)!=3){
                throw new RPCException('This handler requires security key');
            }

            if(!$this->security_key && count($data)==3){
                throw new RPCException('Key was specified but is not required');
            }

            if(count($data)==3){
                list($method,$args,$checksum)=$data;

                $rechecksum=md5(serialize(array($method,$args,$this->security_key)));
                if($rechecksum!=$checksum)
                    throw new RPCException('Specified security key was not correct');
            }else{
                list($method,$args)=$data;
            }

            $result = call_user_func_array(array($this->handler,$method),$args);

            echo 'AMRPC'.serialize($result);
        }catch(BaseException $e){
            echo 'AMRPC'.serialize($e);
        }
    }
}
