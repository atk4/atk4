<?php
/***********************************************************
   ..

   Reference:
     http://atk4.com/doc/ref

 **ATK4*****************************************************
   This file is part of Agile Toolkit 4 
    http://www.atk4.com/
  
   (c) 2008-2011 Agile Technologies Ireland Limited
   Distributed under Affero General Public License v3
   
   If you are using this file in YOUR web software, you
   must make your make source code for YOUR web software
   public.

   See LICENSE.txt for more information

   You can obtain non-public copy of Agile Toolkit 4 at
    http://www.atk4.com/commercial/ 

 *****************************************************ATK4**/
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
					$this->_error_and_exit('Your IP not in allowed list',0,__FILE__,__LINE__);

			}

			if(is_object($class_name)){
				$this->handler=$class_name;
			}else{
				$this->handler=$this->add($class_name);
			}
			if(!isset($_POST['data']))
				$this->_error_and_exit('No "data" specified in POST',0,__FILE__,__LINE__);

			@$data = unserialize(base64_decode($_POST['data']));
			if($data===false || !is_array($data))
				$this->_error_and_exit('Data was received, but was corrupted. POST: '.print_r($_POST,true),0,__FILE__,__LINE__);

			if($this->security_key && count($data)!=3){
				$this->_error_and_exit('This handler requires security key',0,__FILE__,__LINE__);
			}

			if(!$this->security_key && count($data)==3){
				$this->_error_and_exit('Key was specified but is not required',0,__FILE__,__LINE__);
			}

			if(count($data)==3){
				list($method,$args,$checksum)=$data;

				$rechecksum=md5(serialize(array($method,$args,$this->security_key)));
				if($rechecksum!=$checksum)
					$this->_error_and_exit('Specified security key was not correct',0,__FILE__,__LINE__);
			}else{
				list($method,$args)=$data;
			}

			$this->api->debug('RPC call, method: '.$method.'. Parameters: '.print_r($args,true),__FILE__,__LINE__);

			$result = call_user_func_array(array($this->handler,$method),$args);

			$this->api->debug('Successfully executed, result: '.print_r($result,true),__FILE__,__LINE__);

			echo 'AMRPC'.serialize($result);
		}
		catch(BaseException $e){
			// safe send any of type exceptions (remove nested objects from exception)
			$this->_error_and_exit($e->getMessage(),$e->getCode(),$e->getMyFile(),$e->getMyLine());
		}
		catch(Exception $e){
			// safe send any of type exceptions (remove nested objects from exception)
			$this->_error_and_exit($e->getMessage(),$e->getCode(),$e->getFile(),$e->getLine());

			//echo 'AMRPC'.preg_replace('/;O:\d+:".+?"/smi',';a',serialize($e));
		}
	}

	private function _error_and_exit($message,$code,$file,$line) {
		$this->api->debug('Raised exception during execute: '.print_r(array(
			'message'=>$message,'code'=>$code,'file'=>$file,'line'=>$line
										),true),__FILE__,__LINE__);

		echo 'ERRRPC'.serialize(array(
				'message'=>$message,
				'code'=>$code,
				'file'=>$file,
				'line'=>$line));
		exit;
	}

	function setAllowedIP($list_of_ips = array()) {
		$this->allowed_ip = $list_of_ips;
	}

}
