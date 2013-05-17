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
    public $title = null;
    public $default_exception='Exception_ForUser';
    function init(){
        $this->api->page_object=$this;
        if($this->owner!=$this->api && !($this->owner instanceof BasicAuth)){
            throw $this->exception('Do not add page manually. Page is automatically initialized by the Application class')
                ->addMoreInfo('owner',$this->owner->name)
                ->addMoreInfo('api',$this->api->name)
                ;
        }
        $this->template->trySet('_page',$this->short_name);

        if(method_exists($this,get_class($this))){
            throw $this->exception('Your sub-page name matches your page class name. PHP will assume that your method is constructor.')
                ->addMoreInfo('method and class',get_class($this))
                ;
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
        $this->title = $title;
        return $this;
    }
    function recursiveRender(){
        if(isset($_GET['cut_page']) && !isset($_GET['cut_object']) && !isset($_GET['cut_region']))
            $_GET['cut_object']=$this->short_name;
        if($this->title && $this->owner instanceof ApiFrontend){
            $this->owner->template->trySet('page_title',$this->title);
        }
        parent::recursiveRender();
    }
}
