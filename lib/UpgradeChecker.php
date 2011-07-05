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
class UpgradeChecker extends HtmlElement {
    function init(){
        parent::init();
        $this->set($v=$this->api->getVersion());

        if(isset($_COOKIE[$this->name.'_'.str_replace('.','_',$v)]))return;

        //setcookie($this->name,1,time()+3600*24);
        
		$this->api->template->append('js_include',
		'<script aync="true" onload="atk4_version_check(\''.$this->name.
            '\')" type="text/javascript" src="http://agiletoolkit.org/upgrade_check/'.
        $this->api->getVersion().'.js"></script>'."\n");


    }
}
