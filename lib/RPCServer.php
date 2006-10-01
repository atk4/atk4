<?
class RPCServer extends AbstractController {
    /*
     * If you want your API to receive RPC calls, you have to initialize this class
     * and set handler class and key. Everything else will be performed automatically.
     */

    var $handler;
    var $security_key=null;

	private $allowed_ip = array();

    function setSecurityKey($key){
        $this->security_key=$key;
        return $this;
    }
    function setHandler($class_name){
        try{
	    	if (count($this->allowed_ip)) {
	    		if (!in_array($_SERVER['REMOTE_ADDR'],$this->allowed_ip))
	    			throw new Exception('Your IP not in allowed list');
	    	}
        	
            if(is_object($class_name)){
                $this->handler=$class_name;
            }else{
                $this->handler=$this->add($class_name);
            }
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
        }catch(UserException $e){
            echo 'AMRPC'.serialize($e);
        }
    }
    function setAllowedIP($list_of_ips = array()) {
    	$this->allowed_ip = $list_of_ips;
    }
    
}
