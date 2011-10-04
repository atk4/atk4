<?php // vim:ts=4:sw=4:et:fdm=marker
/**
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
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4 
    http://agiletoolkit.org/
  
   (c) 2008-2011 Romans Malinovskis <atk@agiletech.ie>
   Distributed under Affero General Public License v3
   
   See http://agiletoolkit.org/about/license
 =====================================================ATK4=*/
class BasicAuth extends AbstractController {

    /** Once user is authenticated, this will contain some information about the user. Use dq->field() to query more info during login. */
    public $info=false; 

    /** By default a static pairs of user=>passwords are used. Add one by calling allow($user,$password) */
    protected $allowed_credintals=array();

    /** Authentictation creates a form for logging in */
    protected $form=null;

    /** @private */
    protected $password_encryption=null;         // Which encryption to use. Few are built-in

    protected $title="Authoriation is necessary";  // use setTitle() to change this text appearing on the top of the form

    /** @private */
    protected $allowed_pages=array('Logout');


    // {{{ Initialization and destruction
    function init(){
        parent::init();

        // Register as auth handler.
        if(!@$this->api->auth)$this->api->auth=$this;

        // Try to get information from the session. If user is authenticated, information will
        // be available there
        $this->info=$this->recall('info',false);

        // Logout is fictional page. If user tries to access it, he will be logged out and redirected
        if(strtolower($this->api->page)=='logout'){
            $this->logout();
        }
    }
    /** Allow combination of username / password to log-in */
    function allow($username,$password=null){
        if(is_null($password)&&is_array($username)){
            foreach($username as $user=>$pass)$this->allow($user,$pass);
            return $this;
        }
        $this->allowed_credintals[$username]=$password;
        return $this;
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

            // Redirect to index page if its ajax action <
            /*            if (isset($_REQUEST['ajax_submit']) || isset($_REQUEST['cut_object']) || isset($_REQUEST['expanded'])) {
                      echo "window.location = 'Index'; <!--endjs-->";
                      exit;
                      }
             */
            // No information is present. Let's see if cookie is set
            if(isset($_COOKIE[$this->name."_username"]) && isset($_COOKIE[$this->name."_password"])){

                $this->debug("Cookie present, validating it");
                // Cookie is found, but is it valid?
                // passwords are always passed encrypted
                if($this->verifyCredintials(
                            $_COOKIE[$this->name."_username"],
                            $_COOKIE[$this->name."_password"]
                            )){
                    // Cookie login was successful. No redirect will be performed
                    $this->loggedIn($_COOKIE[$this->name."_username"],$_COOKIE[$this->name."_password"]);
                    $this->memorize('info',$this->info);
                    return;
                }
            }else $this->debug("No permanent cookie");
            $this->processLogin();
            return true;
        }else $this->debug('User is already authenticated');
    }
    /** remove ourselves */
    function destroy(){
        if($this->api->auth===$this)unset($this->api->auth);
        parent::destroy();
    }
    // }}}

    // {{{ Login property management
    /** Get one of the user prooperties, which are queried during login. Stored in sessions for speed */
    function get($property=null,$default=null){
        if(is_null($property))return $this->info;
        if(!isset($this->info[$property]))return $default;
        return $this->info[$property];
    }
    function getAll(){
        return $this->info;
    }
    /** Enforce use of encryption. Can be 'sha1','md5','sha256/salt' or 'rot13'. If not called, plain-text passwords will be stored */
    function usePasswordEncryption($method){
        $this->password_encryption=$method;
        return $this;
    }
    function setTitle($title){
        $this->title=$title;
    }
    // }}}

    // {{{ Whitelist page management
    function getAllowedPages(){
        return $this->allowed_pages;
    }
    /** allow implements a white-list for pages. http://agiletoolkit.org/learn/install/auth */
    function allowPage($page){
        /**
         * Allows page access without login
         */
        if(is_array($page)){
            foreach($page as $p)$this->allowPage($p);
            return $this;
        }
        $this->allowed_pages[]=$page;
        return $this;
    }
    /** Check if the page can be accessible without authentication */
    function isPageAllowed($page){
        return in_array($page,$this->allowed_pages) || in_array(str_replace('_','/',$page),$this->allowed_pages);
    }
    // }}}

    // {{{ Password encryption ciphers
    /** Perform password encryption with selected cypher */
    function encryptPassword($password,$salt=null){
        if($this->password_encryption)$this->debug("Encrypting password: '$password' with salt '$salt'");
        switch($this->password_encryption){
            case null: return $password;
            case'sha256/salt':
                   if(!$salt)throw $this->exception('sha256 requires salt (2nd argument to encryptPassword and is normaly an email)');
                   if($this->password_encryption)$this->debug("Using password key: '".$this->api->getConfig('auth/key')."'");
                   return hash_hmac('sha256',
                           $password.$salt,
                           $this->api->getConfig('auth/key'));
            case'sha1':return sha1($password);
            case'md5':return md5($password);
            case'rot13':return str_rot13($password);
            default: throw BaseException('No such encryption method: '.$this->password_encryption);
        }
    }
    // }}}

    // {{{ Mechanics of user authentication, form display and logging-in or out
    /** Store more information about the user */
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
    /** Returns if a valid user is currently logged in */
    function isLoggedIn(){
        return $this->info!==false;
    }
    /** Verifies the validity of username and password. The password must be encrypted when called. Redefine to implement your own check */
    function verifyCredintials($user,$password){
        if(!$this->allowed_credintals)$this->allowed_credintals=array('demo'=>'demo');
        $this->debug("verifying credintals: ".$user.' / '.$password." against array: ".print_r($this->allowed_credintals,true));
        if(!isset($this->allowed_credintals[$user]))return false; // No such user
        if($this->allowed_credintals[$user]!=$password)return false; // Incorrect password
        return true;
    }
    /** This function is executed after successful login. */
    function loggedIn($username,$password,$memorize=false){
        $this->debug("Login successful");
        $this->addInfo('username',$username);

        $memorize=$memorize||($this->form && $this->form->get('memorize'));
        if($memorize){
            $this->debug('setting permanent cookie');
            setcookie($this->name."_username",$username,time()+60*60*24*30*6);
            setcookie($this->name."_password",$password,time()+60*60*24*30*6);
        }

        unset($_GET['submit']);
        unset($_GET['page']);
    }
    /** Manually force user to be logged in */
    function login($username,$memorize=false){
        $this->loggedIn($username,isset($this->allowed_credintals[$username])?
                $this->allowed_credintals[$username]:null,$memorize);
        $this->memorize('info',$this->info);
    }
    /** Rederect to index page. Executed after successful login */
    function loginRedirect(){
        $this->debug("to Index");
        if($this->api->isAjaxOutput())$this->ajax()->redirect($this->api->getIndexPage())->execute();
        $this->api->redirect($this->api->getIndexPage());
    }
    /** Log-out user and destroy authentication data. Possibly will redirect to index page. */
    function logout($redirect=true){
        // Forces logout. This also cleans cookies
        $this->forget('info');
        setcookie($this->name."_username",null);
        setcookie($this->name."_password",null);
        setcookie(session_name(), '', time()-42000, '/');
        session_destroy();

        $this->info=false;
        if($redirect)$this->api->redirect($this->api->getIndexPage());
    }
    /** This function creates a form within a given container */
    function createForm($frame,$login_tag='Content'){
        $form=$frame->add('Form',null,$login_tag);
        $form->setFormClass('basic');
        $form->js_widget=false;


        $form->addField('Line','username','Login')->js(true)->focus();
        $form->addField('Password','password','Password');

        $form->addField('Checkbox','memorize','Remember me on this computer')->set(true);
        $form->addSeparator();
        $form->add('Hint')->set('<font color="red">Security warning</font>: by ticking \'Remember me on this computer\' you ' .
                'will no longer have to use a password to enter this site, until you explicitly ' .
                'log out.');

        $form->addSubmit('Login');
        return $form;
    }
    /** Force to produce a HTML based on global template (empty) instead of (shared) with login form. If $this->login is set, it will be used */ 
    function showLoginForm(){
        $this->debug("initializating authentication page");
        //if(!$_GET['page'])$this->api->page=$this->api->getConfig('auth/login_page','Index');

        $p=$this->add('View',null,null,array('empty'));
        $c=$p->add('Columns');
        $c->addColumn(3);
        $frame=$c->addColumn(4)->add('Frame')->setTitle('Authentication');
        $this->form=$this->createForm($frame);
        $c->addColumn(3);
        return $p;
    }
    function memorizeOriginalURL(){
        if($this->recall('original_request',false)===false)$this->memorize('original_request',$_GET);
        return $this;
    }
    /** this function is called when authorization is not found. You should return true, if login process was successful. Hacks around templates to output login form. */
    function processLogin(){
        $this->memorizeOriginalURL();
        $p=$this->showLoginForm();
        if($this->form->isSubmitted()){
            if($this->verifyCredintials(
                        $this->form->get('username'),
                        $this->encryptPassword($this->form->get('password'),$this->form->get('username'))
                        )){
                $this->loggedIn($this->form->get('username'),$this->encryptPassword($this->form->get('password'),$this->form->get('username')));
                $this->memorize('info',$this->info);
                $this->loginRedirect();
            }
            $this->debug("Incorrect login");
            $this->form->getElement('password')->displayFieldError('Incorrect login information');
        }

        $p->recursiveRender();
        $this->api->jquery->getJS($p);

        $p->template->set('document_ready',$this->api->template->get('document_ready'));
        $p->template->set('js_include',$this->api->template->get('js_include'));
        echo $p->template->render();
        $this->debug("Page rendered");
        exit;
    }
    // }}}
}
