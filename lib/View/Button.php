<?php
/**
 * Implements button element.
 */
class View_Button extends View
{
    /** @var string Menu class */
    public $menu_class = 'Menu_jUI';

    /** @var string Popover class */
    public $popover_class = 'View_Popover';

    /** @var array Options to pass to JS button widget */
    public $options = array();

    /** @var string jQuery-UI class for active (selected) button */
    public $js_active_class = 'ui-state-highlight';

    /** @var string jQuery-UI class for triangle, down arrow button */
    public $js_triangle_class = 'ui-icon-triangle-1-s';

    /** @var Menu_jUI */
    public $menu;

    /** @var string This property looks quite obsolete */
    public $icon;

    /** @var VirtualPage */
    public $virtual_page;

    /** @var bool */
    protected $js_button_called = false;

    /**
     * Set template of button element.
     *
     * @var array|string
     */
    public function defaultTemplate()
    {
        return array('button');
    }

    // {{ Management of button
    /**
     * Set button without text and optionally with icon.
     *
     * @param string $icon Icon CSS class
     *
     * @return $this
     */
    public function setNoText($icon = null)
    {
        $this->options['text'] = false;
        if ($icon !== null) {
            $this->setIcon($icon);
        }

        return $this;
    }

    /**
     * Sets icon for button.
     *
     * @param string $icon Icon CSS class
     *
     * @return $this
     *
     * @todo Implement this trough Icon view
     */
    public function setIcon($icon)
    {
        if ($icon[0] != '<') {
            $icon = '<i class="icon-'.$icon.'"></i>';
        }
        $this->template->trySetHTML('icon', $icon);

        return $this;
    }

