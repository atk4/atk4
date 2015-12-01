<?php
/**
 * PathFinder is responsible for locating resources in Agile
 * Toolkit. One of the most significant principles it implements
 * is ability for any resource (PHP, JS, HTML, IMG) to be
 * located under several locations. Pathfinder will look for
 * the location containing the resource you ask for and will
 * provide you with either a relative path, URL or absolute
 * path to a file.

 * To make this possible, PathFinder relies on a class
 * :php:class:`PathFinder_Location`, which describes each individual
 * location and it's contents.

 * You may add additional locations in your application,
 * add-on or elsewhere. Some locations may be activated only
 * in certain circumstances, for example add-on will be able
 * to add it's location if add-on was added to a page.
 */
class PathFinder extends AbstractController
{
    /**
     * Location of the documentation for this class
     */
    const DOC = 'controller/pathfinder';

    /**
     * Base location is where your interface files are located. Normally
     * this location is added first and all the requests are checked here
     * before elsewhere. Example: /my/path/agiletoolkit/admin/
     */
    public $base_location=null;

    /**
     * This is location where images, javascript files and some other
     * public resources are located. Ex: /my/path/agiletoolkit/public
     */
    public $public_location=null;

    /**
     * Agile Toolkit comes with some assets: lib, template. This
     * location describes those resources. It's not publicly available
     */
    public $atk_location=null;

    /**
     * There are also some public files in ATK folder. Normally
     * this folder would be symlinked like this:
     *
     * public/atk4  -> /vendor/atk4/atk4/public/atk4
     *
     * If that folder is not there, PathFinder will point directly
     * to vendor folder (such as if on development environment),
     * if that is also unavailable, this can fall back to Agile Toolkit CDN.
     */
    public $atk_public=null;

    /**
     * {@inheritdoc}
     */
    public $default_exception='Exception_PathFinder';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->app->pathfinder=$this;

        $this->addDefaultLocations();

        // Unregister previously defined loader
        if (function_exists('agile_toolkit_temporary_load_class')) {
            spl_autoload_unregister('agile_toolkit_temporary_load_class');

            // collect information about previusly loaded files
            foreach ($GLOBALS['agile_toolkit_temporary_load_class_log'] as $class => $path) {
                $this->info('Loaded class %s from file %s', $class, $path);
            }

        }

        $self=$this;

        // Register preceeding autoload method. We want to get a first shot at
        // loading classes
        spl_autoload_register(function ($class) use ($self) {
            try {
                $path = $self->loadClass($class);
                if ($path) {
                    $self->info('Loaded class %s from file %s', $class, $path);
                }
            } catch (Exception $e) {
            }
        }, true, true);

