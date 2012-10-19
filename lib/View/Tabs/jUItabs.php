<?php
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
    public $options=array('cache'=>false);

    function init(){
        parent::init();
        $this->tab_template=$this->template->cloneRegion('tabs');
        $this->template->del('tabs');
    }
    /* Set tabs option, for example, 'selected'=>'zero-based index of tab */
    function setOption($key,$value){
        $this->options[$key]=$value;
        return $this;
    }
    function render(){
        $this->js(true)
            ->tabs($this->options);

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
    function addTabURL($page,$title){
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
