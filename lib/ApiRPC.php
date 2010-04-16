<?php
/*
 * WARNING:
 *
 * This class was created before RPCServer.php. Now it's separated and this class only
 * works as a wrapper. This separation was done so that you can use your own API and
 * still be able to handle RPC.
 *
 * See RPCServer class
 */


class ApiRPC extends ApiCLI {

	var $rpc_server;
	function init(){
		parent::init();
		$this->rpc_server = $this->add('RPCServer');
	}
	function setSecurityKey($key){
		$this->rpc_server->setSecurityKey($key);
		return $this;
	}
	function setHandler($class_name){
		$this->rpc_server->setHandler($class_name);
		return $this;
	}
}
