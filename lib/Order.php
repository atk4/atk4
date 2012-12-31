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
/*
 * This is class for ordering elements. 
 *
 */

class Order extends AbstractController {
    public $rules=array();
    public $array=null;

    function init(){
        parent::init();
        $this->useArray($this->owner->elements);
    }
    function useArray(&$array){
        $this->array=&$array;
        return $this;
    }
    function move($name,$where,$relative=null){
        if(is_object($name))$name=$name->short_name;
        if(is_object($relative))$relative=$relative->short_name;
        $this->rules[]=array($name,$where,$relative);
        return $this;
    }
    function now(){
        foreach($this->rules as $rule){
            list($name,$where,$relative)=$rule;

            // check if element exists
            if(!isset($this->array[$name]))
                throw $this->exception('Element does not exist when trying to move it')
                    ->addMoreInfo('element',$name)
                    ->addMoreInfo('move',$where)
                    ->addMoreInfo('relative',$relative);

            $v=$this->array[$name];
            unset($this->array[$name]);

            switch($where){
                case 'first':
                    // moving element to be a first child
                    $this->array=array($name=>$v)+$this->array;
                    break;
                case 'last':
                    $this->array=$this->array+array($name=>$v);
                    break;
                case 'after':
                    $this->array=array_reverse($this->array);
                case 'before':
                    $tmp=array();
                    foreach($this->array as $key=>$value){
                        if($key===$relative || (is_array($relative) && in_array($key,$relative))){
                            $tmp[$name]=$v;
                            $name=null;
                        }
                        $tmp[$key]=$value;
                    }
                    $this->array=$tmp;
                    if($name)throw $this->exception('Relative element not found while moving')
                        ->addMoreInfo('element',$name)
                            ->addMoreInfo('move',$where)
                            ->addMoreInfo('relative',$relative);

                    if($where=='after')$this->array=array_reverse($this->array);
                    break;
                case 'middle':
                    array_splice($this->array,floor(count($this->array)/2),0,array($name=>$v));
                    break;


            }
        }
    }
    function onHook($object,$hook){
        $object->addHook($hook,array($this,'now'));
        return $this;
    }
    function later(){
        $this->api->addHook('pre-render',array($this,'now'));
        return $this;
    }
}
