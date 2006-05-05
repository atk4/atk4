<?php
/*
 * Created on 06.03.2006 by *Camper*
 */
class RegisterLink extends Text{
	function init(){
		parent::init();
		$this->set('<tr><td align=left><a href='.
			$this->api->getDestinationURL($this->api->getConfig('auth/login_page'),array('register'=>1)).'>Register</a></td><td></td></tr>');
	}
}
class LostPassword extends Text{
	function init(){
		parent::init();
		$this->set('<tr><td align=left><a href='.
			$this->api->getDestinationURL($this->api->getConfig('auth/login_page'), array('restore_password'=>1)).
			'>Lost password</a></td><td></td></tr>');
	}
}
/**
 * login form
 */
class LoginForm extends Form{
	function init(){
		parent::init();
		$this
			->addField('line', 'name', 'Your login')
			->addField('password', 'pass', 'Password')
			->addField('checkbox', 'remember', 'Remember me')
	
			->addSubmit('Login')
		;
		$this->elements['name']->addHook('validate', array($this, 'validateName'));
		$this->elements['pass']->addHook('validate', array($this, 'validatePass'));
		//storing field names to have them checked by api
		$this->api->memorize('authname', $this->elements['name']->name);
		$this->api->memorize('authpass', $this->elements['pass']->name);
		$this->api->memorize('remember', $this->elements['remember']->name);
	}
	function validateName(){
		if($this->get('name') == ''){
			$this->elements['name']->displayFieldError('Your name should NOT be empty!');
			return false;
		}
		return true;
	}
	function validatePass(){
		if($this->get('pass') == ''||strlen($this->get('pass')) < 1){
			$this->elements['pass']->displayFieldError('Your password should NOT be empty!');
			return false;
		}
		return true;
	}
}
/**
 * This class performs an authentication and stores user login in the session
 * If there is no user info in the session - it is displaying a login form
 */
class Auth extends AbstractController{
    public $dq;
    private $name_field;
    private $pass_field;
    private $form;
    public $auth_data;
    private $secure = true;
    private $can_register = false;
    private $show_lost_password = false;

