<?php
/***********************************************************
  ..

  Reference:
  http://agiletoolkit.org/doc/ref

==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
=====================================================ATK4=*/
/**
 * This page is displayed every time unhandled exception occurs
 *
 * Created on 23.01.2008 by *Camper* (camper@adevel.com)
 */
class page_Error extends Page{

    function setError($error){
        $this->template->trySet('message',$error->getMessage());
        return $this;
    }
    function defaultTemplate(){
        return array('page_error','_top');
    }
}
