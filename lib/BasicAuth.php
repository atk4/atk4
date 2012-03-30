<?php
/***********************************************************
   ..

   Reference:
     http://atk4.com/doc/ref

 **ATK4*****************************************************
   This file is part of Agile Toolkit 4 
    http://www.atk4.com/
  
   (c) 2008-2011 Agile Technologies Ireland Limited
   Distributed under Affero General Public License v3
   
   If you are using this file in YOUR web software, you
   must make your make source code for YOUR web software
   public.

   See LICENSE.txt for more information

   You can obtain non-public copy of Agile Toolkit 4 at
    http://www.atk4.com/commercial/ 

 *****************************************************ATK4**/
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

	public $info=false;		// info will contain data loaded about authenticated user. This
							// property can be accessed through $this->get(); and should not
							// be changed after authentication.

	public $form=null;	// This form is created when user is being asked about authentication.
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
    function allow($user,$pass=null){
        // creates fictional model to allow specified user and password
        // TODO: test this
        $this->setModel($this->add('Model')
            ->setSource('Array',is_array($user)?$user:array('email'=>$user,'password'=>$pass)));
        return $this;
    }
    function setModel($model,$login_field='email',$password_field='password'){
        parent::setModel($model);
        $this->login_field=$login_field;
        $this->password_field=$password_field;

        // Load model from session
        if($this->info && $this->recall("id")){
            if($this->recall('class',false)==get_class($this->model)){
                $this->debug("Loading model from cache");
                $this->model->set($this->info);
                $this->model->id=$this->recall('id',null);
            }else{
                // Class changed, re-fetch data from database
                $this->debug("Class changed, loading from database");
                $this->model->load($this->recall('id'));
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
        return $this->model;
    }
    function addEncryptionHook($model){
        // If model is saved, encrypt password
        $t=$this;
        $model->addHook('beforeSave',function($m)use($t){
            if(isset($m->dirty[$t->password_field])){
                $m['password']=$t->encryptPassword($m[$t->password_field],$m[$t->login_field]);
            }
        });
    }
	function destroy(){
		unset($this->api->auth);
		parent::destroy();
	}
	function get($property=null,$default=null){
		if(is_null($property))return $this->info;
		if(!isset($this->info[$property]))return $default;
		return $this->info[$property];
	}
	function getAll(){
		return $this->info;
	}
	function getAllowedPages(){
		return $this->allowed_pages;
	}
    /**
     * Allows page access without login
     */
	function allowPage($page){
		if(is_array($page)){
			foreach($page as $p)$this->allowPage($p);
			return $this;
		}
		$this->allowed_pages[]=$page;
		return $this;
	}
	function isPageAllowed($page){
        if($this->hook('isPageAllowed')===true)return true;
		return in_array($page,$this->allowed_pages) || in_array(str_replace('_','/',$page),$this->allowed_pages);
	}
	function usePasswordEncryption($method){
		$this->password_encryption=$method;
		return $this;
	}
	function encryptPassword($password,$salt=null){
        if(is_callable($this->password_encryption)){
            $e=$this->password_encryption;
            return $e($password,$salt);
        }
		if($this->password_encryption)$this->debug("Encrypting password: '$password'");
		switch($this->password_encryption){
			case null: return $password;
            case'sha256/salt':
                       if(!$salt)throw $this->exception('sha256 requires salt (2nd argument to encryptPassword and is normaly an email)');
                       return hash_hmac('sha256',
                                 $password.$salt,
                                 $this->api->getConfig('auth/key',$this->api->name));
			case'sha1':return sha1($password);
			case'md5':return md5($password);
			case'rot13':return str_rot13($password);
			default: throw $this->exception('No such encryption method')->addMoreInfo('encryption',$this->password_encryption);
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


		// Logout is fictional page. If user tries to access it, he will be logged out and redirected
		if(strtolower($this->api->page)=='logout'){
			$this->logout();
            $this->api->redirect('/');
		}

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
		return $this->model->loaded();
	}
    /**
     * This function verifies username and password. Password must be supplied in plain text.
     */
	function verifyCredintials($user,$password){
        if($this->model->hasMethod('verifyCredintials'))return $this->model->verifyCredintials($user,$passord);
        $data = $this->model->getBy($this->login_field,$user);
        if(!$data)return false;
        if($data[$this->password_field]==$this->encryptPassword($password,$user)){
            return $data[$this->model->id_field];
        }else return false;
	}

    /** Memorize current URL. Called when the first unsuccessful check is executed. */
    function memorizeURL(){
        if(!$this->recall('url',false)){
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
        $this->model->load($id);
        $this->memorizeModel();
        return $this;
    }
	function login($user){
        if(is_object($user)){
            if(!$this->model)throw $this->exception('Auth Model should be set');
            $c=get_class($this->model);

            if(!$user instanceof $c)throw $this->exception('Specified model with incompatible class')
                ->addMoreInfo('required',$c)
                ->addMoreInfo('supplied',get_class($user));

            $this->model=$user;
            return $this;
        }

        $this->model->loadBy($this->login_field,$user);
        $this->memorizeModel();
        return $this;
	}
    /** Manually log out user */
	function logout(){
        $this->hook('logout');

		$this->forget('info');
		$this->forget('id');

   	 	setcookie(session_name(), '', time()-42000, '/');
		session_destroy();

		$this->info=false;
        return $this;
	}
    /** Rederect to index page */
	function loginRedirect(){
		$this->debug("to Index");
		$this->api->redirect($this->getURL());
	}
	function createForm($page){
		$form=$page->add('Form');
		$form->addField('Line','username','Login');
		$form->addField('Password','password','Password');
		$form->addSubmit('Login');

        return $form;
	}
	function showLoginForm(){
		/*
		 * This function shows a login form. If $this->form is already defined, it's shown right away,
		 * otherwise simple login form is displayed
		 */
        $this->api->page_object=$p=$this->api->add('Page',null,null,array('page/login'));

        // hook: createForm use this to build basic login form
        $this->form=$this->hook('createForm',array($p));

        // If no hook, build standard form
        if(!is_object($this->form))
            $this->form=$this->createForm($p);

        $this->hook('updateForm');
        $f=$this->form;
        if($f->isSubmitted()){
			if($this->verifyCredintials($f->get('username'), $f->get('password'))){				
                $this->login($f->get('username'));
                $this->loggedIn($f->get('username'),$f->get('password'));
                exit;
            }
			$f->getElement('password')->displayFieldError('Incorrect login information');
        }
		return $p;
	}
	function memorizeOriginalURL(){
		if($this->recall('original_request',false)===false)$this->memorize('original_request',$_GET);
		return $this;
	}
	function processLogin(){
		// this function is called when authorization is not found.
		// You should return true, if login process was successful.
		$this->memorizeOriginalURL();
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
