<?php
/**
 * App_Web extends an APP of CommandLine applications with knowledge of HTML
 * templates, understanding of pages and routing.
*/
class App_Web extends App_CLI
{
    /**
     * Cleaned up name of the currently requested page.
     */
    public $page = null;

    /**
     * Root page where URL will send when ('/') is encountered.
     *
     * @todo: make this work properly
     */
    public $index_page = 'index';

    /**
     * Recorded time when execution has started.
     */
    public $start_time = null;

    /**
     * Skin for web application templates.
     */
    public $skin;

    /**
     * Set a title of your application, will appear in <title> tag.
     */
    public $title;

    /**
     * Authentication object
     *
     * @see Auth_Basic::init()
     * @var Auth_Basic
     */
    public $auth;

    /**
     * jQuery object. Initializes only if you add jQuery in your app.
     *
     * @see jQuery::init()
     * @var jQuery
     */
    public $jquery;

    /**
     * jQuery UI object. Initializes only if you add jQuery UI in your app.
     *
     * @see jUI::init()
     * @var jUI
     */
    public $jui;

    /** @var App_Web */
    public $app;
    /** @var array For internal use */
    protected $included;
    /** @var array For internal use */
    protected $rendered;



    // {{{ Start-up
    public function __construct($realm = null, $skin = 'default', $options = array())
    {
        $m = explode(' ', microtime());
        $this->start_time = time() + $m[0];

        $this->skin = $skin;
        try {
            parent::__construct($realm, $options);
        } catch (Exception $e) {
            // This exception is used to abort initialisation of the objects,
            // but when normal rendering is still required
            if ($e instanceof Exception_Stop) {
                return;
            }

            $this->caughtException($e);
        }
    }

    /**
     * Executed before init, this method will initialize PageManager and
     * pathfinder.
     */
    public function _beforeInit()
    {
        $this->pm = $this->add($this->pagemanager_class, $this->pagemanager_options);
        /** @type Controller_PageManager $this->pm */
        $this->pm->parseRequestedURL();
        parent::_beforeInit();
    }

    /**
     * Redefine this function instead of default constructor.
     */
    public function init()
    {
        $this->getLogger();

        // Verify Licensing
        //$this->licenseCheck('atk4');

        // send headers, no caching
        $this->sendHeaders();

        $this->cleanMagicQuotes();

        parent::init();

        // in addition to default initialization, set up logger and template
        $this->initializeTemplate();

        if (get_class($this) == 'App_Web') {
            $this->setConfig(array('url_postfix' => '.php', 'url_prefix' => ''));
        }
    }

    /**
     * Magic Quotes were a design error. Let's strip them if they are enabled.
     */
    public function cleanMagicQuotes()
    {
        if (!function_exists('stripslashes_array')) {
            function stripslashes_array(&$array, $iterations = 0)
            {
                if ($iterations < 3) {
                    foreach ($array as $key => $value) {
                        if (is_array($value)) {
                            stripslashes_array($array[$key], $iterations + 1);
                        } else {
                            $array[$key] = stripslashes($array[$key]);
                        }
                    }
                }
            }
        }

        if (get_magic_quotes_gpc()) {
            stripslashes_array($_GET);
            stripslashes_array($_POST);
            stripslashes_array($_COOKIE);
        }
    }

    /**
     * Sends default headers. Re-define to send your own headers.
     */
    public function sendHeaders()
    {
        header('Content-Type: text/html; charset=utf-8');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');               // Date in the past
        header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');  // always modified
        header('Cache-Control: no-store, no-cache, must-revalidate');   // HTTP/1.1
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');                                     // HTTP/1.0
    }

    /**
     * Call this method if you want to see execution time on the bottom of your pages.
     */
    public function showExecutionTime()
    {
        $this->addHook('post-render-output', array($this, '_showExecutionTime'));
        $this->addHook('post-js-execute', array($this, '_showExecutionTimeJS'));
    }

