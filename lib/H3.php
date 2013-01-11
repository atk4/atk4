<?php // vim:ts=4:sw=4:et:fdm=marker
/*
 * Undocumented
 *
 * @link http://agiletoolkit.org/
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
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
