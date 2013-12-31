<?php
/**
 * Dummy is a class ignoring all the calls. It is used to
 * substitute some other classes when their functionality
 * is not needed
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
class Dummy {
    function __call($foo,$bar){
        return $this;
    }
    function __set($foo,$bar){
        return $this;
    }
    function __toString() {
        return '##Dummy Object';
    }
    function __get($foo){
        return null;
    }
}
