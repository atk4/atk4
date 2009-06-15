<?
/*
 * jQuery is an compatibility layer if jQuery UI is not used.

 * by romans
 */
class jQuery_plugin extends AbstractController {
    private $active=array();
    function init(){
        parent::init();
        $this->api->jquery
            ->addInclude(basename($this->short_name).'/jquery.'.basename($this->short_name))
            ->addStylesheet(basename($this->short_name).'/jquery.'.basename($this->short_name))
            ;
    }
    function activate($tag=null,$param=null){
        if($thdropdownis->active[$tag])return;
        if(!$tag)$tag=".".$this->short_name;
        $this->api->jquery->addOnReady($o='$("'.$tag.'").'.$this->prefix.$this->short_name.'('.($param?"{".$param."}":'').')');
        $this->active[$tag]=true;
    }
}
class jQuery extends AbstractController {
    public $dir=null;

    function init(){
        parent::init();

        $this->api->jquery=$this;
        $this->js_dir=$this->api->getConfig('js/dir','amodules3/templates/js');
        $this->css_dir=$this->api->getConfig('css/dir','amodules3/templates/css');

        if(!$this->api->template->is_set('js_include'))
            throw new BaseException('Tag js_include must be defined in shared.html');
        if(!$this->api->template->is_set('document_ready'))
            throw new BaseException('Tag document_ready must be defined in shared.html');


        $this->api->template->del('js_include');

        $this->addInclude('jquery-1.3.2.min');

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
    function addStylesheet($file){
        $this->api->template->append('js_include',
                '<link type="text/css" href="'.$this->js_dir.'/'.$file.'.css" rel="stylesheet" />'."\n");
        return $this;
    }
    function addOnReady($js){
        if(is_object($js))$js=$js->getString();
        $this->api->template->append('document_ready', '    '.$js.";\n");
        return $this;
    }
    function addPlugin($name){
        return $this->add('jQuery_plugin',$name);
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
    }
}
