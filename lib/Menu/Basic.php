<?php
/**
 * Basic single-level Menu implementation on top of Lister.
 *
 * @author      Romans <romans@agiletoolkit.org>
 */
class Menu_Basic extends CompleteLister
{
    public $items = array();

    protected $class_tag = 'class';

    protected $last_item = null;

    public $current_menu_class = 'atk-state-active';

    public $inactive_menu_class = '';

    /**
     * if set, then instead of setting a destination page for the URLs
     * the links will return to the same page, however new argument
     * will be added to each link containing ID of the menu.
     */
    public $dest_var = null;

    public function init()
    {
        if ($this->template->is_set('current')) {
            $this->current_menu_class = $this->template->get('current');
            $this->inactive_menu_class = '';
            $this->template->del('current');
            $this->class_tag = 'current';
        }
        parent::init();
    }

    public function defaultTemplate()
    {
        return array('menu');
    }

    /**
     * This will add a new behaviour for clicking on the menu items. For
     * example setTarget('frameURL') will show menu links inside a frame
     * instead of just linking to them.
     */
    public function setTarget($js_func)
    {
        $this->on('click', 'a')->univ()->frameURL($this->js()->_selectorThis()->attr('href'));

        return $this;
    }

    public function addMenuItem($page, $label = null)
    {
        if (!$label) {
            $label = ucwords(str_replace('_', ' ', $page));
        }
        $id = $this->name.'_i'.count($this->items);
        $label = $this->app->_($label);
        $js_page = null;
        if ($page instanceof jQuery_Chain) {
            $js_page = '#';
            $this->js('click', $page)->_selector('#'.$id);
            $page = $id;
        }
        $this->items[] = array(
            'id' => $id,
            'page' => $page,
            'href' => $js_page ?: $this->app->url($page),
            'label' => $label,
            $this->class_tag => $this->isCurrent($page) ? $this->current_menu_class : $this->inactive_menu_class,
        );

        return $this;
    }
    public function addSubMenu($label)
    {
        // we use MenuSeparator tag here just to put View_Popover outside of UL list.
        // Otherwise it breaks correct HTML and CSS.
        $f = $this->add('View_Popover');
        $this->addMenuItem($f->showJS('#'.$this->name.'_i'.count($this->items)), $label);

        return $f->add('Menu_jUI');
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
        if (!is_object($href)) {
            $href = str_replace('/', '_', $href);
        }

        return $href == $this->app->page
            || $href == ';'.$this->app->page
            || $href.$this->app->getConfig('url_postfix', '') == $this->app->page
            || (string) $href == (string) $this->app->url();
    }
    public function render()
    {
        $this->setSource($this->items);
        parent::render();
    }
}