    function init(){
    	if(!$this->api->recall('requested_page', null))$this->api->memorize('requested_page', $this->api->page);
    	//if user is not authorized, redirect him to the index
		$this->auth_data = $this->api->recall('auth_data');
        $this->api->addHook('pre-exec',array($this,'checkRestore'), 1);
    	if(isset($_REQUEST['rp'])||strpos(strtolower($_REQUEST['submit']), 'formchangepassword')!==false){
    		//user clicked a link in e-mail
    		return;
    	}
    	/*if($this->api->page!=$this->api->getConfig('auth/login_page', 'Index')&&
    	!$this->auth_data['authenticated']){
    		$this->doLogin();
    		//$this->api->redirect($this->api->getConfig('auth/login_page', 'Index'));
    	}*/
        $this->api->addHook('pre-exec',array($this,'doLogin'));
    }
    function checkRestore(){
    	if(isset($_REQUEST['rp'])||strpos(strtolower($_REQUEST['submit']), 'formchangepassword')!==false){
    		//user clicked a link in e-mail
    		$this->api->stickyGET('rp');
    		$this->api->stickyGET('key');
    		$this->changePassword($_REQUEST['rp'], $_REQUEST['key']);
    	}
    	//checking if there is a password restore request
    	if(isset($_REQUEST['restore_password'])||strpos(strtolower($_REQUEST['submit']), 'formsendlink')!==false){
    		$this->sendLink();
    	}
    }
    function changePassword($id, $key){
   		$row=$this->api->db->getHash("select * from ".$this->api->getConfig('auth/rp_table').
    			" where id=$id and changed=0");
    	//looking for the key in DB and checking expiration dts
   		$db_key=sha1($row['id'].$row['email'].strtotime($row['expire']));
   		$can_change=$db_key==$key&&strtotime($row['expire'])>time();
   	    $p=$this->add('Page');
       	$p->template->loadTemplate('empty');
		$p->template->trySet('page_title', 'Change password');
    	if($can_change){
    		//displaying changepass page
    		$username=$this->api->db->getOne("select $this->name_field from ".
    			$this->dq->args['table']." where id=".$row['user_id']);
	        // Initialize an empty page
    		
			$form=$p->frame('Content', "Change password for $username")->add('FormChangePassword', null, 'content');
			$this->api->memorize('auth_table', $this->dq->args['table']);
			$this->api->memorize('auth_pass_field', $this->pass_field);
			$this->api->memorize('auth_secure', $this->secure?1:0);
    	}else{
    		//denial page
			unset($this->api->sticky_get_arguments['rp']);
			unset($this->api->sticky_get_arguments['key']);
    		$p->frame('Content', 'Request error')->add('Text', null, 'content')
    			->set("Sorry, this page is not valid. Activation period might have been expired." .
    			" <a href=".
				$this->api->getDestinationURL($this->api->getConfig('auth/login_page'), array('restore_password'=>1)).
				">Click here</a> if You want to repeat Your request.");
			$this->addBackLink($p);
    	}
		$p->downCall('render');
    	echo $p->template->render();
    	exit;
    }
    function setSource($table,$login='login',$password='password'){
    	$this->name_field = $login;
    	$this->pass_field = $password;
        $this->dq=$this->api->db->dsql()
            ->table($table)
            ->field($this->name_field)
            ->field($this->pass_field);
        return $this;
    }
	function sec($str){
		return $this->secure?sha1($str):$str;
	}
	function getNameField(){
		return $this->name_field;
	}
	function doLogin($plogin = null, $ppassword = null){
		//do nothing if we are in password restore request
    	if(isset($_REQUEST['restore_password'])||strpos(strtolower($_REQUEST['submit']), 'formsendlink')!==false)return;
		//getting stored auth_data
		if(!$this->auth_data['authenticated']){
			$remember = false;
			//checking if there are loginform's fields are stored
			if($this->api->recall('authname', false)){
				if(isset($_POST[$this->api->recall('authname')])){
					//getting from POST
					$user = $_POST[$this->api->recall('authname')];
					$pass = $this->sec($_POST[$this->api->recall('authpass')]);
					$remember = isset($_POST[$this->api->recall('remember')]);
				}
			}elseif(isset($plogin)){
				$user = $plogin;
				$pass = $ppassword;
			}elseif(isset($_COOKIE['username'])){
				//getting username from cookies
				$user = $_COOKIE['username'];
				$pass = $_COOKIE['password'];
				$remember = true;
			}else{
			}
			/*$user = $this->auth_data[$this->name_field];
			$pass = $this->auth_data[$this->pass_field];*/
			if(isset($user)&&isset($pass)){
   		       	$this->auth_data = $this->dq->where($this->name_field,$user)->do_getHash();
				$this->auth_data['authenticated'] = $this->auth_data[$this->pass_field] == $pass;
			}
			if(!$this->auth_data['authenticated']){
				$this->showLoginForm(isset($user));
			}else{
				$this->api->memorize('auth_data', $this->auth_data);
				//storing in cookies for a month
				if($remember){
					setcookie('username', $this->auth_data[$this->name_field], time()+60*60*24*30, 
						$this->getPath(), $this->getDomain());
					setcookie('password', $this->auth_data[$this->pass_field], time()+60*60*24*30, 
						$this->getPath(), $this->getDomain());
				}
				$this->onLogin();
			}
		}
	}
	function getDomain(){
		/**
		 * Returns a second level domain for cookies
		 */
		$domain=explode('.', $_SERVER['SERVER_NAME']);
		return $domain[sizeof($domain)-2].'.'.$domain[sizeof($domain)-1];
	}
	function getPath(){
		/**
		 * returns a path for cookies
		 */
		$path=dirname($_SERVER['PHP_SELF']);
		return $path;
	}
	function setNoCrypt(){
		$this->secure = false;
		return $this;
	}
	function logout(){
		$this->api->forget('auth_data');
		setcookie('username', '', time()-1);
		setcookie('password', '', time()-1);
		$this->api->redirect($this->api->getConfig('auth/login_page', 'Index'));
	}
	function onLogin(){
		$page=$this->api->recall('requested_page', 'Index');
		$this->api->forget('requested_page');
		$this->api->redirect($page);
	}
	function addRegisterLink(){
		$this->can_register = true;
		return $this;
	}
	function addLostPassword(){
		$this->show_lost_password=true;
		return $this;
	}
	function showLoginForm($retry=false){
        // Initialize an empty page
        $p=$this->add('Page');
        $p->template->loadTemplate('empty');
		$p->template->trySet('page_title', 'Login');
		
		if($retry)$p->add('Text', null, 'Content')
			->set("<div align=center><font color=red>Your login is incorrect</font></div>");

		$form = $p->frame('Content', 'Login')->add('LoginForm', null, 'content');
		//adding a link to the registration page
		$form->addSeparator();
		if($this->can_register){
			$form->add('RegisterLink', null, 'form_body');
		}
		if($this->show_lost_password){
			$form->add('LostPassword', null, 'form_body');
		}
		$p->downCall('render');
		echo $p->template->render();
		exit;
	}
	function sendLink(){
   	    $p=$this->add('Page', 'restore');
       	$p->template->loadTemplate('empty');
		$p->template->trySet('page_title', 'Restore password');
		$form=$p->frame('Content', 'Restore password')->add('FormSendLink', null, 'content');
		$this->api->memorize('auth_table', $this->dq->args['table']);
		$this->api->memorize('auth_name_field', $this->name_field);
		$this->addBackLink($p);
		$p->downCall('render');
		echo $p->template->render();
		//exit;
	}
	function addBackLink($p){
		$p->add('Text', 'back', 'Content')
			->set("<div align=center><a href=".$this->api
			->getDestinationURL($this->api->getConfig('auth/login_page')).">Back to main page</a></div>");
	}
}
class FormSendLink extends Form{
	
