<?php
/**
 * This is the most appropriate APP file for your web application. It builds on
 * top of App_Web and introduces concept of "Pages" on top of "Layout" concept
 * defined in App_Web.
 */
class App_Frontend extends App_Web
{
    /**
     * When page is determined, it's class instance is created and stored in here.
     *
     * @var Page
     */
    public $page_object = null;

    /**
     * Class which is used for static pages.
     *
     * @var string
     */
    public $page_class = 'Page';

    /**
     * List of pages which are routed into namespace.
     *
     * @var array
     */
    public $namespace_routes = array();

    /**
     * Object for a custom layout, introduced in 4.3.
     *
     * @var AbstractView
     */
    public $layout = null;

    /** @var App_Frontend */
    public $app;

    // {{{ Layout Implementation
    /**
     * Content in the global (shared.html) template is rendered by page object.
     * This method loads either class or static file.
     */
    public function initLayout()
    {
        parent::initLayout();
        $this->addLayout('Content');
    }

    /**
     * Pages with a specified prefix will loaded from a specified namespace.
     *
     * @param string $prefix
     * @param string $ns
     */
    public function routePages($prefix, $ns = null)
    {
        $this->namespace_routes[$prefix] = $this->normalizeClassName($ns ?: $prefix);
    }

    /**
     * @todo Description
     */
    public function layout_Content()
    {
        $this->template->trySet('pagename', 'page-'.$this->page);

        $layout = $this->layout ?: $this;

        // TODO: refactor using pathfinders 4th argument to locate = null,
        // to avoid exceptions as those might be expensive.

        // This function initializes content. Content is page-dependant
        $page = str_replace('/', '_', $this->page);
        $page = str_replace('-', '', $page);
        $class = 'page_'.$page;

        if ($this->app->page_object) { // page is already initialized;
            return;
        }

        if (method_exists($this, $class)) {
            $this->page_object = $layout->add($this->page_class, $page);
            $this->$class($this->page_object);
        } else {
            $class_parts = explode('_', $page);
            $funct_parts = array();
            $ns = '';
            if ($this->namespace_routes[$page]) {
                $ns = $this->namespace_routes[$page].'\\';
                $class = 'page_index';
            } else {
                while (! empty($class_parts)) {
                    array_unshift($funct_parts, array_pop($class_parts));
                    if ($ns1 = $this->namespace_routes[implode('_', $class_parts)]) {
                        $ns = $ns1.'\\';
                        $page = implode('_', $funct_parts);
                        $class = 'page_'.$page;
                        break;
                    }
                }
            }

            try {
                $this->app->pathfinder->loadClass($ns.$class);
            } catch (Exception_PathFinder $e) {

                // page not found, trying to load static content
                try {
                    $this->loadStaticPage($this->page);
                    if ($this->layout) {
                        $this->layout->template->tryDel('has_page_title');
                    }
                } catch (Exception_PathFinder $e2) {
                    $class_parts = explode('_', $page);
                    $funct_parts = array();
                    while (! empty($class_parts)) {
                        array_unshift($funct_parts, array_pop($class_parts));
                        $fn = 'page_'.implode('_', $funct_parts);
                        if (! empty($class_parts)) {
                            $in = $ns.'page_'.implode('_', $class_parts);
                        } else {
                            $in = $ns.'page_index';
                        }
                        if ($in == 'page_') {
                            $in = 'page_index';
                        }
                        try {
                            $this->app->pathfinder->loadClass($in);
                        } catch (Exception_PathFinder $e3) {
                            continue;
                        }
                        // WorkAround for PHP5.2.12+ PHP bug #51425
                        // @todo Maybe this can be removed because we don't support PHP 5.2 anymore
                        $tmp = new $in();
                        if (!method_exists($tmp, $fn) && !method_exists($tmp, 'subPageHandler')) {
                            continue;
                        }

                        $this->page_object = $layout->add($in, $page);
                        /** @type Page $this->page_object */
                        if (method_exists($tmp, $fn)) {
                            $this->page_object->$fn();
                        } elseif (method_exists($tmp, 'subPageHandler')) {
                            if ($this->page_object->subPageHandler(implode('_', $funct_parts)) === false) {
                                break;
                            }
                        }

                        return;
                    }

                    $e->addMoreInfo('static_page_error', $e2->getText());

                    // throw original error
                    $this->pageNotFound($e);
                }

                return;
            }

            // i wish they implemented "finally"
            $this->page_object = $layout->add($ns.$class, $page, 'Content');
            /** @type Page $this->page_object */
            if (method_exists($this->page_object, 'initMainPage')) {
                $this->page_object->initMainPage();
            }
            if (method_exists($this->page_object, 'page_index')) {
                $this->page_object->page_index();
            }
        }
    }

    /**
     * This method is called as a last resort, when page is not found.
     * It receives the exception with the actual error.
     *
     * @param Exception $e
     */
    public function pageNotFound($e)
    {
        throw $e;
    }

    /**
     * Attempts to load static page. Raises exception if not found.
     *
     * @param string $page
     *
     * @return Page
     */
    protected function loadStaticPage($page)
    {
        $layout = $this->layout ?: $this;
        try {
            $t = 'page/'.str_replace('_', '/', strtolower($page));
            $this->template->findTemplate($t);

            $this->page_object = $layout->add($this->page_class, $page, 'Content', array($t));
        } catch (Exception_PathFinder $e2) {
            $t = 'page/'.strtolower($page);
            $this->template->findTemplate($t);
            $this->page_object = $layout->add($this->page_class, $page, 'Content', array($t));
        }

        return $this->page_object;
    }
    // }}}

    /**
     * @todo Description
     *
     * @param Exception $e
     */
    public function caughtException($e)
    {
        if ($e instanceof Exception_Migration) {
            try {

                // The mesage is for user. Let's display it nicely.
                $this->app->pathfinder->addLocation(array('public' => '.'))
                   ->setCDN('http://www.agiletoolkit.org/');

                /** @type Layout_Basic $l */
                $l = $this->app->add('Layout_Basic', null, null, array('layout/installer'));
                
                /** @type View $i */
                $i = $l->add('View');
                $i->addClass('atk-align-center');
                /** @type H1 $h */
                $h = $i->add('H1');
                $h->set($e->getMessage());

                if ($e instanceof Exception_Migration) {
                    /** @type P $p */
                    $p = $i->add('P');
                    $p->set('Hello and welcome to Agile Toolkit 4.3. '.
                        'Your project may require some minor tweaks before you can use 4.3.');
                }

                /** @type Button $b */
                $b = $i->add('Button');
                $b->addClass('atk-swatch-green')
                    ->set(array('Migration Guide', 'icon' => 'book'))
                    ->link('https://github.com/atk4/docs/blob/master/articles/migration42/index.md');

                if ($this->app->template && $this->app->template->hasTag('Layout')) {
                    $t = $this->app->template;
                } else {
                    /** @type GiTemplate $t */
                    $t = $this->add('GiTemplate');
                    $t->loadTemplate('html');
                }

                $t->setHTML('Layout', $l->getHTML());
                $t->trySet('css', 'http://css.agiletoolkit.org/framework/css/installer.css');
                echo $t->render();

                exit;
            } catch (BaseException $e) {
                echo 'here';
                $this->app->add('Logger');

                return parent::caughtException($e);
            }
        }

        return parent::caughtException($e);
    }
}