    /**
     * Sets label of button.
     *
     * @param string|array $label
     *
     * @return $this
     */
    public function setLabel($label)
    {
        if (is_array($label) && $label['icon']) {
            $this->setIcon($label['icon']);
        } elseif (is_string($label)) {
            return $this->setText($label);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function jsButton()
    {
        if ($this->js_button_called) {
            return $this;
        }

        $this->js_button_called = true;
        if ($this->icon) {
            $this->options['icons']['primary'] = $this->icon;
        }

        // enable widget
        $this->js(true)->button($this->options);

        return $this;
    }

    /**
     * Render button.
     */
    public function render()
    {
        $this->addClass('atk-button');
        $c = $this->template->get('Content');
        if (is_array($c)) {
            $c = $c[0];
        }
        if ($this->template->get('icon') && $c) {
            $this->template->setHTML('nbsp', '&nbsp;');
        }
        parent::render();
    }

    /**
     * Set button as HTML link object <a href="">.
     *
     * @param string $page
     * @param array  $args
     *
     * @return $this
     */
    public function link($page, $args = array())
    {
        $this->setElement('a');
        $this->setAttr('href', $this->app->url($page, $args));

        return $this;
    }
    // }}}

    // {{{ Enhanced javascript functionality
    /**
     * When button is clicked, opens a frame containing generic view.
     * Because this is based on a dialog (popover), this is a modal action
     * even though it does not add dimming / transparency.
     *
     * @param array $js_options    Options to pass to popover JS widget
     * @param array $class_options Options to pass to popover PHP class
     *
     * @return View_Popover
     */
    public function addPopover($js_options = array(), $class_options = null)
    {
        $this->options['icons']['secondary'] = $this->js_triangle_class;

        /** @var View_Popover $popover */
        $popover = $this->owner->add($this->popover_class, $class_options, $this->spot);

        $this->js('click', $popover->showJS($js_options));

        return $popover;
    }

    /**
     * Adds another button after this one with an arrow and returns it.
     *
     * @param array $options Options to pass to new Button class
     *
     * @return Button New button object (button with triangle)
     */
    public function addSplitButton($options = null)
    {
        $options = array_merge(
            array(
                'text' => false,
                'icons' => array(
                    'primary' => $this->js_triangle_class,
                ),
            ),
            $options ?: array()
        );

        /** @var Button $but */
        $but = $this->owner->add('Button', array('options' => $options), $this->spot);

        /** @var Order $order */
        $order = $this->owner->add('Order');
        $order->move($but, 'after', $this)->now();

        // Not very pretty, but works well
        $but
            ->js(true)
            ->removeClass('ui-corner-all')
            ->addClass('ui-corner-right')
            ->css('margin-left', '-2px');

        $this
            ->js(true)
            ->removeClass('ui-corner-all')
            ->addClass('ui-corner-left')
            ->css('margin-right', '-2px');

        return $but;
    }

    /**
     * Show menu when clicked. For example, dropdown menu.
     *
     * @param array $options  Options to pass to Menu class
     * @param bool  $vertical Direction of menu (false=horizontal, true=vertical)
     *
     * @return Menu
     */
    public function addMenu($options = array(), $vertical = false)
    {
        $this->options['icons']['secondary'] = $this->js_triangle_class;

        // add menu
        $this->menu = $this->owner->add($this->menu_class, $options, $this->spot);
        /** @var Menu_jUI $this->menu */
        $this->menu->addStyle('display', 'none');

        // show/hide menu on button click
        $this->js('click', array(
            $this->js()
                ->toggleClass($this->js_active_class),
            $this->menu->js()
                ->toggle()
                ->position($this->getPosition($vertical)),
        ));

        // hide menu on clicking outside of menu
        $this->js(true)->_selectorDocument()->bind(
            'click',
            $this->js(null, array(
                $this->js()->removeClass($this->js_active_class),
                $this->menu->js()->hide(),
            ))->_enclose()
        );

        return $this->menu;
    }

    /**
     * Return array with position settings for JS.
     *
     * @param bool $vertical Direction of menu (false=horizontal, true=vertical)
     *
     * @return array
     */
    public function getPosition($vertical = false)
    {
        return $vertical
            ? array( // vertical menu to the right
                'my' => 'right top',
                'at' => 'left top',
                'of' => $this,
            )
            : array( // horizontal dropdown menu
                'my' => 'left top',
                'at' => 'left bottom',
                'of' => $this,
            )
            ;
    }
    // }}}

    // {{{ Click handlers
    /**
     * Add click handler on button and returns true if button was clicked.
     *
     * @param string $message Confirmation question to ask
     *
     * @return bool
     */
    public function isClicked($message = null)
    {
        $cl = $this->js('click')->univ();
        if ($message !== null) {
            $cl->confirm($message);
        }

        $cl->ajaxec($this->app->url(null, array($this->name => 'clicked')), true);

        return isset($_GET[$this->name]);
    }

    /**
     * Add click handler on button and executes $callback if button was clicked.
     *
     * @param callback $callback    Callback function to execute
     * @param string   $confirm_msg Confirmation question to ask
     *
     * @return $this
     */
    public function onClick($callback, $confirm_msg = null)
    {
        if ($this->isClicked($confirm_msg)) {

            // TODO: add try catch here
            $ret = call_user_func($callback, $this, $_POST);

            // if callback response is JS, then execute it
            if ($ret instanceof jQuery_Chain) {
                $ret->execute();
            }

            // blank chain otherwise
            $this->js()->univ()->successMessage(is_string($ret) ? $ret : 'Success')->execute();
        }

        return $this;
    }

    /**
     * Add click handler on button, that will execute callback. Similar to
     * onClick, however output from callback execution will appear in a
     * dialog window with a console.
     *
     * @param callable $callback
     * @param string $title
     */
    public function onClickConsole($callback, $title = null)
    {
        if (is_null($title)) {
            $title = $this->template->get('Content');
        }

        $this->virtual_page = $this->add('VirtualPage', ['type' => 'frameURL']);
        /** @var VirtualPage $this->virtual_page */
        $this->virtual_page
            ->bindEvent($title)
            ->set(function ($p) use ($callback) {
                /** @var View_Console $console */
                $console = $p->add('View_Console');
                $console->set($callback);
            });
    }
    // }}}
}
