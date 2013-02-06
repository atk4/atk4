<?php // vim:ts=4:sw=4:et:fdm=marker
/**
 * A basic authentication class. Include inside your API or
 * on a page. You may have multiple Auth instances. Supports
 * 3rd party plugins.
 * 
 * @link http://agiletoolkit.org/doc/auth
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
/*
 * Use:
 *
 * $auth=$this->add('Auth');
 * $auth->setModel('User');
 * $auth->check();
 *
 * Auth accessible from anywhere through $this->api->auth;
 *
 * Auth has several extensions, enable them like this:
 *
 * $auth->add('auth/Controller_DummyPopup');        // allows you to pick user from list and bypass password
 * $auth->add('auth/Controller_Cookie');            // adds "remember me" checkbox
 *
 * See documentation on "auth" add-on for more information
 *  http://agiletoolkit.org/a/auth
 *
 */
class Auth_Basic extends AbstractController {

    public $info=false;     // info will contain data loaded about authenticated user. This
                            // property can be accessed through $this->get(); and should not
                            // be changed after authentication.

    public $form=null;  // This form is created when user is being asked about authentication.
                            // If you are willing to change the way form looks, create it
                            // prior to calling check(). Your form must have compatible field
                            // names: "username" and "password"

    protected $password_encryption=null;         // Which encryption to use. Few are built-in

    protected $allowed_pages=array();

    public $login_field='email';
    public $password_field='password';

