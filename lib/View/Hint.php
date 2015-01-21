<?php
/***********************************************************
  Standard hints. Add on page or forms

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
class View_Hint extends View_Box {
    function defaultTemplate(){
        return array('view/hint');
    }
    /* Set hint title */
    function setTitle($title){
        $this->template->set('title',$title);
        return $this;
    }
}
