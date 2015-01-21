<?php // vim:ts=4:sw=4:et:fdm=marker
/**
 * This is the most appropriate API file for your web application. It builds on
 * top of App_Web and introduces concept of "Pages" on top of "Layout" concept
 * defined in App_Web.
 *
 * @link http://agiletoolkit.org/learn/understand/api
 * @link http://agiletoolkit.org/learn/template
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
class App_Frontend extends App_Web {

    /** When page is determined, it's class instance is created and stored in here */
    public $page_object=null;

    /** Class which is used for static pages */
    public $page_class='Page';

    /** List of pages which are routed into namespace */
    public $namespace_routes=array();

    /** Object for a custom layout, introduced in 4.3 */
    public $layout=null;

    // {{{ Layout Implementation
    /** Content in the global (shared.html) template is rendered by page object. This method loads either class or static file */
    function initLayout(){
        parent::initLayout();
        $this->addLayout('Content');
    }
    /**
     * Pages with a specified prefix will loaded from a specified namespace.
     *
     * @param  [type] $prefix [description]
     * @param  [type] $ns     [description]
     * @return [type]         [description]
     */
    function routePages($prefix,$ns=null){
        if(!$ns)$ns=$prefix;
        $this->namespace_routes[$prefix]=$this->normalizeClassName($ns);
    }
    function layout_Content(){
        $this->template->trySet('pagename','page-'.$this->page);

        $layout = $this->layout ?: $this;

        // TODO: refactor using pathfinders 4th argument to locate = null,
        // to avoid exceptions as those might be expensive.

        // This function initializes content. Content is page-dependant
        $page=str_replace('/','_',$this->page);
        $page=str_replace('-','',$page);
        $class='page_'.$page;

        if($this->app->page_object)return;   // page is already initialized;

        if(method_exists($this,$class)){
            $this->page_object=$layout->add($this->page_class,$page);
            $this->$class($this->page_object);
        }else{
            $class_parts=explode('_',$page);
            $funct_parts=array();
            $ns='';
            if($this->namespace_routes[$page]){
                $ns=$this->namespace_routes[$page].'\\';
                $class='page_index';
            }else{
                while($class_parts){
                    array_unshift($funct_parts,array_pop($class_parts));
                    if($ns1=$this->namespace_routes[join('_',$class_parts)]){
                        $class='page_'.join('_',$funct_parts);
                        $ns=$ns1.'\\';
                        $page=join('_',$funct_parts);
                        break;
                    }
                }
            }

            try{
                $this->app->pathfinder->loadClass($ns.$class);
            }catch(PathFinder_Exception $e){


                // page not found, trying to load static content
                try{
                    $this->loadStaticPage($this->page);
                }catch(PathFinder_Exception $e2){

                    $class_parts=explode('_',$page);
                    $funct_parts=array();
                    while($class_parts){
                        array_unshift($funct_parts,array_pop($class_parts));
                        $fn='page_'.join('_',$funct_parts);
                        if($class_parts){
                            $in=$ns.'page_'.join('_',$class_parts);
                        }else{
                            $in=$ns.'page_index';
                        }
                        if($in=='page_')$in='page_index';
                        try {
                            $this->app->pathfinder->loadClass($in);
                        }catch(PathFinder_Exception $e3){
                            continue;
                        }
                        // WorkAround for PHP5.2.12+ PHP bug #51425
                        $tmp=new $in;
                        if(!method_exists($tmp,$fn) && !method_exists($tmp,'subPageHandler'))continue;

                        $this->page_object=$layout->add($in,$page);
                        if(method_exists($tmp,$fn)){
                            $this->page_object->$fn();
                        }elseif(method_exists($tmp,'subPageHandler')){
                            if($this->page_object->subPageHandler(join('_',$funct_parts))===false)break;
                        }
                        return;
                    }

                    $e->addMoreInfo('static_page_error',$e2->getText());

                    // throw original error
                    $this->pageNotFound($e);
                }
                return;
            }
            // i wish they implemented "finally"
            $this->page_object=$layout->add($ns.$class,$page,'Content');
            if(method_exists($this->page_object,'initMainPage'))$this->page_object->initMainPage();
            if(method_exists($this->page_object,'page_index'))$this->page_object->page_index();
        }
    }
    /**
     * This method is called as a last resort, when page is not found. It receives the exception with the actual error
     *
     * @param  [type] $e [description]
     * @return [type]    [description]
     */
    function pageNotFound($e){
        throw $e;
    }
    /**
     * Attempts to load static page. Raises exception if not found
     *
     * @param  [type] $page [description]
     * @return [type]       [description]
     */
    protected function loadStaticPage($page){
        $layout = $this->layout ?: $this;
        try{
            $t='page/'.str_replace('_','/',strtolower($page));
            $this->template->findTemplate($t);

            $this->page_object=$layout->add($this->page_class,$page,'Content',array($t));
        }catch(PathFinder_Exception $e2){

            $t='page/'.strtolower($page);
            $this->template->findTemplate($t);
            $this->page_object=$layout->add($this->page_class,$page,'Content',array($t));
        }

        return $this->page_object;
    }
    // }}}

    function caughtException($e) {
        if ($e instanceof Exception_Migration) {

            try {

                // The mesage is for user. Let's display it nicely.

                $this->app->pathfinder->addLocation(array('public'=>'.'))
                   ->setCDN('http://www4.agiletoolkit.org/atk4');

                $l=$this->app->add('Layout_Basic',null,null,array('layout/installer'));
                $i=$l->add('View')->addClass('atk-align-center');
                $i->add('H1')->set($e->getMessage());

                if ($e instanceof Exception_Migration) {
                    $i->add('P')->set('Hello and welcome to Agile Toolkit 4.3. Your project may require some minor tweaks before you can use 4.3.');
                }

                $b=$i->add('Button')->addClass('atk-swatch-green');
                $b->set(array('Migration Guide','icon'=>'book'));
                $b->link('https://github.com/atk4/docs/blob/master/articles/migration42/index.md');

                if ($this->app->template && $this->app->template->hasTag('Layout')) {
                    $t=$this->app->template;
                } else {
                    $t=$this->add('GiTemplate');
                    $t->loadTemplate('html');
                }

                $t->setHTML('Layout',$l->getHTML());
                $t->trySet('css','http://css.agiletoolkit.org/framework/css/installer.css');
                echo $t->render();

                exit;
            } catch (BaseException $e){
                echo 'here';
                $this->app->add('Logger');
                return parent::caughtException($e);
            }


        }
        return parent::caughtException($e);
    }
}
