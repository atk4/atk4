<?php
/***********************************************************
  ..

  Reference:
  http://agiletoolkit.org/doc/ref

 **ATK4*****************************************************
 This file is part of Agile Toolkit 4 
 http://agiletoolkit.org

 (c) 2008-2011 Agile Technologies Ireland Limited
 Distributed under Affero General Public License v3

 If you are using this file in YOUR web software, you
 must make your make source code for YOUR web software
 public.

 See LICENSE.txt for more information

 You can obtain non-public copy of Agile Toolkit 4 at
 http://agiletoolkit.org/commercial

 *****************************************************ATK4**/
/*
 * This class is similar to BasicAuth, but it will allow you to set up a query used
 * for checking username and password. Here is how this class must be used. Place
 * this code inside Api::init().
 *
 * $auth=$this->add('SQLAuth');
 * $auth->setSource('users','username','password');
 * $auth->check();
 *
 *
 * If you are willing to specify more complicated check, you can adjust $auth->dq
 * before calling check, such as:
 *
 * $auth->dq->where('active','Y');
 *
 * If you are willing to store passwords encrypted in the database you may call one of:
 *
 * $auth->usePasswordEncryption('md5');
 * $auth->usePasswordEncryption('sha1');
 * $auth->usePasswordEncryption('sha256/salt');
 *
 * To use your own encrpytion redefine this function:
 *
 *   function encryptPassword($password,$salt){
 *       return str_rot13($password);
 *   }
 *
 *
 * Loading additional data
 *
 * before check() do this
 *   $auth->dq->field('full_name,email');
 *
 *
 * If user is authenticated successfuly, you'll be able to access fields through
 *   $auth->get('full_name');
 *
 * or
 *
 *   $this->api->auth->get('full_name');
 *
 */

class SQLAuth extends BasicAuth {


	function init(){
		parent::init();
		$this->dq=$this->api->db->dsql();
	}
	function setSource($table,$login_field,$password_field){
		$this->debug("using source for SQLAuth: table=$table, login_field=$login_field, password_field=$password_field");
		$this->dq->table($table);
		$this->dq->field($password_field);
		$this->login_field=$login_field;
		$this->password_field=$password_field;
		return $this->dq;
	}
	function addInfo($key,$val=null){
		if($key==$this->password_field)return $this;        // skip password field
		return parent::addInfo($key,$val);
	}
	function verifyCredintials($login,$password){
		$this->debug("Verifying credintals for $login / $password");

		$q=clone $this->dq; // in case we are called several times
		$q->where($this->login_field,$login);
		$data=$q->do_getHash();
		// If passwords are matched we will record some information
		$this->debug("comparing with ".$data[$this->password_field]);
		if($data && $data[$this->password_field]==$password){
			unset($data[$this->password_field]);    // do not store password in session
			$this->addInfo($data);
			return true;
		}
		return false;
	}
}
