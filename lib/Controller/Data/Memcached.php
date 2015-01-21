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
 * Implementation of Memcached data controller
 *
 * $m=$this->add('Model');
 * $m->addField('test');
 * $m->table='test_table';
 *
 * $m->setSource('Memcached', $storage);
 * $m['test']=123;
 * $m->save(1);
 *
 * $m['test']=321;
 * $m->save(2);
 *
 * $m->load(1);
 * echo $m['test'];
 */

class Controller_Data_Memcached extends Controller_Data {

    function setSource($model,$data=undefined){
        parent::setSource($model,array(
            'db'=>new Memcached($x=$data.'_'.$model->table),
            'prefix'=>$model->table
        ));

        if(!$model->_table[$this->short_name]['db']->getServerList()){
            $model->_table[$this->short_name]['db']->addServer('localhost', 11211);
        }

        if(!$model->hasElement($model->id_field))$model->addField($model->id_field)->system(true);

        return $this;
    }

    function save($model,$id,$data){

        if(is_null($id)){
            $id=uniqid();
            if($model->id_field){
                $model->data[$model->id_field]=$id;
            }
        }
        $model->_table[$this->short_name]['db']->set($id,$data);
        return $id;
    }
    function loadById($model, $id){
        $model->data=$model->_table[$this->short_name]['db']->get($id);
        if($model->data===false){
            return $this; // not loaded
        }
        $model->dirty=array();

        $model->id=$id;
        if($model->id_field){
            $model->data[$model->id_field]=$id;
        }

        return $this;
    }

    function prefetchAll($model){

    }

    function delete($model,$id=null){
        $model->_table[$this->short_name]['db']->delete($id?:$model->id);
        if($model->id==$id || is_null($id))
            $model->unload();
    }

    function deleteAll($model){
        $model->_table[$this->short_name]['db']->flush();
    }
    function getRows($model){
        return $model->_table;
        $t =& $model->_table[$this->short_name];
    }
}
