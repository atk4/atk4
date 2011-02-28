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
