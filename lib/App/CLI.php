<?php
/**
 * Base class for Command-Line Applications. The purpose of Application class
 * is to initialize all the other classes and aid their connectivity. APP
 * class can be accessed from any object through $this->app property.
 *
 * APP classes are derrived from AbstractView because normally they would have
 * a template and will be able to render themselves consistently to any other
 * view in the system. Although App_CLI does not do any rendering, it's
 * descendants do.
 *
 * @link http://agiletoolkit.org/doc/app
 */
class App_CLI extends AbstractView
{
    /**
     * In a typical application, one connection to the database is enough for
     * majority of applications. Calling $app->dbConnect will read Database
     * data from config file and store it in $db property. If you requires
     * a more advanced connectivity or multiple connections, you can manually
     * initialize more database connections.
     *
     * @see dbConnect()
     * @var DB
     */
    public $db = null;

    /**
     * App_CLI implements a API for accessing your application configuration.
     * Once configuration file is read, data is saved inside this property.
     *
     * @see getConfig()
     * @see readConfig()
     * @var array
     */
    public $config = array();

    /**
     * This is config relative location to the initializing file. By default
     * it's in the same folder, but you can move it one folder up ".." or
     * inside "config" sub-folder by setting value to 'config' to better
     * reflect your application layout.
     *
     * @var string
     */
    public $config_location = '.';

    /**
     * Config files. If you have extra configuration files you want
     * to be automatically loaded, please redefine this property. You must
     * do this before Application class constructor is executed.
     *
     * If you are loading additional config later, use readConfig() instead
     *
     * Order of this array is important.
     *
     * @var array
     */
    public $config_files = array('config-default', 'config');

    /**
     * Contains list of loaded config files.
     *
     * @var array
     */
    public $config_files_loaded = array();

    /**
     * Without logger, APP will dump out errors and exceptions in a very brief
     * and straigtforward way. Logger is a controller which enhances error
     * output and in most cases you do need one. Logger can be further configured
     * to either output detailed errors or show brief message instead.
     *
     * @see Logger
     * @var Logger
     */
    public $logger = null;

    /**
     * If you want to use your own logger class, redefine this property.
     *
     * @param string
     */
    public $logger_class = 'Logger';

    /**
     * PathFinder is a controller which is responsible for locating resources,
     * such as PHP includes, JavaScript files, templates, etc. APP Initializes
     * PathFinder as soon as possible, then defines "Locations" which describe
     * type of data found in different folders.
     *
     * @var PathFinder
     */
    public $pathfinder = null;

    /**
     * If you would want to use your own PathFinder class, you must change
     * this property and include it.
     *
     * @var string
     */
    protected $pathfinder_class = 'PathFinder';

    /**
     * PageManager object
     *
     * @see Controller_PageManager::init()
     * @var Controller_PageManager
     */
    public $pm;

    /**
     * Change a different Page Manager class.
     *
     * @var string
     */
    protected $pagemanager_class = 'Controller_PageManager';

    /**
     * Set to array('debug' => true) to debug Page Manager.
     *
     * @var array
     */
    protected $pagemanager_options = null;

    /**
     * This is a major version of Agile Toolkit. The APP of Agile Toolkit is
     * very well established and changes rarely. Your application would generally
     * be compatible throughout the same major version of Agile Tooolkit.
     *
     * @see requires();
     * @var string
     */
    public $atk_version = 4.3;

    /**
     * If you want Agile Toolkit to be compatible with 4.2 version, include
     * compatibility controller. For more information see:.
     *
     * @var bool
     */
    public $compat_42 = false;

    /**
     * Some Agile Toolkit classes contain references to profiler. Profiler
     * would be initialized early and reference would be kept in this variable.
     * Profiler measures relative time it took in certain parts of your
     * application to help you find a slow-perfoming parts of application.
     *
     * By default $pr points to empty profiler object, which implements empty
     * methods. All the lines referencing $pr myst be prefixed with the
     * 4-symbol sequence "/ ** /" (no spaces). If you want to speed up Agile
     * Toolkit further, you can eliminate all lines started with this sequence
     * from your source code.
     *
     * @var object
     */
    public $pr;

