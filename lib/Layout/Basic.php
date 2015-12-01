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
class Layout_Basic extends View {

    /**
     * Creates a layer for a top-bar 
     *
     * sticky - will glue the bar to the top of the screen. This will also 
     *      pad your content. If this bar is not on very top, then it will
     *      automatically re-position itself as absolute as you scroll the
     *      page down
     *
     * responsive - 'mobile', 'desktop' or 'both'. Determines if you want this bar to appear on 
     *      moible devices.
     *
     *
     * height - can be either in pixels. If height is unspecified or is null / zero,
     *      then the bar will automatically collapse to have minimal size to
     *      facilitate content.
     *
     *
     * element - specifies which element to use for the bar. By default <div>s are used
     *      but you can also use <nav>, <header> or <footer>.
     *
     *
     * jackscrew - this property means that the element will expand horizontally
     *      the maximum width.
     *
     */
    function init() {
        parent::init();

        $this->app->layout = $this;
    }
    function addRow($spot) {
        return $this->add('View',null,$spot)->addClass('atk-layout-row');

    }



    /**
     * Footer be positioned from the bottom edge of the page and upwards.
     *
     * If your page does not have enough content, it will automatically be expanded
     * to prevent you from having a gap underneath the bottom bar.
     */
    function addFooter($class = 'Layout_Footer') {
        return $this->addRow('Footer')
            ->add($class)
            ;
    }



    function addLeftBar($optons = array()) {
        return $this->add('View',null,'LeftBar')
            ->setElement('nav')
            ->addClass('atk-layout-column span_2 hecto');
    }

    function defaultSpot() {
        return 'Layout';
    }
    function defaultTemplate() {
        return array('layout/fluid');
    }
}
