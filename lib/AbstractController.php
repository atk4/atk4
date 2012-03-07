<?php // vim:ts=4:sw=4:et:fdm=marker
/**
  Abstract Controller can be used as a base class for any
  fundamental controller implementation. Controller class
  by definition should be state-less, although it can have
  associated model, which it then would manipulate.

  Learn:
  http://agiletoolkit.org/learn/understad/controller

  Reference:
  http://agiletoolkit.org/doc/controller
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4 
    http://agiletoolkit.org/
  
   (c) 2008-2011 Romans Malinovskis <atk@agiletech.ie>
   Distributed under Affero General Public License v3
   
   See http://agiletoolkit.org/about/license
 =====================================================ATK4=*/
class AbstractController extends AbstractObject {
    /** Associate controller with Model */
    public function setModel($model) {
        if(is_string($model)&&substr($model,0,strlen('Model'))!='Model'){
            $model=preg_replace('|^(.*/)?(.*)$|','\1Model_\2',$model);
        }
        $model=$this->owner->add($model);
        if(!$this->owner->model)$this->owner->model = $model;
        return $model;
    }
    /** get associated model */
    public function getModel() {
        return $this->owner->model;
    }
}
