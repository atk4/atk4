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
class Controller_DatabaseMigration extends AbstractController {
    function executeMigrations(){
        // TODO: check in pathfinder 
        $dbupdates = $this->api->pathfinder->search('dbupdates');

        $results=array();
        foreach($dbupdates as $dir){
            $d=dir($dir);
            while(false !== ($file=$d->read())){
                if($file[0]=='.')continue;
                if(!preg_match('/.*\.sql$/',$file))continue;
                //if(file_exists($dir.$file.'.ok'))continue;
                $results[]=$this->executeMigration($dir.$file);
            }
        }
        return $results;
    }
    function executeMigration($file){

        // TODO: 
        // 1. test write permissions
        // 2. execute migration
        // 3. write .ok file
        // 3a. roll-back and write .fail if needed
        // 4. store short result in "result"
        
        return array('name'=>$file,'result'=>'Not Supported Yet');
    }
}