    /** @ignore */
    public function _showExecutionTime()
    {
        echo 'Took '.(time() + microtime() - $this->start_time).'s';
    }

    /** @ignore */
    public function _showExecutionTimeJS()
    {
        echo "\n\n/* Took ".number_format(time() + microtime() - $this->start_time, 5).'s */';
    }
    // }}}

    // {{{ Obsolete
    /**
     * This method is called when exception was caught in the application.
     *
     * @param Exception $e
     */
    public function caughtException($e)
    {
        $this->hook('caught-exception', array($e));
        throw $e;
        /* unreachable code
        echo '<span style="color:red">Problem with your request.</span>';
        echo "<p>Please use 'Logger' class for more sophisticated output<br>\$app-&gt;add('Logger');</p>";
        exit;
        */
    }

    /**
     * @todo Description
     *
     * @param string $msg
     * @param int    $shift
     *
     * @return bool|void
     */
    public function outputWarning($msg, $shift = 0)
    {
        if ($this->hook('output-warning', array($msg, $shift))) {
            return true;
        }
        echo '<span style="color:red">', $msg, '</span>';
    }

    /**
     * @todo Description
     *
     * @param string $msg
     * @param int    $shift
     *
     * @return bool|void
     */
    public function outputDebug($msg, $shift = 0)
    {
        if ($this->hook('output-debug', array($msg, $shift))) {
            return true;
        }
        echo '<span style="color:blue">', $msg, '</font><br />';
    }

    // }}}

