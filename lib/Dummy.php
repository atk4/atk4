<?php
/**
 * Dummy is a class ignoring all the calls. It is used to
 * substitute some other classes when their functionality
 * is not needed
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4 
    http://agiletoolkit.org/
  
   (c) 2008-2012 Romans Malinovskis <romans@agiletoolkit.org>
   Distributed under Affero General Public License v3
   
   See http://agiletoolkit.org/about/license
 =====================================================ATK4=*/
class Dummy {
    function __call($foo,$bar){
    }
    function __set($foo,$bar){
    }
    function __get($foo){
        return null;
    }
}
