<?php
/***********************************************************
  Upgrade Checker class. Will check agiletoolkit.org for new
  Agile Toolkit releases

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
        if(!$this->api->template->is_set('js_include'))return;  // no support in templtae
        $this->set($v=$this->api->getVersion());
        if($v[0]!=4)return;     // probably not ATK version

        if(isset($_COOKIE[$this->name.'_'.str_replace('.','_',$v)]))return;

        $this->api->template->appendHTML('js_include',
                '<script async="true" onload="try{ atk4_version_check(\''.$this->name.
            '\'); } catch(e){ }" type="text/javascript" src="http://agiletoolkit.org/upgrade_check/'.
                $this->api->getVersion().'.js"></script>'."\n");
    }
}
