<?php
class Menu_Vertical extends Menu_Objective {
    public $current_menu_class = 'atk-state-active';

    function addMenuItem($page,$label=null){
        if(!$label){
            $label=ucwords(str_replace('_',' ',$page));
        }

        $a=$this->add('View',null,null,array('menu/vertical','row'))//->setElement('a')
            ->addClass('atk-padding-small atk-cells')
            ->set($label);

        if($page instanceof jQuery_Chain){
            $a->js('click',$page);
            return $li;
        }

        $a->template->set('href',$this->api->url($page));

        if($this->isCurrent($page) && $this->current_menu_class){
            $a->addClass($this->current_menu_class);
        }

        return $a;
    }

    function addLabel($label){

        $a=$this->add('View')->setElement('span')
            ->setClass('atk-padding-small atk-size-kilo')
            ->set($label);

        return $a;
    }



    function defaultTemplate() {
        return array('menu/vertical');
    }
}
