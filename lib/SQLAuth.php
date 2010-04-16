<?php
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
 *
 * To use your own encrpytion redefine this function:
 *
 *   function encryptPassword($password){
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
	private $login_field;
	private $password_field;


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
		$this->dq->where($this->login_field,$login);
		$data=$this->dq->do_getHash();
		// If passwords are matched we will record some information
		if($data && $data[$this->password_field]==$password){
			$this->addInfo($data);
			return true;
		}
		return false;
	}
}
