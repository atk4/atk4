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
 * Login form for auth classes.
 *
 * Created on 31.05.2007 by *Camper* (camper@adevel.com)
 */
class Auth_Form extends Form{
	function init(){
		parent::init();
		///$this->owner->owner->template->set('page_title','Login');
		$this->addSeparator('Authentication is required');
		$this->addField('Line','username','Login');
		$this->addField('Password','password','Password');

		$this->addField('Checkbox','memorize','Remember me');
		$this->addComment('<div align="left"><font color="red">Security warning</font>: by ticking \'Remember me on this computer\'<br>you ' .
				'will no longer have to use a password to enter this site,<br>until you explicitly ' .
				'log out.</b></div><div style="display: none">' .
				'session is expired, relogin</div>');
		$this->addSubmit('Login');
	}
}
