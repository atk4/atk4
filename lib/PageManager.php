<?php
class PageManager extends AbstractController {
    /*
     * This is a generic page manager. For web applications it calculates
     * base URI, sets up path manager with the URI locations, determines
     * which page was requested
     *
     * This class works with PathFinder, ApiWeb, and Location.
     */


    // you can access variabless below through $this->api->pm->base_url
    // concatinate them to get full URL

    public $base_url;           // http://yoursite.com:81
    /*
       Base URL defines the absolute destination of our server. Because some
       other resources may be located outside of our Base Path, we need to
       know a Base URL.

       For CLI scripts, you need to set this manually. Also if you are
       going to use URLs in emails, you should use this.

       See also: URL::useAbsoluteURL();
     */

    public $base_path;          // /admin/
    /*
       Base PATH points to the top location of our project. Basically it's
       where the project is installed in the webroot. This is determined
       by thelocation of catch-all file. It is determined by SCRIPT_NAME
       which should be supported by most web installations. It will also
       work when mod_rewrite is not used.

       You can use $base_path in your script to put it say on a logo link

       Also - some other parts of the library may have a different path,
       for example base_path could be = /admin/, and atk4_path could be /amodules/

       If project is installed in web-root, then $base_path will be "/"

       path always starts and ends with slash
     */

    public $page;               // user/add
    /*
       This is a third and a final part of the URLs. This points to a page
       which were reuqested. You can pass path to getDestinationURL() function,
       as a first argument. Also $path is used to determine which page class
       to load.

       Page must never start with slash. Also if path is empty, then
       the "index" is used automatically.


     */

    public $base_directory;     // /home/web/admin/ - physical path
    public $template_filename;

    function init(){
        parent::init();
        $this->page=&$this->api->page;      // link both variables

        $this->api->pm=$this;
        // Firstly, the original URL is retrieved. This function should
        // take care of all possible rewrite engines and bring up a real
        // URL which matches the one in the browser. Also e will need to
        // determine a relative path for the requested page
        $this->parseRequestedURL();

        // This function will continue initialization of the page itself.
        $this->calculatePageName();
    }

    function calculatePageName(){
        // Now. We need to decide what will be the main page to
        // to display the whole thing. This page will contain a
        // main template and will be responsible for rendering the
        // whole page. This function will initialize the object and
        // return it

        // Lastly we need a sub-class, which would worry only about
        // the requested page. This is what we call - a page.
    }

    function parseRequestedURL(){
        $this->base_path=unix_dirname($_SERVER['SCRIPT_NAME']);
        // for windows
        if(substr($this->base_path,-1)=='\\')$this->base_path=substr($this->base_path,1,-1).'/';
        if(substr($this->base_path,-1)!='/')$this->base_path.='/';

        // We are assuming that all requests are being redirected thorugh a single file
        $this->base_directory=unix_dirname($_SERVER['SCRIPT_FILENAME']).'/';

        // This is the re-constructions of teh proper URL.
        // 1. Schema
        $url=$this->api->getConfig('atk/base_url',null);
        if(is_null($url)){
            // Detect it
            $url = 'http';
            $https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || $_SERVER['SERVER_PORT']==443;
            if($https)$url.='s';

            // 2. Continue building. We are adding hostname next and port.
            $url .= "://".$_SERVER["SERVER_NAME"];
            //if($_SERVER["SERVER_PORT"]!="80")$url .= ":".$_SERVER['SERVER_PORT'];
            if(($_SERVER["SERVER_PORT"]=="80" && !$https ) || ($_SERVER["SERVER_PORT"]=="443" && $https)){
                ;

            }else{
                $url .= ":".$_SERVER['SERVER_PORT'];
            }
        }

        // We have now arrived at base_url as defined
        $this->base_url=$url;

        // 3. Next we need a base_part of our URL. There are many different
        // variables and approaches we tried it, REDIRECT_URL_ROOT, REDIRECT_URL,
        // etc, however most reliable is unix_dirname(SCRIPT_NAME)
        $path=unix_dirname($_SERVER['SCRIPT_NAME']);
        if(substr($path,-1)!='/')$path.='/';

        // We have now arrived at base_path as defined
        $this->base_path=$path;

        // 4. We now look at RequestURI and extract base_path from the beggining
        if(isset($_GET['page'])){
            $page=$_GET['page'];
        }else{
            $request_uri=$this->getRequestURI();
            if($path != substr($request_uri,0,strlen($path))){
                throw new BaseException("URL matching problem. RequestURI=$request_uri should start with BasePath=$path");
            }
            $page=substr($request_uri,strlen($path));
            if(!$page)$page='index';

            // Remove postfix from page if any
            $page=preg_replace('/\..*$/','',$page);
            $page=preg_replace('/\/$/','',$page);
            $page=str_replace('/','_',$page);

            if(substr($page,-1,1)=='_')$page=substr($page,0,-1);
        }

        if(strpos($page,'.')!==false)throw new BaseException("Failed to determine page name. Page name may not contain ".
                "periods (.) page=".$page);

        // We have now arrived at the page as per specification.
        $this->page=str_replace('/','_',$page);

        $this->template_filename=$this->page;
        if(substr($this->template_filename,-1)=='/')$this->template_filename.="index";

        $this->api->pathfinder->base_location->setBaseURL($this->base_path);

        $this->debug("base_path=".$this->base_path);
        $this->debug("base_directory=".$this->base_directory);
        $this->debug("page=".$this->page);
        $this->debug("api/page=".$this->api->page);
        $this->debug("template_filename=".$this->template_filename);
    }
    function getRequestURI(){
        // WARNING. This function URI excludes query string

        if (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // IIS
            $request_uri = $_SERVER['HTTP_X_REWRITE_URL'];
        } elseif (isset($_SERVER['REQUEST_URI'])) { // Apache
            $request_uri = $_SERVER['REQUEST_URI'];
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0, PHP as CGI
            $request_uri = $_SERVER['ORIG_PATH_INFO'];
            // This one comes without QUERRY string
        } else {
            throw new BaseException('Unable to determine RequestURI. This shouldnt be called at all in CLI');
        }
        $request_uri=explode('?',$request_uri,2);
        return $request_uri[0];
    }

    function getUrlRoot(){
        if($r=='')$r='/';
        return $r;
    }
    function getDestinationURL($page){
        if($page[0]=='/'){
            // Location absolute
            return $this->base_path.substr($page,1).'.html';
        }
        return $page.'.html';
    }
}
