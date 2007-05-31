<?php
/**
 * Login form for auth classes.
 * 
 * Created on 31.05.2007 by *Camper* (camper@adevel.com)
 */
class Auth_Form extends Form{
	function init(){
		parent::init();
        $this->owner->owner->template->set('page_title','Login');
        $this
            ->addSeparator('Authentication is required')
            ->addField('Line','username','Login')
            ->addField('Password','password','Password')

            ->addField('Checkbox','memorize','Remember me')
            ->addComment('<div align="left"><font color="red">Security warning</font>: by ticking \'Remember me on this computer\'<br>you ' .
            		'will no longer have to use a password to enter this site,<br>until you explicitly ' .
            		'log out.</b></div>')
			
            ->addSubmit('Login');
        $this->onLoad()->setFormFocus($this,'username');
	}
}