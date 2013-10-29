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
 * PathFinder is responsible for locating resources in Agile
 * Toolkit. One of the most significant principles it implements
 * is ability for any resource (PHP, JS, HTML, IMG) to be
 * located under several locations. Pathfinder will look for
 * the location containing the resource you ask for and will
 * provide you with either a relative path, URL or absolute
 * path to a file.

 * To make this possible, PathFinder relies on a class
 * PathFinder_Location, which describes each individual
 * location and it's contents.

 * You may add additional locations in your application,
 * add-on or elsewhere. Some locations may be activated only
 * in certain circumstances, for example add-on will be able
 * to add it's location only if add-on have been added to
 * a page.

 * Here is a list of resource categories:
 * php, page, addons, template, mail, js, css

 * Resources which are planned or under development:
 * dbupdates, logs, public

 * @link http://agiletoolkit.org/pathfinder
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
     * This is location where images, javascript files and some other
     * public resources are located.
     */
    public $public_location=null;

    /**
     * Agile Toolkit comes with some assets: lib, template. This
     * location describes those resources. It's not publicly available
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

        $this->addDefaultLocations();

        // Unregister previously defined loader
        if(function_exists('agile_toolkit_temporary_load_class')) {
            spl_autoload_unregister('agile_toolkit_temporary_load_class');

            // collect information about previusly loaded files
            foreach($GLOBALS['agile_toolkit_temporary_load_class_log'] as $class=>$path) {
                $this->info('Loaded class %s from file %s',$class,$path);
            }

        }

        $self=$this;

        // Register preceeding autoload method. We want to get a first shot at
        // loading classes
        spl_autoload_register(function ($class)use($self){
            try {
                $path = $self->loadClass($class);
                if($path)$self->info('Loaded class %s from file %s',$class,$path);
            }catch(Exception $e) {
            }
        },true,true);

        // If we couldn't load the class, let's throw exception
        spl_autoload_register(function ($class)use($self){
            try {
                $self->loadClass($class);
            }catch(Exception $e) {
                $self->api->caughtException($e);
            }
        });


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
        $base_directory=getcwd();


        /// Add base location - where our private files are
        if ($this->api->hasMethod('addDefaultLocations')) {
            $this->api->addDefaultLocations($this, $base_directory);
        }

        $this->base_location=$this->addLocation(array(
            'php'=>'lib',
            'page'=>'page',
            'template'=>'templates',
            'mail'=>'mail',
            'logs'=>'logs',
            'dbupdates'=>'doc/dbupdates',
        ))->setBasePath($base_directory);

        // Add public location - assets, but only if 
        // we hav PageManager to identify it's location
        if(@$this->api->pm) {
            $this->public_location=$this->addLocation(array(
                'public'=>'.',
                'js'=>'js',
                'css'=>'css',
            ))
                ->setBasePath($base_directory.'/public')
                ->setBaseURL($this->api->pm->base_path);
        }

        if ($this->api->hasMethod('addSharedLocations')) {
            $this->api->addSharedLocations($this, $base_directory);
        }

        $atk_base_path=dirname(dirname(__FILE__));

        $this->atk_location=$this->addLocation(array(
            'php'=>'lib',
            'template'=>'templates',
        ))
            ->setBasePath($atk_base_path)
            ;

        if(@$this->api->pm) {
            $this->atk_public=$this->public_location->addRelativeLocation('atk4',array(
                'public'=>'.',
                'js'=>'js',
                'css'=>'css',
            ));
        }
    }

    /**
     * Cretes new PathFinder_Location object and specifies it's contents.
     * You can subsequentially add more contents by calling 
     *
     *   $location->defineContents
     *
     * @param array  $contents     Array describing contents
     * @param array  $old_contents Remains for backwards compatibility
     *
     * @return PathFinder_Location New Location
     */
    function addLocation($contents=array(), $old_contents=null)
    {

        if ($old_contents) $contents = $old_contents;

        return $this
            ->add('PathFinder_Location')
            ->defineContents($contents)
            ;
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
    function locate($type, $filename='', $return='relative', $throws_exception=true)
    {
        $attempted_locations=array();
        if (!$return) {
            $return='relative';
        }
        foreach ($this->elements as $key=>$location) {
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

        if($throws_exception) {
            throw $this->exception('File not found')
                ->addMoreInfo('file', $filename)
                ->addMoreInfo('type', $type)
                ->addMoreInfo('attempted_locations', $attempted_locations)
                ;
        }
    }
    /**
     * Search is similar to locate, but will return array of all matching
     * files.
     *
     * @param string $type     Type of resource to search surch as "php"
     * @param string $filename Name of the file to search for
     * @param string $return   'relative','url','path' or 'location'
     *
     * @return string|object as specified by $return
     */
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
    /** 
     * Provided with a class name, this will attempt to 
     * find and load it
     *
     * @return String path from where the class was loaded
     */
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
        if(!class_exists($origClassName ,false) && !interface_exists($origClassName, false))throw $this->exception('Class is not defined in file')
            ->addMoreInfo('file',$path)
            ->addMoreInfo('class',$className);
        /**/$this->api->pr->stop();
        return $path;
    }


}
class PathFinder_Exception extends BaseException
{
}
class Exception_PathFinder extends Pathfinder_Exception
{
}

/**
 * Represents a location, which contains number of sub-locations. Each
 * of which may contain certain type of data
 */
class PathFinder_Location extends AbstractModel {

    /**
     * contains list of 'type'=>'subdir' which lists all the
     * resources which can be found in this directory
     */
    public $contents=array();

    /**
     * Locations could have a URL defined, if location is exposed on-line.
     */
    public $base_url=null;

    /**
     * All location would have a base_path defined
     */
    public $base_path=null;

    public $auto_track_element = true;

    /**
     * Returns how this location or file can be accessed through web
     * base url + relative path + file_path
     */
    function getURL($file_path=null){

        if (!$this->base_url) {
            throw new BaseException('Unable to determine URL');
        }

        if (!$file_path) return $this->base_url;

        $u=$this->base_url;
        if(substr($u,-1) != '/') $u.='/';



        return $u.$file_path;
    }

    /**
     * Returns how this location or file can be accessed through filesystem
     */
    function getPath($file_path=null){

        if (!$file_path) return $this->base_path;

        return $this->base_path.'/'.$file_path;
    }

    /**
     * Set a new BaseURL
     */
    function setBaseURL($url){
        /*
           something like /my/app
         */
        $url=preg_replace('|//|','/',$url);
        $this->base_url=$url;
        return $this;
    }

    /**
     * Set a new BaseURL
     */
    function setBasePath($path){
        /*
           something like /home/web/public_html
         */
        $this->base_path=$path;
        return $this;
    }

    function defineContents($contents){
        $this->contents=array_merge_recursive($this->contents,$contents);
        return $this;
    }

    function addRelativeLocation($relative_path, array $contents) {

        $location = $this->newInstance();

        $location->setBasePath($this->base_path.'/'.$relative_path);

        if ($this->base_url) {
            $location->setBaseURL($this->base_url.'/'.$relative_path);
        }

        return $location->defineContents($contents);
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
