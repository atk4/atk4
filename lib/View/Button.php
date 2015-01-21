<?php // vim:ts=4:sw=4:et:fdm=marker
/**
 * Implements button element
 *//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
class View_Button extends View
{
    // Menu class
    public $menu_class = 'Menu_jUI';

    // Popover class
    public $popover_class = 'View_Popover';

    // Options to pass to JS button widget
    public $options = array();

    // used jQuery-UI classes
    public $js_active_class = 'ui-state-highlight';     // active (selected) button
    public $js_triangle_class = 'ui-icon-triangle-1-s'; // triangle, down arrow icon



    /**
     * Set template of button element
     */
    function defaultTemplate()
    {
        return array('button');
    }




    // {{ Management of button
    /**
     * Set button without text and optionally with icon
     *
     * @param string $icon Icon CSS class
     *
     * @return $this
     */
    function setNoText($icon = null)
    {
        $this->options['text'] = false;
        if ($icon) {
            $this->setIcon($icon);
        }
        return $this;
    }

    /**
     * Sets icon for button
     *
     * @param string $icon Icon CSS class
     *
     * @return $this
     * @todo Implement this trough Icon view
     */
    function setIcon($icon)
    {
        if($icon[0]!='<')$icon='<i class="icon-'.$icon.'"></i>';
        $this->template->trySetHTML('icon',$icon);
        return $this;
    }

    /**
     * Sets label of button
     *
     * @param string $label
     *
     * @return $this
     */
    function setLabel($label)
    {
        if(is_array($label) && $label['icon']){
            $this->setIcon($label['icon']);

        }
        return $this->setText($label);
    }


    private $js_button_called = false;
    function jsButton()
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
     * Render button
     *
     * @return void
     */
    function render(){
        $this->addClass('atk-button');
        $c=$this->template->get('Content');
        if(is_array($c))$c=$c[0];
        if($this->template->get('icon') && $c) {
            $this->template->setHTML('nbsp','&nbsp;');
        }
        parent::render();
    }

    /**
     * Set button as HTML link object <a href="">
     *
     * @param string $page
     * @param array $args
     *
     * @return $this
     */
    function link($page, $args = array())
    {
        $this->setElement('a');
        $this->setAttr('href', $this->api->url($page, $args));

        return $this;
    }
    // }}}



    // {{{ Enhanced javascript functionality
    /**
     * When button is clicked, opens a frame containing generic view.
     * Because this is based on a dialog (popover), this is a modal action
     * even though it does not add dimming / transparency.
     *
     * @param array $js_options Options to pass to popover JS widget
     * @param array $class_options Options to pass to popover PHP class
     *
     * @return View_Popover
     */
    function addPopover($js_options = null, $class_options = null)
    {
        $this->options['icons']['secondary'] = $this->js_triangle_class;
        $popover = $this->owner->add($this->popover_class, $class_options, $this->spot);
        $this->js('click', $popover->showJS($this, $js_options));

        return $popover;
    }

    /**
     * Adds another button after this one with an arrow and returns it
     *
     * @param array $options Options to pass to new Button class
     *
     * @return Button New button object (button with triangle)
     */
    function addSplitButton($options = null)
    {
        $options = array_merge(
            array(
                'text'  => false,
                'icons' => array(
                    'primary' => $this->js_triangle_class
                ),
            ),
            $options?:array()
        );

        $but = $this->owner->add('Button', array('options' => $options), $this->spot);
        $this->owner->add('Order')->move($but, 'after', $this)->now();

        // Not very pretty, but works well
        $but
            ->js(true)
            ->removeClass('ui-corner-all')
            ->addClass('ui-corner-right')
            ->css('margin-left','-2px');

        $this
            ->js(true)
            ->removeClass('ui-corner-all')
            ->addClass('ui-corner-left')
            ->css('margin-right','-2px');

        return $but;
    }

    /**
     * Show menu when clicked. For example, dropdown menu.
     *
     * @param array $options Options to pass to Menu class
     * @param boolean $vertical Direction of menu (false=horizontal, true=vertical)
     *
     * @return Menu
     */
    function addMenu($options = array(), $vertical = false)
    {
        $this->options['icons']['secondary'] = $this->js_triangle_class;

        // add menu
        $this->menu = $this->owner->add($this->menu_class, $options, $this->spot);
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
        $this->js(true)->_selectorDocument()->bind('click',
            $this->js(null, array(
                $this->js()->removeClass($this->js_active_class),
                $this->menu->js()->hide(),
            ))->_enclose()
        );

        return $this->menu;
    }

    /**
     * Return array with position settings for JS
     *
     * @param boolean $vertical Direction of menu (false=horizontal, true=vertical)
     *
     * @return array
     */
    function getPosition($vertical = false) {
        return $vertical
            ? array( // vertical menu to the right
                'my' => 'right top',
                'at' => 'left top',
                'of' => $this
            )
            : array( // horizontal dropdown menu
                'my' => 'left top',
                'at' => 'left bottom',
                'of' => $this
            )
            ;
    }
    // }}}



    // {{{ Click handlers
    /**
     * Add click handler on button and returns true if button was clicked
     *
     * @param string $message Confirmation question to ask
     *
     * @return boolean
     */
    function isClicked($message = null)
    {
        $cl = $this->js('click')->univ();
        if ($message) {
            $cl->confirm($message);
        }

        $cl->ajaxec($this->api->url(null, array($this->name => 'clicked')),true);

        return isset($_GET[$this->name]);
    }

    /**
     * Add click handler on button and executes $callback if button was clicked
     *
     * @param callback $callback Callback function to execute
     * @param string $confirm_msg Confirmation question to ask
     *
     * @return void Executes JavaScript and stop processing
     */
    function onClick($callback, $confirm_msg = null)
    {
        if ($this->isClicked($confirm_msg)) {

            // TODO: add try catch here
            $ret = call_user_func($callback, $this, $_POST);

            // if callback response is JS, then execute it
            if ($ret instanceof jQuery_Chain) {
                $ret->execute();
            }

            // blank chain otherwise
            $this->js()->univ()->successMessage(is_string($ret)?$ret:'Success')->execute();
        }
    }
    // }}}
}