    /**
     * Object in Agile Toolkit contain $name property which is derrived from
     * the owher object and keeps extending as you add objects deeper into
     * run-time tree. Sometimes that may generate long names. Long names are
     * difficult to read, they increase HTML output size but most importantly
     * they may be restricted by security extensions such as SUHOSIN.
     *
     * Agile Toolkit implements a mechanism which will replace common beginning
     * of objects with an abbreviation thus keeping object name length under
     * control. This variable defines the maximum length of the object's $name.
     * Be mindful that some objects will concatinate theri name with fields,
     * so the maximum letgth of GET argument names can exceed this value by
     * the length of your field.
     *
     * We recommend you to increase SUHOSIN get limits if you encounter any
     * problems. Set this value to "false" to turn off name shortening.
     *
     * @var int
     */
    public $max_name_length = 60;

    /**
     * As more names are shortened, the substituted part is being placed into
     * this hash and the value contains the new key. This helps to avoid creating
     * many sequential prefixes for the same character sequenece.
     *
     * @var array
     */
    public $unique_hashes = array();

    /**
     * This is the default locale for the application. You change this manually
     * inside application APP class or use some controller which will pull this
     * variable out of the URL. This variable will be respected throughout the
     * framework.
     *
     * @var string
     */
    public $locale = 'en_US';

    // {{{ Start-up of application
    /**
     * Regular objects in Agile Toolkit use init() and are added through add().
     * Application class is differente, you use "new" keyword because it's the
     * first class to be created. That's why constructor will perform quite a
     * bit of initialization.
     *
     * Do not redefine constructor but instead use init();
     *
     * $realm defines a top-level name of your application. This impacts all
     * id= prefixes in your HTML code, form field names and many other things,
     * such as session name. If you have two application classes which are part
     * of same web app and may want to use same realm, but in other cases it's
     * preferably that you keep realm unique on your domain in the interests
     * of security.
     *
     * @param string $realm Will become $app->name
     * @param array $options
     */
    public function __construct($realm = null, $options = array())
    {
        parent::__construct($options);
        if ($realm === null) {
            $realm = get_class($this);
        }
        $this->owner = $this;
        $this->name = $realm;
        $this->app = $this;
        $this->api = $this->app; // compatibility with ATK 4.2 and lower

        // Profiler is a class for benchmarking your application. All calls to pr
        /**/$this->pr = new Dummy();

        try {
            $this->_beforeInit();

            $this->init();
        } catch (Exception $e) {

            // This exception is used to abort initialisation of the objects,
            // but when normal rendering is still required
            if ($e instanceof Exception_StopInit) {
                return;
            }

            // Handles output of the exception
            $this->caughtException($e);
        }
    }

    /**
     * Finds out which page is requested. We don't need this method
     * for CLI, but others might need it.
     */
    public function _beforeInit()
    {
        // Loads all configuration files
        $this->readAllConfig();
        $this->add($this->pathfinder_class);
    }
    // }}}

    // {{{ Management of Global Methods
    /**
     * Agile Toolkit objects allow method injection. This is quite similar
     * to technique used in JavaScript:.
     *
     *     obj.test = function() { .. }
     *
     * All non-existant method calls on all Agile Toolkit objects will be
     * tried against local table of registered methods and then against
     * global registered methods.
     *
     * addGlobalmethod allows you to register a globally-recognized for all
     * agile toolkit object. PHP is not particularly fast about executing
     * methods like that, but this technique can be used for adding
     * backward-compatibility or debugging, etc.
     *
     * @see AbstractObject::hasMethod()
     * @see AbstractObject::__call()
     *
     * @param string   $name     Name of the method
     * @param callable $callable Calls your function($object, $arg1, $arg2)
     */
    public function addGlobalMethod($name, $callable)
    {
        if ($this->hasMethod($name)) {
            throw $this->exception('Registering method twice')
                ->addMoreInfo('name', $name);
        }
        $this->addHook('global-method-'.$name, $callable);
    }

    /**
     * Returns if a global method with such name was defined.
     *
     * @param string $name Name of the method
     *
     * @return bool if registered
     */
    public function hasGlobalMethod($name)
    {
        return isset($this->hooks['global-method-'.$name]);
    }

