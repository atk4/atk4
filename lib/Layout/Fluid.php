<?php
/**
 * The layout engine helping you to create a flexible and responsive layout
 * of your page. The best thing is - you don't need to CSS !
 *
 * Any panel you have added can have a number of classes applied. Of course
 * you are can use those classes in other circumstances too.
 */
class Layout_Fluid extends Layout_Basic
{
    /**
     * Points to a user_menu object.
     *
     * @var Menu_Advanced
     */
    public $user_menu;

    /**
     * Points to a footer, if initialized.
     *
     * @var View
     */
    public $footer;

    /**
     * Points to menu left-menu if initialized.
     *
     * @var Menu_Advanced
     */
    public $menu;

    /**
     * Points to top menu.
     *
     * @var Menu_Advanced
     */
    public $top_menu;

    /**
     * Undocumented.
     *
     * @var View
     */
    public $header;

    /**
     * Undocumented.
     *
     * @var View
     */
    public $header_wrap;

    // {{{ Inherited properties

    /** @var App_Web */
    public $app;

    // }}}

    /**
     * Initializaction.
     */
    public function init()
    {
        parent::init();
        if ($this->template->hasTag('UserMenu')) {
            if (isset($this->app->auth)) {
                /** @var Menu_Horizontal $this->user_menu */
                $this->user_menu = $this->add('Menu_Horizontal', null, 'UserMenu');
                $this->user_menu->addMenu($this->app->auth->model[$this->app->auth->model->title_field]);
                $this->user_menu->addItem('Logout', 'logout');
            } else {
                $this->template->tryDel('UserMenu');
                $this->template->tryDel('user_icon');
            }
        }
    }

    /**
     * Adds header.
     *
     * @param string $class
     *
     * @return View
     */
    public function addHeader($class = 'Menu_Objective')
    {
        /** @var View */
        $this->header_wrap = $this->add('View', null, 'Header', array('layout/fluid', 'Header'));

        $this->header = $this->header_wrap->add($class, null, 'Header_Content');

        return $this->header;
    }

    /**
     * Adds menu.
     *
     * @param string $class
     * @param array $options
     *
     * @return Menu_Advanced
     */
    public function addMenu($class = 'Menu_Vertical', $options = null)
    {
        return $this->menu = $this->add($class, $options, 'Main_Menu');
    }

    /**
     * Adds footer.
     *
     * @param string $class
     *
     * @return View
     */
    public function addFooter($class = 'View')
    {
        return $this->footer = $this->add($class, null, 'Footer_Content');
    }

    /**
     * Default template.
     *
     * @return array|string
     */
    public function defaultTemplate()
    {
        return array('layout/fluid');
    }
}
