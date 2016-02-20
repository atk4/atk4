<?php
/***********************************************************
  ..

  Reference:
  http://agiletoolkit.org/doc/ref

==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
=====================================================ATK4=*/
/**
 * This is the description for the Class
 *
 * @author      Romans <romans@adevel.com>
 * @copyright   See file COPYING
 * @version     $Id$
 */
class Page extends AbstractView {

    /**
     * Specify page title which will be used inside the <title> tag in the main
     * application template.
     *
     * If array is specified in here, then it is considered to be a breadcrumb
     * in the following format:
     *
     * array ( 'page' => 'caption' )
     *
     * if page is numeric, then no link is added. Breadcrumb is
     * glued with title_separator
     */
    public $title = null;
    public $title_separator = '»';

    /**
     * @var array
     */
    public $metas = array();

    /**
     * Errors generated on the page are primarily there to alert the user,
     * although they are not logic-related and page shouldn't do validation
     * either.
     */
    public $default_exception='Exception_ForUser';

    function init(){
        $this->app->page_object=$this;
        $this->template->trySet('_page',$this->short_name);

        if(method_exists($this,get_class($this))){
            throw $this->exception('Your sub-page name matches your page class name. PHP will assume that your method is constructor.')
                ->addMoreInfo('method and class',get_class($this))
                ;
        }

        if($this->app instanceOf App_Frontend && @$this->app->layout && $this->app->layout->template->hasTag('page_title')) {
            $this->app->addHook('afterInit',array($this,'addBreadCrumb'));
        }

        if($this->app instanceOf App_Frontend && $this->app->template->hasTag('page_metas')) {
            $this->app->addHook('afterInit',array($this,'addMetas'));
        }

        if($this->app instanceOf App_Frontend && $this->app->template->hasTag('page_title')) {
            $this->app->addHook('afterInit',array($this,'addTitle'));
        }

        parent::init();
    }
    function defaultTemplate(){
        if(isset($_GET['cut_page']))return array('page');
        $page_name='page/'.strtolower($this->short_name);
        // See if we can locate the page
        try{
            $p=$this->app->locate('template',$page_name.'.html');
        }catch(Exception_PathFinder $e){
            return array('page');
        }
        return array($page_name,'_top');
    }
    function setTitle($title){
        $this->title = array($title);
        return $this;
    }
    function addTitle() {
        $first = true;
        $title = '';
        if (is_array($this->title)) {
            foreach ($this->title as $t) {
                if (!$first) {
                    $title = $title . $this->title_separator;
                }
                $first = false;
                if (is_array($t)) {
                    $title = $title . $t['name'];
                } else {
                    $title = $title . $t;
                }
            }
        } elseif($this->title) {
            $title = $this->title;
        } elseif($this->app->title) {
            $title = $this->app->title;
        }
        if (trim($title)) {
            $this->app->template->trySet('page_title',$title);
        }

    }
    function setMetaTag($key,$value) {
        $this->metas[$key] = $value;
    }
    function addMetas() {
        foreach ($this->metas as $k=>$v) {
            $this->app->template->appendHTML('page_metas',
                '<meta name="'.
                    htmlspecialchars($k,ENT_NOQUOTES,'UTF-8')
                .'" content="'.
                    htmlspecialchars($v,ENT_NOQUOTES,'UTF-8')
            .'" />'
            );
        }
    }
    function addCrumb($title,$page=null){
        // First, convert the main page
        if(is_string($this->title)) {
            $this->title=array(array(
                'name'=>$this->title,
                'page'=>null
            ));
        }
        array_unshift($this->title,array('name'=>$title,'page'=>$page));
        return $this;
    }
    function addCrumbReverse($title,$page=null){
        // First, convert the main page
        if(is_string($this->title)) {
            $this->title=array(array(
                'name'=>$this->title,
                'page'=>null
            ));
        }
        array_push($this->title,array('name'=>$title,'page'=>$page));
        return $this;
    }
    function addBreadCrumb() {
            $t = $this->title;
            if(!is_array($t)) {

                $last_title=$t;
                if($this->app->layout && $this->title) {
                    $this->app->layout->template->trySet('page_title',$this->title);
                }


            }else{
                $last_title = end($t);
                $last_title=$last_title['name'];

                $this->app->layout->add('View_Breadcrumb',null,'page_title')
                    ->setSource($this->title);
            }

            $tmp=array();
            if($last_title)$tmp[]=$last_title;
            if($this->app->title)$tmp[]=$this->app->title;
            $this->app->template->trySet('page_title',join(' - ',$tmp));
    }
    function recursiveRender(){
        if(isset($_GET['cut_page']) && !isset($_GET['cut_object']) && !isset($_GET['cut_region']))
            $_GET['cut_object']=$this->short_name;

        parent::recursiveRender();
    }
}
