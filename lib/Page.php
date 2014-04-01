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
     * api template.
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
     * Errors generated on the page are primarily there to alert the user,
     * although they are not logic-related and page shouldn't do validation
     * either.
     */
    public $default_exception='Exception_ForUser';

    function init(){
        $this->api->page_object=$this;
        $this->template->trySet('_page',$this->short_name);

        if(method_exists($this,get_class($this))){
            throw $this->exception('Your sub-page name matches your page class name. PHP will assume that your method is constructor.')
                ->addMoreInfo('method and class',get_class($this))
                ;
        }

        if($this->api instanceOf App_Frontend && @$this->api->layout && $this->api->layout->template->hasTag('page_title')) {
            $this->api->addHook('afterInit',array($this,'addBreadCrumb'));
        }

        parent::init();
    }
    function defaultTemplate(){
        if(isset($_GET['cut_page']))return array('page');
        $page_name='page/'.strtolower($this->short_name);
        // See if we can locate the page
        try{
            $p=$this->api->locate('templates',$page_name.'.html');
        }catch(PathFinder_Exception $e){
            return array('page');
        }
        return array($page_name,'_top');
    }
    function setTitle($title){
        $this->title = array($title);
        return $this;
    }
    function addCrumb($title,$page=null){
        // First, convert the main page
        if(is_string($this->title)) {
            $this->title=array(array(
                'name'=>$this->title,
                'page'=>null
            ));
        }
        $this->title[]=array('name'=>$title,'page'=>$page);
        return $this;
    }
    function addBreadCrumb() {
            $t = $this->title;
            if(!is_array($t)) { 

                $last_title=$t;
                if($this->api->layout && $this->title) {
                    $this->api->layout->template->trySet('page_title',$this->title);
                }


            }else{
                $last_title = end($t);
                $last_title=$last_title['name'];

                $this->api->layout->add('View_Breadcrumb',null,'page_title')
                    ->setSource($this->title);
            }

            $tmp=array();
            if($last_title)$tmp[]=$last_title;
            if($this->api->title)$tmp[]=$this->api->title;
            $this->api->template->trySet('page_title',join(' - ',$tmp));
    }
    function recursiveRender(){
        if(isset($_GET['cut_page']) && !isset($_GET['cut_object']) && !isset($_GET['cut_region']))
            $_GET['cut_object']=$this->short_name;

        parent::recursiveRender();
    }
}
