<?php
/**
 * This controller allows you to have nice url rewrites without using web 
 * server. Usage:
 *
 * in Frontend:
 *
 * $r = $this->add("Controller_PatternRouter")
 *   ->addRule("(news\/.*)", "news_item", array("u"))
 *   ->route();
 *
 * if REQUEST_URI is "/news/some-name-of-your-news/", then router would:
 * 1) set $this->api->page to "news_item"
 * 2) set $_GET["u"] to "news/some-name-of-your-news/"
 * uri.
 *
 * Authors: j@agiletech.ie, r@agiletech.ie.
 */
class Controller_PatternRouter extends AbstractController {
    protected $rules;
    function init(){
        parent::init();
        $this->api->router = $this;
    }
    /**
     * Add new rule to the pattern router. If $regexp is matched, then
     * page is changed to $target and arguments returned by preg_match
     * are stored inside GET as per supplied params array
     */
    function addRule($regex, $target=null, $params=null){
        $this->rules[] = array($regex, $target, $params);
        return $this;
    }
    /**
     * Allows use of models. Define a model with fields:
     *  - rule
     *  - target
     *  - params (comma separated)
     *
     * and content of that model will be used to auto-fill routing
     */
    function setModel($model){
        $model=parent::setModel($model);

        foreach ($model as $rule){
            $this->addRule($rule["rule"], $rule["target"], explode(",", $rule["params"]));  
        }
        return $this;
    }
    /**
     * Perform the necessary changes in the API's page. After this
     * you can still get the orginal page in api->page_orig.
     */
    function route(){
        $this->api->page_orig = $this->api->page;
        $r=$_SERVER["REQUEST_URI"];
        foreach ($this->rules as $rule){
            if (preg_match("/" . $rule[0] . "/", $r, $t)){
                $this->api->page = $rule[1];
                if ($rule[2]){
                    foreach ($rule[2] as $k => $v){
                        $_GET[$v] = $t[$k+1];
                    }
                }
            }
        }
    }
}
