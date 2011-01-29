<?php
/*
 * Alternative version of authorization class. This works very similar to regular Auth class,
 *
 * The most simpliest way to use this class is:
 * $this->add('MultiuserAuth')->login();
 *
 * This will authenticate against default table, would display login form if necessary. For
 * non-authenticated users execution will not go further, so the rest of your code is protected.
 *
 * A complex way to use this form would be:
 * $a=$this->add('MultiuserAuth')
 * if(!$a->isLoggedIn()){       // if we are not logged in already
 *   $a->setSource('person','login','password')->encryptPasswordMD5()
 *   $f=$this->getLoginForm();
 *   $f->add('Dropdown','domain')->setValueList(array('example.com','example.net'));
 *   if($a->getCredentialsFromForm($f)){
 *     $a->dq->where('domain',$f->get('domain'));
 *
 *   if(!$a->tryLogin()){
 *     $this->api->redirect('/');
 *   }
 * }
 */
class MultiuserAuth extends BasicAuth {

	private $user_field;
	private $password_field;

	//TODO Check this method
	function processLogin($return){
		// if($ ---> add comment by MVS, not completed method???
		$this->getCredentials();

	}
	function filterCredentials(&$source){
		return hash_filter($source,array('username','password'));
	}

	function getCredentials(){
		if(empty($this->credentials))$this->credentials=$this->filterCredentials($_POST);
		if(empty($this->credentials))$this->credentials=$this->filterCredentials($_GET);
		if(empty($this->credentials))$this->credentials=$this->filterCredentials($_COOKIE);
	}
	function getUser(){
		return $this->info[$this->user_field];
	}
	function setSource($table='user',$user_field='user',$password_field='password'){
		$this->user_field=$user_field;
		$this->password_field=$password_field;
		$this->dq=$this->api->dq->dsql()
			->table($table)
			->field('*')
			;
		return $this;
	}
	function encryptPasswordMD5(){
		return $this->customEncryptor('$password=md5($password)');
	}
	function encryptPasswordSHA(){
		return $this->customEncryptor('$password=sha($password)');
	}
}
