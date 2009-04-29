<?
/*
 * jQuery UI is an interface to jQueryUI widgets. 

 * by romans
 */
class jUI_widget extends AbstractController {
    function init(){
        parent::init();
        $this->api->jui
            ->addInclude('atk4_'.basename($this->short_name))
            ->addOnReady('$.widget("ui.atk4_expander", atk4_expander)')
            ;
    }
    function activate($tag=null){
        if(!$tag)$tag=".".$this->short_name;
        $this->api->jui->addOnReady('$("'.$tag.'").'.$this->short_name.'()');
    }
}
class jUI extends AbstractController {
    public $dir=null;

    function init(){
        parent::init();

        $this->api->jui=$this;
        $this->js_dir=$this->api->getConfig('js/dir','amodules3/templates/js');
        $this->css_dir=$this->api->getConfig('css/dir','amodules3/templates/js');


        if(!$this->api->template->is_set('js_include'))
            throw new BaseException('Tag js_include must be defined in shared.html');
        if(!$this->api->template->is_set('js_onready'))
            throw new BaseException('Tag js_onready must be defined in shared.html');

        $this->api->template->del('js_include');

        // jQueryUI skin load
        $this->api->template->append('js_include',
                //'<link type="text/css" href="css/smoothness/jquery-ui-1.7.1.custom.css" rel="stylesheet" />  

                '<script type="text/javascript" src="'.$this->dir.'/'.$file.'.js"></script>'."\n");
        return $this;


        $this->addInclude('jquery-1.3.2.min');
        $this->addInclude('jquery-ui.1.7.1.custom.min.js');

        // temporarily for compatibility
        $this->addInclude('jam3');
        $this->addInclude('jquery.form');

        // Controllers are not rendered, but we need to do some stuff manually
        $this->api->addHook('pre-render-output',array($this,'postRender'));
    }
    function addInclude($file){
        $this->api->template->append('js_include',
                '<script type="text/javascript" src="'.$this->dir.'/'.$file.'.js"></script>'."\n");
        return $this;
    }
    function addOnReady($js){
        $this->api->template->append('js_onready', '    '.$js.";\n");
        return $this;
    }
    function addWidget($name){
        // if we can we should load jUI_widget_name <-- TODO
        return $this->add('jUI_widget',$name);
    }
    function activate(){
    }
    function postRender(){
        //echo nl2br(htmlspecialchars("Dump: \n".$this->api->template->renderRegion($this->api->template->tags['js_include'])));


    }
}
