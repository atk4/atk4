<?
/*
 * jQuery UI is an interface to jQueryUI widgets. 

 * by romans
 */
class jUI_widget extends AbstractController {
    private $actitve=false;
    function init(){
        parent::init();
        $this->api->jui
            ->addInclude('ui.atk4_'.basename($this->short_name))
            ;
    }
    function activate($tag=null){
        if($this->active)return;
        if(!$tag)$tag=".".$this->short_name;
        $this->api->jui->addOnReady('$("'.$tag.'").atk4_'.$this->short_name.'()');
        $this->active=true;
    }
}
class jUI extends AbstractController {
    public $dir=null;
    private $theme=false;

    function init(){
        parent::init();

        $this->api->jui=$this;
        $this->js_dir=$this->api->getConfig('js/dir','amodules3/templates/js');
        $this->css_dir=$this->api->getConfig('css/dir','amodules3/templates/css');

        // default theme. Change with $jui->setTheme();
        $this->theme=$this->css_dir.'/smoothness';


        if(!$this->api->template->is_set('js_include'))
            throw new BaseException('Tag js_include must be defined in shared.html');
        if(!$this->api->template->is_set('document_ready'))
            throw new BaseException('Tag document_ready must be defined in shared.html');


        $this->api->template->del('js_include');


        $this->addInclude('jquery-1.3.2.min');
        $this->addInclude('jquery-ui-1.7.1.custom.min');

        // temporarily for compatibility
        $this->addInclude('jam3');
        $this->addInclude('jquery.form');

        // Controllers are not rendered, but we need to do some stuff manually
        $this->api->addHook('pre-render-output',array($this,'postRender'));
    }
    function addInclude($file){
        $this->api->template->append('js_include',
                '<script type="text/javascript" src="'.$this->js_dir.'/'.$file.'.js"></script>'."\n");
        return $this;
    }
    function addOnReady($js){
        $this->api->template->append('document_ready', '    '.$js.";\n");
        return $this;
    }
    function addWidget($name){
        // if we can we should load jUI_widget_name <-- TODO
        return $this->add('jUI_widget',$name);
    }
    function setTheme($theme){
        $this->theme=$theme;
        return $this;
    }
    function postRender(){
        //echo nl2br(htmlspecialchars("Dump: \n".$this->api->template->renderRegion($this->api->template->tags['js_include'])));

        $this->api->template->append('js_include',
                '<link type="text/css" href="'.$this->theme.'/jquery-ui-theme.css" rel="stylesheet" />'."\n");

    }
}