	function init(){
		parent::init();
		$this
			->addComment('Enter a username You specified at registration')
			->addField('line', 'username', 'Your login')->setNotNull()
			
			//->addButton('Send')->submitForm($this)
			->addSubmit('Send')
		;
		if($this->isSubmitted())$this->processSubmit();
	}
	function processSubmit(){
		//finding a user in DB and sending him a email with a link
		$row=$this->api->db->getHash("select id, email from ".$this->api->recall('auth_table').
			" where ".$this->api->recall('auth_name_field')."='".$this->get('username')."'");
		$user_id=$row['id'];
		$email=$row['email'];
		if(!$email)throw new BaseException("User with a name you specified have not been found. Please try again");
		else{
			$this->sendEmail($user_id, $this->get('username'), $email);
			$this->owner->owner->add('Text', null, 'Content')->set("<div align=center>An e-mail with instruction " .
				"to restore password has been sent" .
				" to user '".$this->get('username')."'</div>");
		}
		$this->api->forget('auth_table');
		$this->api->forget('auth_name_field');
	}
	function sendEmail($user_id, $username, $address){
		//adding a DB record with a key to a change password page
		$table=DTP.$this->api->getConfig('auth/rp_table');
		$expire=time()+$this->api->getConfig('auth/rp_timeout', 15)*60;
		$this->api->db->query("insert into $table (user_id, email, expire) values($user_id, '$address', " .
				"'".date('Y-m-d H:i:s', $expire)."')");
		$id=mysql_insert_id();
		$server=$_SERVER['SERVER_NAME'];
		//combining a message
		$msg="This is $server password recovery subsystem.\n\nWe recieved the request " .
				" to change the password for the user '$username'. To change your password " .
				"click the link below. REMEMBER: this link is actual for a period of ".
				$this->api->getConfig('auth/rp_timeout', 15)." minutes only. If you do not change the password " .
				"during this period, you will have to make a new change request.\n\n".
				"http://".$_SERVER['SERVER_NAME'].dirname($_SERVER['PHP_SELF'])."/".
				$this->api->getDestinationURL(null, array('rp'=>$id, 'key'=>sha1($id.$address.$expire)));
		$subj="Password recovery";
		$from="noreply@$server";
		$headers = "From: $from \n";
		//$headers .= "Return-Path: <".$this->owner->settings['return_path'].">\n";
		//$headers .= "To: $to \n"; 
		$headers .= "MIME-Version: 1.0 \n"; 
		//$headers .= "Content-Type: text/plain; charset=KOI8-R; format=flowed Content-Transfer-Encoding: 8bit " .
		//		"\n"; 
		$headers .= "X-Mailer: PHP script "; 
		
		mail($address, $subj, $msg, $headers);
	}
}
class FormChangePassword extends Form{

