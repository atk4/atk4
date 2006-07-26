<?php
/**
 * Improved version of BasicAuth.
 * Login/password retrieved from DB table
 * Functionality of password recovery and new user registration is included.
 * 
 * Passwords could be stored as plain text or sha1 hash. By default they are encrypted.
 * If you do not want them to be encrypted - use Auth::setEncrypted(false)
 * 
 * If you want to use password recovery feature:
 * - create a fake page for password recovery (it is needed!)
 * - add a link to password recovery page: Auth::addPwdRecoveryLink()
 * - specify a page name to password recovery in your config.php ($config['auth']['pwd_recovery']['page'])
 * - specify a table name which will be used for password recovery process ($config['auth']['pwd_recovery']['table'])
 *   This table structure is:
 *   CREATE TABLE pwd_recovery_request (
 *     id int(11) unsigned NOT NULL auto_increment,
 *     user_id int(11) unsigned NOT NULL default '0',
 *     email varchar(32) NOT NULL default '',
 *     expire datetime default NULL,
 *     changed int(1) default '0',
 *     changed_dts datetime default NULL,
 *     PRIMARY KEY  (id)
 *     ) ENGINE=MyISAM
 * - specify a period in minutes in which link to recovery will be actual ($config['auth']['pwd_recovery']['timeout'])
 * 
 * If you want to use register feature:
 * - create a page with the user registration data
 * - add a link to login form: Auth::addRegisterLink()
 * - specify register page in your config.php
 * 
 * Created on 04.07.2006 by *Camper* (camper@adevel.com)
 */
class DBAuth extends BasicAuth{
    public $dq;
    private $name_field;
    private $pass_field;
    private $email_field;
    private $secure = true;
    private $can_register = false;
    private $show_lost_password = false;
    private $from='';
    private $subj='Password recovery';

