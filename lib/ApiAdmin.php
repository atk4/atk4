<?php
class ApiAdmin extends ApiWeb {
    public $info_messages = array();

    public $ns=null;            // current namespace object or null if none

    public $not_html=false;     // this is true if output is NOT html. It might be javascript ajax response or redirect
    function __construct($realm=null,$skin='kt2'){
        parent::__construct($realm,$skin);
        if(!$this->ns)$this->hook('init-namespaces');
    }
    function init(){
        parent::init();

        $this->initializeTemplate();
    }

    /////////////// H e l p e r   f u n c t i o n s ///////////////
    function getDestinationURL($page=null,$args=array()){
        /**
         * Construct URL for getting to page
         */

        // If first argument is null, stay on the same page
        if(is_null($page)||$page=='')$page=$this->page;

        /*
         *
         * TODO: this should be implemented php friendly. Php sets cookie php_sess or something,
         * if that cookie is not available, we need to add using stickyGET. we shouldn't
         * modify this method.
         * from camper: without this line user cannot login with cookies disabled. i think until
         * this problem solved another way - we need to have this line to avoid "strange" bugs
         */
        // checking whether cookies are enabled and, if no, including SID
        if(!$_COOKIE[$this->name])$args=array_merge($args, array($this->name=>session_id()));

        if($this->ns){
            if(substr($page,0,1)===';'){
                // Going to main namespace
                $page=substr($page,1);
            }elseif(strpos($page,';')!==false){
                // Going to some other namespace
            }else{
                // Staying in this namespace
                $page=$this->ns->short_name.';'.$page;
            }
        }else{
            if(substr($page,0,1)===';'){
                // Going to main namespace
                $page=substr($page,1);
            }
        }
        return parent::getDestinationURL($page,$args);
    }
    function initDefaults(){
		ApiCLI::initDefaults(); // DTP constant checked
        if(strpos($_GET['page'],';')!==false){
            list($namespace,$_GET['page'])=explode(';',$_GET['page']);
            if(!isset($this->namespaces[$namespace])){
                throw new BaseException('Specified namespace ('.$namespace.') can\'t be found');
                // it's also
            }
            $this->ns=$this->namespaces[$namespace];
            $this->ns->initLayout();
        }else{
        	// do nothing!
        	//$this->initLayout();
        }
    }
    function initLayout(){
        // This function adds layout of how the webpage looks like. It should be initializing
        // content of the page, sidebars and any other elements on the page. Different
        // content pages are handled by page_*
        return $this
            ->addLayout('Content')
            ->addLayout('Menu')
            ->addLayout('LeftSidebar')
            ->addLayout('RightSidebar')
            ->addLayout('InfoWindow')
            ;
    }
    function layout_LeftSidebar(){
        $this->template->del('LeftSidebar');
    }
    function layout_RightSidebar(){
        $this->template->del('RightSidebar');
    }
    function layout_InfoWindow(){
        $this->add('InfoWindow',null,'InfoWindow');//,'InfoWindow');
    }

    function addNamespace($nm,$name=null){
        include_once($nm.'/'.$nm.'.php');
        $this->add($nm,$name?$name:$nm);
    }

    function outputInfo($msg){
        if($this->isAjaxOutput()){
            $this->ajax()->displayAlert($msg)->execute();
        }else{
            $this->info_messages[]=array('no'=>count($this->info_messages),'content'=>htmlspecialchars($msg),'backtrace'=>debug_backtrace());
        }
    }

    function render(){
        if($this->ns)return;    // it already puts something on our page
        return parent::render();
    }
    function outputFatal($name,$shift=0){
        $this->hook('output-fatal',array($name,$shift+1));
        throw new BaseException("Fatal: ".$name,'fatal',$shift+1);
    }
}
?>
