<?php
/**
 * Implementation of jQuery UI Tabs.
 *
 * Use:
 *  $tabs=$this->add('Tabs');
 *  $tabs->addTab('Tab1')->add('LoremIpsum');
 *
 *  $tabs->addTabURL('./details','Details');    // AJAX tab
 */
class View_Tabs_jUItabs extends View
{
    /** @var Template */
    public $tab_template = null;

    /** @var array */
    public $options = array();

    /** @var string */
    public $position = 'top'; // can be 'top','left','right','bottom'

    /**
     * Should we show loader indicator while loading tabs
     * @var bool
     */
    public $show_loader = true;



    /**
     * Initialization
     */
    public function init()
    {
        parent::init();
        $this->tab_template = $this->template->cloneRegion('tabs');
        $this->template->del('tabs');
    }
    /* Set tabs option, for example, 'active'=>'zero-based index of tab */
    public function setOption($key, $value)
    {
        $this->options[$key] = $value;

        return $this;
    }
    public function toBottom()
    {
        $this->position = 'bottom';

        return $this;
    }
    public function toLeft()
    {
        $this->position = 'left';

        return $this;
    }
    public function toRight()
    {
        $this->position = 'right';

        return $this;
    }
    public function render()
    {
        // add loader to JS events
        if ($this->show_loader) {
            $this->options['beforeLoad'] = $this->js()->_enclose()->_selectorThis()
                ->atk4_loader()->atk4_loader('showLoader');
            $this->options['load'] = $this->js()->_enclose()->_selectorThis()
                ->atk4_loader()->atk4_loader('hideLoader');
        }
        // render JUI tabs
        $this->js(true)
            ->tabs($this->options);

        if ($this->position == 'bottom') {
            $this->js(true)->_selector('#'.$this->name)
                    ->addClass('tabs-bottom')
            ;
            $this->js(true)->_selector('.tabs-bottom .ui-tabs-nav, .tabs-bottom .ui-tabs-nav *')
                    ->removeClass('ui-corner-all ui-corner-top')
                    ->addClass('ui-corner-bottom')
            ;
            $this->js(true)->_selector('.tabs-bottom .ui-tabs-nav')
                    ->appendTo('.tabs-bottom')
            ;
        }

        if (($this->position == 'left') || ($this->position == 'right')) {
            $this->js(true)->_selector('#'.$this->name)
                    ->addClass('ui-tabs-vertical ui-helper-clearfix')
            ;
            $this->js(true)->_selector('#'.$this->name.' li')
                    ->removeClass('ui-corner-top')
                    ->addClass('ui-corner-'.$this->position)
            ;
        }

        return parent::render();
    }
    /* Add tab and returns it so that you can add static content */
    public function addTab($title, $name = null)
    {
        $container = $this->add('View_HtmlElement', $name);

        $this->tab_template->set(array(
                    'url' => '#'.$container->name,
                    'tab_name' => $title,
                    'tab_id' => $container->short_name,
                    ));
        $this->template->appendHTML('tabs', $this->tab_template->render());

        return $container;
    }
    /* Add tab which loads dynamically. Returns $this for chaining */
    public function addTabURL($page, $title = null)
    {
        if (is_null($title)) {
            $title = ucwords(preg_replace('/[_\/\.]+/', ' ', $page));
        }
        $this->tab_template->set(array(
                    'url' => $this->app->url($page, array('cut_page' => 1)),
                    'tab_name' => $title,
                    'tab_id' => basename($page),
                    ));
        $this->template->appendHTML('tabs', $this->tab_template->render());

        return $this;
    }
    public function defaultTemplate()
    {
        return array('tabs');
    }
}
