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
 * To stop process flow, debug purposes
 * @author Camper (cmd@adevel.com) on 04.08.2009
 */
class Exception_Stop extends BaseException{
    function __construct($msg=null){
        parent::__construct($msg?:'This exception must be ignored in API');
    }
}
