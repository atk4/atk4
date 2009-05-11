<?
/*
 * jQuery UI is an interface to jQueryUI widgets. 

 * by romans
 */
class jUI_widget extends AbstractController {
    private $active=array();
    function init(){
        parent::init();
        $this->api->jui
            ->addInclude('ui.atk4_'.basename($this->short_name))
            ;
    }
    function activate($tag=null,$param=null){
        if($this->active[$tag])return;
        if(!$tag)$tag=".".$this->short_name;
        $this->api->jui->addOnReady('$("'.$tag.'").atk4_'.$this->short_name.'('.($param?"{".addslashes($param)."}":'').')');
        $this->active[$tag]=true;
    }
}
class jUI_widget_todo extends jUI_widget {
    function init(){
        parent::init();
        $this->api->template->append('Content','<div class="todo_frame" title="TODO list"></div>');
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
        $this->api->addHook('cut-output',array($this,'cutRender'));
    }
    function addInclude($file){
        $this->api->template->append('js_include',
                '<script type="text/javascript" src="'.$this->js_dir.'/'.$file.'.js"></script>'."\n");
        return $this;
    }
    function addOnReady($js){
        if(is_object($js))$js=$js->getString();
        $this->api->template->append('document_ready', '    '.$js.";\n");
        return $this;
    }
    function addWidget($name){
        // if we can we should load jUI_widget_name <-- TODO
        if(class_exists('jUI_widget_'.$name,false)){
            return $this->add('jUI_widget_'.$name,$name);
        }
        return $this->add('jUI_widget',$name);
    }
    function setTheme($theme){
        $this->theme=$theme;
        return $this;
    }
    function cutRender(){
        $x=$this->api->template->get('document_ready');
        $this->logVar($x);
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
    function postRender(){
        //echo nl2br(htmlspecialchars("Dump: \n".$this->api->template->renderRegion($this->api->template->tags['js_include'])));

        $this->api->template->append('js_include',
                '<link type="text/css" href="'.$this->theme.'/jquery-ui-theme.css" rel="stylesheet" />'."\n");

    }
}
