<?php
/**
 * This is the description for the Class.
 *
 * @author      Romans <romans@adevel.com>
 * @copyright   See file COPYING
 *
 * @version     $Id$
 */
// @codingStandardsIgnoreStart
class Menu_Compat extends AbstractView
{
    protected $items = array();
    protected $last_item = null;
    public $current_menu_class = 'ui-state-active';
    public $inactive_menu_class = 'ui-state-default';

    // {{{ Inherited properties

    /** @var View */
    public $owner;

    /** @var App_Web */
    public $app;

    // }}}

    public function init()
    {
        parent::init();
        // if controller was set - initializing menu now
    }
    public function setController($controller)
    {
        parent::setController($controller);
        $this->getController()->initMenu();
    }
    public function defaultTemplate()
    {
        return array('menu', 'Menu');
    }
    public function addMenuItem($label, $href = null)
    {
        if (!$href) {
            $href = $this->getDefaultHref($label);
            $label = ucwords($label);
        }
        $this->items[] = $this->last_item = $this->add('MenuItem', $this->short_name."_$href", 'Item')
            ->setProperty(array(
                        'page' => $href,
                        'href' => $this->app->url($href),
                        'label' => $label,
                        'class' => $this->isCurrent($href) ? $this->current_menu_class : $this->inactive_menu_class,
                       ));

        return $this;
    }
    protected function getDefaultHref($label)
    {
        $href = $this->app->normalizeName($label, '');
        if ($label[0] == ';') {
            $label = substr($label, 1);
            $href = ';'.$href;
        }

        return $href;
    }
    public function isCurrent($href)
    {
        // returns true if item being added is current
        $href = str_replace('/', '_', $href);

        return $href == $this->app->page
            || $href == ';'.$this->app->page
            || $href.$this->app->getConfig('url_postfix', '') == $this->app->page;
    }
    /*function insertMenuItem($index,$label,$href=null){
      $tail=array_slice($this->data,$index);
      $this->data=array_slice($this->data,0,$index);
      $this->addMenuItem($label,$href);
      $this->data=array_merge($this->data,$tail);
      return $this;
      }*/
    public function addSeparator($template = null)
    {
        $this->items[] = $this->add(
            'MenuSeparator',
            $this->short_name.'_separator'.count($this->items),
            'Item',
            $template
        );

        return $this;
    }
}
class MenuItem extends AbstractView
{
    protected $properties = array();

    public function init()
    {
        parent::init();
    }
    public function setProperty($key, $val = null)
    {
        if (is_null($val) && is_array($key)) {
            foreach ($key as $k => $v) {
                $this->setProperty($k, $v);
            }

            return $this;
        }
        $this->properties[$key] = $val;

        return $this;
    }
    public function render()
    {
        $this->template->set($this->properties);
        parent::render();
    }
    public function defaultTemplate()
    {
        $owner_template = $this->owner->templateBranch();

        return array(array_shift($owner_template), 'MenuItem');
    }
}
class MenuSeparator extends AbstractView
{
    public function defaultTemplate()
    {
        $owner_template = $this->owner->templateBranch();

        return array(array_shift($owner_template), 'MenuSeparator');
    }
}
// @codingStandardsIgnoreEnd
