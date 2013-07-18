<?php
class Menu_Objective extends View {

    function init(){
        parent::init();
        $this->setElement('ul');
    }


    function isCurrent($href){
        // returns true if item being added is current
        if(!is_object($href))$href=str_replace('/','_',$href);
        return $href==$this->api->page||$href==';'.$this->api->page||$href.$this->api->getConfig('url_postfix','')==$this->api->page||(string)$href==(string)$this->api->url();
    }

    function addMenuItem($page,$label=null){
        if(!$label){
            $label=ucwords(str_replace('_',' ',$page));
        }

        $li=$this->add('View')->setElement('li');
        $a=$li->add('View')->setElement('a')->set($label);

        if($page instanceof jQuery_Chain){
            $li->js('click',$page);
            return $li;
        }

        $a->setAttr('href',$this->api->url($page));

        if($this->isCurrent($page) && $this->current_menu_class){
            $li->addClass($this->current_menu_class);
        }

        return $li;
    }

    function addSubMenu($name){
        $li=$this->add('View')
            ->setElement('li')
            ;
        $li->add('Text')->set($name);
        return $li
            ->add(get_class($this));
    }
}
