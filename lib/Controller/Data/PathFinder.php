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
/* 
Implements connectivity between Model and Session 
*/
class Controller_Data_PathFinder extends Controller_Data_Array {

    function setSource($model, $data) {
        var_Dump($data);
        $array = $this->api->pathfinder->searchDir($data);
        var_Dump($array);
        exit;

        parent::setSource($model, $array);

    }
}
