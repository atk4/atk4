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
 * Created on 21.03.2006 by *Camper*
 */
class PageProject extends Page{
	function init(){
		parent::init();
		$this->add('Text', null, 'Content')
			->set("<p>This wizard will help you to import a project. " .
				"Enter project properties below and click 'Next' to continue.</p>"
			);
		$this->add('FormProjectProps', null, 'Content');
	}
}
class PageImport extends Page{
	function init(){
		parent::init();
		$this->add('Text', null, 'Content')
			->set("<p>Your project properies has been stored to DB. " .
				"Next phase is to import its structure from the local path.</p>" .
				"<div id=reload_progress>To start import click 'Import' button. You should" .
				" see a process progress on this line</div>"
			);
		$this->add('FormImport', null, 'Content');
	}
}
class FormImport extends Form{
	function init(){
		parent::init();
		$this->addSubmit('Import');
	}
	function submitted(){
	}
}
class page_AddProject extends Page{
	function init(){
		parent::init();
		$this->frame('Content', 'Properties')->add('Form_ProjectProps', null, 'content');
		/*
		$wizard = $this->frame('Content', 'Properties')
			->add('Wizard', null, 'content');
		$wizard
			->addPage('page1', 'Project import: step 1', 'PageProject')
			->addPage('page2', 'Project import: step 2', 'PageImport')
			->addPage('finish', 'Project import: finished')
		;*/
	}
}	
?>
