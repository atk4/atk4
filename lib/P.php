<?php // vim:ts=4:sw=4:et:fdm=marker
/**
 * Adds a <p> element
 *
 * $this->add('H1')->set('Welcome');
 * $this->add('P')->set('Your balance is: '.$balance);
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
class P extends HtmlElement
{
    function init()
    {
        parent::init();
        $this->setElement('p');
    }
}
