<?php
/***********************************************************
  Implements basic interface to jQuery

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
/*
 * jQuery is an compatibility layer if jQuery UI is not used.

 * by romans
 */
class jQuery extends AbstractController {
    private $chains=0;

    public $included=array();

    public $chain_class='jQuery_Chain';

    function init(){
        parent::init();

        $this->api->jquery=$this;

        if(!$this->api->template->is_set('js_include'))
            throw $this->exception('Tag js_include must be defined in shared.html');
        if(!$this->api->template->is_set('document_ready'))
            throw $this->exception('Tag document_ready must be defined in shared.html');


        $this->api->template->del('js_include');

        /* $config['js']['jquery']='http://code.jquery.com/jquery-1.8.2.min.js'; // to use CDN */
        if($v=$this->api->getConfig('js/versions/jquery',null))$v='jquery-'.$v;
        else($v=$this->api->getConfig('js/jquery','jquery-1.8.3.min'));   // bundled jQuery version

        $this->addInclude($v);

        // Controllers are not rendered, but we need to do some stuff manually
        $this->api->addHook('pre-render-output',array($this,'postRender'));
        $this->api->addHook('cut-output',array($this,'cutRender'));
    }
    /* Locate javascript file and add it to HTML's head section */
    function addInclude($file,$ext='.js'){
        return $this->addStaticInclude($file,$ext);
    }
    function addStaticInclude($file,$ext='.js'){
        if(@$this->included['js-'.$file.$ext]++)return;

        if(strpos($file,'http')!==0){
            $url=$this->api->locateURL('js',$file.$ext);
        }else $url=$file;

        $this->api->template->appendHTML('js_include',
                '<script type="text/javascript" src="'.$url.'"></script>'."\n");
        return $this;
    }
    /* Locate stylesheet file and add it to HTML's head section */
    function addStylesheet($file,$ext='.css',$locate='css'){
        return $this->addStaticStylesheet($file,$ext,$locate);
    }
    function addStaticStylesheet($file,$ext='.css',$locate='css'){
        //$file=$this->api->locateURL('css',$file.$ext);
        if(@$this->included[$locate.'-'.$file.$ext]++)return;

        $this->api->template->appendHTML('js_include',
                '<link type="text/css" href="'.$this->api->locateURL($locate,$file.$ext).'" rel="stylesheet" />'."\n");
        return $this;
    }
    /* Add custom code into onReady section. Will be executed under $(function(){ .. }) */
    function addOnReady($js){
        if(is_object($js))$js=$js->getString();
        $this->api->template->appendHTML('document_ready', $js.";\n");
        return $this;
    }
    /* [private] use $object->js() instead */
    function chain($object){
        if(!is_object($object))throw new BaseException("Specify \$this as argument if you call chain()");
        return $object->add($this->chain_class);
    }
    /* [private] When partial render is done, this function includes JS for rendered region */
    function cutRender(){
        $x=$this->api->template->get('document_ready');
        if(is_array($x))$x=join('',$x);
        if(!empty($x)) echo '<script type="text/javascript">'.$x.'</script>';
        return;
    }
    /* [private] .. ? */
    function postRender(){
        //echo nl2br(htmlspecialchars("Dump: \n".$this->api->template->renderRegion($this->api->template->tags['js_include'])));
    }
    /* [private] Collect JavaScript chains from specified object and add them into onReady section */
    function getJS($obj){

        $r='';
        foreach($obj->js as $key=>$chains){
            $o='';
            foreach($chains as $chain){
                $o.=$chain->_render().";\n";
            }
            switch($key){
                case 'never':
                    // send into debug output
                    //if(strlen($o)>2)$this->addOnReady("if(console)console.log('Element','".$obj->name."','no action:','".str_replace("\n",'',addslashes($o))."')");
                    continue;

                case 'always':
                    $r.=$o;
                    break;
                default:
                    $o='';
                    foreach($chains as $chain){
                        $o.=$chain->_enclose($key)->_render().";\n";
                    }
                    $r.=$o;
            }
        }
        if($r)$this->addOnReady($r);
    }
}
