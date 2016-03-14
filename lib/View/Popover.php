<?php
/**
 * Popover is a handy view which can be used to display content in
 * frames.The popover will automatically hide itself and position itself
 * relative to your element.
 */
class View_Popover extends View
{
    /** @var string Popover position can be top, bottom, left or right */
    public $position = 'top';

    /** @var string */
    public $url = null;

    /** @var string */
    public $pop_class = '';

    /**
     * Initialization.
     */
    public function init()
    {
        parent::init();
        $this->addStyle('display', 'none');
    }

    /**
     * If callable is passed, it will be executed when the dialog is popped
     * through the use of VirtualPage.
     *
     * @param callable $fx
     *
     * @return $this
     */
    public function set($fx)
    {
        /** @type VirtualPage $p */
        $p = $this->add('VirtualPage');
        $p->set($fx);

        $this->setURL($p->getURL());

        return $this;
    }

    /**
     * Specify URL here and it will be automatically loaded in the popover
     * every time it's shown.
     *
     * @param string $url
     *
     * @return $this
     */
    public function setURL($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @param string $class
     *
     * @return $this
     */
    public function addClass($class)
    {
        $this->pop_class .= ' '.$class;

        return $this;
    }

    /**
     * Returns JS which will position this element and show it.
     *
     * @param array $options
     * @param array $options_compat Deprecated options
     *
     * @return jQuery_Chain
     */
    public function showJS($options = null, $options_compat = array())
    {
        if (!empty($options_compat)) {
            $options = $options_compat;
        }

        $loader_js = $this->url ?
            $this->js()->atk4_load(array($this->url)) : $options['open_js'] ?: null;

        $this->js(true)->dialog(array_extend(array(
            'modal' => true,
            'dialogClass' => ($options['class'] ?: 'atk-popover').$this->pop_class.
                            ' atk-popover-'.($options['tip'] ?: 'top-center'),
            'dragable' => false,
            'resizable' => false,
            'minHeight' => 'auto',
            'autoOpen' => false,
            'width' => 250,
            'open' => $this->js(null, array(
                $this->js()->_selector('.ui-dialog-titlebar:last')->hide(),
                $loader_js,
            ))->click(
                $this->js()->dialog('close')->_enclose()
            )->_selector('.ui-widget-overlay:last')->_enclose()->css('opacity', '0'),

        ), $options))->parent()->append('<div class="atk-popover-arrow"></div>')
        ;

        return $this->js()->dialog('open')->dialog('option', array(
            'position' => $p = array(
                'my' => $options['my'] ?: 'center top',
                'at' => $options['at'] ?: 'center bottom+8',
                'of' => $this->js()->_selectorThis(),
                //'using'=>$this->js(
                //    null,
                //    'function(position,data){ $( this ).css( position ); console.log("Position: ",data); '.
                //    'var rev={vertical:"horizontal",horizontal:"vertical"}; '.
                //    '$(this).find(".arrow").addClass(rev[data.important]+" "+data.vertical+" "+data.horizontal);}'
                //)
            ),
        ));
    }
}

/**
 * Deep array extend: http://stackoverflow.com/questions/12725113/php-deep-extend-array.
 *
 * @todo: merge JS chains by putting them into combined chain.
 *
 * @param array $a
 * @param array $b
 *
 * @return array
 */
function array_extend($a, $b = array())
{
    if (empty($b)) {
        return $a;
    }
    foreach ($b as $k => $v) {
        if (is_array($v)) {
            $a[$k] = isset($a[$k]) ? array_extend($a[$k], $v) : $v;
        // } else if $v or $a[$k] instanceof jQuery_Chain, merge them!
        } else {
            $a[$k] = $v;
        }
    }

    return $a;
}
