<?php
/**
 * Shortcut class for using Heading style
 *
 * Use: 
 *  $this->add('H3')->set('Header');
 *
 * @license See http://agiletoolkit.org/about/license
 * 
**/
class H3 extends HX { function init(){ parent::init(); $this->setElement('H3'); } }
