<?php
/*
 * Mandatory Authorization module. Once you add this to your API, it will protect
 * it without any further actions.
 *
 *
 * This is an semi-abstract class. You may use this one, but it's pretty straightforward with
 * the authentication and is good only for testing.
 *
 * You have to add this object to your API after API is initialized. Inside API::init is a good
 * place (after parental call)
 *
 * $this->add('BasicAuth')->check();
 *
 * This way user will be able to login by entereing: user as "demo" and password as "demo"
 *
 * If you are willing to make it a bit more challenging use:
 *
 * $this->add('BasicAuth')->allow('joe','secret')->check();
 *
 * You can use multiple allow strings to allow multiple username/password pairs. Sample above
 * will allow user "joe" to login with password "secret".
 *
 * BasicAuth supports ability to store password in the cookies. Password will be stored in cookies
 * as plain-text. Some might think it's not a good idea, however if you think about it - coding
 * cookie will not provide any additional security. Cookie may be stolen and used as a cookie by
 * a hacker. Cookie is transmitted the same way http request does.
 *
 */
class BasicAuth extends AbstractController {

    protected $info=false;                      // info will contain data loaded about authenticated user. This
                                                // property can be accessed through $this->get(); and should not
                                                // be changed after authentication.

    protected $allowed_credintals=array();      // contains pairs with allowed credintals. Use function allow()
                                                // to permit different user/password pairs to login

    protected $form=null;                       // This form is created when user is being asked about authentication.
                                                // If you are willing to change the way form looks, create it
                                                // prior to calling check(). Your form must have compatible field
                                                // names: "username" and "password"

    protected $passwordencryption=null;         // Which encryption to use. Few are built-in

    protected $title="Authoriation is necessary";  // use setTitle() to change this text appearing on the top of the form

