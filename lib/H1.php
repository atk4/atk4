<?php
/**
 * Shortcut class for using Heading style
 *
 * Use: 
 *  $this->add('H1')->set('Header');
 *
 * @license See http://agiletoolkit.org/about/license
 * 
**/
class H1 extends HX { function init(){ parent::init(); $this->setElement('h1'); } }