    function init(){
        parent::init();

        // Register as auth handler.
        $this->api->auth=$this;

        // Try to get information from the session. If user is authenticated, information will
        // be available there
        $this->info=$this->recall('info',false);

    }
    /** Create an array model and specify it for authentication as a quick way to get authentication working */
    function allow($user,$pass=null){
        // creates fictional model to allow specified user and password
        // TODO: test this
        if($this->model){
            $this->model->table[]=array($this->login_field=>$user,$this->password_field=>$pass);
            return $this;
        }
        $m=$this->add('Model')
            ->setSource('Array',array(is_array($user)?$user:array($this->login_field=>$user,$this->password_field=>$pass)));
        $m->id_field=$this->login_field;
        $this->setModel($m);
        return $this;
    }
    /** Specify user model */
    function setModel($model,$login_field='email',$password_field='password'){
        parent::setModel($model);
        $this->login_field=$login_field;
        $this->password_field=$password_field;

        // Load model from session
        if($this->info && $this->recall("id")){
            if($this->recall('class',false)==get_class($this->model)){
                $this->debug("Loading model from cache");
                $this->model->set($this->info);
                $this->model->dirty=array();
                $this->model->id=$this->recall('id',null);
            }else{
                // Class changed, re-fetch data from database
                $this->debug("Class changed, loading from database");
                $this->model->tryLoad($this->recall('id'));
                if(!$this->model->loaded())$this->logout(false);

                $this->memorizeModel();
            }
        }

        $t=$this;

        // If model is saved, update our cache too, but don't store password
        $this->model->addHook('afterSave',function($m)use($t){
            // after this model is saved, re-cache the info
            $tmp=$m->get();
            unset($tmp[$t->password_field]);
            $t->memorize('info',$tmp);
        });

        $this->addEncryptionHook($this->model);

        if(strtolower($this->api->page)=='logout'){
            $this->logout();
            $this->api->redirect('/');
        }

        return $this->model;
    }
    /** Adds a hook to specified model which will encrypt password before save. Do not call
     * on api->auth->model, because that model already has the hook */
    function addEncryptionHook($model){
        // If model is saved, encrypt password
        $t=$this;
        $model->has_encryption_hook=true;
        $model->addHook('beforeSave',function($m)use($t){
            if(isset($m->dirty[$t->password_field])){
                $m[$t->password_field]=$t->encryptPassword($m[$t->password_field],$m[$t->login_field]);
            }
        });
    }
    function destroy(){
        unset($this->api->auth);
        parent::destroy();
    }
    /** Auth memorizes data about a logged-in user in session. You can either use this function to access
     * that data or $auth->model (preferred)   $auth->get('username') will always point to the login field
     * value ofthe user regardless of how your field is named
     */
    function get($property=null,$default=null){
        if(is_null($property))return $this->info;
        if(!isset($this->info[$property]))return $default;
        return $this->info[$property];
    }
    function getAll(){
        return $this->info;
    }
    /** Specify page or array of pages which will exclude authentication. Add your registration page here
     * or page containing terms and conditions */
    function allowPage($page){
        if(is_array($page)){
            foreach($page as $p)$this->allowPage($p);
            return $this;
        }
        $this->allowed_pages[]=$page;
        return $this;
    }
    function getAllowedPages(){
        return $this->allowed_pages;
    }
    function isPageAllowed($page){
        if($this->hook('isPageAllowed')===true)return true;
        return in_array($page,$this->allowed_pages) || in_array(str_replace('_','/',$page),$this->allowed_pages);
    }
    /** Specifies how password will be encrypted when stored. Some values are "sha256/salt", "md5", "rot13". If you
     * don't call this, passwords will be stored in plain-text */
    function usePasswordEncryption($method){
        $this->password_encryption=$method;
        return $this;
    }
    /** Manually encrypt password */
    function encryptPassword($password,$salt=null){
        if(!is_string($this->password_encryption) && is_callable($this->password_encryption)){
            $e=$this->password_encryption;
            return $e($password,$salt);
        }
        if($this->password_encryption)$this->debug("Encrypting password: '$password' with ".$this->password_encryption.' salt='.$salt);
        switch($this->password_encryption){
            case null: return $password;
            case'sha256/salt':
                       if(!$salt)throw $this->exception('sha256 requires salt (2nd argument to encryptPassword and is normaly an email)');
                       $key=$this->api->getConfig('auth/key',$this->api->name);
                       if($this->password_encryption)$this->debug("Using key ".$key);
                       return hash_hmac('sha256',
                                 $password.$salt,
                                 $key);
            case'sha1':return sha1($password);
            case'md5':return md5($password);
            case'rot13':return str_rot13($password);
            default: throw $this->exception('No such encryption method')->addMoreInfo('encryption',$this->password_encryption);
        }
    }
    /** Call this function to perform a check for logged in user. This will also display a login-form
     * and will verify user's credential. If you want to handle log-in form on your own, use
     * auth->isLoggedIn() to check and redirect user to a login page.
     *
     * check() returns true if user have just logged in and will return "null" for requests when user
     * continues to use his session. Use that to perform some calculation on log-in */
    function check(){

        if($this->isPageAllowed($this->api->page))return null;      // no authentication is required

        // Check if user's session contains autentication information
        if(!$this->isLoggedIn()){

            $this->memorizeURL();

            // Brings up additional verification methods, such as cookie
            // authentication, token or OpenID. In case of successful login, 
            // breakHook($user_id) must be used
            $user_id=$this->hook('check');
            if(!is_array($user_id) && !is_bool($user_id)){
                $this->model->load($user_id);
                //$this->loggedIn();
                $this->memorizeModel();
                return true;
            }


            /*
            $this->debug('User is not authenticated yet');

            // No information is present. Let's see if cookie is set
                }
            }else $this->debug("No permanent cookie");
             */
            $this->processLogin();
            return true;
        }else $this->debug('User is already authenticated');
    }
    /** Add additional info to be stored in user session. */
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
    /** Returns if user is authenticated or not. For more info on user see auth->model */
    function isLoggedIn(){
        /*
         * This function determines - if user is already logged in or not. It does it by
         * looking at $this->info, which was loaded during init() from session.
         */
        return $this->model->loaded();
    }
    /** This function verifies username and password. Password must be supplied in plain text. Does not affect currently 
     * logged in user */
    function verifyCredentials($user,$password){
        $this->debug('verifying credentials for '.$user.' '.$password);
        if($this->model->hasMethod('verifyCredentials'))return $this->model->verifyCredentials($user,$password);
        if(!$this->model->hasElement($this->password_field)){
            $this->model->addField($this->password_field);
        }
        $data = $this->model->tryLoadBy($this->login_field,$user)->get();
        $this->model->unload();  // just to be sure, we don't leave it there
        if(!$data)return false;
        $this->debug('data says password is '.$data[$this->password_field]);
        $ep=$this->encryptPassword($password,$user);
        if($data[$this->password_field]==$ep){
            $this->debug('Matched to encrypted password is '.$ep);
            return $data[$this->model->id_field];
        }else{
            $this->debug('Missmatch to '.$ep);
            return false;
        }
    }
    /** Memorize current URL. Called when the first unsuccessful check is executed. */
    function memorizeURL(){
        if(!$this->recall('page',false)){
            $this->memorize('page',$this->api->page);
            $g=$_GET;unset($g['page']);
            $this->memorize('args',$g);
        }
    }
    /** Return originalally requested URL. */
    function getURL(){
        $p=$this->recall('page');

        // If there is a login page, no need to return to it
        if($p=='login')return $this->api->url('/');

        $url=$this->api->url($p, $this->recall('args',null));
        $this->forget('url');$this->forget('args');
        return $url;
    }
    /** Rederect to page user tried to access before authentication was requested */
    function loginRedirect(){
        $this->debug("to Index");
        $this->api->redirect($this->getURL());
    }
    /**
     * This function is always executed after successfull login through a normal means (login form or plugin)
     *
     * It will create cache model data.
     */
    function loggedIn($user=null,$pass=null){ //$username,$password,$memorize=false){
        $this->hook('loggedIn',array($user,$pass));
        $this->api->redirect($this->getURL());
    }
    /** Store model in session data so that it can be retrieved faster */
    function memorizeModel(){
        if(!$this->model->loaded())throw $this->exception('Authentication failure','AccessDenied');

        // Don't store password in model / memory / session
        $this->model['password']=null;
        unset($this->model->dirty['password']);

        // Cache memory. Should we use controller?
        $this->info=$this->model->get();
        $this->info['username']=$this->info[$this->login_field];

        $this->memorize('info',$this->info);
        $this->memorize('class',get_class($this->model));
        $this->memorize('id',$this->model->id);
    }
    /** Manually Log in as specified users. Will not perform password check or redirect */
    function loginByID($id){
        $this->model->tryLoad($id);
        $this->memorizeModel();
        return $this;
    }
    /** Manually Log in with specified condition */
    function loginBy($field,$value){
        $this->model->tryLoadBy($field,$value);
        $this->memorizeModel();
        return $this;
    }
    /** Manually Log in as specified users by using login name. */
    function login($user){
        if(is_object($user)){
            if(!$this->model)throw $this->exception('Auth Model should be set');
            $c=get_class($this->model);

            if(!$user instanceof $c)throw $this->exception('Specified model with incompatible class')
                ->addMoreInfo('required',$c)
                ->addMoreInfo('supplied',get_class($user));

            $this->model=$user;
            $this->memorizeModel();
            return $this;
        }

        $this->model->tryLoadBy($this->login_field,$user);
        $this->memorizeModel();
        return $this;
    }
    /** Manually log out user */
    function logout(){
        $this->hook('logout');

        $this->model->unload();

        // maybe can use $this->api->destroySession() here instead?
        $this->forget('info');
        $this->forget('id');

        setcookie(session_name(), '', time()-42000, '/');
        @session_destroy();

        $this->info=false;
        return $this;
    }
    /** Creates log-in form. Override if you want to use your own form. If you need to change template used by a log-in form, 
     * add template/default/page/login.html */
    function createForm($page){
        $form=$page->add('Form');

        $email=$this->model->hasField($this->login_field);
        $email=$email?$email->caption:'E-mail';

        $password=$this->model->hasField($this->password_field);
        $password=$password?$password->caption:'Password';

        $form->addField('Line','username',$email);
        $form->addField('Password','password',$password);
        $form->addSubmit('Login');

        return $form;
    }
    /** Do not override this function. */ 
    function showLoginForm(){
        $this->api->page_object=$p=$this->api->add('Page',null,null,array('page/login'));

        // hook: createForm use this to build basic login form
        $this->form=$this->hook('createForm',array($p));

        // If no hook, build standard form
        if(!is_object($this->form))
            $this->form=$this->createForm($p);

        $this->hook('updateForm');
        $f=$this->form;
        if($f->isSubmitted()){
            if($this->verifyCredentials($f->get('username'), $f->get('password'))){             
                $this->login($f->get('username'));
                $this->loggedIn($f->get('username'),$f->get('password'));
                exit;
            }
            $f->getElement('password')->displayFieldError('Incorrect login information');
        }
        return $p;
    }
    /** Do not override this function. */
    function processLogin(){
        $this->memorizeURL();
        $this->api->template->tryDel('Menu');
        $p=$this->showLoginForm();

        $this->api->hook('post-init');
        $this->api->hook('pre-exec');

        if(isset($_GET['submit']) && $_POST){
            $this->api->hook('submitted');
        }

        $this->api->hook('post-submit');
        $this->api->execute();
        exit;
    }
}