	function init(){
		parent::init();
		$this
			->addField('password', 'password', 'Enter new password')
			->addField('password', 'password2', 'Confirm new password')
			->addField('checkbox', 'send', 'Send me new password by e-mail')
			
			->addSubmit('Change')
		;
		$this->addField('hidden', 'rp_id');
		if(isset($_REQUEST['rp']))$this->set('rp_id', $_REQUEST['rp']);
		$this->elements['password']->addHook('validate', array($this, 'validatePassword'));
		$this->elements['password2']->addHook('validate', array($this, 'validatePassword2'));
		if($this->isSubmitted())$this->processSubmit();
	}
	function validatePassword(){
		if(strlen($this->get('password'))<$this->api->getConfig('auth/password_len', 1)){
			$this->elements['password']->displayFieldError('Password is too short');
			return false;
		}
		return true;
	}
	function validatePassword2(){
		if($this->get('password')!=$this->get('password2')){
			$this->elements['password2']->displayFieldError('Password differs from its confirmation!');
			return false;
		}
		return true;
	}
	function sec($str){
		return $this->api->recall('auth_secure')==1?sha1($str):$str;
	}
	function sendPassword($user_id, $password){
		$server=$_SERVER['SERVER_NAME'];
		$address=$this->api->db->getOne("select email from ".$this->api->recall('auth_table').
			" where id=".$user_id);
		//combining a message
		$msg="This is $server password recovery subsystem.\n\nHere is your new password : " .
			$password;
		$subj="Your password has changed";
		$from="noreply@$server";
		$headers = "From: $from \n";
		//$headers .= "Return-Path: <".$this->owner->settings['return_path'].">\n";
		//$headers .= "To: $to \n"; 
		$headers .= "MIME-Version: 1.0 \n"; 
		//$headers .= "Content-Type: text/plain; charset=KOI8-R; format=flowed Content-Transfer-Encoding: 8bit " .
		//		"\n"; 
		$headers .= "X-Mailer: PHP script "; 
		
		mail($address, $subj, $msg, $headers);
	}
	function processSubmit(){
		//getting a user id
		$user_id=$this->api->db->getOne("select user_id from ".$this->api->getConfig('auth/rp_table').
			" where id = ".$this->get('rp_id'));
		//changing password
		$this->api->db->query("update ".$this->api->recall('auth_table')." set ".$this->api->recall('auth_pass_field').
			" = '".$this->sec($this->get('password')).
				"' where id = $user_id");
		//storing changed info
		$this->api->db->query("update ".$this->api->getConfig('auth/rp_table')." set changed=1, changed_dts=SYSDATE()" .
				" where id=".$this->get('rp_id'));
		$this->owner->owner->add('Text', null, 'Content')->set('<center>Password changed succefully</center>');
		if($this->get('send')=='Y')$this->sendPassword($user_id, $this->get('password'));
		unset($this->api->sticky_get_arguments['rp']);
		unset($this->api->sticky_get_arguments['key']);
		$this->owner->owner->add('Text', 'back', 'Content')
			->set("<div align=center><a href=".$this->api->getDestinationURL('Index').">Back to main page</a></div>");
		//$this->owner->owner->template->trySet('onload', "self.setTimeout(self.location.href='".
			//$this->api->getDestinationURL($this->api->getConfig('auth/login_page'))."', 5000)");
	}
}
