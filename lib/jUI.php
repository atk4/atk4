<?php
/***********************************************************
  jQuery UI support

  Reference:
  http://agiletoolkit.org/doc/ref

==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
=====================================================ATK4=*/
class jUI extends jQuery {
    /*
       ATK4 system for javascript file management
     */
    public $dir=null;
    private $theme=false;

    private $atk4_initialised=false;

    function init(){

        parent::init();
        $this->api->jui=$this;

        $this->addDefaultIncludes();

        $this->atk4_initialised=true;
    }
    function addDefaultIncludes(){
        $this->addInclude('start-atk4');

        /* $config['js']['jquery']='http://code.jquery.com/jquery-1.8.2.min.js'; // to use CDN */
        if($v=$this->api->getConfig('js/versions/jqueryui',null))$v='jquery-ui-'.$v;
        else($v=$this->api->getConfig('js/jqueryui','jquery-ui-1.9.2.min'));  // bundled jQueryUI version

        $this->addInclude($v);

        $this->addInclude('ui.atk4_loader');
        $this->addInclude('ui.atk4_notify');
        $this->addInclude('atk4_univ');
    }
    function addInclude($file,$ext='.js'){
        if(strpos($file,'http')===0){
            parent::addOnReady('$.atk4.includeJS("'.$file.'")');
            return $this;
        }
        $url=$this->api->locateURL('js',$file.$ext);

        if(!$this->atk4_initialised){
            return parent::addInclude($file,$ext);
        }

        parent::addOnReady('$.atk4.includeJS("'.$url.'")');
        return $this;
    }
    function addStylesheet($file,$ext='.css',$template=false){
        $url=$this->api->locateURL('css',$file.$ext);
        if(!$this->atk4_initialised || $template){
            return parent::addStylesheet($file,$ext);
        }

        parent::addOnReady('$.atk4.includeCSS("'.$url.'")');
    }
    function addOnReady($js){
        if(is_object($js))$js=$js->getString();
        if(!$this->atk4_initialised){
            return parent::addOnReady($js);
        }

        $this->api->template->append('document_ready', "$.atk4(function(){ ".$js."; });\n");
        return $this;
    }
    function addWidget($name){
        // if we can we should load jUI_widget_name <-- TODO
        if(class_exists($n='jUI_widget_'.$name,false)){
            return $this->add('jUI_widget_'.$name,$name);
        }
        return $this->add('jUI_widget',$name);
    }
    function addStdWidget($name){
        // if we can we should load jUI_widget_name <-- TODO
        return $this->add('jUI_stdWidget',$name);
    }
}
