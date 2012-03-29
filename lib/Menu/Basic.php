<?php
/***********************************************************
  ..

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
/**
 * This is the description for the Class
 *
 * @author		Romans <romans@adevel.com>
 * @copyright	See file COPYING
 * @version		$Id$
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
        if(isset($this->api->compat)){
            // exchange arguments
            list($page,$label)=array($label,$page);
        }
		if(!$label){
			$label=ucwords(str_replace('_',' ',$page));
		}
		$this->items[]=array(
						'page'=>$page,
						'href'=>$this->api->url($page),
						'label'=>$label,
						$this->class_tag=>$this->isCurrent($page)?$this->current_menu_class:$this->inactive_menu_class,
					   );
		return $this;
	}
	protected function getDefaultHref($label){
		$href=preg_replace('/[^a-zA-Z0-9]/','',$label);
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
    function render(){
        $this->setSource($this->items);
        parent::render();
    }
}
