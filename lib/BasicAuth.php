<?
/*
 * Mandatory Authorization module. Once you add this to your API, it will protect
 * it without any further actions.
 */
class BasicAuth extends AbstractController {
    public $info=false;

    protected $password=null;     // this is password to let people in

    protected $form;
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
    function setTitles($username='', $password='', $comment=''){
    	/**
    	 * Sets titles on the login form for the corresponding fields and upper comment
    	 */
    	if($username!='')$this->title_name=$username;
    	if($password!='')$this->title_pass=$password;
    	if($comment!='')$this->title_comment=$comment;
    }
    function check(){
        if(!$this->isLoggedIn()){
            // verify if cookie is present
            if(isset($_COOKIE[$this->name."_user"]) && isset($_COOKIE[$this->name."_password"])){
                if($this->verifyCredintals(
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
        if($this->info)return true;
    }
    function verifyCredintals($user,$password){
        return $user.'123'==$password;
    }
    function loggedIn(){
        $this->info=array('auth'=>true);
        $this->memorize('info',$this->info);
        if($this->form && $this->form->get('memorize')){
            setcookie($this->name."_user",$this->form->get('username'),time()+60*60*24*30*6);
            setcookie($this->name."_password",$this->form->get('password'),time()+60*60*24*30*6);

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
        $p->template->loadTemplate('empty');
        $p->template->set('page_title', $_SERVER['SERVER_NAME'].' login');
        $this->form=$p->frame('Content','Authentication')
            ->add('Form',null,'content');

        $this->form
            ->addComment($this->title_comment)
            ->addField('Line','username',$this->title_name)
            ->addField('Password','password',$this->title_pass)
            ->addField('Checkbox','memorize','Remember me on this computer')
            ->addComment('<div align="left">Security warning: by ticking \'Remember me on this computer\' you ' .
            		'will not longer have to use a password to enter this site, until you explicitly ' .
            		'log out</div>')
			->addSeparator()
			
            ->addSubmit('Login');
		return $p;
    }
    function processLogin(){
        // this function is called when authorization is not found. 
        // You should return true, if login process was successful.

		$p=$this->showLoginForm();
        if($this->form->isSubmitted()){
            if($this->verifyCredintals($this->form->get('username'),$this->form->get('password'))){
                $this->loggedIn();
                $this->api->redirect(null,$_GET);
            }
            $this->form->getElement('password')->displayFieldError('Incorrect login information');
        }

        $p->downCall('render');
        echo $p->template->render();
        exit;
    }
}