    function init(){
        parent::init();

        // Register as auth handler.
        $this->api->auth=$this;

        // Try to get information from the session. If user is authenticated, information will
        // be available there
        $this->info=$this->recall('info',false);

        // Logout is fictional page. If user tries to access it, he will be logged out and redirected
        if($this->api->page=='Logout'){
            $this->logout();
        }
    }
    function get($property,$default=null){
        if(!isset($this->info[$property]))return $default;
        return $this->info[$property];
    }
    function allow($username,$password){
        /*
         * This function will add specified credintals to allowed user list. If they are entered
         * properly to the login form, user will be granted access
         */
        $this->allowed_credintals[$username]=$password;
        return $this;
    }
    function usePasswordEncryption($method){
        $this->password_encryption=$method;
        return $this;
    }
    function setTitle($title){
        $this->title=$title;
    }
    function encryptPassword($password){
        if($this->password_encryption)$this->debug("Encrypting password: '$password'");
        switch($this->password_encryption){
            case false:;
            case true:;
            case null: return $password;
            case'sha1':return sha1($password);
            case'md5':return md5($password);
            case'rot13':return str_rot13($password);
            default: throw BaseException('No such encryption method: '.$this->password_encryption);
        }
    }
    function check(){
        /* 
         * This is a public function you must call when preparations are complete. It will verify
         * if user is logged in or not. If he's not logged in, it will try to verify his
         * credintals. If he's verified - browser will be redirected and execution terminated.
         * If not - login form will be displayed and execution terminated.
         *
         * You can be safe, that only allowed users will be able to get past this function inside
         * your code;
         */

        // Check if user's session contains autentication information
        if(!$this->isLoggedIn()){
            $this->debug('User is not authenticated yet');
            // No information is present. Let's see if cookie is set
            if(isset($_COOKIE[$this->name."_username"]) && isset($_COOKIE[$this->name."_password"])){

                $this->debug("Cookie present, validating it");
                // Cookie is found, but is it valid?
                if($this->verifyCredintials(
                            $_COOKIE[$this->name."_username"],
                            $this->encryptPassword($_COOKIE[$this->name."_password"])
                           )){
                    // Cookie login was successful. No redirect will be performed
                    $this->loggedIn($_COOKIE[$this->name."_username"]);
                    $this->memorize('info',$this->info);
                    return;
                }
            }else $this->debug("No permanent cookie");
            $this->processLogin();
            return true;
        }else $this->debug('User is already authenticated');
    }
    function addInfo($key,$val=null){
        if(is_null($val) && is_array($key)){
            foreach($key as $a=>$b){
                $this->addInfo($a,$b);
            }
            return;
        }
        $this->debug("Gathered info: $key=$val");
        $this->info[$key]=$val;
        return $this;
    }
    function isLoggedIn(){
        /*
         * This function determines - if user is already logged in or not. It does it by
         * looking at $this->info, which was loaded during init() from session.
         */
        return $this->info!==false;
    }
    function verifyCredintials($user,$password){
        /*
         * This function verifies username and password. Password might actually be encryped.
         * If you want to change authentication logic - redefine this function
         */
        if(!$this->allowed_credintals)$this->allowed_credintals=array('demo'=>'demo');
        $this->debug("verifying credintals: ".$user.' / '.$password." against array: ".print_r($this->allowed_credintals,true));

        if(!isset($this->allowed_credintals[$user]))return false; // No such user

        if($this->allowed_credintals[$user]!=$password)return false; // Incorrect password

        // Successful
        return true;
    }
    function loggedIn($username){
        /*
         * This function is always executed after successful login.
         *
         * It will create $this->info with non-false value. If you are willing to add more
         * data to $auth->info, re-define this function and do addition there. You must
         * call parent, then modify it.
         */
        $this->debug("Login successful");
        $this->addInfo('username',$username);

        if($this->form && $this->form->get('memorize')){
            $this->debug('setting permanent cookie');
            setcookie($this->name."_username",$this->form->get('username'),time()+60*60*24*30*6);
            setcookie($this->name."_password",$this->form->get('password'),time()+60*60*24*30*6);
        }

        unset($_GET['submit']);
        unset($_GET['page']);
    }
    function loginRedirect(){
        $this->debug("Redirecting to original page");

        // Redirect to the page which was originally requested
        if($original_request=$this->recall('original_request',false)){
            $p=$original_request['page'];
            unset($original_request['page']);
            unset($original_request['submit']);
            $this->api->redirect($p,$original_request);
        }


        // Rederect to index page
        $this->api->redirect(null);
    }
	function logout(){
        // Forces logout. This also cleans cookies
		$this->forget('info');
        setcookie($this->name."_username",null);
        setcookie($this->name."_password",null);
        $this->info=false;
        $this->memorize('info',$this->info);
        $this->api->redirect('Index');
	}
    function createForm($frame){
        $form=$frame->add('Form');
        

        $form
            ->addSeparator($this->title)
            ->addField('Line','username','Login')
            ->addField('Password','password','Password')

            ->addField('Checkbox','memorize','Remember me')
            ->addComment('<div align="left"><font color="red">Security warning</font>: by ticking \'Remember me on this computer\'<br>you ' .
            		'will no longer have to use a password to enter this site,<br>until you explicitly ' .
            		'log out.</b></div>')
			
            ->addSubmit('Login');
        $form->onLoad()->setFormFocus($form,'username');
        return $form;
    }
    function showLoginForm(){
        /*
         * This function shows a login form. If $this->form is already defined, it's shown right away,
         * otherwise simple login form is displayed
         */
        $this->debug("initializating authentication page");
		if(!$_GET['page'])$this->api->page=$this->api->getConfig('auth/login_page','Index');

        $p=$this->add('Page');
        $p->template->loadTemplate('empty');
        $p->template->set('page_title', $this->title_form);
        $frame=$p->frame('Authentication');

        $this->form=$this->createForm($frame);

		return $p;
    }
    function memorizeOriginalURL(){
        $this->memorize('original_request',$_GET);
        return $this;
    }
    function processLogin(){
        // this function is called when authorization is not found. 
        // You should return true, if login process was successful.
        $this->memorizeOriginalURL();
		$p=$this->showLoginForm();
        if($this->form->isSubmitted()){
            if($this->verifyCredintials(
                        $this->form->get('username'),
                        $this->encryptPassword($this->form->get('password'))
                        )){
                $this->loggedIn($this->form->get('username'));
                $this->memorize('info',$this->info);
                $this->loginRedirect();
            }
            $this->debug("Incorrect login");
            $this->form->getElement('password')->displayFieldError('Incorrect login information');
        }

        $p->recursiveRender();
        echo $p->template->render();
        $this->debug("Page rendered");
        exit;
    }
}
