<?php
/**
 * The layout engine helping you to create a flexible and responsive layout
 * of your page. The best thing is - you don't need to CSS !
 *
 * Any panel you have added can have a number of classes applied. Of course
 * you are can use those classes in other circumstances too.
 *
 *
 */
class Layout_Fluid extends Layout_Basic {

    /**
     * Pointns to a user_menu object
     *
     * @var [type]
     */
    public $user_menu;

    /**
     * Points to a footer, if initialized
     *
     * @var [type]
     */
    public $footer;

    /**
     * Points to menu left-menu if initialized
     *
     * @var [type]
     */
    public $menu;

    /**
     * Points to top menu
     *
     * @var [type]
     */
    public $top_menu;

    function defaultTemplate() {
        return array('layout/fluid');
    }

    function init(){
        parent::init();
        if ($this->template->hasTag('UserMenu')) {
            if(isset($this->app->auth)){
                $this->user_menu = $this->add('Menu_Horizontal',null,'UserMenu')
                    ->addMenu($this->app->auth->model[$this->app->auth->model->title_field]);
                $this->user_menu->addItem('Logout','logout');
            } else {
                $this->template->tryDel('UserMenu');
                $this->template->tryDel('user_icon');
            }
        }
    }

    function addHeader($class = 'Menu_Objective') {
        $this->header_wrap = $this->add('View',null,'Header',array('layout/fluid','Header'));

        $this->header=$this->header_wrap->add($class,null,'Header_Content');

        return $this->header;
    }

    function addMenu($class = 'Menu_Vertical', $options=null) {
        return $this->menu = $this->add($class,$options,'Main_Menu');
    }

    function addFooter($class = 'View') {
        return $this->footer = $this->footer = $this->add($class,null,'Footer_Content');
    }
}
