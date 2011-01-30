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
/**
 * Password recovery plugin for DBAuth
 *
 * Created on 26.05.2007 by *Camper* (camper@adevel.com)
 */
class Auth_PasswordRecovery extends AbstractController{
	protected $page=null;
	protected $mailer=null;

	function init(){
		parent::init();
		// password recovery process triggers by the page which set in config
		if($this->api->page!==$this->api->getConfig('auth/pwd_recovery/page','none')){
			return;
		}
		// it is that page, processing
		$this->page=$this->add('Page');
		$this->page->template->loadTemplate('empty');
		$this->page->template->set('page_title', 'Password recovery');
		if(!$_GET['key'])$this->processStage1();
		else $this->processStage2();
	}
	function getLink(){
		return '<a href="'.$this->api->getDestinationURL($this->api->getConfig('auth/pwd_recovery/page')).'">' .
				'Lost password</a>';
	}
	function showRecoveryForm(){
		$form=$this->page->frame('Content', 'Password recovery',null,'width="200"')
			->add('Form', 'pwd_recovery_form', 'content');
		$form
			->addComment('To restore your password please enter the login specified at registration')
			->addField('line', 'username', 'Login')
				->setNotNull('Login should not be empty')
		;
		return $form;
	}
	function processStage1(){
		/**
		 * First stage of recovery:
		 * - showing a form with password request
		 * - sending a link with change password form by email
		 */
		//displaying a form with username for password recovery
		$form=$this->showRecoveryForm();
		if($form->isSubmitted()){
			$user=$this->api->db->dsql()->table($this->owner->dq->args['table'])
				->field('id')
				->field($this->owner->name_field)
				->field($this->owner->email_field)
				->where($this->owner->name_field,$form->get('username'))
				->do_getHash();
			if(sizeof($user)!=0){
				//generating and sending e-mail
				$this->sendChangeLink($user['id'], $user[$this->owner->name_field], $user[$this->owner->email_field]);
				$form->addComment("<b>An e-mail with instruction " .
					"to restore password has been sent" .
					" to user<br>'".$form->get('username')."'</b>");
			}else{
				$form->elements['username']
					->displayFieldError("User with a name you specified have not been found. Please try again");
			}
		}else{
			$form
				->addSubmit('Submit')
			;
		}
		$this->render();
	}
	function processStage2(){
		/**
		 * Second stage of recovery:
		 * - showing a change password form
		 * - changing password
		 * - sending password by email
		 */
		//user clicked link in e-mail
		$this->api->stickyGET('rp');
		$this->api->stickyGET('key');
		$id=$_GET['rp'];
		$key=$_GET['key'];
   		$row=$this->api->db->getHash("select * from ".DTP.$this->api->getConfig('auth/pwd_recovery/table').
				" where id=$id and changed=0");
		//looking for the key in DB and checking expiration dts
   		$db_key=($row['id'])?sha1($row['id'].$row['email'].strtotime($row['expire'])):'';
   		$can_change=$db_key==$key&&strtotime($row['expire'])>time();
		if($can_change){
			//displaying changepass form
			$username=$this->api->db->dsql()->table($this->owner->dq->args['table'])
				->field($this->owner->name_field)
				->where('id',$row['user_id'])
				->do_getOne();

			$form=$this->page->frame('Content', "Change password for $username")->add('Form', null, 'content');
			$form
				->addField('hidden', 'rp_id')
				->addField('password', 'password', 'Enter new password')
					->validateField('strlen($this->get())>=6', 'Password is too short')
				->addField('password', 'password2', 'Confirm new password')
					->validateField('$this->get()==$this->owner->get(\'password\')',
					'Confirmation differs from password')
					->addField('checkbox', 'send', 'Send me new password by e-mail')
			;
			if(isset($_REQUEST['rp']))$form->set('rp_id', $_REQUEST['rp']);
			if($form->isSubmitted()){
				//getting a user id
				$user_id=$this->api->db->dsql()->table($this->api->getConfig('auth/pwd_recovery/table'))
					->field('user_id')
					->where('id',$form->get('rp_id'))
					->do_getOne();
				//changing password
				$this->api->db->dsql()->table($this->owner->dq->args['table'])
					->set($this->owner->pass_field,$this->owner->encrypt($form->get('password')))
					->where('id',$user_id)
					->do_update();
				//storing info about changes
				$this->api->db->dsql()->table($this->api->getConfig('auth/pwd_recovery/table'))
					->set('changed',1)
					->setDate('changed_dts')
					->where('id',$form->get('rp_id'))
					->do_update();
				$form->addComment("<b>Password changed succefully</b>");
				if($form->get('send')=='Y')$this->sendPassword($user_id, $form->get('password'));
				unset($this->api->sticky_get_arguments['rp']);
				unset($this->api->sticky_get_arguments['key']);
			}else{
				$form
					->addSubmit('Change')
				;
			}
		}else{
			//denial page
			unset($this->api->sticky_get_arguments['rp']);
			unset($this->api->sticky_get_arguments['key']);
			$this->page->frame('Content', 'Request error')->add('Text', null, 'content')
				->set("Sorry, this page is not valid. Activation period might have been expired." .
				" <a href=".
				$this->api->getDestinationURL($this->api->getConfig('auth/pwd_recovery/page')).
				">Click here</a> if You want to repeat Your request.");
		}
		$this->render();
	}
	function render(){
		/**
		 * Rendering only self-constructed objects and exit
		 */
		$this->page->add('Text', 'back', 'Content')->set("<div align=center><a href=".
			$this->api->getDestinationURL($this->api->getConfig('auth/login_page')).">Back to login</a></div>");
		$this->page->downCall('render');
		echo $this->page->template->render();
		exit;
	}
	protected function sendPassword($user_id, $password){
		/**
		 * Sends changed password to user
		 */
		$this->getMailer()->loadTemplate($this->api->getConfig('auth/mail/pwd_recovery_pwd'));
		$address=$this->api->db->dsql()->table($this->owner->dq->args['table'])
			->field($this->owner->email_field)
			->where('id',$user_id)
			->do_getOne();
		$username=$this->api->db->dsql()->table($this->owner->dq->args['table'])
			->field($this->owner->name_field)
			->where('id',$user_id)
			->do_getOne();
		//combining a message
		$this->getMailer()->setTag('username',$username);
		$this->getMailer()->setTag('password',$password);
		$this->getMailer()->send($address);
	}
	function getChangeLink($id,$address,$expire){
		return $this->owner->getServerName(true).dirname($_SERVER['PHP_SELF'])."/".
				$this->api->getDestinationURL(null, array('rp'=>$id, 'key'=>sha1($id.$address.$expire)));
	}
	protected function sendChangeLink($user_id, $username, $address){
		//adding a DB record with a key to a change password page
		$table=DTP.$this->api->getConfig('auth/pwd_recovery/table');
		$expire=time()+$this->api->getConfig('auth/pwd_recovery/timeout', 15)*60;
		$id=$this->api->db->dsql()->table($table)
			->set('user_id',$user_id)
			->set('email',$address)
			->setDate('expire',$expire)
			->do_insert();
		//combining a message
		$link=$this->getChangeLink($id,$address,$expire);

		$this->getMailer()->loadTemplate($this->api->getConfig('auth/mail/pwd_recovery_link'));
		$address=$this->api->db->dsql()->table($this->owner->dq->args['table'])
			->field($this->owner->email_field)
			->where('id',$user_id)
			->do_getOne();
		//combining a message
//		$this->getMailer()->setTag('link',$link);
		$this->getMailer()->setTag('link',$link);
		$this->getMailer()->setTag('username',$username);
		$this->getMailer()->setTag('timeout',$this->api->getConfig('auth/pwd_recovery/timeout', 15));
		$this->getMailer()->send($address);
	}
	function getMailer(){
		if(!$this->mailer)$this->mailer=$this->add('TMail');
		return $this->mailer;
	}
}
