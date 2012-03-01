<?php
/***********************************************************
  ..

  Reference:
  http://agiletoolkit.org/doc/ref

 **ATK4*****************************************************
 This file is part of Agile Toolkit 4 
 http://agiletoolkit.org

 (c) 2008-2011 Agile Technologies Ireland Limited
 Distributed under Affero General Public License v3

 If you are using this file in YOUR web software, you
 must make your make source code for YOUR web software
 public.

 See LICENSE.txt for more information

 You can obtain non-public copy of Agile Toolkit 4 at
 http://agiletoolkit.org/commercial

 *****************************************************ATK4**/
abstract class page_DefaultAbout extends Page {
	var $about_this;
	function init(){
		parent::init();
		$this->api->addHook('post-init',array($this,'aboutFramework'));
	}
	function aboutframework(){
		$msg=$this->add('Frame')->setTitle('About Agile Toolkit');
		$t=$msg->add('Html');
		$text="<p>This web application was developed using <a href=\"http://agiletoolkit.org/\">Agile Toolkit
			framework</a>. Agile Toolkit is licensed under Affero Gnu Public License. You may use it for free, but you
			must release software you have built on Agile Toolkit under AGPL license. Sharing your code gives a warm,
			     fuzzy feeling, but we ALL must share to make a world better.
				     </p><p>
				     If you are willing to use Agile Toolkit for commercial project, please visit 
				     <a href=\"http://agiletoolkit.org/commercial\">http://agiletoolkit.org/commercial</a> where you can obtain
				     commercial license, commercial support, training and consulting services. 
				     </p>
				     <h3>Agile Toolkit Authors</h3>
				     <p><a href=\"http://agiletech.ie\">Agile Technologies Limited</a> is a developer and copyright holder of
				     Agile Toolkit.
				     <a href=\"http://agiletoolkit.org/contact\">Contact us for more information</a>.
				     </p>
				     ";
		$t->set($text);
	}
}
