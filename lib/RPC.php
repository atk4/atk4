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

class RPC extends AbstractController {
	/*
	 * RPC class implements remote method call. It's very similar to
	 * XML-RPC, but it does not use XML, but uses serialize. Also it
	 * will work perfectly with AModules3
	 *
	 * You must use ApiRPC on the other side of the request
	 *
	 *
	 * After you initialize object, you may play with $this->ch to customize curl
	 * request options.
	 */
	var $destination_url;    // where requests will be sent
	var $security_key=null;
	var $ch;

	function setURL($url){
		$this->destination_url=$url;
		curl_setopt($this->ch, CURLOPT_URL, $this->destination_url);
		return $this;
	}
	function setSecurityKey($key){
		$this->security_key=$key;
		return $this;
	}

	function init(){
		parent::init();
		$this->ch=curl_init();
	}

	function __call($method,$arguments){
		if($this->security_key){
			// if security key is specified there will be 3 elements in top-array
			// where 3rd will contain checksum

			$data = serialize(
					array(
						$method,
						$arguments,
						md5(
							$s=serialize(
								array(
									$method,
									$arguments,
									$this->security_key
									)
								)
							)
						)
					);
		}else{
			$data = serialize(array($method,$arguments));
		}

		curl_setopt($this->ch, CURLOPT_POST, 1);
		curl_setopt($this->ch, CURLOPT_USERAGENT, "SERWEB full_access version 0.1");
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, "data=".base64_encode($data));
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0); // need these if we don't have cert
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($this->ch, CURLOPT_FAILONERROR, 1);
		curl_setopt($this->ch, CURLOPT_TIMEOUT, 15);
		$response = curl_exec ($this->ch);

		if(!$response){
			throw new RPCException("CURL error ('.$this->destination_url.'): ".curl_error($this->ch));
		}

		curl_close ($this->ch);
		$this->ch=curl_init();  // in case they will want to send another request...
		curl_setopt($this->ch, CURLOPT_URL, $this->destination_url);

		if($response==serialize(false)){
			// we won't be sure if it was false returned or if there was error, so we
			// unserialize it
			return false;
		}
		// TODO - we need to ignore error here
		if(substr($response,0,6)=='ERRRPC'){
			$response=unserialize(substr($response,6));
			throw new RPCException($response['message'],$response['code'],$response['file'],$response['line']);
		}
		elseif(substr($response,0,5)!='AMRPC'){
			throw new RPCException("Fatal error on remote end: ".$response);
		}

		$response=unserialize(substr($response,5));

		/*if($response===false){
			// it was really an error
			throw new RPCException("Couldn't connect to handler URL");
		}*///I think it was not error. For example remote function could return false
		if($response instanceof Exception){
			throw $response;    // if exception was raised on other end - we just raise it again
								// mvs: this is old feature, stay here for comatiblility with scripts what
								// using old version of amodules RPC-code
		}
		return $response;
	}
}
