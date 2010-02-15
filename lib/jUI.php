<?
/*
 * jQuery UI is an interface to jQueryUI widgets. 

 * by romans
 */
class jUI_widget extends AbstractController {
    protected $active=array();
    protected $prefix='atk4_';
    protected $dir='';
    function init(){
        parent::init();
        $this->api->jui
            ->addInclude($this->dir.'ui.'.$this->prefix.basename($this->short_name))
            ;
    }
    function activate($tag=null,$param=null){
        if($this->active[$tag])return;
        if(!$tag)$tag=".".$this->short_name;
        $this->api->jui->addOnReady($o='$("'.$tag.'").'.$this->prefix.$this->short_name.'('.($param?
                        json_encode($param):'').')');
        $this->active[$tag]=true;
    }
}
class jUI_stdWidget extends jUI_widget {
    protected $prefix='';
    function init(){
        $this->dir=$this->short_name.'/';
        parent::init();
        $this->api->jui
            ->addStylesheet($this->dir.'ui.'.$this->prefix.basename($this->short_name));
    }
}
class jUI_widget_datepicker extends jUI_widget {
    protected $prefix='';
    function activate($tag=null,$param=null){
        $this->api->jui->addOnReady($o='$("'.$tag.'").datepicker('.($param?"{".($param)."}":'').')');
    }
}
class jUI_widget_todo extends jUI_widget {
    function init(){
        parent::init();
        $this->api->template->append('Content','<div class="todo_frame" title="TODO list"></div>');
    }
}
class jUI extends jQuery {
    /*
        ATK4 system for javascript file management
      */
    public $dir=null;
    private $theme=false;

    private $atk4_initialised=false;

    function init(){
        $this->js_dir=$this->api->getConfig('js/dir','templates/js');
        $this->css_dir=$this->api->getConfig('css/dir','templates/css');


        parent::init();
        $this->api->jui=$this;




        // default theme. Change with $jui->setTheme();
        $this->theme=$this->css_dir.'/smoothness';

        $this->addInclude('start-atk4');
        $this->addInclude('jquery-ui-'.$this->api->getConfig('js/versions/jqueryui','1.7.1.custom.min'));

        $this->atk4_initialised=true;

    }
    function addInclude($file,$ext='.js'){
        $try=array();
        if(file_exists($try[]=BASEDIR.'/'.($relative_path=$this->js_dir.'/'.$file).$ext)){}   // do nothing, relative_path is set
        elseif(file_exists($try[]=AMODULES3_DIR.'/'.($relative_path=$this->js_dir.'/'.$file).$ext))$relative_path=basename(AMODULES3_DIR).'/'.$relative_path;
        elseif(file_exists($try[]=BASEDIR.'/'.($relative_path=$file).$ext));
        else throw new BaseException("Can't find ($file$ext) (tried: ".join(', ',$try).")");


        if(!$this->atk4_initialised){
			$this->api->template->append('js_include',
                '<script type="text/javascript" src="'.$this->api->getBaseURL().$relative_path.'.js"></script>'."\n");
			return $this;
        }

        parent::addOnReady('$.atk4.includeJS("'.$relative_path.$ext.'")');
        return $this;
    }
    function addStylesheet($file,$ext='.css'){
        if(file_exists($try[]=BASEDIR.'/'.($relative_path=$this->js_dir.'/'.$file).$ext)){}   // do nothing, relative_path is set
        elseif(file_exists($try[]=AMODULES3_DIR.'/'.($relative_path=$this->js_dir.'/'.$file).$ext))$relative_path=basename(AMODULES3_DIR).'/'.$relative_path;
        elseif(file_exists($try[]=BASEDIR.'/'.($relative_path=$file).$ext));
        else throw new BaseException("Can't find ($file$ext) (tried: ".join(', ',$try).")");


        parent::addOnReady('$.atk4.includeCSS("'.$relative_path.$ext.'")');
    }
    function addOnReady($js){
        if(is_object($js))$js=$js->getString();
        if(!$this->atk4_initialised){
            return parent::addOnReady($js);
        }

        $this->api->template->append('document_ready', '$.atk4(function(){ '.$js."; });\n");
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
    function setTheme($theme){
        $this->theme=$theme;
        return $this;
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
    function postRender(){
        //echo nl2br(htmlspecialchars("Dump: \n".$this->api->template->renderRegion($this->api->template->tags['js_include'])));

        $this->api->template->append('js_include',
                '<link type="text/css" href="'.$this->api->getBaseURL().$this->theme.'/jquery-ui-theme.css" rel="stylesheet" />'."\n");

    }
}
