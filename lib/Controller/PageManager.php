<?php
/**
 * This is a generic page manager. For web applications it calculates
 * base URI, sets up path manager with the URI locations, determines
 * which page was requested.
 *
 * This class works with PathFinder, App_Web, and Location.
 *
 * Terminology:
 *
 * Project - entirety of your files including shared folders,
 * documestation, migrations scripts, support files etc etc.
 * Project concept is introduced in Agile Toolkit 4.3
 *
 * Interface - (such as "admin", "frontend", "cli", "api")
 * is located in the project folder and contains interface
 * specific pages, librares and templates.
 *
 * Location - represents a physical location on the filesystem,
 * where files can be located either through file access or
 * whrough URL access.
 *
 *
 * you can access variabless below through $this->app->pm->base_url
 * concatinate them to get full URL
 */
class Controller_PageManager extends AbstractController
{
    /**
     *  Base URL defines the absolute destination of our server. Because some
     *  other resources may be located outside of our Base Path, we need to
     *  know a Base URL.
     *
     *  For CLI scripts, you need to set this manually. Also if you are
     *  going to use URLs in emails, you should use this.
     *
     *  See also: URL::useAbsoluteURL();
     */
    public $base_url;           // http://yoursite.com:81

    /**
     *  Base PATH points to the top location of our project. Basically it's
     *  where the project is installed in the webroot. This is determined
     *  by thelocation of catch-all file. It is determined by SCRIPT_NAME
     *  which should be supported by most web installations. It will also
     *  work when mod_rewrite is not used.
     *
     *  You can use $base_path in your script to put it say on a logo link
     *
     *  Also - some other parts of the library may have a different path,
     *  for example base_path could be = /admin/, and atk4_path could be /amodules/
     *
     *  If project is installed in web-root, then $base_path will be "/"
     *
     *  path always starts and ends with slash
     */
    public $base_path;          // /admin/

    /**
     *  This is a third and a final part of the URLs. This points to a page
     *  which were reuqested. You can pass path to getDestinationURL() function,
     *  as a first argument. Also $path is used to determine which page class
     *  to load.
     *
     *  Page must never start with slash. Also if path is empty, then
     *  the "index" is used automatically.
     */
    public $page;               // user/add

    public $template_filename;

    public function init()
    {
        parent::init();

        $this->app->pm = $this;
        // Firstly, the original URL is retrieved. This function should
        // take care of all possible rewrite engines and bring up a real
        // URL which matches the one in the browser. Also e will need to
        // determine a relative path for the requested page
    }

    public function setURL($url)
    {
        $url = parse_url($url);

        $scheme = isset($url['scheme']) ? $url['scheme'].'://' : '';
        $host = isset($url['host']) ? $url['host'] : '';
        $port = isset($url['port']) ? ':'.$url['port'] : '';
        $user = isset($url['user']) ? $url['user'] : '';
        $pass = isset($url['pass']) ? ':'.$url['pass']  : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = isset($url['path']) ? $url['path'] : '';

        if (substr($path, -1) != '/') {
            $path .= '/';
        }

        $this->base_url = $scheme.$user.$pass.$host.$port;
        $this->base_path = $path;
        $this->app->page = 'index';

        return $this;
    }

    /**
     * Detect server environment and tries to guess absolute and relative
     * URLs to your application.
     *
     * See docs: doc/application/routing/parsing
     */
    public function parseRequestedURL()
    {
        // This is the re-constructions of teh proper URL.
        // 1. Schema
        $url = $this->app->getConfig('atk/base_url', null);
        if (is_null($url)) {
            // Detect it
            $url = 'http';
            $https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || $_SERVER['SERVER_PORT'] == 443;
            if ($https) {
                $url .= 's';
            }

            // 2. Continue building. We are adding hostname next and port.
            $url .= '://'.$_SERVER['SERVER_NAME'];
            //if($_SERVER["SERVER_PORT"]!="80")$url .= ":".$_SERVER['SERVER_PORT'];
            if (($_SERVER['SERVER_PORT'] == '80' && !$https) || ($_SERVER['SERVER_PORT'] == '443' && $https)) {
                ;
            } else {
                $url .= ':'.$_SERVER['SERVER_PORT'];
            }
        }

        // We have now arrived at base_url as defined
        $this->base_url = $url;

        // 3. Next we need a base_part of our URL. There are many different
        // variables and approaches we tried it, REDIRECT_URL_ROOT, REDIRECT_URL,
        // etc, however most reliable is $this->unix_dirname(SCRIPT_NAME)
        $path = $this->unix_dirname($_SERVER['SCRIPT_NAME']);
        if (substr($path, -1) != '/') {
            $path .= '/';
        }

        // We have now arrived at base_path as defined
        $this->base_path = $path;

        // 4. We now look at RequestURI and extract base_path from the beginning
        if (isset($_GET['page'])) {
            $page = $_GET['page'];
            $this->page = $page;
        } else {
            $request_uri = $this->getRequestURI();
            if (strpos($request_uri, $path) !== 0) {
                throw $this->exception('URL matching problem')
                    ->addMoreInfo('RequestURI', $request_uri)
                    ->addMoreInfo('BasePath', $path);
            }
            $page = substr($request_uri, strlen($path));
            if (!$page) {
                $page = 'index';
            }

            // Preserve actual page
            $this->page = $page;

            // Remove postfix from page if any
            $page = preg_replace('/\..*$/', '', $page);
            $page = preg_replace('/\/$/', '', $page);
            $page = str_replace('/', '_', $page);

            if (substr($page, -1) == '_') {
                $page = substr($page, 0, -1);
            }
        }

        if (strpos($page, '.') !== false) {
            throw $this->exception('Page may not contain periods (.)')
            ->addMoreInfo('page', $page);
        }

        // We have now arrived at the page as per specification.
        $this->app->page = str_replace('/', '_', $page);

        $this->template_filename = $this->app->page;
        if (substr($this->template_filename, -1) == '/') {
            $this->template_filename .= 'index';
        }
    }
    public function debug()
    {
        $this->debug = true;
        parent::debug('base_path='.$this->base_path);
        parent::debug('page='.$this->app->page);
        parent::debug('template_filename='.$this->template_filename);
    }
    public function getRequestURI()
    {
        // WARNING. This function URI excludes query string

        if (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // IIS
            $request_uri = $_SERVER['HTTP_X_REWRITE_URL'];
        } elseif (isset($_SERVER['REQUEST_URI'])) { // Apache
            $request_uri = $_SERVER['REQUEST_URI'];
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0, PHP as CGI
            $request_uri = $_SERVER['ORIG_PATH_INFO'];
            // This one comes without QUERRY string
        } else {
            throw new BaseException('Unable to determine RequestURI. This shouldn\'t be called at all in CLI');
        }
        $request_uri = explode('?', $request_uri, 2);

        return $request_uri[0];
    }
    public function unix_dirname($path)
    {
        $chunks = explode('/', $path);
        array_pop($chunks);
        if (!$chunks) {
            return '/';
        }

        return implode('/', $chunks);
    }
}
