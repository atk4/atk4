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
class Form_Field_Slider extends Form_Field {
    public $left='Min',$right='Max';
    public $min=0,$max=10;
    function setRange($min,$max){
        $this->min=$min;
        $this->max=$max;
        return $this;
    }
    function setLabels($left,$right){
        $this->left=$left;
        $this->right=$right;
        return $this;
    }
    function getInput(){
        $s=$this->name.'_slider';
        $this->js(true)->_selector('#'.$s)->slider(array(
                    'min'=>$this->min,
                    'max'=>$this->max,
                    'value'=>$this->js()->val(),
                    'change'=>$this->js()->_enclose()->val(
                        $this->js()->_selector('#'.$s)->slider('value')
                        )->change()
                    ));

        $this->setAttr('style','display: none');
        return '<table width=200 border=0><tr>'.
            '<td align="left">'.$this->left.'</td>'.
            '<td align="right">'.$this->right.'</td>'.
            '</tr><tr><td colspan=2>'.
            parent::getInput().
            '<div id="'.$s.'"></div></td></tr></table>';
    }
}
