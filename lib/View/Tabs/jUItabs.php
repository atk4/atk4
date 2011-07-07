<?php
/***********************************************************
  Implementation of jQuery UI Tabs

  Reference:
  http://agiletoolkit.org/doc/ref

 **ATK4*****************************************************
 This file is part of Agile Toolkit 4 
 http://agiletoolkit.org

 (c) 2008-2011 Agile Technologies Ireland Limited
 Distributed under Affero General Public License v3

 If you are using this file in YOUR web software, you
 must make your make source code for YOUR web software
 public.

 See LICENSE.txt for more information

 You can obtain non-public copy of Agile Toolkit 4 at
 http://agiletoolkit.org/commercial

 *****************************************************ATK4**/
class View_Tabs_jUItabs extends View_Tabs {
    public $tab_template=null;

    function init(){
        parent::init();
        $this->js(true)
            ->tabs(array('cache'=>false));
        $this->tab_template=$this->template->cloneRegion('tabs');
        $this->template->del('tabs');
    }
    /* Add tab and returns it so that you can add static content */
    function addTab($title,$name=null){

        $container=$this->add('View_HtmlElement',$name);

        $this->tab_template->set(array(
                    'url'=>'#'.$container->name,
                    'tab_name'=>$title,
                    'tab_id'=>$container->short_name,
                    ));
        $this->template->append('tabs',$this->tab_template->render());
        return $container;
    }
    /* Add tab which loads dynamically. Returns $this for chaining */
    function addTabURL($page,$title,$args=array()){
        $this->tab_template->set(array(
                    'url'=>$this->api->getDestinationURL($page,array('cut_page'=>1)),
                    'tab_name'=>$title,
                    'tab_id'=>basename($page),
                    ));
        $this->template->append('tabs',$this->tab_template->render());
        return $this;
    }
    function defaultTemplate(){
        return array('tabs');

    }
}
