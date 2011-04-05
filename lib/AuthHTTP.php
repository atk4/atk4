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
class AuthHTTP extends AbstractController{
	/*
	 * This class will add authentication to your web application. All you need to do is:
	 * $this->api->add('AuthWeb');
	 */
	public $api;
	public $owner;
	public $dq;
	public $auth_data;
	private $name_field;
	private $pwd_field;

	function init(){
		$this->api->addHook('pre-exec',array($this,'authenticate'));
	}
	function setSource($table,$login='login',$password='password'){
		$this->name_field = $login;
		$this->pwd_field = $password;
		$this->dq=$this->api->db->dsql()
			->table($table)
			->field($this->name_field)
			->field($this->pwd_field);
		return $this;
	}
	function authenticate(){
		if(!isset($_SERVER['PHP_AUTH_USER'])||(!isset($_SERVER['PHP_AUTH_PW']))){
			header('WWW-Authenticate: Basic realm="Private"');
			header('HTTP/1.0 401 Unauthorized');
		}else{
			//checking user
			$this->auth_data = $this->dq
				->where($this->name_field,$_SERVER['PHP_AUTH_USER'])
				->do_getHash();
			echo "!";
			$this->authenticated = $this->auth_data[$this->pwd_field] == $_SERVER['PHP_AUTH_PW'];
			unset($this->auth_data[$this->pwd_field]);
		}
		if(!$this->authenticated)throw new BaseException('Authorization Required');
	}
}
