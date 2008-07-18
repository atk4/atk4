<?php
class ApiAdmin extends ApiWeb {
    public $page_title = null;

    public $skin = null;      // skin used to render everything
    public $info_messages = array();

    public $apinfo=array();

    public $ns=null;            // current namespace object or null if none

    public $not_html=false;     // this is true if output is NOT html. It might be javascript ajax response or redirect
    function __construct($realm=null,$skin='kt2'){
        $this->skin=$skin;
        parent::__construct($realm);
        if(!$this->ns)$this->hook('init-namespaces');
    }
    function init(){
        parent::init();


        $this->initializeTemplate();
        $this->addHook('api-defaults',array($this,'initDefaults'));
    }
    function defaultTemplate(){
        return array('shared','_top');
    }

    function dbConnect($dsn=null){
        include_once'DBlite.php';
        if (is_null($dsn)) $dsn=$this->getConfig('dsn');
        $result = $this->db=DBlite::tryConnect($dsn);
        if(is_string($result))throw new DBlite_Exception($result,"Please edit 'config.php' file, where you can set your database connection properties",2);
    }

    /////////////// H e l p e r   f u n c t i o n s ///////////////
    function stickyGET($name){
        $this->sticky_get_arguments[$name]=$_GET[$name];
    }
    function stickyForget($name){
	unset($this->sticky_get_arguments[$name]);
    }
    function getDestinationURL($page=null,$args=array()){
        /**
         * Construct URL for getting to page
         */
        
        // If first argument is null, stay on the same page
        if(!isset($page))$page=$this->page;

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
        // Check sticky arguments. If argument value is true, 
        // GET is checked for actual value.
        if(isset($this->sticky_get_arguments)){
            foreach($this->sticky_get_arguments as $key=>$val){
                if($val===true){
                    if(isset($_GET[$key])){
                        $val=$_GET[$key];
                    }else{
                        continue;
                    }
                }
                if(!isset($args[$key])){
                    $args[$key]=$val;
                }
            }
        }

        // construct query string
        $tmp=array();
        foreach($args as $arg=>$val){
            if(!isset($val) || $val===false)continue;
            if(is_array($val)||is_object($val))$val=serialize($val);
            $tmp[]="$arg=".urlencode($val);
        }
        if($this->getConfig('url_prefix',false)){
            return $this->getConfig('url_prefix','').$page.($tmp?"&".join('&',$tmp):'');
        }else return $page.($tmp?"?".join('&',$tmp):'');
    }
    function redirect($page=null,$args=array()){
        /**
         * Redirect to specified page. $args are $_GET arguments.
         * Use this function instead of issuing header("Location") stuff
         */
        $this->api->not_html=true;
        header("Location: ".$this->getDestinationURL($page,$args));
        exit;
    }
    function isClicked($button_name){
        /**
         * Will return true if button with this name was clicked
         */
        return isset($_POST[$button_name])||isset($_POST[$button_name.'_x']);
    }
    function isAjaxOutput(){
        return isset($_POST['ajax_submit']);
    }


    function initDefaults(){
        if(!defined('DTP'))define('DTP','');

        if($_GET['page']=="")$_GET['page']='Index';
        if(strpos($_GET['page'],';')!==false){
            list($namespace,$_GET['page'])=explode(';',$_GET['page']);
            if(!isset($this->namespaces[$namespace])){
                throw new BaseException('Specified namespace ('.$namespace.') can\'t be found');
                // it's also 
            }
            $this->ns=$this->namespaces[$namespace];
            $this->page=$_GET['page'];
            $this->ns->initLayout();
        }else{
            $this->page=$_GET['page'];

            $this->initLayout();
        }
		$this->add('Logger');
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
    function addLayout($name){
        if(method_exists($this,$lfunc='layout_'.$name)){
            if($this->template->is_set($name)){
                $this->$lfunc();
            }
        }
        return $this;
    }
    function layout_Content(){
        // This function initializes content. Content is page-dependant

        if(method_exists($this,$pagefunc='page_'.$this->page)){
            $p=$this->add('Page',$this->page,'Content');
            $this->$pagefunc($p);
        }else{
            $this->add('page_'.$this->page,$this->page,'Content');
            //throw new BaseException("No such page: ".$this->page);
        }
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

    function page_Index($p){
        $p->add('LoremIpsum',null,'Content');
    }

    function addNamespace($nm,$name=null){
        include_once($nm.'/'.$nm.'.php');
        $this->add($nm,$name?$name:$nm);
    }

    function outputInfo($msg){
        if($this->isAjaxOutput()){
            $this->add('Ajax')->displayAlert($msg)->execute();
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
