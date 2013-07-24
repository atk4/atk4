<?php // vim:ts=4:sw=4:et:fdm=marker
/**
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
class View_DropButton extends Button
{
    /**
     * Render button
     *
     * @return void
     */
    function render()
    {
        if (!isset($this->options['icons']['secondary'])) {
            $this->options['icons']['secondary'] = $this->js_triangle_class;
        }
        parent::render();
    }
}
