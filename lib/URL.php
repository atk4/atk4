<?php
/**
 * When $app->url() is called, this object is used to avoid
 * double-encoding. Return URL when converting to string.
 */
class URL extends AbstractModel
{
    // Page is a location of destination page. It have to be absolute and relative to project root
    public $page = null;

    public $arguments = array();

    protected $extension = '.html';

    protected $absolute = false;  // if true then will return full URL (for external documents)

    public $base_url = null;

    private $_current = false;
    private $_current_sub = false;

    public function init()
    {
        parent::init();

        if (!isset($this->app->pm)) {
            throw $this->exception('You must initialize PageManager first');
        }

        if (!$this->app->pm->base_url) {
            throw $this->exception('PageManager is did not parse request URL. '.
                'Use either parseRequestedURL or setURL (if you are in CLI application)');
        }

        $this->addStickyArguments();
        $this->extension = $this->app->getConfig('url_postfix', $this->extension);
    }

    /** [private] add arguments set as sticky through APP */
    public function addStickyArguments()
    {
        $sticky = $this->app->getStickyArguments();
        $args = array();

        if (is_array($sticky) && !empty($sticky)) {
            foreach ($sticky as $key => $val) {
                if ($val === true) {
                    if (isset($_GET[$key])) {
                        $val = $_GET[$key];
                    } else {
                        continue;
                    }
                }
                if (!isset($args[$key])) {
                    $args[$key] = $val;
                }
            }
        }
        $this->setArguments($args);
    }

    /** Call this if you want full URL, not relative */
    public function useAbsoluteURL()
    {
        /*
           Produced URL will contain absolute, rather than relative address:
            http://mysite:123/install/dir/my/page.html
         */
        return $this->absolute();
    }

    public function absolute()
    {
        $this->absolute = true;

        return $this;
    }

    /**
     * Detects if the URL matches current page.
     *
     * If $inc_parent is set to true, then it will also try to match sub-pages.
     * If $inc_args is set to true then it will also try to match arguments.
     *
     * @param bool $inc_parent
     * @param bool $inc_args
     *
     * @return bool
     */
    public function isCurrent($inc_parent = false, $inc_args = false)
    {
        // strict match with arguments
        if ($inc_args) {
            return $this->app->url()->getURL() == $this->getURL();
        }

        // match current page or parent page without arguments
        return $this->_current || ($inc_parent && $this->_current_sub);
    }

    /** [private] automatically called with 1st argument of $app->url() */
    public function setPage($page = null)
    {
        // The following argument formats are supported:
        //
        // null = set current page
        // '.' = set current page
        // 'page' = sets webroot/page.html
        // './page' = set page relatively to current page
        // '..' = parent page
        // '../page' = page besides our own (foo/bar -> foo/page)
        // 'index' = properly points to the index page defined in APP
        // '/admin/' = will not be converted

        /** @type $this->app App_Web */

        $destination = '';

        if (is_null($page)) {
            $page = '.';
        }

        foreach (explode('/', $page) as $component) {
            if ($component == '') {
                continue;
            }
            if ($component == '.' && $destination == '') {
                if ($this->app->page == 'index') {
                    continue;
                }
                $destination = str_replace('_', '/', $this->app->page);
                continue;
            }

            if ($component == '..') {
                if (!$destination) {
                    $destination = str_replace('_', '/', $this->app->page);
                }
                $tmp = explode('/', $destination);
                array_pop($tmp);
                $destination = implode('/', $tmp);
                continue;
            }

            if ($component == 'index' && $destination == '') {
                $destination = $this->app->index_page;
                continue;
            }

            $destination = $destination ? $destination.'/'.$component : $component;
        }
        if ($destination === '') {
            $destination = @$this->app->index_page;
        }

        $this->page = $destination;

        // check if it's current page
        list($p, $ap) = str_replace('/', '_', array($this->page, $this->app->page));
        $this->_current = ($p == $ap); // page exactly match
        $this->_current_sub = ($p == substr($ap, 0, strlen($p)) && isset($ap[strlen($p)]) && $ap[strlen($p)] == '_'); // first part of page match followed by separator

        return $this;
    }

    /** Set additional arguments */
    public function set($argument, $value = null)
    {
        if (!is_array($argument)) {
            $argument = array($argument => $value);
        }

        return $this->setArguments($argument);
    }

    /** Get value of an argument */
    public function get($argument)
    {
        return $this->arguments[$argument];
    }

    /**
     * Set arguments to specified array.
     *
     * @param array $arguments
     *
     * @return $this
     */
    public function setArguments($arguments = array())
    {
        // add additional arguments
        if (!$arguments) {
            $arguments = array();
        }
        if (!is_array($arguments)) {
            throw new BaseException('Arguments must be always an array');
        }
        $this->arguments = $args = array_merge($this->arguments, $arguments);
        foreach ($args as $arg => $val) {
            if (is_null($val)) {
                unset($this->arguments[$arg]);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->getURL();
    }

    public function setURL($url)
    {
        return $this->setBaseURL($url);
    }

    /** By default uses detected base_url, but you can use this to redefine */
    public function setBaseURL($base)
    {
        $this->base_url = $base;

        return $this;
    }

    public function getBaseURL()
    {
        // Oherwise - calculate from detected values
        $url = '';

        // add absolute if necessary
        if ($this->absolute) {
            $url .= $this->app->pm->base_url;
        }

        // add base path
        $url .= $this->app->pm->base_path;

        return $url;
    }

    public function getExtension()
    {
        return $this->extension;
    }

    public function getArguments($url = null)
    {
        $tmp = array();
        foreach ($this->arguments as $key => $value) {
            if ($value !== false) {
                $tmp[$key] = $value;
            }
        }
        return $tmp ? (strpos($url, '?') !== false ? '&' : '?').http_build_query($tmp) : '';
    }

    public function getURL()
    {
        if ($this->base_url) {
            return $this->base_url.$this->getArguments($this->base_url);
        }

        // Optional hook, which can change properties for page and arguments
        // based on some routing logic
        $url = $this->app->hook('buildURL', array($this));
        if (is_string($url)) {
            return $url;
        }

        $url = $this->getBaseURL();
        if ($this->page && $this->page != 'index') {
            // add prefix if defined in config
            $url .= $this->app->getConfig('url_prefix', '');
            $url .= $this->page;
            $url .= $this->getExtension();
        }

        $url .= $this->getArguments($url);

        return $url;
    }

    /** Returns html-encoded URL */
    public function getHTMLURL()
    {
        return htmlentities($this->getURL());
    }
}
