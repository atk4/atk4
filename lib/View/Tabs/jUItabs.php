<?php // vim:ts=4:sw=4:et:fdm=marker
/*
 * Undocumented
 *
 * @link http://agiletoolkit.org/
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
/**
 * Implementation of jQuery UI Tabs
 *
 * Use: 
 *  $tabs=$this->add('Tabs');
 *  $tabs->addTab('Tab1')->add('LoremIpsum');
 *
 *  $tabs->addTabURL('./details','Details');    // AJAX tab
 *
 * @license See http://agiletoolkit.org/about/license
 * 
**/
class View_Tabs_jUItabs extends View_Tabs {
    public $tab_template=null;
    public $options=array();
    public $position='top'; // can be 'top','left','right','bottom'

    // should we show loader indicator while loading tabs
    public $show_loader = true;

    function init(){
        parent::init();
        $this->tab_template=$this->template->cloneRegion('tabs');
        $this->template->del('tabs');
    }
    /* Set tabs option, for example, 'active'=>'zero-based index of tab */
    function setOption($key,$value){
        $this->options[$key]=$value;
        return $this;
    }
    function toBottom() {
    	$this->position='bottom';
    }
    function toLeft() {
    	$this->position='left';
    }
    function toRight() {
    	$this->position='right';
    }
    function render(){
        // add loader to JS events
        if ($this->show_loader) {
            $this->options['beforeLoad'] = $this->js()->_enclose()->_selectorThis()
                ->atk4_loader()->atk4_loader('showLoader');
            $this->options['load'] = $this->js()->_enclose()->_selectorThis()
                ->atk4_loader()->atk4_loader('hideLoader');
        }
        // render JUI tabs
        $this->js(true)
            ->tabs($this->options);

        if ($this->position=="bottom"){
        	$this->js(true)->_selector('#'.$this->name)
		        	->addClass("tabs-bottom")
        	;
        	$this->js(true)->_selector('.tabs-bottom .ui-tabs-nav, .tabs-bottom .ui-tabs-nav *')
		        	->removeClass("ui-corner-all ui-corner-top")
		        	->addClass("ui-corner-bottom")
        	;
        	$this->js(true)->_selector(".tabs-bottom .ui-tabs-nav")
        			->appendTo(".tabs-bottom")
        	;
        }
        
        if ( ($this->position=="left") || ($this->position=="right") ){
        	$this->js(true)->_selector('#'.$this->name)
		        	->addClass("ui-tabs-vertical ui-helper-clearfix")
        	;
        	$this->js(true)->_selector('#'.$this->name.' li')
		        	->removeClass("ui-corner-top")
		        	->addClass("ui-corner-".$this->position)
        	;
        }
        
        return parent::render();
    }
    /* Add tab and returns it so that you can add static content */
    function addTab($title,$name=null){

        $container=$this->add('View_HtmlElement',$name);

        $this->tab_template->set(array(
                    'url'=>'#'.$container->name,
                    'tab_name'=>$title,
                    'tab_id'=>$container->short_name,
                    ));
        $this->template->appendHTML('tabs',$this->tab_template->render());
        return $container;
    }
    /* Add tab which loads dynamically. Returns $this for chaining */
    function addTabURL($page,$title=null){
        if(is_null($title)){
            $title=ucwords(preg_replace('/[_\/\.]+/',' ',$page));
        }
        $this->tab_template->set(array(
                    'url'=>$this->api->url($page,array('cut_page'=>1)),
                    'tab_name'=>$title,
                    'tab_id'=>basename($page),
                    ));
        $this->template->appendHTML('tabs',$this->tab_template->render());
        return $this;
    }
    function defaultTemplate(){
        return array('tabs');

    }
}
