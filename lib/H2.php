<?php
/**
 * Shortcut class for using Heading style
 *
 * Use: 
 *  $this->add('H2')->set('Header');
 *
 * @license See http://agiletoolkit.org/about/license
 * 
**/
class H2 extends HX { function init(){ parent::init(); $this->setElement('H2'); } }
