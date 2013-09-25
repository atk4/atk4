<?php
class Layout_Footer extends View {
    function init() {
        parent::init();
        $this->setElement('footer');
    }
    function getJSID() {
        return 'atk-footer';
    }
}
