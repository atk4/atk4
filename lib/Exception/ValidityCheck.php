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
 * Thrown by Model on validity check fail
 * @author Camper (cmd@adevel.com) on 07.09.2009
 */
class Exception_ValidityCheck extends Exception_ForUser{
    private $field;
    function __construct($msg,$field=null){
        parent::__construct($msg);
        if($field)$this->setField($field);
    }
    function setField($field){
        $this->field=$field;
        return $this;
    }
    function getField(){
        return $this->field;
    }
}
