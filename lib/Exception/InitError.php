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
 * Thrown when object initialization sequence was wrong (e.g. object referenced before it is initialized)
 * or object is initialized in wrong way (e.g. string value instead of integer)
 * 
 * @author Camper (cmd@adevel.com) on 02.04.2009
 */
class Exception_InitError extends BaseException{}