        // If we couldn't load the class, let's throw exception
        spl_autoload_register(function ($class) use ($self) {
            try {
                $self->loadClass($class);
            } catch (Exception $e) {
                // If due to your PHP version, it says that
                // autoloader may not throw exceptions, then you should
                // add PHP verion check here and skip to next line.
                // PHP 5.5 - throwing seems to work out OK
                throw $e;
                $self->app->caughtException($e);
            }
        });
    }

    /**
     * Agile Toolkit-based application comes with a predefined resource
     * structure. For new users it's easier if they use a consistest structure,
     * for example having all the PHP classes inside "lib" folder.
     *
     * A more advanced developer might be willing to add additional locations
     * of resources to suit your own preferences. You might want to do
     * this if you are integrating with your existing application or
     * another framework or building multi-tiered project with extensive
     * structure.
     *
     * To extend the default structure which this method defines - you should
     * look into :php:class:`App_CLI::addDefaultLocations` and
     * :php:class:`App_CLI::addSharedLocations`
     *
     * @return void
     */
    public function addDefaultLocations()
    {
        // app->addAllLocations - will override creation of all locations
        // app->addDefaultLocations - will let you add high-priority locations
        // app->addSharedLocations - will let you add low-priority locations
        if ($this->app->hasMethod('addAllLocations')) {
            return $this->app->addAllLocations();
        }

        $base_directory=getcwd();

        /// Add base location - where our private files are
        if ($this->app->hasMethod('addDefaultLocations')) {
            $this->app->addDefaultLocations($this, $base_directory);
        }

        $templates_folder=array('template','templates');

        if ($this->app->compat_42 && is_dir($base_directory.'/templates/default')) {
            $templates_folder='templates/default';
        }

        $this->base_location=$this->addLocation(array(
            'php'=>'lib',
            'page'=>'page',
            'tests'=>'tests',
            'template'=>$templates_folder,
            'mail'=>'mail',
            'logs'=>'logs',
            'dbupdates'=>'doc/dbupdates',
        ))->setBasePath($base_directory);

        if (@$this->app->pm) {
            // Add public location - assets, but only if
            // we hav PageManager to identify it's location
            if (is_dir($base_directory.'/public')) {
                $this->public_location=$this->addLocation(array(
                    'public'=>'.',
                    'js'=>'js',
                    'css'=>'css',
                ))
                    ->setBasePath($base_directory.'/public')
                    ->setBaseURL($this->app->pm->base_path);
            } else {
                $this->base_location
                    ->setBaseURL($this->app->pm->base_path);
                $this->public_location = $this->base_location;
                $this->public_location->defineContents(array('js'=>'templates/js','css'=>'templates/css'));
            }

            if (basename($this->app->pm->base_path)=='public') {
                $this->base_location
                    ->setBaseURL(dirname($this->app->pm->base_path));
            }
        }

        if ($this->app->hasMethod('addSharedLocations')) {
            $this->app->addSharedLocations($this, $base_directory);
        }

        // Add shared locations
        if (is_dir(dirname($base_directory).'/shared')) {
            $this->shared_location=$this->addLocation(array(
                'php'=>'lib',
                'addons'=>'addons',
                'template'=>$templates_folder,
            ))->setBasePath(dirname($base_directory).'/shared');
        }

        $atk_base_path=dirname(dirname(__FILE__));

        $this->atk_location=$this->addLocation(array(
            'php'=>'lib',
            'template'=>$templates_folder,
            'tests'=>'tests',
            'mail'=>'mail',
        ))
            ->setBasePath($atk_base_path)
            ;

        if (@$this->app->pm) {
            if ($this->app->compat_42 && is_dir($this->public_location->base_path.'/atk4/public/atk4')) {
                $this->atk_public=$this->public_location->addRelativeLocation('atk4/public/atk4');
            } elseif (is_dir($this->public_location->base_path.'/atk4')) {
                $this->atk_public=$this->public_location->addRelativeLocation('atk4');
            } elseif (is_dir($base_directory.'/vendor/atk4/atk4/public/atk4')) {
                $this->atk_public=$this->base_location->addRelativeLocation('vendor/atk4/atk4/public/atk4');
            } elseif (is_dir($base_directory.'/../vendor/atk4/atk4/public/atk4')) {
                $this->atk_public=$this->base_location->addRelativeLocation('../vendor/atk4/atk4/public/atk4');
            } else {
                echo $this->public_location;
                throw $this->exception('Unable to locate public/atk4 folder', 'Migration');
            }

            $this->atk_public->defineContents(array(
                    'public'=>'.',
                    'js'=>'js',
                    'css'=>'css',
                ));
        }

        // Add sandbox if it is found
        $this->addSandbox();
    }

    public function addSandbox()
    {
        $sandbox_posible_locations = array(
            'agiletoolkit-sandbox'        =>'agiletoolkit-sandbox',
            '../agiletoolkit-sandbox'     =>'../agiletoolkit-sandbox',
            'agiletoolkit-sandbox.phar'   =>'phar://agiletoolkit-sandbox.phar',
            '../agiletoolkit-sandbox.phar'=>'phar://../agiletoolkit-sandbox.phar',
        );

        $this->sandbox = null;
        foreach ($sandbox_posible_locations as $k => $v) {
            if ($this->sandbox) continue;
            if (file_exists($k)) {
                if (is_dir($k)) {
                    $this->sandbox = $this->app->pathfinder->base_location->addRelativeLocation($v);
                } else {
                    $this->sandbox = $this->app->pathfinder->addLocation()->setBasePath($v);
                }
                break;
            }
        }
        if ($this->sandbox) {
            $this->sandbox->defineContents(array(
                'template'=>'template',
                'addons'=>'addons',
                'php'=>'lib'
            ));
        }
    }

    /**
     * Cretes new PathFinder_Location object and specifies it's contents.
     * You can subsequentially add more contents by calling:
     * :php:meth:`PathFinder_Location::defineContents`
     */
    public function addLocation($contents = array(), $old_contents = null)
    {

        if ($old_contents && @$this->app->compat_42) {
            return $this->base_location->addRelativeLocation($contents, $old_contents);
        }

        return $this
            ->add('PathFinder_Location')
            ->defineContents($contents)
            ;
    }

    /**
     * Search for a $filename inside multiple locations, associated with resource
     * $type. By default will return relative path, but 3rd argument can
     * change that.
     *
     * The third argument can also be 'location', in which case a :php:class:`PathFinder_Location`
     * object will be returned.
     *
     * If file is not found anywhere, then :php:class:`Exception_PathFinder` is thrown
     * unless you set $throws_exception to ``false``, and then method would return null.
     *
     * @param string $type     Type of resource to search surch as "php"
     * @param string $filename Name of the file to search for
     * @param string $return   'relative','url','path' or 'location'
     *
     * @return string|object as specified by $return
     */
    public function locate($type, $filename = '', $return = 'relative', $throws_exception = true)
    {
        $attempted_locations=array();
        if (!$return) {
            $return='relative';
        }
        foreach ($this->elements as $key => $location) {
            if (!($location instanceof PathFinder_Location)) {
                continue;
            }

            $path=$location->locate($type, $filename, $return);

            if (is_string($path) || is_object($path)) {
                return $path;
            } elseif (is_array($path)) {
                if($return==='array' && @isset($path['name']))

                    return $path;

                $attempted_locations=array_merge($attempted_locations, $path);
            }
        }

        if ($throws_exception) {
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
    public function search($type, $filename = '', $return = 'relative')
    {
        $matches=array();
        foreach ($this->elements as $location) {
            if(!($location instanceof PathFinder_Location))continue;

            $path=$location->locate($type, $filename, $return);

            if (is_string($path)) {
                // file found!
                $matches[]=$path;
            }
        }

        return $matches;
    }
    public function _searchDirFiles($dir, &$files, $prefix = '')
    {
        $d=dir($dir);
        while (false !== ($file=$d->read())) {
            if($file[0]=='.')continue;
            if (is_dir($dir.'/'.$file)) {
                $this->_searchDirFiles($dir.'/'.$file, $files, $prefix.$file.'/');
            } else {
                $files[]=$prefix.$file;
            }
        }
        $d->close();
    }
    /**
      * Specify type and directory and it will return array of all files
      * of a matching type inside that directory. This will work even
      * if specified directory exists inside multiple locations.
      */
    public function searchDir($type, $directory = '')
    {
        $dirs=$this->search($type, $directory, 'path');
        $files=array();
        foreach ($dirs as $dir) {
            $this->_searchDirFiles($dir, $files);
        }

        return $files;
    }
    /**
     * Provided with a class name, this will attempt to
     * find and load it
     *
     * @return String path from where the class was loaded
     */
    public function loadClass($className)
    {
        $origClassName = str_replace('-', '', $className);

        /**/$this->app->pr->start('pathfinder/loadClass ');

        /**/$this->app->pr->next('pathfinder/loadClass/convertpath ');
        $className = ltrim($className, '\\');
        $nsPath = '';
        $namespace = '';
        if ($lastNsPos = strripos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $nsPath  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace);
        }
        $classPath = str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        /**/$this->app->pr->next('pathfinder/loadClass/locate ');
        try {
            if ($namespace) {
                if (strpos($className, 'page_')===0) {
                    $path=$this->app->locatePath(
                        'addons',
                        $nsPath.DIRECTORY_SEPARATOR.$classPath
                    );
                } else {
                    $path=$this->app->locatePath(
                        'addons',
                        $nsPath.DIRECTORY_SEPARATOR
                        .'lib'.DIRECTORY_SEPARATOR.$classPath
                    );
                }
            } else {
                if (strpos($className, 'page_')===0) {
                    $path=$this->app->locatePath(
                        'page',
                        substr($classPath, 5)
                    );
                } else {
                    $path=$this->app->locatePath(
                        'php',
                        $classPath
                    );
                }
            }
        } catch (PathFinder_Exception $e) {
            $e
                ->addMoreInfo('class', $className)
                ->addMoreInfo('namespace', $namespace)
                ->addMoreInfo('orig_class', $origClassName)
                ;
            throw $e;
        }

        if (!is_readable($path)) {
            throw new PathFinder_Exception('addon', $path, $prefix);
        }

        /**/$this->app->pr->next('pathfinder/loadClass/include ');
        /**/$this->app->pr->start('php parsing');
        include_once $path;
        /**/$this->app->pr->stop();
        if(!class_exists($origClassName, false) && !interface_exists($origClassName, false))
            throw $this->exception('Class is not defined in file')
                ->addMoreInfo('file', $path)
                ->addMoreInfo('namespace', $namespace)
                ->addMoreInfo('class', $className)
                ;
        /**/$this->app->pr->stop();

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
class PathFinder_Location extends AbstractModel
{
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

    public $is_cdn = false;
    /**
     * If you set this to true, then baseURL will be considered
     * to point to a remote location.
     *
     * use setCDN() method;
     */

    /** OBSOLETE **/
    private $_relative_path=null;

    /**
     * Returns how this location or file can be accessed through web
     * base url + relative path + file_path
     */
    public function getURL($file_path = null)
    {
        if (!$this->base_url) {
            throw new BaseException('Unable to determine URL');
        }

        if (!$file_path) return $this->base_url;

        $u=$this->base_url;
        if(substr($u, -1) != '/') $u.='/';

        return $u.$file_path;
    }

    /**
     * Returns how this location or file can be accessed through filesystem
     */
    public function getPath($file_path = null)
    {
        if (!$file_path) return $this->base_path;
        return $this->base_path.'/'.$file_path;
    }

    /**
     * Set a new BaseURL
     * 
     * something like /my/app
     */
    public function setBaseURL($url)
    {
        $this->base_url = preg_replace('#[\\\\|/]+#', '/', $url);

        return $this;
    }

    public function setCDN($url)
    {
        $this->setBaseURL($url);
        $this->is_cdn = true;
        
        return $this;
    }

    /**
     * Set a new BasePath
     * 
     * something like /home/web/public_html
     */
    public function setBasePath($path)
    {
        $this->base_path=$path;

        return $this;
    }

    public function defineContents($contents)
    {
        $this->contents=@array_merge_recursive($this->contents, $contents);

        return $this;
    }

    /**
     * Adds a new location object which is relative to $this location.
     *
     * @param [type] $relative_path [description]
     * @param array  $contents      [description]
     */
    public function addRelativeLocation($relative_path, array $contents = array())
    {
        $location = $this->newInstance();

        $location->setBasePath($this->base_path.'/'.$relative_path);

        if ($this->base_url) {
            $location->setBaseURL($this->base_url.'/'.$relative_path);
        }

        return $contents ? $location->defineContents($contents) : $location;
    }

    // OBSOLETE - Compatiblity
    public function setParent(Pathfinder_Location $parent)
    {
        $this->setBasePath($parent->base_path.'/'.$this->_relative_path);
        $this->setBaseURL($parent->base_url.'/'.$this->_relative_path);

        return $this;
    }

    public function locate($type, $filename, $return = 'relative')
    {
        // Locates the file and if found - returns location,
        // otherwise returns array of attempted locations.
        // Specify empty filename to find location.

        // Imants: dirty fix for finding files with complex namespaces like
        // Vendor\MyAddon otherwise these are not found on *Nix systems


        $filename = str_replace('\\', '/', $filename);

        $attempted_locations=array();
        $locations=array();
        $location=null;

        // first - look if type is explicitly defined in
        if (isset($this->contents[$type])) {
            if (is_array($this->contents[$type])) {
                $locations=$this->contents[$type];
            } else {
                $locations=array($this->contents[$type]);
            }
            // next - look if locations claims to have all resource types
        } elseif (isset($this->contents['all'])) {
            $locations=array($type);
            echo (string) $this;
        }

        foreach ($locations as $path) {
            $pathfile=$path.'/'.$filename;

            // If this location represents CDN, it always finds URl files
            if ($this->is_cdn && $return=='url') {
                return $this->getURL($pathfile);
            }

            $f=$this->getPath($pathfile);

            if (file_exists($f)) {
                if (!is_readable($f)) {
                    throw $this->exception('File found but it is not readable')
                        ->addMoreInfo('type', $type)
                        ->addMoreInfo('filename', $filename)
                        ->addMoreInfo('f', $f)
                        ;
                }

                if($return=='array')return array(
                    'name'=>$filename,
                    'relative'=>$pathfile,
                    'url'=>$this->base_url ? $this->getURL($pathfile) : null,
                    'path'=>$f,
                    'location'=>$this
                );
                if($return=='location')return $this;
                if($return=='relative')return $pathfile;
                if($return=='url')return $this->getURL($pathfile);
                if($return=='path')return $f;

                throw $this->exception('Wrong return type for locate()');

            }else $attempted_locations[]=$f;
        }

        return $attempted_locations;
    }
}
