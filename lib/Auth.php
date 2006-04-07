<?php
/*
 * Created on 06.03.2006 by *Camper*
 */
/**
 * This class performs an authentication and stores user login in the session
 * If there is no user info in the session - it is displaying a login form
 */
/**
 * Simple login form
 */
class RegisterLink extends Text{
	function init(){
		parent::init();
		$this->set('<tr><td align=left><a href='.
			$this->api->getDestinationURL($this->api->page,array('register'=>1)).'>Register</a></td><td></td></tr>');
	}
}
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
class Auth extends AbstractController{//extends Page{
    public $api;
    public $owner;
    public $template;
    public $dq;
    private $name_field;
    private $pass_field;
    private $form;
    public $auth_data;
    private $secure = true;
    private $can_register = false;

    function init(){
		$this->auth_data = $this->api->recall('auth_data');
        $this->api->addHook('pre-exec',array($this,'doLogin'));
        //$this->api->addHook('post-submit',array($this,'onLogin'));
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
	private function sec($str){
		return $this->secure?sha1($str):$str;
	}
	function doLogin($plogin = null, $ppassword = null){
		//getting stored auth_data
		if(!$this->auth_data['authenticated']){
			$remember = false;
			//checking if there are loginform's fields are stored
			if($this->api->recall('authname', false)){
				if(isset($_POST[$this->api->recall('authname')])){
					//getting from POST
					$user = $_POST[$this->api->recall('authname')];
					$pass = $this->sec($_POST[$this->api->recall('authpass')]);
					//echo "$user : $pass";
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
				if(isset($user))$this->api->add('Text', null, 'Content')
					->set("<div align=center>Your login is incorrect</div>");
				$this->showLoginForm();
			}else{
				$this->api->memorize('auth_data', $this->auth_data);
				//storing in cookies for a month
				if($remember){
					setcookie('username', $this->auth_data[$this->name_field], time()+60*60*24*30);
					setcookie('password', $this->auth_data[$this->pass_field], time()+60*60*24*30);
				}
				$this->onLogin();
			}
		}
	}
	function setNoCrypt(){
		$this->secure = false;
		return $this;
	}
	function logout(){
		$this->api->forget('auth_data');
		setcookie('username', '', time()-1);
		setcookie('password', '', time()-1);
		$this->api->redirect('Index');
	}
	function onLogin(){
		$this->api->redirect($this->api->page);
	}
	function addRegisterLink(){
		$this->can_register = true;
		return $this;
	}
	function showLoginForm(){
        $this->api->template->del('Content');
        $this->api->template->del('Menu');
		$form = $this->api->frame('Content', 'Login')->add('LoginForm', null, 'content');
		//adding a link to the registration page
		if($this->can_register){
			$form->addSeparator()
				->add('RegisterLink', null, 'form_body');
		}
	}
}
?>
