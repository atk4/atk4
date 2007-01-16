<?php
/*
 * Mandatory Authorization module. Once you add this to your API, it will protect
 * it without any further actions.
 */
class BasicAuth extends AbstractController {
    public $info=false;

    protected $password=null;     // this is password to let people in

    protected $form;
    protected $name_field='username';
    protected $pass_field='password';
    protected $title_form='Login';
    protected $title_name='Username';
    protected $title_pass='Password';
    protected $title_comment='Please enter your username and password';

    function init(){
        parent::init();
        $this->api->auth=$this;
        $this->info=$this->recall('info',false);
        if($this->api->page=='Logout'){
            $this->logout();
        }
    }
    function setPassword($password){
        $this->password=$password;
        return $this;
    }
    function setTitles($form='', $username='', $password='', $comment=''){
    	/**
    	 * Sets titles on the login form for the corresponding fields and upper comment
    	 */
    	if($form)$this->title_form=$form;
    	if($username!='')$this->title_name=$username;
    	if($password!='')$this->title_pass=$password;
    	if($comment!='')$this->title_comment=$comment;
    }
    function check(){
        if(!$this->isLoggedIn()){
            // verify if cookie is present
            if(isset($_COOKIE[$this->name."_user"]) && isset($_COOKIE[$this->name."_password"])){
                if($this->verifyCredintials(
                            $_COOKIE[$this->name."_user"],
                            $_COOKIE[$this->name."_password"]
                           )){
                    // cookie login was successful
                    $this->loggedIn();
                    return;
                }
            }
            $this->processLogin();
        }
    }
    function isLoggedIn(){
        if($this->info['auth']===true)return true;
    }
    function verifyCredintials($user,$password){
        $result=$this->hook('verifyCredintals');
        var_dump($result);
        return $user.'123'==$password;
    }
    function loggedIn(){
        $this->info=array_merge($this->recall('info', array()),array('auth'=>true));
        $this->memorize('info',$this->info);
        if($this->form && $this->form->get('memorize')){
            setcookie($this->name."_user",$this->form->get($this->name_field),time()+60*60*24*30*6);
            setcookie($this->name."_password",$this->form->get($this->pass_field),time()+60*60*24*30*6);

        }
        unset($_GET['submit']);
        unset($_GET['page']);
    }
	function logout(){
		$this->forget('info');
        setcookie($this->name."_user",null);
        setcookie($this->name."_password",null);
        $this->info=false;
        $this->api->redirect('Index');
	}
    function showLoginForm(){
        // Initialize an empty page
        $p=$this->add('Page');
		if(!$_GET['page'])$this->api->page=$this->api->getConfig('auth/login_page');
        $p->template->loadTemplate('empty');
        $p->template->set('page_title', $this->title_form);
        $this->form=$p->frame('Authentication')
            ->add('Form');

        $this->form
            ->addComment($this->title_comment)
            ->addField('Line',$this->name_field,$this->title_name)
            ->addField('Password',$this->pass_field,$this->title_pass)
            ->addField('Checkbox','memorize','Remember me on this computer')
            ->addComment('<div align="left"><b>Security warning: by ticking \'Remember me on this computer\'<br>you ' .
            		'will no longer have to use a password to enter this site,<br>until you explicitly ' .
            		'log out.</b></div>')
			->addSeparator()
			
            ->addSubmit('Login');
		return $p;
    }
    function processLogin(){
        // this function is called when authorization is not found. 
        // You should return true, if login process was successful.
		$p=$this->showLoginForm();
        if($this->form->isSubmitted()){
            if($this->verifyCredintials($this->form->get($this->name_field),$this->form->get($this->pass_field))){
                $this->loggedIn();
                $this->api->redirect(null,$_GET);
            }
            $this->form->getElement($this->pass_field)->displayFieldError('Incorrect login information');
        }

        $p->downCall('render');
        echo $p->template->render();
        exit;
    }
}