    /**
     * Removes global method.
     *
     * @param string $name
     */
    public function removeGlobalMethod($name)
    {
        $this->removeHook('global-method-'.$name);
    }
    // }}}

    // {{{ Localization
    /**
     * Redefine this function to introduce your localization.
     * Agile Toolkit will pass all system strings through this method.
     * If some methods are not properly passed through, please fork
     * Agile Toolkit from http://github.com/atk4/atk4/ , modify, commit, push
     * your fix and notify authors of Agile Toolkit using contact form on
     * http://agiletoolkit.org/contact.
     *
     * See file CONTRIBUTING
     *
     * @param string $str String which needs localization
     *
     * @return string Localized string
     */
    public function _($str)
    {
        $x = $this->hook('localizeString', array($str));
        if ($x) {
            return $x[0];
        }

        return $str;
    }
    // }}}

    // {{{ PathFinder and PageManager bindings
    /**
     * Find relative path to the resource respective to the current directory.
     *
     * @param string $type     [description]
     * @param string $filename [description]
     * @param string $return   [description]
     *
     * @return [type] [description]
     */
    public function locate($type, $filename = '', $return = 'relative')
    {
        return $this->pathfinder->locate($type, $filename, $return);
    }

    /**
     * Calculate URL pointing to specified resource.
     *
     * @param string $type     [description]
     * @param string $filename [description]
     *
     * @return [type] [description]
     */
    public function locateURL($type, $filename = '')
    {
        return $this->pathfinder->locate($type, $filename, 'url');
    }

    /**
     * Return full system path to specified resource.
     *
     * @param string $type     [description]
     * @param string $filename [description]
     *
     * @return [type] [description]
     */
    public function locatePath($type, $filename = '')
    {
        return $this->pathfinder->locate($type, $filename, 'path');
    }

    /**
     * Add new location with additional resources.
     *
     * @param [type] $contents [description]
     * @param [type] $obsolete [description]
     *
     * @return [type]
     */
    public function addLocation($contents, $obsolete = undefined)
    {
        if ($obsolete !== undefined) {
            throw $this->exception('Use a single argument for addLocation');
        }

        return $this->pathfinder->addLocation($contents);
    }

    /**
     * Returns base URL of this Web application installation. If you require
     * link to a page, you can use URL::useAbsoluteURL();.
     *
     * @see URL::useAbsoluteURL()
     *
     * @return [type]
     */
    public function getBaseURL()
    {
        return $this->pm->base_path;
    }

    /**
     * Generates URL for specified page. Useful for building links on pages or emails. Returns URL object.
     *
     * @param [type] $page      [description]
     * @param array  $arguments [description]
     *
     * @return [type] [description]
     */
    public function url($page = null, $arguments = array())
    {
        if (is_object($page) && $page instanceof URL) {
            // we receive URL
            return $page->setArguments($arguments);
        }
        if (is_array($page)) {
            $p = $page[0];
            unset($page[0]);
            $arguments = $page;
            $page = $p;
        }
        $url = $this->add('URL');
        unset($this->elements[$url->short_name]); // garbage collect URLs
        if (strpos($page, 'http://') === 0 || strpos($page, 'https://') === 0) {
            $url->setURL($page);
        } else {
            $url->setPage($page);
        }

        return $url->setArguments($arguments);
    }

    /**
     * @todo Description
     *
     * @return array
     */
    public function getStickyArguments()
    {
        return array();
    }
    // }}}

    // {{{ Error handling
    /**
     * Initialize logger or return existing one.
     *
     * @param string $class_name
     *
     * @return Logger
     */
    public function getLogger($class_name = undefined)
    {
        if (is_null($this->logger)) {
            $this->logger = $this->add($class_name === undefined
                                            ? $this->logger_class
                                            : $class_name);
        }

        return $this->logger;
    }

    /**
     * Is executed if exception is raised during execution.
     * Re-define to have custom handling of exceptions system-wide.
     *
     * @param Exception $e
     */
    public function caughtException($e)
    {
        $this->hook('caught-exception', array($e));
        echo get_class($e), ': '.$e->getMessage();
        exit;
    }