    // {{{ Sessions
    /**
     * Initializes existing or new session.
     *
     * Attempts to re-initialize session. If session is not found,
     * new one will be created, unless $create is set to false. Avoiding
     * session creation and placing cookies is to enhance user privacy.
     * Call to memorize() / recall() will automatically create session
     *
     * @param bool $create
     */
    public $_is_session_initialized = false;
    public function initializeSession($create = true)
    {
        if ($this->_is_session_initialized || session_id()) {
            return;
        }

        // Change settings if defined in settings file
        $params = session_get_cookie_params();

        $params['httponly'] = true;   // true by default

        foreach ($params as $key => $default) {
            $params[$key] = $this->app->getConfig('session/'.$key, $default);
        }

        if ($create === false && !isset($_COOKIE[$this->name])) {
            return;
        }
        $this->_is_session_initialized = true;
        session_set_cookie_params(
            $params['lifetime'],
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
        session_name($this->name);
        session_start();
    }

    /**
     * Completely destroy existing session.
     */
    public function destroySession()
    {
        if ($this->_is_session_initialized) {
            $_SESSION = array();
            if (isset($_COOKIE[$this->name])) {
                setcookie($this->name/*session_name()*/, '', time() - 42000, '/');
            }
            session_destroy();
            $this->_is_session_initialized = false;
        }
    }
    // }}}

    // {{{ Sticky GET Argument implementation. Register stickyGET to have it appended to all generated URLs
    public $sticky_get_arguments = array();

    /**
     * Make current get argument with specified name automatically appended to all generated URLs.
     *
     * @param string $name
     *
     * @return string
     */
    public function stickyGet($name)
    {
        $this->sticky_get_arguments[$name] = @$_GET[$name];

        return $_GET[$name];
    }

    /**
     * Remove sticky GET which was set by stickyGET.
     *
     * @param string $name
     */
    public function stickyForget($name)
    {
        unset($this->sticky_get_arguments[$name]);
    }

    /** @ignore - used by URL class */
    public function getStickyArguments()
    {
        return $this->sticky_get_arguments;
    }

    /**
     * @todo Description
     *
     * @param string $name
     *
     * @return string
     */
    public function get($name)
    {
        return $_GET[$name];
    }

    /**
     * @todo Description
     *
     * @param string $file
     * @param string $ext
     * @param string $locate
     *
     * @return $this
     */
    public function addStylesheet($file, $ext = '.css', $locate = 'css')
    {
        //$file = $this->app->locateURL('css', $file . $ext);
        if (@$this->included[$locate.'-'.$file.$ext]++) {
            return;
        }

        if (strpos($file, 'http') !== 0 && $file[0] != '/') {
            $url = $this->locateURL($locate, $file.$ext);
        } else {
            $url = $file;
        }

        $this->template->appendHTML(
            'js_include',
            '<link type="text/css" href="'.$url.'" rel="stylesheet" />'."\n"
        );

        return $this;
    }
    // }}}

    // {{{ Very Important Methods
    /**
     * Call this method from your index file. It is the main method of Agile Toolkit.
     */
    public function main()
    {
        try {
            // Initialize page and all elements from here
            $this->initLayout();
        } catch (Exception $e) {
            if (!($e instanceof Exception_Stop)) {
                return $this->caughtException($e);
            }
            //$this->caughtException($e);
        }

        try {
            $this->hook('post-init');
            $this->hook('afterInit');

            $this->hook('pre-exec');
            $this->hook('beforeExec');

            if (isset($_GET['submit']) && $_POST) {
                $this->hook('submitted');
            }

            $this->hook('post-submit');
            $this->hook('afterSubmit');

            $this->execute();
        } catch (Exception $e) {
            $this->caughtException($e);
        }
        $this->hook('saveDelayedModels');
    }

    /**
     * Main execution loop.
     */
    public function execute()
    {
        $this->rendered['sub-elements'] = array();
        try {
            $this->hook('pre-render');
            $this->hook('beforeRender');
            $this->recursiveRender();
            if (isset($_GET['cut_object'])) {
                throw new BaseException("Unable to cut object with name='".$_GET['cut_object']."'. ".
                    "It wasn't initialized");
            }
            if (isset($_GET['cut_region'])) {
                // @todo Imants: This looks something obsolete. At least property cut_region_result is never defined.
                if (!$this->cut_region_result) {
                    throw new BaseException("Unable to cut region with name='".$_GET['cut_region']."'");
                }
                echo $this->cut_region_result;

                return;
            }
        } catch (Exception $e) {
            if ($e instanceof Exception_Stop) {
                $this->hook('cut-output');
                if (isset($e->result)) {
                    echo $e->result;
                }
                $this->hook('post-render-output');

                return;
            }
            throw $e;
        }
    }

    /**
     * Renders all objects inside applications and echo all output to the browser.
     */
    public function render()
    {
        $this->hook('pre-js-collection');
        if (isset($this->app->jquery) && $this->app->jquery) {
            $this->app->jquery->getJS($this);
        }

        if (!($this->template)) {
            throw new BaseException('You should specify template for APP object');
        }

        $this->hook('pre-render-output');
        if (headers_sent($file, $line)) {
            echo "<br />Direct output (echo or print) detected on $file:$line. <a target='_blank' "
                ."href='http://agiletoolkit.org/error/direct_output'>Use \$this->add('Text') instead</a>.<br />";
        }
        echo $this->template->render();
        $this->hook('post-render-output');
    }
    // }}}

    // {{{ Miscelanious Functions
    /**
     * Render only specified object or object with specified name.
     *
     * @param mixed $object
     *
     * @return $this
     */
    public function cut($object)
    {
        $_GET['cut_object'] = is_object($object) ? $object->name : $object;

        return $this;
    }

    /**
     * Perform instant redirect to another page.
     *
     * @param string $page
     * @param array  $args
     */
    public function redirect($page = null, $args = array())
    {
        /*
         * Redirect to specified page. $args are $_GET arguments.
         * Use this function instead of issuing header("Location") stuff
         */
        $url = $this->url($page, $args);
        if ($this->app->isAjaxOutput()) {
            if ($_GET['cut_page']) {
                echo '<script>'.$this->app->js()->redirect($url).'</script>Redirecting page...';
                exit;
            } else {
                $this->app->js()->redirect($url)->execute();
            }
        }
        header('Location: '.$url);
        exit;
    }

    /**
     * Called on all templates in the system, populates some system-wide tags.
     *
     * @param Template $t
     */
    public function setTags($t)
    {
        // Determine Location to atk_public
        if ($this->app->pathfinder && $this->app->pathfinder->atk_public) {
            $q = $this->app->pathfinder->atk_public->getURL();
        } else {
            $q = 'http://www.agiletoolkit.org/';
        }

        $t->trySet('atk_path', $q.'/');
        $t->trySet('base_path', $q = $this->app->pm->base_path);

        // We are using new capability of SMlite to process tags individually
        try {
            $t->eachTag($tag = 'template', array($this, '_locateTemplate'));
            $t->eachTag($tag = 'public', array($this, '_locatePublic'));
            $t->eachTag($tag = 'js', array($this, '_locateJS'));
            $t->eachTag($tag = 'css', array($this, '_locateCSS'));
            $t->eachTag($tag = 'page', array($this, '_locatePage'));
        } catch (BaseException $e) {
            throw $e
                ->addMoreInfo('processing_tag', $tag)
                ->addMoreInfo('template', $t->template_file)
                ;
        }

        $this->hook('set-tags', array($t));
    }

    /**
     * Returns true if browser is going to EVAL output.
     *
     * @todo rename into isJSOutput();
     *
     * @return bool
     */
    public function isAjaxOutput()
    {
        return isset($_POST['ajax_submit']) || ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest');
    }

    /** @private - NO PRIVATE !!! */
    public function _locateTemplate($path)
    {
        return $this->locateURL('public', $path);
    }
    public function _locatePublic($path)
    {
        return $this->locateURL('public', $path);
    }
    public function _locateJS($path)
    {
        return $this->locateURL('js', $path);
    }
    public function _locateCSS($path)
    {
        return $this->locateURL('css', $path);
    }
    public function _locatePage($path)
    {
        return $this->url($path);
    }

    /**
     * Only show $object in the final rendering.
     *
     * @deprecated 4.4
     */
    public function renderOnly($object)
    {
        return $this->cut($object);
    }
    // }}}

    // {{{ Layout implementation
    protected $layout_initialized = false;
    /**
     * Implements Layouts.
     * Layout is region in shared template which may be replaced by object.
     */
    public function initLayout()
    {
        if ($this->layout_initialized) {
            throw $this->exception('Please do not call initLayout() directly from init()', 'Obsolete');
        }
        $this->layout_initialized = true;
    }

    // TODO: layouts need to be simplified and obsolete, because we have have other layouts now.
    // doc/layouts
    //
    /**
     * Register new layout, which, if has method and tag in the template, will be rendered.
     *
     * @param string $name
     *
     * @return $this
     */
    public function addLayout($name)
    {
        if (!$this->template) {
            return;
        }
        // TODO: change to functionExists()
        if (method_exists($this, $lfunc = 'layout_'.$name)) {
            if ($this->template->is_set($name)) {
                $this->$lfunc();
            }
        }

        return $this;
    }

    /**
     * Default handling of Content page. To be replaced by App_Frontend
     * This function initializes content. Content is page-dependant.
     */
    public function layout_Content()
    {
        $page = str_replace('/', '_', $this->page);

        if (method_exists($this, $pagefunc = 'page_'.$page)) {
            $p = $this->add('Page', $this->page, 'Content');
            $this->$pagefunc($p);
        } else {
            $this->app->locate('page', str_replace('_', '/', $this->page).'.php');
            $this->add('page_'.$page, $page, 'Content');
            //throw new BaseException("No such page: ".$this->page);
        }
    }

    /**
     * Default template for the application. Redefine to add your own rules.
     *
     * @return array|string
     */
    public function defaultTemplate()
    {
        return array('html');
    }
    // }}}
}
