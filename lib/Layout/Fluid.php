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
    function defaultTemplate() {
        return array('layout/fluid');
    }
}