    /** @obsolete */
    public function outputWarning($msg, $shift = 0)
    {
        if ($this->hook('output-warning', array($msg, $shift))) {
            return true;
        }
        echo "warning: $msg\n";
    }

    /** @obsolete */
    public function outputDebug($object, $msg, $shift = 0)
    {
        if ($this->hook('output-debug', array($object, $msg, $shift))) {
            return true;
        }
        echo "debug: $msg\n";
    }
    // }}}

    // {{{ Configuration File Handling
    /**
     * Will include all files as they are defined in $this->config_files
     * from folder $config_location.
     */
    public function readAllConfig()
    {
        // If configuration files are not there - will silently ignore
        foreach ($this->config_files as $file) {
            $this->readConfig($file);
        }

        $tz = $this->getConfig('timezone', null);
        if (!is_null($tz) && function_exists('date_default_timezone_set')) {
            // with seting default timezone
            date_default_timezone_set($tz);
        } else {
            if (!ini_get('date.timezone')) {
                ini_set('date.timezone', 'UTC');
            }
        }
    }

    /**
     * Executed when trying to access config parameter which is not find in the file.
     *
     * @param string $default
     * @param string $exceptiontext
     */
    public function configExceptionOrDefault($default, $exceptiontext)
    {
        if ($default != '_config_get_false') {
            return $default;
        }
        throw new BaseException($exceptiontext);
    }

    /**
     * Read config file and store it in $this->config. Use getConfig() to access.
     *
     * @param string $file Filename
     *
     * @return bool
     */
    public function readConfig($file = 'config.php')
    {
        $orig_file = $file;

        if (strpos($file, '.php') != strlen($file) - 4) {
            $file .= '.php';
        }

        if (strpos($file, '/') === false) {
            $file = getcwd().'/'.$this->config_location.'/'.$file;
        }

        if (file_exists($file)) {
            // some tricky thing to make config be read in some cases it could not in simple way
            unset($config);

            $config = &$this->config;
            $this->config_files_loaded[] = $file;
            include $file;

            unset($config);

            return true;
        }

        return false;
    }

    /**
     * Manually set configuration option.
     *
     * @param array  $config [description]
     * @param [type] $val    [description]
     */
    public function setConfig($config = array(), $val = UNDEFINED)
    {
        if ($val !== UNDEFINED) {
            return $this->setConfig(array($config => $val));
        }
        $this->config = array_merge($this->config ?: array(), $config ?: array());
    }

    /**
     * Load config if necessary and look up corresponding setting.
     *
     * @param string $path
     * @param mixed $default_value
     *
     * @return string
     */
    public function getConfig($path, $default_value = undefined)
    {
        /*
         * For given path such as 'dsn' or 'logger/log_dir' returns
         * corresponding config value. Throws ExceptionNotConfigured if not set.
         *
         * To find out if config is set, do this:
         *
         * $var_is_set = true;
         * try { $app->getConfig($path); } catch ExceptionNotConfigured($e) { $var_is_set=false; }
         */
        $parts = explode('/', $path);
        $current_position = $this->config;
        foreach ($parts as $part) {
            if (!array_key_exists($part, $current_position)) {
                if ($default_value !== undefined) {
                    return $default_value;
                }
                throw $this->exception('Configuration parameter is missing in config.php', 'NotConfigured')
                    ->addMoreInfo('config_files_loaded', $this->config_files_loaded)
                    ->addMoreInfo('missign_line', " \$config['".implode("']['", explode('/', $path))."']");
            } else {
                $current_position = $current_position[$part];
            }
        }

        return $current_position;
    }
    // }}}

    // {{{ Version handling
    private $version_cache = null;
    /**
     * Determine version of Agile Toolkit or specified plug-in.
     *
     * @param string $of
     *
     * @return string
     */
    public function getVersion($of = 'atk')
    {
        // TODO: get version of add-on
        if (!$this->version_cache) {
            $f = $this->app->pathfinder->atk_location->base_path.DIRECTORY_SEPARATOR.'VERSION';
            if (file_exists($f)) {
                $this->version_cache = trim(file_get_contents($f));
            } else {
                $this->version_cache = '4.0.1';
            }
        }

        return $this->version_cache;
    }

