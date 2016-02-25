<?php
/**
 * Implementation of plain HTML-only form. Does not support submission or anything
 */
class Form_Plain extends HtmlElement
{
    public function init()
    {
        parent::init();
        $this->setElement('form');
    }
    public function addInput($type, $name, $value, $tag = 'Content')
    {
        $f = $this->add('HtmlElement', $name, $tag);
        $f->setAttr('name', $name);
        $f->setElement('input');
        $f->setAttr(array('type' => $type, 'value' => $value));
        $f->set('');

        return $f;
    }
}
