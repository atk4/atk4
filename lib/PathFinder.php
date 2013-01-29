<?php
/**
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
=====================================================ATK4=*/
/**
  PathFinder is responsible for locating resources in Agile
  Toolkit. One of the most significant principles it implements
  is ability for any resource (PHP, JS, HTML, IMG) to be
  located under several locations. Pathfinder will look for
  the location containing the resource you ask for and will
  provide you with either a relative path, URL or absolute
  path to a file.

  To make this possible, PathFinder relies on a class
  PathFinder_Location, which describes each individual
  location and it's contents.

  You may add additional locations in your application,
  add-on or elsewhere. Some locations may be activated only
  in certain circumstances, for example add-on will be able
  to add it's location only if add-on have been added to
  a page.

  Here is a list of resource categories:
  php, page, addons, template, mail, js, css

  Resources which are planned or under development:
  dbupdates, logs, public

  @link http://agiletoolkit.org/pathfinder
*/
class PathFinder extends AbstractController
{
    /**
     * Base location is where your application files are located. Normally
     * this location is added first and all the requests are checked here
     * before elsewhere.
     */
    public $base_location=null;

    /**
     * Agile Toolkit comes with some assets: lib, js, template, css. This
     * location describes those resources.
     */
    public $atk_location=null;


    /** {@inheritdoc} */
    public $default_exception='Exception_PathFinder';

    /**
     * {@inheritdoc}
     */
    function init()
    {
        parent::init();
        $this->api->pathfinder=$this;

        // TODO: get rid of this, we don't use autoloader anymore
        $GLOBALS['atk_pathfinder']=$this;   // used by autoload

        $this->addDefaultLocations();
    }

    /**
     * Agile Toolkit-based application comes with a structure as described
     * in documentation. For new users it's easier if they see consistent
     * structure they are used to.
     *
     * As a more advanced developer (since you reading this text!) you
     * may know that it's possible to completely redefine locations
     * of resources to suit your own preferences. You might want to do
     * this if you are integrating with your existing application or
     * another framework or building multi-tiered project with extensive
     * structure.
     *
     * Usually it's enough to add additional resources by creating one or
     * both methods in your API class:
     *
     * * addDefaultLocations() - will be checked first (overrides)
     * *  base_location is checked after
     * * addSharedLocations() - add your secondary location (shared/lib)
     * *  atk_location is checked Here
     * * api->init() executes after where you can add more fall-back locations
     *
     * Add-ons would typically be added during init() so they would have low
     * precedence. PathPinder does not support priority handling.
     *
     * As each location must know both physical path and URL, for custom
     * configuration you will need to define both - base path and base url.
     *
     * By default the following things are assumed:
     *  * path of base location is same as the front-controller (index.php)
     *  * URL of base location is same as dirname(request url)
     *  * path to ATK location determined by 2 directories up from this file
     *  * URL of ATK location is ./atk4/ or $config['atk_location'], if set
     *
     * @return void
     */
    function addDefaultLocations()
    {
        $base_directory=dirname(@$_SERVER['SCRIPT_FILENAME']);

        // Compatibility with command-line
        if (!$base_directory) {
            $base_directory=realpath($GLOBALS['argv'][0]);
        }

        if ($this->api->hasMethod('addDefaultLocations')) {
            $this->api->addDefaultLocations($this, $base_directory);
        }

        $this->base_location=$this->addLocation('/', array(
            'php'=>'lib',
            'page'=>'page',
            'addons'=>'atk4-addons',
            'template'=>'templates/'.$this->api->skin,
            'mail'=>'templates/mail',
            'js'=>'templates/js',
            'logs'=>'logs',
            'dbupdates'=>'doc/dbupdates',
        ))->setBasePath($base_directory);


        if ($this->api->hasMethod('addSharedLocations')) {
            $this->api->addSharedLocations($this, $base_directory);
        }

        $atk_directory=dirname(dirname(__FILE__));
        $atk_url=basename($atk_directory);

        $this->atk_location=$this->addLocation('atk4', array(
            'php'=>'lib',
            'template'=>array(
                'templates/'.$this->api->skin,
                'templates/shared'
            ),
            'mail'=>'templates/mail',
            'js'=>'templates/js',
            'css'=>array(
                'templates/js',
                'templates/'.$this->api->skin.'/css',
                'templates/shared/css'
            ),
        ))
            ->setBasePath(dirname(dirname(__FILE__)))
            ->setBaseURL($this->api->getConfig('atk/base_path', './atk4/'))
            ;
    }

