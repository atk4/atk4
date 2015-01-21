<?php
/***********************************************************
  Generic class for several UI elements (warnings, errors, info)

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
class View_Box extends View {
    function set($text){
        $this->template->set('Content',$text);
        return $this;
    }
    /**
     * By default box uses information Icon. You can use addIcon() to override or $this->template->del('Icon') to remove.
     *
     * @param [type] $i [description]
     */
    function addIcon($i){
        return $this->add('Icon',null,'Icon')->set($i)->addClass('atk-size-mega');
    }

    /**
     * Adds Button on the right side of the box for follow-up action
     *
     * @param [type] $page  [description]
     * @param string $label [description]
     */
    function addButton($label='Continue'){
        if(!is_array($label)){
            $label=array($label, 'icon-r'=>'right-big');
        }
        return $this->add('Button',null,'Button')
            ->set($label);
    }


    function link($page){
        $this->addButton(false)->link($page);
    }
    /**
     * View box can be closed by clicking on the cross
     */
    function addClose() {

        if($this->recall('closed',false))$this->destroy();

        $self = $this;
        $this->add('Icon',null,'Button')
            ->addComponents(array('size'=>'mega'))
            ->set('cancel-1')
            ->addStyle(array('cursor'=>'pointer'))
            ->on('click',function($js) use($self) {
                $self->memorize('closed', true);
                return $self->js()->hide()->execute();
            });

        return $this;
    }

    function defaultTemplate(){
        return array('view/box');
    }
}
