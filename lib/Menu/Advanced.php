<?php

abstract class Menu_Advanced extends View
{
    public $swatch='ink';
    public $hover_swatch=null;

    /**
     * Adds a title to your menu.
     */

    public function addTitle($title, $class = 'Menu_Advanced_Title')
    {
        $i = $this->add($class,null,null,
            array_merge($this->defaultTemplate(),array('Title'))
        );

        $i->set($title);

        return $i;
    }

    public function addItem($title, $action=null, $class='Menu_Advanced_Item')
    {
        $i = $this->add($class,null,null,
            array_merge($this->defaultTemplate(),array('Item'))
        );

        if (is_array($title)) {

            if ($title['badge']) {
                $i->add('View',null,'Badge')
                    ->setElement('span')
                    ->addClass('atk-label')
                    ->set($title['badge']);
                unset($title['badge']);
            }

        }

        if ($action) {
            if (is_string($action) || is_array($action) || $action instanceof URL) {
                $i->template->set('url',$url = $this->api->url($action));
                if($url->isCurrent()){
                    $i->addClass('active');
                }
            } else {
                $this->on('click',$action);
            }
        }

        $i->set($title);

        return $i;
    }

    public function addMenu($title, $class=null, $options=array())
    {
        if(is_null($class))$class='Menu_Vertical';
        if($class=='Horizontal')$class='Menu_Horizontal';

        $i = $this->add('Menu_Advanced_Item',null,null,
            array_merge($this->defaultTemplate(),array('Menu'))
        );
        if ($this->hover_swatch) {
            $i->template->set('li-class','atk-swatch-'.$this->hover_swatch);
        }

        if (is_array($title)) {

            /*
            // Allow to set custom classes on a element
            if ($title['a']) {
                $this->setComponents($title['a'],'a');
                unset($title['a']);
            }
             */

        }
        $i->set($title);

        $m = $i->add($class,array(
            'swatch'=>$options['swatch'] ?: $this->swatch,
            'hover_swatch'=>$this->hover_swatch
        ),'SubMenu');

        return $m;
    }

    public function addSeparator($class='Menu_Advanced_Separator')
    {
        $i = $this->add($class,null,null,
            $x=array_merge($this->defaultTemplate(),array('Separator'))
        );

        return $i;
    }

    public function setModel($m, $options=array())
    {
        $m=parent::setModel($m);
        foreach ($m as $model) {

            // check subitems
            if (@$model->hierarchy_controller && $model[strtolower($model->hierarchy_controller->child_ref).'_cnt']) {
                $m=$this->addMenu($model[$options['title_field']?:$m->title_field]);
                foreach ($model->ref($model->hierarchy_controller->child_ref) as $child) {
                    $m->addItem($options['title_field']?:$child[$options['title_field']?:$model->title_field],$child['page']);
                }
            } else {
                $this->addItem($model[$options['title_field']?:$model->title_field],$model['page']);
            }
        }
        return $m;
    }

    // compatibility
    public function addMenuItem($page,$label=null)
    {
        if (!$label) {
            $label=ucwords(str_replace('_',' ',$page));
        }

        return $this->addItem($label,$page);

    }
    public function addLabel($label)
    {
        return $this->addTitle($label);
    }

}
