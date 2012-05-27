<?php
/***********************************************************
  jQuery UI support

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
        $this->addInclude('jquery-ui-'.$this->api->getConfig('js/versions/jqueryui','1.9.0m8.min'));
        $this->addInclude('ui.atk4_loader');
        $this->addInclude('ui.atk4_notify');
        $this->addInclude('atk4_univ');
    }
    function addInclude($file,$ext='.js'){
        if(substr($file,0,4)=='http'){
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
        /*
           if(file_exists($try[]=BASEDIR.'/'.($relative_path=$this->js_dir.'/'.$file).$ext)){}   // do nothing, relative_path is set
           elseif(file_exists($try[]=AMODULES3_DIR.'/'.($relative_path=$this->js_dir.'/'.$file).$ext))$relative_path=basename(AMODULES3_DIR).'/'.$relative_path;
           elseif(file_exists($try[]=BASEDIR.'/'.($relative_path=$file).$ext));
           else throw new BaseException("Can't find ($file$ext) (tried: ".join(', ',$try).")");
         */
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
    function cutRender(){
        $x=$this->api->template->get('document_ready');
        if(is_array($x))$x=join('',$x);
        echo '<script type="text/javascript">'.$x.'</script>';
        return;
        echo "
            <script>
            $(function(){
                    ".$x."
                    });
        </script>

            ";
    }
}
