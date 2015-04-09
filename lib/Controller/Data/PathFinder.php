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
class Controller_Data_PathFinder extends Controller_Data {

    function save($model, $id, $data) {
        throw $this->exception('Unable to save into pathfinder');
    }
    function delete($model, $id) {
        throw $this->exception('Unable to save into pathfinder');
    }
    function loadById($model, $id) {
        try {
            $model->data = $this->api->pathfinder->locate($model->_table[$this->short_name],$id,'array');
        }catch(Exception_PathFinder $e){
            throw $this->exception('Requested file not found','NotFound')
                ->by($e);
        }


        if(!$model->data)return;

        $model->id=$id;

        // If location field is not defined, get rid unnecessary reference
        if(!$model->hasElement('location'))unset($model->data['location']);

        // only load contents if field is defined
        if($model->hasElement('data') && $model->data['path']){
            $model->data['data']=file_get_contents($model->data['path']);
        }
    }
    // TODO: testing
    function prefetchAll($model) {
        $dirs = $this->api->pathfinder->search($model->_table[$this->short_name],'','path');
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

        return $colls;
    }
}
