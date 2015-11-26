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
    private $atk4_initialised=false;

    function init(){

        parent::init();
        if (@$this->app->jui) {
            throw $this->exception('Do not add jUI twice');
        }
        $this->app->jui=$this;

        $this->addDefaultIncludes();

        $this->atk4_initialised=true;
    }
    function addDefaultIncludes(){
        $this->addInclude('start-atk4');

        /* $config['js']['jquery']='http://code.jquery.com/jquery-1.8.2.min.js'; // to use CDN */
        if($v=$this->app->getConfig('js/versions/jqueryui',null))$v='jquery-ui-'.$v;
        else($v=$this->app->getConfig('js/jqueryui','jquery-ui-1.11.beta2.min'));  // bundled jQueryUI version

        $this->addInclude($v);

        $this->addInclude('ui.atk4_loader');
        $this->addInclude('ui.atk4_notify');
        $this->addInclude('atk4_univ_basic');
        $this->addInclude('atk4_univ_jui');
    }
    function addInclude($file,$ext='.js'){
        if(strpos($file,'http')===0){
            parent::addOnReady('$.atk4.includeJS("'.$file.'")');
            return $this;
        }
        $url=$this->app->locateURL('js',$file.$ext);

        if(!$this->atk4_initialised){
            return parent::addInclude($file,$ext);
        }

        parent::addOnReady('$.atk4.includeJS("'.$url.'")');
        return $this;
    }
    function addStylesheet($file,$ext='.css',$template=false){
        $url=$this->app->locateURL('css',$file.$ext);
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

        $this->app->template->append('document_ready', "$.atk4(function(){ ".$js."; });\n");
        return $this;
    }
}
