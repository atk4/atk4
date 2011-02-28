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
 * Created on 09.03.2006 by *Camper*
 */
class Form_RegistrationData extends Form{
	private $is_update = false;
	public $id;
	
	function init(){
		parent::init();
		$this
			->addField('line', 'name', 'Your login')
			->addField('password', 'password', 'Your password')
			->addField('password', 'password2', 'Confirm password')
			->addField('line', 'first_name', 'First name')->validateNotNull()
			->addField('line', 'last_name', 'Last Name')->validateNotNull()
			->addField('line', 'email', 'E-Mail')->validateNotNull()
			
			->addSubmit('Submit')
			
			->setSource('user')
			->addCondition('id', $this->api->getUserId());
		;
		unset($this->dq->args['fields'][3]);
		/*unset($this->dq->args['fields']);
		$this->dq->field(array('name', 'password', 'first_name', 'last_name', 'email', 'country_id',
			'region', 'city', 'zip', 'address'));*/
		if($this->isUpdate())$this->data['password2'] = $this->data['password'];
		$this->elements['password']->addHook('validate', array($this, 'validatePassword'));
		$this->elements['password2']->addHook('validate', array($this, 'validatePassword2'));
	}
	function loadData(){
		parent::loadData();
		$this->data['password2'] = $this->data['password'];
	}
		
	function validateLogin(){
		if($this->isUpdate())return true;
		
		if(trim($this->data['name']) == ''){
			$this->elements['name']->displayFieldError('Username should NOT be blank!');
			return false;
		}
		$nc = $this->api->db->getOne("select count(*) from user where name = '".
			$this->data['name']."'");
		if($nc > 0)$this->elements['name']->displayFieldError('User with this name ' .
				'already exists. Please choose another one.');
	}
	function validatePassword(){
		//TODO validate password
	}
	function validatePassword2(){
	}
	function validateZIP(){
		if($this->data['zip'] == '')
			$this->elements['zip']->displayFieldError('Please enter ZIP code');
	}
	function isUpdate(){
		$auth = $this->api->recall('auth_data', null);
		if(isset($auth))return $auth['authenticated'];
		return false;
	}
	function submitted(){
		if(!parent::submitted())return true;
		unset($this->data['password2']);
		//crypting password
		if(!$this->isUpdate())$this->data['password'] = sha1($this->data['password']);
		else{
			if($this->data['password'] != $this->api->recall('oldpassword', ''))
				$this->data['password'] = sha1($this->data['password']);
		}
		if(!$adv_id = $this->update())throw new BaseException("Cannot save record");
		//we need to initialize data
		$this->api->memorize('auth_data', 
			array('name'=>$this->data['name'], 'password'=>$this->data['password']));
		//$this->api->redirect('PersonalSettings');
	}
}

?>
