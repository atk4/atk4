<?php
/**
 * This controller allows you to have nice url rewrites without using web
 * server. Usage:.
 *
 * in Frontend:
 *
 * $r = $this->add("Controller_PatternRouter")
 *   ->addRule("(news\/.*)", "news_item", array("u"))
 *   ->route();
 *
 * if REQUEST_URI is "/news/some-name-of-your-news/", then router would:
 * 1) set $this->app->page to "news_item"
 * 2) set $_GET["u"] to "news/some-name-of-your-news/"
 * uri.
 *
 * Authors: j@agiletech.ie, r@agiletech.ie.
 */
class Controller_PatternRouter extends AbstractController
{
    protected $links = array();

    protected $rules = array();

    public function init()
    {
        parent::init();
        $this->app->router = $this;
        $this->app->addHook('buildURL', $this);
    }

    public function buildURL($junk, $url)
    {
        if ($this->links[$url->page]) {
            // start consuming arguments

            $args = $this->links[$url->page];
            foreach ($args as $key => $match) {
                if (is_numeric($key)) {
                    $key = $match;
                }

                if (isset($url->arguments[$key])) {
                    $url->page .= '/'.$url->arguments[$key];
                    unset($url->arguments[$key]);
                }
            }
        }
    }

    public function url()
    {
    }

    /**
     * Link method creates a bi-directional link between a URL and
     * a page along with some GET parameters. This method is
     * entirely tranpsarent and can be added for pages which
     * are already developed at any time.
     *
     * Example:
     *
     * $this->link('profile',array('user_id'));
     */
    public function link($page, $args = array())
    {
        if ($this->links[$page]) {
            throw $this->exception('This page is already linked')
                ->addMoreInfo($page);
        }

        $this->links[$page] = $args;

        return $this;
    }

    /**
     * Add new rule to the pattern router. If $regexp is matched, then
     * page is changed to $target and arguments returned by preg_match
     * are stored inside GET as per supplied params array.
     */
    public function addRule($regex, $target = null, $params = null)
    {
        $this->rules[] = array($regex, $target, $params);

        return $this;
    }

    /**
     * Allows use of models. Define a model with fields:
     *  - rule
     *  - target
     *  - params (comma separated).
     *
     * and content of that model will be used to auto-fill routing
     */
    public function setModel($model)
    {
        $model = parent::setModel($model);

        foreach ($model as $rule) {
            $this->addRule($rule['rule'], $rule['target'], explode(',', $rule['params']));
        }

        return $this;
    }
    /**
     * Perform the necessary changes in the APP's page. After this
     * you can still get the orginal page in app->page_orig.
     */
    public function route()
    {
        $this->app->page_orig = $this->app->page;

        foreach ($this->links as $page => $args) {
            $page = str_replace('/', '_', $page);

            // Exact match, no more routing needed
            if ($this->app->page == $page) {
                return $this;
            }

            $page .= '_';

            if (substr($this->app->page, 0, strlen($page)) == $page) {
                $rest = explode('_', substr($this->app->page, strlen($page)));

                reset($args);
                foreach ($rest as $arg) {
                    list($key, $match) = each($args);
                    if (is_numeric($key) || is_null($key)) {
                        $key = $match;
                    } else {
                        if (!preg_match($match, $arg)) {
                            break 2;
                        }
                    }
                    $_GET[$key] = $arg;
                }

                $this->app->page = substr($page, 0, -1);

                return $this;
            }

            //$misc=explode()$this->app->page = substr
        }

        $r = $_SERVER['REQUEST_URI'];
        foreach ($this->rules as $rule) {
            if (preg_match('/'.$rule[0].'/', $r, $t)) {
                $this->app->page = $rule[1];
                if ($rule[2]) {
                    foreach ($rule[2] as $k => $v) {
                        $_GET[$v] = $t[$k + 1];
                    }
                }
            }
        }
    }
}
