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

    public $template=null;
	public $info=false;		// info will contain data loaded about authenticated user. This
							// property can be accessed through $this->get(); and should not
							// be changed after authentication.

	protected $allowed_credintals=array();	// contains pairs with allowed credintals. Use function allow()
							// to permit different user/password pairs to login

	protected $form=null;	// This form is created when user is being asked about authentication.
							// If you are willing to change the way form looks, create it
							// prior to calling check(). Your form must have compatible field
							// names: "username" and "password"

	protected $password_encryption=null;         // Which encryption to use. Few are built-in

	protected $title="Authoriation is necessary";  // use setTitle() to change this text appearing on the top of the form

	protected $allowed_pages=array('Logout');

	function init(){
		parent::init();

		// Register as auth handler.
		$this->api->auth=$this;

		// Try to get information from the session. If user is authenticated, information will
		// be available there
		$this->info=$this->recall('info',false);

		// Logout is fictional page. If user tries to access it, he will be logged out and redirected
		if(strtolower($this->api->page)=='logout'){
			$this->logout();
		}
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
	 * This function will add specified credintals to allowed user list. If they are entered
	 * properly to the login form, user will be granted access
	 */
	function allow($username,$password=null){
		if(is_null($password)&&is_array($username)){
			foreach($username as $user=>$pass)$this->allow($user,$pass);
			return $this;
		}
		$this->allowed_credintals[$username]=$password;
		return $this;
	}
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
	function isPageAllowed($page){
		return in_array($page,$this->allowed_pages) || in_array(str_replace('_','/',$page),$this->allowed_pages);
	}
	function usePasswordEncryption($method){
		$this->password_encryption=$method;
		return $this;
	}
	function setTitle($title){
		$this->title=$title;
	}
	function encryptPassword($password,$salt=null){
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
	function loggedIn($username,$password,$memorize=false){
		/*
		 * This function is always executed after successful login.
		 *
		 * Password passed should be encrypted
		 *
		 * It will create $this->info with non-false value. If you are willing to add more
		 * data to $auth->info, re-define this function and do addition there. You must
		 * call parent, then modify it.
		 */
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

        $this->memorize('info',$this->info);
	}
	function login($username,$memorize=false){
		$this->loggedIn($username,isset($this->allowed_credintals[$username])?
                $this->allowed_credintals[$username]:null,$memorize);
	}
	function loginRedirect(){

		/*
		$this->debug("Redirecting to original page");
		// Redirect to the page which was originally requested
		if($original_request=$this->recall('original_request',false)){
			$p=$original_request['page'];
			if(!$p)$p=null;
			unset($original_request['page']);
			// the following parameters should not remain as thay break the page
			unset($original_request['submit']);
			// expanders should not be displayed, going to parent page instead
			if(isset($original_request['expander'])){
				$parts=split('_',$p);
				if(count($parts)>0){
					unset($parts[count($parts)-1]);
					$p=join('_',$parts);
				}
				unset($original_request['expander']);
				unset($original_request['expanded']);
				unset($original_request['id']);
			}
			unset($original_request['cut_object']);
			$this->debug("to $p");
			// erasing stored URL
			$this->forget('original_request');
			if($this->api->isAjaxOutput())$this->ajax()->redirect($p,$original_request)->execute();
			else $this->api->redirect($p,$original_request);
		}
		*/

		// Rederect to index page
		$this->debug("to Index");
		if($this->api->isAjaxOutput())$this->ajax()->redirect($this->api->getIndexPage())->execute();
		$this->api->redirect($this->api->getIndexPage());
	}
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
	function createForm($frame,$login_tag='Content'){
		$form=$frame->add('Form',null,$login_tag);


		$form->addField('Line','username','Login');
		$form->addField('Password','password','Password');

		$form->addField('Checkbox','memorize','Remember me');
		$form->add('Html')->set('<dl align="left"><font color="red">Security warning</font>: by ticking \'Remember me on this computer\'<br>you ' .
					'will no longer have to use a password to enter this site,<br>until you explicitly ' .
					'log out.</b></dl>');

		$form->addSubmit('Login');
		return $form;
	}
	function showLoginForm(){
		/*
		 * This function shows a login form. If $this->form is already defined, it's shown right away,
		 * otherwise simple login form is displayed
		 */
		$this->debug("initializating authentication page");
		//if(!$_GET['page'])$this->api->page=$this->api->getConfig('auth/login_page','Index');

		$p=$this->add('Page',null,null,array('empty'));
		try{
			$p->template->loadTemplate('login');
			$this->form=$this->createForm($p,'Login');
		}catch(PathFinder_Exception $e){
			//$p->template->loadTemplate('empty');
			/*
			$p->template->trySet('atk_path',$q=
								$this->api->pathfinder->atk_location->getURL().'/');
			$p->template->trySet('base_href',
								$q=$this->api->pm->base_url.'/'
								);
								*/
			//$this->api->setTags($p->template);
			$frame=$p->add('Frame')->setTitle('Authentication');
			$this->form=$this->createForm($frame);
		}
		// adding token for session exiration detection
		//$p->add('Text','session_token')
		//	->set('<div id="session_token" style="display: none;">session is expired, relogin</div>');
		//$p->template->set('page_title', $this->title_form);

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
		$p=$this->showLoginForm();
		if($this->form->isSubmitted()){
			if($this->verifyCredintials(
						$this->form->get('username'),
						$this->encryptPassword($this->form->get('password'),$this->form->get('username'))
						)){
				$this->loggedIn($this->form->get('username'),$this->encryptPassword($this->form->get('password')));
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