    /**
     * Cretes new PathFinder_Location object and specifies it's contents.
     * You can subsequentially add more contents by calling 
     *
     *   $location->defineContents
     *
     * @param string $path     UNCLEAR!!! TODO: FIX
     * @param array  $contents Array describing contents
     *
     * @return PathFinder_Location New Location
     */
    function addLocation($path, $contents=array())
    {
        $location=$this
            ->add('PathFinder_Location', $path)
            ->defineContents($contents)
            ;
        return $location;
    }

    /**
     * Search for a file inside multiple locations, associated with resource
     * $type. By default will return relative path, but 3rd argument can
     * change that
     *
     * @param string $type     Type of resource to search surch as "php"
     * @param string $filename Name of the file to search for
     * @param string $return   'relative','url','path' or 'location'
     *
     * @return string|object as specified by $return
     */
    function locate($type, $filename='', $return='relative')
    {
        $attempted_locations=array();
        foreach ($this->elements as $location) {
            if (!($location instanceof PathFinder_Location)) {
                continue;
            }

            $path=$location->locate($type, $filename, $return);

            if (is_string($path) || is_object($path)) {
                return $path;
            } elseif (is_array($path)) {
                $attempted_locations=array_merge($attempted_locations, $path);
            }
        }

        throw $this->exception('File not found')
            ->addMoreInfo('file', $filename)
            ->addMoreInfo('type', $type)
            ->addMoreInfo('attempted_locations', $attempted_locations)
            ;
    }
    function search($type,$filename='',$return='relative'){
        /*
           Similar to locate but returns array with all matches for the specified file
           in array
         */
        $matches=array();
        foreach($this->elements as $location){
            if(!($location instanceof PathFinder_Location))continue;

            $path=$location->locate($type,$filename,$return);

            if(is_string($path)){
                // file found!
                $matches[]=$path;
            }
        }
        return $matches;
    }
    function _searchDirFiles($dir,&$files,$prefix=''){
        $d=dir($dir);
        while(false !== ($file=$d->read())){
            if($file[0]=='.')continue;
            if(is_dir($dir.'/'.$file)){
                $this->_searchDirFiles($dir.'/'.$file,$files,$prefix.$file.'/');
            }else{
                $files[]=$prefix.$file;
            }
        }
        $d->close();
    }
    function searchDir($type,$directory=''){
        /*
           List all files inside particular directory
         */
        $dirs=$this->search($type,$directory,'path');
        $files=array();
        foreach($dirs as $dir){
            $this->_searchDirFiles($dir,$files);
        }
        return $files;
    }
    function loadClass($className){
        $origClassName = str_replace('-','',$className);

        /**/$this->api->pr->start('pathfinder/loadClass ');

        /**/$this->api->pr->next('pathfinder/loadClass/convertpath ');
        $className = ltrim($className, '\\');
        $nsPath = '';
        $namespace = '';
        if ($lastNsPos = strripos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $nsPath  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace);
        }
        $classPath = str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        /**/$this->api->pr->next('pathfinder/loadClass/locate ');
        try {
            if ($namespace){
                if (strpos($className,'page_')===0) {
                    $path=$this->api->locatePath(
                        'addons',
                        $nsPath.DIRECTORY_SEPARATOR.$classPath
                    );
                } else {
                    $path=$this->api->locatePath(
                        'addons',
                        $nsPath.DIRECTORY_SEPARATOR
                        .'lib'.DIRECTORY_SEPARATOR.$classPath
                    );
                }
            } else {
                if (strpos($className,'page_')===0) {
                    $path=$this->api->locatePath(
                        'page',
                        substr($classPath,5)
                    );
                } else {
                    $path=$this->api->locatePath(
                        'php',
                        $classPath
                    );
                }
            }
        }catch(PathFinder_Exception $e){
            $e
                ->addMoreInfo('class',$className)
                ->addMoreInfo('namespace',$namespace)
                ->addMoreInfo('orig_class',$origClassName)
                ;
            throw $e;
        }

        if(!is_readable($path)){
            throw new PathFinder_Exception('addon',$path,$prefix);
        }


        /**/$this->api->pr->next('pathfinder/loadClass/include ');
        /**/$this->api->pr->start('php parsing');
        include_once($path);
        /**/$this->api->pr->stop();
        if(!class_exists($origClassName ,false))throw $this->exception('Class is not defined in file')
            ->addMoreInfo('file',$path)
            ->addMoreInfo('class',$className);
        /**/$this->api->pr->stop();
    }


    /*
        list($namespace,$file)=explode('\\',$class_name);
        if (!$file && $namespace){
            $file = $namespace;
            $namespace=null;
        }else $class_name_nonn=$file;
        // Include class file directly, do not rely on auto-load functionality
        if(!class_exists($class_name,false) && isset($this->api->pathfinder) && $this->api->pathfinder){
            $file = str_replace('_',DIRECTORY_SEPARATOR,$file).'.php';
            if($namespace){
                if(strpos($class_name_nonn,'page_')===0){
                    $path=$this->api->locatePath('addons',$namespace.DIRECTORY_SEPARATOR.$file);
                }else{
                    $path=$this->api->locatePath('addons',$namespace.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.$file);
                }

                }
            }else{
                if(substr($class_name,0,5)=='page_'){
                    $path=$this->api->pathfinder->locate('page',substr($file,5),'path');
                }else $path=$this->api->pathfinder->locate('php',$file,'path');

            }

        }
     */
}
class PathFinder_Exception extends BaseException
{
}
class Exception_PathFinder extends Pathfinder_Exception
{
}
class PathFinder_Location extends AbstractModel {
    /*
       Represents a location, which contains number of sub-locations. Each
       of which may contain certain type of data
     */


