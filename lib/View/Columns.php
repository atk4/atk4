<?php
/***********************************************************
  ..

  Reference:
  http://agiletoolkit.org/doc/ref

 **ATK4*****************************************************
 This file is part of Agile Toolkit 4 
 http://agiletoolkit.org

 (c) 2008-2011 Agile Technologies Ireland Limited
 Distributed under Affero General Public License v3

 If you are using this file in YOUR web software, you
 must make your make source code for YOUR web software
 public.

 See LICENSE.txt for more information

 You can obtain non-public copy of Agile Toolkit 4 at
 http://agiletoolkit.org/commercial

 *****************************************************ATK4**/
class View_Columns extends View {
    public $cnt=0;
    function init(){
        parent::init();
    }
    function addColumn($columns='5'){
        if($columns=='50%')$columns=5;
        // TODO: implement  width
        //++$this->cnt;
        $c=$this->add('View',null,'Columns',array('view/columns','Columns'));
        $c->template->trySet('columns',$columns);
        //$this->template->trySet('cnt',$this->cnt);
        return $c;
    }
    function defaultTemplate(){
        return array('view/columns');
    }
}
