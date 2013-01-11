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
 * Stops initialisation process. For example if we are sure than no more objects needs to be added
 *  on the page.
 */
class Exception_StopInit extends BaseException{
    function __construct(){
        parent::__construct('This exception must be ignored in API');
    }
}
