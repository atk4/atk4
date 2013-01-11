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
 * Represents a field that consists of several groups of values
 * separated by a divider, e.g. 123-456-789
 * Each group is a line input, separator is a text between inputs
 *
 * Field value does not contain separators
 *
 * To set the format of the field use the setFormat($format) method.
 * $format is a string like '3-3-3-1', where numbers represent a number of chars in a group,
 * '*' means all the remaining chars ($format='*' turns input to a generic line input)
 *
 * Created on 01.02.2008 by *Camper* (camper@adevel.com)
 */
class Form_Field_Grouped extends Form_Field{
    protected $format='*';
    protected $separator='-';

    function getInput($attr=array()){
        $input=explode($this->separator,$this->format);
        $count=count($input);
        //$onChange=($this->onchange)?$this->onchange->getString():'';
        $output=$this->getTag('span', array('style'=>'white-space: nowrap;'));
        $i=0;
        $s_start=0;
        foreach($input as $size){
            $id=$this->name.'_'.$i;
            $next_id=$this->name.'_'.($i+1);
            if($size!='*'){
                $this->attr['maxlength']=$size;
                $this->attr['size']=$size;
            }
            // onChange should contain switching to the next group
            if($count>$i+1){
                //
                $onchange=$this->ajax()
                    ->ajaxFunc("switchFieldOn('$id','$next_id',$size)")
                    ->getString();
            }else{
                $onchange=$this->onchange;
            }
            $output.=$this->getTag('input',array_merge(array(
                            'id'=>$id,
                            'name'=>$id,
                            //'onchange'=>$onchange,
                            'onkeyup'=>$onchange,
                            'type'=>'text',
                            'value'=>substr($this->value,$s_start,$input[$i])
                            ), $attr, $this->attr)
                    );
            if($count>$i+1)$output.='&nbsp;'.$this->separator.'&nbsp;';
            // starting character of the next part
            $s_start+=$input[$i];
            // increasing i after we set start for the next part
            $i++;
        }
        $output.=$this->getTag('/span');
        return $output;
    }
    function setFormat($format,$separator='-'){
        $this->format=$format;
        $this->separator=$separator;
        return $this;
    }
    function loadPOST(){
        if(empty($_POST))return;
        // value consists of several parts that are in separate POST items
        $input=explode($this->separator,$this->format);
        $this->value='';
        for($i=0;$i<count($input);$i++)$this->value.=$_POST[$this->name."_$i"];
    }
}
