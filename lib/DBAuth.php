<?php
/**
 * Improved version of BasicAuth.
 * Login/password retrieved from DB table
 * Functionality of password recovery and new user registration is included.
 *
 * Passwords could be stored as plain text or sha1 hash. By default they are encrypted.
 * If you do not want them to be encrypted - use Auth::setEncrypted(false)
 *
 * If you want to use password recovery feature:
 * - create a fake page for password recovery (it is needed!)
 * - add a link to password recovery page: Auth::addPwdRecoveryLink()
 * - specify a page name to password recovery in your config.php ($config['auth']['pwd_recovery']['page'])
 * - specify a table name which will be used for password recovery process ($config['auth']['pwd_recovery']['table'])
 *   This table structure is:
 *   CREATE TABLE pwd_recovery_request (
 *     id int(11) unsigned NOT NULL auto_increment,
 *     user_id int(11) unsigned NOT NULL default '0',
 *     email varchar(32) NOT NULL default '',
 *     expire datetime default NULL,
 *     changed int(1) default '0',
 *     changed_dts datetime default NULL,
 *     PRIMARY KEY  (id)
 *     ) ENGINE=MyISAM
 * - specify a period in minutes in which link to recovery will be actual ($config['auth']['pwd_recovery']['timeout'])
 *
 * If you want to use register feature:
 * - create a page with the user registration data
 * - add a link to login form: Auth::addRegisterLink()
 * - specify register page in your config.php
 *
 * Created on 04.07.2006 by *Camper* (camper@adevel.com)
 */
class DBAuth extends BasicAuth{
	public $dq;
	protected $table;
	public $email_field;
	public $pass_field;
	public $name_field;
	protected $signup = null;
	protected $pwd_recovery = null;
	protected $signup_processor = null;

	function processLogin(){
		/**
		 * Draws page which set in config.php
		 * Page should contain login form or other stuff which could be used as login object
		 */
		/*$this->debug('Creating page');
		$p=$this->api->add('Page');
		$p->template->loadTemplate('empty');
		$
		$p->add('page_'.$this->api->getConfig('auth/login_page'),null,'Content');
		$this->form=$p->form;
		$p->downCall('render');
		$this->debug('Rendering login page');
		echo $p->template->render();
		exit;*/
		parent::processLogin();
	}
	//function check(){
	//	if($this->doRecovery===true)$this->processRecovery();
	//	else parent::check();
	//}

	function getPwd(){
		return $this->pwd_recovery;
	}


	function setEncrypted($secure=true){
		if($secure)$this->usePasswordEncryption('sha1');
		return $this;
	}
	function setPassword($password){
		return false;
	}
	function setSource($table,$login='login',$password='password',$email='email'){
		$this->table=$table;
		$this->name_field = $login;
		$this->pass_field = $password;
		$this->email_field = $email;
		$this->dq=$this->api->db->dsql()
			->table($table)
			->field($this->name_field)
			->field($this->pass_field)
		;
		return $this;
	}
	function verifyCredintials($user,$password){
		/**
		 * Verifying user and password. Password in params should be an SHA1 hash in ALL cases
		 */
		unset($this->dq->args['where']);
		$password=$this->encryptPassword($password);
		$data=$this->dq->where($this->name_field, $user)->do_getHash();
		$result=(sizeof($data)>0&&($data[$this->pass_field]==$password||$this->encryptPassword($data[$this->pass_field])==$password));
		if($result)$this->addInfo($data);
		return $result;
	}
	function encrypt($str){
		return $this->encryptPassword($str);
	}
	function login($username,$memorize=false){
		// in order to store proper data performing verification
		$password=$this->api->db->dsql()->table($this->table)
			->where($this->name_field,$username)
			->field($this->pass_field)
			->do_getOne();
		if($this->verifyCredintials($username,$password)){
			$this->memorize('info',$this->info);
			$this->loggedIn($username,$password,$memorize);
		}
	}
	function addSignupProcessor($class_name='Auth_SignupProcessor'){
		$this->signup_processor=$this->add($class_name);
		return $this;
	}
	function addPasswordRecovery($class_name='Auth_PasswordRecovery'){
		$this->pwd_recovery=$this->add($class_name);
		return $this;
	}
	function getServerName($full=false){
		/**
		 * Static method
		 * Returns server name by these rules:
		 * 1) if there is $_SERVER['HTTP_X_FORWARDED_HOST'] set - takes first server from the line
		 * 2) else returns $_SERVER['HTTP_HOST']
		 */
		$server='';
		if($_SERVER['HTTP_X_FORWARDED_HOST']){
			$server=$_SERVER['HTTP_X_FORWARDED_HOST'];
			$server=strpos(',',$server)==0?$server:split(',',$server);
			if(is_array($server))$server=$server[0];
		}
		if($server==''||strtolower($server)=='unknown')$server=$_SERVER['HTTP_HOST'];
		return ($_SERVER['HTTPS']?'https':'http').'://'.($full?$server:str_replace('www.', '', $server));
	}
	function createForm($frame,$login_tag='Content'){
		$form=$frame->add('Auth_Form',null,$login_tag);
		return $form;
	}
}
