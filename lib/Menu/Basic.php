<?php // vim:ts=4:sw=4:et:fdm=marker
/**
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
/**
 * Basic single-level Menu implementation on top of Lister
 *
 * @author      Romans <romans@agiletoolkit.org>
 */
class Menu_Basic extends CompleteLister {

    public $items=array();

    protected $class_tag='class';

    protected $last_item=null;

    public $current_menu_class="atk-state-active";

    public $inactive_menu_class="";

    /**
     * if set, then instead of setting a destination page for the URLs
     * the links will return to the same page, however new argument
     * will be added to each link containing ID of the menu
     */
    public $dest_var=null;

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
        return array('menu');
    }

    /**
     * This will add a new behaviour for clicking on the menu items. For 
     * example setTarget('frameURL') will show menu links inside a frame
     * instead of just linking to them
     */
    function setTarget($js_func){
        $this->on('click','a')->univ()->frameURL($this->js()->_selectorThis()->attr('href'));
        return $this;
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
    function addSubMenu($label){
        $f = $this->add('View_Popover'); // we use MenuSeparator tag here just to put View_Popover outside of UL list. Otherwise it breaks correct HTML and CSS.
        $this->addMenuItem($f->showJS('#'.$this->name.'_i'.count($this->items)),$label);
        return $f->add('Menu_jUI');
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
        return $href==$this->api->page||$href==';'.$this->api->page||$href.$this->api->getConfig('url_postfix','')==$this->api->page||(string)$href==(string)$this->api->url();
    }
    function render(){
        $this->setSource($this->items);
        parent::render();
    }
}
