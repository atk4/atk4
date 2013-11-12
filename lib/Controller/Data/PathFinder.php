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
        $dirs = $this->api->pathfinder->search($data,'','path');
        $colls=array();


        foreach($dirs as $dir){

            // folder will contain collections
            $d=dir($dir);
            while(false !== ($file=$d->read())){
                if($file[0]=='.')continue;
                if(is_dir($dir.'/'.$file)){
                    $colls[]=array(
                        'base_path'=>$dir.'/'.$file,
                        'name'=>$file,
                        'id'=>$file,
                    );
                }
            }
        }

        parent::setSource($model, $colls);

    }
}