    public $parent_location=null;

    public $contents=array();
    // contains list of 'type'=>'subdir' which lists all the
    // resources which can be found in this directory


    public $relative_path=null;
    // Path to relative file within this resource

    public $base_url=null;
    public $base_path=null;

    public $auto_track_element=true;


    function init(){
        parent::init();

        $this->relative_path=$this->short_name;

        if($this->short_name[0]=='/' || (strlen($this->short_name)>1 && $this->short_name[1]==':')){
            // Absolute path. Independent from base_location

        }else{
            $this->setParent($this->owner->base_location);
        }
    }
    function setRelativePath($path){
        $this->relative_path = $path;
        return $this;
    }
    function setParent($parent){
        $this->parent_location=$parent;
        return $this;
    }

    function __toString(){
        // this is our path
        $s=(isset($this->parent_location)?
                ((string)$this->parent_location):'');
        if($s && substr($s,-1)!='/' && $this->relative_path)$s.='/';
        $s.=$this->relative_path;
        return $s;
    }

    function getURL($file_path=null){
        // Returns how this location or file can be accessed through web
        // base url + relative path + file_path

        $url='';
        if($this->base_url)$url=$this->base_url;else
            if($this->parent_location){
                $url=$this->parent_location->getURL();
                if(substr($url,-1)!='/')$url.='/';
                $url.=$this->relative_path;
            }else
                throw new BaseException('Unable to determine URL');

        if($file_path){
            if(substr($url,-1)!='/')$url.='/';
            $url.=$file_path;
        }
        $url=str_replace(array('\\','/./','/./'),'/',$url);
        return $url;
    }

    function getPath($file_path=null){
        // Returns how this location or file can be accessed through filesystem

        $path='';
        if($this->base_path)$path=$this->base_path;else
            if($this->parent_location){
                $path=$this->parent_location->getPath();
                if(substr($path,-1)!='/')$path.='/';
                $path.=$this->relative_path;
            }else
                throw new BaseException('Unable to determine Path for '.$this.', parent='.$this->parent_location);

            if($file_path){
                if(substr($path,-1)!='/')$path.='/';
                $path.=$file_path;
            }
            return $path;
    }

    function setBaseURL($url){
        /*
           something like /my/app
         */
        $this->base_url=$url;
        return $this;
    }
    function setBasePath($path){
        /*
           something like /home/web/public_html
         */
        $this->base_path=$path;
        return $this;
    }
    function defineContents($contents){
        if($contents==='all'){
            $contents=array('all'=>'all');
        }
        if(is_string($contents)){
            $contents=array($contents=>'.');
        }
        $this->contents=array_merge_recursive($this->contents,$contents);
        return $this;
    }

    function locate($type,$filename,$return='relative'){
        // Locates the file and if found - returns location,
        // otherwise returns array of attempted locations.
        // Specify empty filename to find location.

        // Imants: dirty fix for finding files with complex namespaces like
        // Vendor\MyAddon otherwise these are not found on *Nix systems
        $filename = str_replace('\\','/',$filename);

        $attempted_locations=array();
        $locations=array();
        $location=null;

        // first - look if type is explicitly defined in
        if(isset($this->contents[$type])){
            if(is_array($this->contents[$type])){
                $locations=$this->contents[$type];
            }else{
                $locations=array($this->contents[$type]);
            }
            // next - look if locations claims to have all resource types
        }elseif(isset($this->contents['all'])){
            $locations=array($type);
            echo (string)$this;
        }

        foreach($locations as $path){
            $f=$this->getPath($pathfile=$path.'/'.$filename);
            if(file_exists($f)){
                if(!is_readable($f)){
                    throw new PathFinder_Exception($type,$filename,$f,'File found but it is not readable');
                }

                if($return=='location')return $this;
                if($return=='relative')return $pathfile;
                if($return=='url')return $this->getURL($pathfile);
                if($return=='path')return $f;

                throw new BaseException('Wrong return type for locate()');

            }else $attempted_locations[]=$f;
        }

        return $attempted_locations;
    }
}