	function init(){
		parent::init();
		$this->api->addHook('pre-exec',array($this,'check'));
		//process recovery and register
		if($this->api->page==$this->api->getConfig('auth/register_page')){
			$this->api->addHook('post-init', array($this, 'processRegister'));
		}
		if($this->api->page==$this->api->getConfig('auth/pwd_recovery/page')){
			$this->api->addHook('post-init', array($this, 'processRecovery'));
			//$this->processRecovery();
		}
	}
	function processRegister(){
		$p=$this->add('Page');
        $p->template->loadTemplate('empty');
		$p->add('page_'.$this->api->getConfig('auth/register_page'), null, 'Content');
		$p->downCall('render');
		echo $p->template->render();
		exit;
	}
	function processRecovery(){
		$p=$this->add('Page');
		$p->template->loadTemplate('empty');
		$p->template->set('page_title', 'Password recovery');
		if($_GET['key']){
			//user clicked link in e-mail
			$this->api->stickyGET('rp');
			$this->api->stickyGET('key');
			$id=$_GET['rp'];
			$key=$_GET['key'];
	   		$row=$this->api->db->getHash("select * from ".$this->api->getConfig('auth/pwd_recovery/table').
	    			" where id=$id and changed=0");
	    	//looking for the key in DB and checking expiration dts
	   		$db_key=sha1($row['id'].$row['email'].strtotime($row['expire']));
	   		$can_change=$db_key==$key&&strtotime($row['expire'])>time();
	    	if($can_change){
	    		//displaying changepass form
	    		$username=$this->api->db->getOne("select $this->name_field from ".
	    			$this->dq->args['table']." where id=".$row['user_id']);
	    		
				$form=$p->frame('Content', "Change password for $username")->add('Form', null, 'content');
				$form
					->addField('hidden', 'rp_id')
					->addField('password', 'password', 'Enter new password')
						->validateField('strlen($this->get())>=6', 'Password is too short')
					->addField('password', 'password2', 'Confirm new password')
						//TODO validation does not work
						->validateField('$this->get()==$this->owner->get(\'password\')', 
						'Confirmation differs from password')
					->addField('checkbox', 'send', 'Send me new password by e-mail')
			
					->addSubmit('Change')
				;
				if(isset($_REQUEST['rp']))$form->set('rp_id', $_REQUEST['rp']);
				if($form->isSubmitted()){
					//getting a user id
					$user_id=$this->api->db->getOne("select user_id from ".
						$this->api->getConfig('auth/pwd_recovery/table').
						" where id = ".$form->get('rp_id'));
					//changing password
					$this->api->db->query("update ".$this->dq->args['table']." set ".$this->pass_field.
						" = '".$this->encrypt($form->get('password')).
							"' where id = $user_id");
					//storing info about changes
					$this->api->db->query("update ".$this->api->getConfig('auth/pwd_recovery/table').
							" set changed=1, changed_dts=SYSDATE()" .
							" where id=".$form->get('rp_id'));
					$p->add('Text', null, 'Content')->set('<center>Password changed succefully</center>');
					if($form->get('send')=='Y')$this->sendPassword($user_id, $form->get('password'));
					unset($this->api->sticky_get_arguments['rp']);
					unset($this->api->sticky_get_arguments['key']);
				}
	    	}else{
	    		//denial page
				unset($this->api->sticky_get_arguments['rp']);
				unset($this->api->sticky_get_arguments['key']);
	    		$p->frame('Content', 'Request error')->add('Text', null, 'content')
	    			->set("Sorry, this page is not valid. Activation period might have been expired." .
	    			" <a href=".
					$this->api->getDestinationURL($this->api->getConfig('auth/pwd_recovery/page')).
					">Click here</a> if You want to repeat Your request.");
	    	}
		}else{
			//displaying a form with username for password recovery
			$form=$p->frame('Content', 'Password recovery')->add('Form', 'pwd_recovery_form', 'content');
			$form
				->addComment('To restore Your password please enter the '.$this->title_name.' specified at registration')
				->addField('line', 'username', $this->title_name)->setNotNull()
				
				->addSubmit('Submit')
			;
			if($form->isSubmitted()){
				$user=$this->api->db->getHash("select id, $this->name_field, $this->email_field from ".DTP.
					$this->dq->args['table']." where $this->name_field='".
					$form->get('username')."'");
				if(sizeof($user)!=0){
					//generating and sending e-mail
					$this->sendChangeLink($user['id'], $user[$this->name_field], $user[$this->email_field]);
					$p->add('Text', null, 'Content')->set("<div align=center>An e-mail with instruction " .
						"to restore password has been sent" .
						" to user '".$form->get('username')."'</div>");
				}else{
					$form->elements['username']->displayFieldError("User with a name you specified have not been found. Please try again");
				}
			}
		}
		$p->add('Text', 'back', 'Content')->set("<div align=center><a href=".
			$this->api->getDestinationURL('Index').">Back to login</a></div>");
		$p->downCall('render');
		echo $p->template->render();
		exit;
	}
	protected function sendPassword($user_id, $password){
		//TODO make template based
		$server=$this->getServerName();
		$address=$this->api->db->getOne("select $this->email_field from ".$this->dq->args['table'].
			" where id=".$user_id);
		//combining a message
		$msg="This is $server password recovery subsystem.\n\nHere is your new password : " .
			$password;
		$subj=$this->subj;
		$from=$this->from==''?"noreply@$server":$this->from;
		$headers = "From: $from \n";
		//$headers .= "Return-Path: <".$this->owner->settings['return_path'].">\n";
		//$headers .= "To: $to \n"; 
		$headers .= "MIME-Version: 1.0 \n"; 
		//$headers .= "Content-Type: text/plain; charset=KOI8-R; format=flowed Content-Transfer-Encoding: 8bit " .
		//		"\n"; 
		$headers .= "X-Mailer: PHP script "; 
		
		mail($address, $subj, $msg, $headers);
	}
	protected function sendChangeLink($user_id, $username, $address){
		//TODO make template based
		//adding a DB record with a key to a change password page
		$table=DTP.$this->api->getConfig('auth/pwd_recovery/table');
		$expire=time()+$this->api->getConfig('auth/pwd_recovery/timeout', 15)*60;
		$this->api->db->query("insert into $table (user_id, email, expire) values($user_id, '$address', " .
				"'".date('Y-m-d H:i:s', $expire)."')");
		$id=mysql_insert_id();
		$server=$this->getServerName();
		//combining a message
		$msg="This is $server password recovery subsystem.\n\nWe recieved the request " .
				" to change the password for the user '$username'. To change your password " .
				"click the link below. REMEMBER: this link is actual for a period of ".
				$this->api->getConfig('auth/pwd_recovery/timeout', 15)." minutes only. If you do not change the password " .
				"during this period, you will have to make a new change request.\n\n".
				"http://".$this->getServerName(true).dirname($_SERVER['PHP_SELF'])."/".
				$this->api->getDestinationURL(null, array('rp'=>$id, 'key'=>sha1($id.$address.$expire)));
		$subj=$this->subj;
		$from=$this->from==''?"noreply@$server":$this->from;
		$headers = "From: $from \n";
		//$headers .= "Return-Path: <".$this->owner->settings['return_path'].">\n";
		//$headers .= "To: $to \n"; 
		$headers .= "MIME-Version: 1.0 \n"; 
		//$headers .= "Content-Type: text/plain; charset=KOI8-R; format=flowed Content-Transfer-Encoding: 8bit " .
		//		"\n"; 
		$headers .= "X-Mailer: PHP script "; 
		
		mail($address, $subj, $msg, $headers);
	}
	function setEncrypted($secure=true){
		$this->secure=$secure;
		return $this;
	}
	function setPassword($password){
		return false;
	}
    function setSource($table,$login='login',$password='password',$email='email'){
    	$this->name_field = $login;
    	$this->pass_field = $password;
    	$this->email_field = $email;
        $this->dq=$this->api->db->dsql()
            ->table($table)
            ->field($this->name_field)
            ->field($this->pass_field);
        return $this;
    }
    function setEmailParams($from, $subj){
    	/**
    	 * Sets subj and from for e-mail sent by password recovery subsystem
    	 * As we going to use this class for many projects it is cool to customize class output
    	 */
    	$this->from=$from;
    	$this->subj=$subj;
    }
    function verifyCredintals($user,$password){
    	$data=$this->dq->where($this->name_field, $user)->do_getHash();
    	$this->info=array_merge($this->recall('info',array()), $this->dq->do_getHash());
        $this->memorize('info',$this->info);
    	return(sizeof($data)>0&&($data[$this->pass_field]==$password||$data[$this->pass_field]==sha1($password)));
    }
	private function encrypt($str){
		return $this->secure?sha1($str):$str;
	}
    function loggedIn(){
    	parent::loggedIn();
    }
	function showLoginForm(){
		$p=parent::showLoginForm();
		if($this->can_register){
			$this->form->add('RegisterLink', null, 'form_body');
		}
		if($this->show_lost_password){
			$this->form->add('PwdRecoveryLink', null, 'form_body');
		}
		return $p;
	}
	function addRegisterLink(){
		$this->can_register=true;
		return $this;
	}
	function addPwdRecoveryLink(){
		$this->show_lost_password=true;
		return $this;
	}
	function getServerName($full=false){
		/**
		 * Static method
		 * Returns server name by these rules:
		 * 1) if there is $_SERVER['HTTP_X_FORWARDED_HOST'] set - takes first server from the line
		 * 2) else returns $_SERVER['HTTP_HOST']
		 */
		$server='';
		if($_SERVER['HTTP_X_FORWARDED_HOST']){
			$server=$_SERVER['HTTP_X_FORWARDED_HOST'];
			$server=strpos(',',$server)==0?$server:split(',',$server);
			if(is_array($server))$server=$server[0];
		}
		if($server==''||strtolower($server)=='unknown')$server=$_SERVER['HTTP_HOST'];
		return $full?$server:str_replace('www.', '', $server);
	}
}
class RegisterLink extends Text{
	function init(){
		parent::init();
		$this->set('<tr><td align=left><a href='.
			$this->api->getDestinationURL($this->api->getConfig('auth/register_page')).'>Register</a></td><td></td></tr>');
	}
}
class PwdRecoveryLink extends Text{
	function init(){
		parent::init();
		$this->set('<tr><td align=left><a href='.
			$this->api->getDestinationURL($this->api->getConfig('auth/pwd_recovery/page')).
			'>Lost password</a></td><td></td></tr>');
	}
}
