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
class Menu_Compat extends AbstractView {
    protected $items=array();
    protected $last_item=null;
    public $current_menu_class="ui-state-active";
    public $inactive_menu_class="ui-state-default";

    function init(){
        parent::init();
        // if controller was set - initializing menu now
    }
    function setController($controller){
        parent::setController($controller);
        $this->getController()->initMenu();
    }
    function defaultTemplate(){
        return array('menu','Menu');
    }
    function addMenuItem($label,$href=null){
        if(!$href){
            $href=$this->getDefaultHref($label);
            $label=ucwords($label);
        }
        $this->items[]=$this->last_item=$this->add('MenuItem',$this->short_name."_$href",'Item')
            ->setProperty(array(
                        'page'=>$href,
                        'href'=>$this->api->url($href),
                        'label'=>$label,
                        'class'=>$this->isCurrent($href)?$this->current_menu_class:$this->inactive_menu_class,
                       ));

        return $this;
    }
    protected function getDefaultHref($label){
        $href=$this->api->normalizeName($label,'');
        if($label[0]==';'){
            $label=substr($label,1);
            $href=';'.$href;
        }
        return $href;
    }
    function isCurrent($href){
        // returns true if item being added is current
        $href=str_replace('/','_',$href);
        return $href==$this->api->page||$href==';'.$this->api->page||$href.$this->api->getConfig('url_postfix','')==$this->api->page;
    }
    /*function insertMenuItem($index,$label,$href=null){
      $tail=array_slice($this->data,$index);
      $this->data=array_slice($this->data,0,$index);
      $this->addMenuItem($label,$href);
      $this->data=array_merge($this->data,$tail);
      return $this;
      }*/
    function addSeparator($template=null){
        $this->items[]=$this->add('MenuSeparator',$this->short_name.'_separator'.count($this->items),'Item',$template);
        return $this;
    }
}
class MenuItem extends AbstractView{
    protected $properties=array();

    function init(){
        parent::init();
    }
    function setProperty($key,$val=null){
        if(is_null($val)&&is_array($key)){
            foreach($key as $k=>$v)$this->setProperty($k,$v);
            return $this;
        }
        $this->properties[$key]=$val;
        return $this;
    }
    function render(){
        $this->template->set($this->properties);
        parent::render();
    }
    function defaultTemplate(){
        $owner_template=$this->owner->templateBranch();
        return array(array_shift($owner_template),'MenuItem');
    }
}
class MenuSeparator extends AbstractView{
    function defaultTemplate(){
        $owner_template=$this->owner->templateBranch();
        return array(array_shift($owner_template),'MenuSeparator');
    }
}