    /**
     * Verifies version. Should be used by addons. For speed improvement,
     * redefine this into empty function.
     *
     * @param string $addon
     * @param string $v
     * @param string $location
     *
     * @return bool
     */
    public function requires($addon = 'atk', $v, $location = null)
    {
        $cv = $this->getVersion($addon);
        if (version_compare($cv, $v) < 0) {
            if ($addon == 'atk') {
                $e = $this->exception('Agile Toolkit version is too old');
            } else {
                $e = $this->exception('Add-on is outdated')
                    ->addMoreInfo('addon', $addon);
            }
            $e->addMoreInfo('required', $v)
                ->addMoreInfo('you have', $cv);
            if ($location !== null) {
                $e->addMoreInfo('download_location', $location);
            }
            throw $e;
        }

        // Possibly we need to enable compatibility version
        if ($addon == 'atk') {
            if (version_compare($v, '4.2') < 0 && version_compare($v, '4.1.4') >= 0) {
                $this->add('Controller_Compat');

                return true;
            }
        }

        return true;
    }

    /** @obsolete use @requires */
    public function versionRequirement($v, $location = null)
    {
        return $this->requires('atk', $v, $location);
    }
    // }}}

    // {{{ Database connection handling
    /**
     * Use database configuration settings from config file to establish default connection.
     *
     * @param mixed $dsn
     *
     * @return DB
     */
    public function dbConnect($dsn = null)
    {
        return $this->db = $this->add('DB')->connect($dsn);
    }
    // }}}

    // {{{ Helper / utility methods
    /**
     * Normalize field or identifier name. Can also be used in URL normalization.
     * This will replace all non alpha-numeric characters with separator.
     * Multiple separators in a row is replaced with one.
     * Separators in beginning and at the end of name are removed.
     *
     * Sample input:  "Hello, Dear Jon!"
     * Sample output: "Hello_Dear_Jon"
     *
     * @param string $name      String to process
     * @param string $separator Character acting as separator
     *
     * @return string Normalized string
     */
    public function normalizeName($name, $separator = '_')
    {
        if (strlen($separator) == 0) {
            return preg_replace('|[^a-z0-9]|i', '', $name);
        }

        $s = $separator[0];
        $name = preg_replace('|[^a-z0-9\\'.$s.']|i', $s, $name);
        $name = trim($name, $s);
        $name = preg_replace('|\\'.$s.'{2,}|', $s, $name);

        return $name;
    }

    /**
     * First normalize class name, then add specified prefix to
     * class name if it's passed and not already added.
     * Class name can have namespaces and they are treated prefectly.
     *
     * If object is passed as $name parameter, then same object is returned.
     *
     * Example: normalizeClassName('User','Model') == 'Model_User';
     *
     * @param string|object $name   Name of class or object
     * @param string        $prefix Optional prefix for class name
     *
     * @return string|object Full, normalized class name or received object
     */
    public function normalizeClassName($name, $prefix = null)
    {
        if (!is_string($name)) {
            return $name;
        }

        $name = str_replace('/', '\\', $name);
        if ($prefix !== null) {
            $class = ltrim(strrchr($name, '\\'), '\\') ?: $name;
            $prefix = ucfirst($prefix);
            if (strpos($class, $prefix) !== 0) {
                $name = preg_replace('|^(.*\\\)?(.*)$|', '\1'.$prefix.'_\2', $name);
            }
        }

        return $name;
    }

    /**
     * Encodes HTML special chars.
     * By default does not encode already encoded ones.
     *
     * @param string $s
     * @param int    $flags
     * @param string $encode
     * @param bool   $double_encode
     *
     * @return string
     */
    public function encodeHtmlChars($s, $flags = null, $encode = null, $double_encode = false)
    {
        if ($flags === null) {
            $flags = ENT_COMPAT;
        }
        if ($encode === null) {
            $encode = ini_get('default_charset') ?: 'UTF-8';
        }

        return htmlspecialchars($s, $flags, $encode, $double_encode);
    }
    // }}}
}
