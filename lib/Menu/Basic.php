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
class Menu_Basic extends CompleteLister {
    protected $items=array();
    protected $class_tag='class';
    protected $item_tag='MenuItem';
    protected $container_tag='Item';
    protected $last_item=null;
    public $current_menu_class="ui-state-active";
    public $inactive_menu_class="ui-state-default";
    function init(){
        if($this->template->is_set('current')){
            $this->current_menu_class=$this->template->get('current');
            $this->inactive_menu_class='';
            $this->template->del('current');
            $this->class_tag='current';
        }
        parent::init();
    }
    function defaultTemplate(){
        return array('menu','Menu');
    }
    function addMenuItem($page,$label=null){
        if(!$label){
            $label=ucwords(str_replace('_',' ',$page));
        }
        $id=$this->name.'_i'.count($this->items);
        $label=$this->api->_($label);
        $js_page=null;
        if($page instanceof jQuery_Chain){
            $js_page="#";
            $this->js('click',$page)->_selector('#'.$id);
            $page=$id;
        }
        $this->items[]=array(
            'id'=>$id,
            'page'=>$page,
            'href'=>$js_page?:$this->api->url($page),
            'label'=>$label,
            $this->class_tag=>$this->isCurrent($page)?$this->current_menu_class:$this->inactive_menu_class,
        );
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
        if(!is_object($href))$href=str_replace('/','_',$href);
        return $href==$this->api->page||$href==';'.$this->api->page||$href.$this->api->getConfig('url_postfix','')==$this->api->page;
    }
    function render(){
        $this->setSource($this->items);
        parent::render();
    }
}
