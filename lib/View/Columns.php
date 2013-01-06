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
/**
 * Implementation of columns
 *
 * Use: 
 *
 * 12-GS:
 *  $cols=$this->add('Columns');
 *  $cols->addColumn(4)->add('LoremIpsum');
 *  $cols->addColumn(6)->add('LoremIpsum');
 *  $cols->addColumn(2)->add('LoremIpsum');
 *
 * Flexible
 *  $cols=$this->add('Columns');
 *  $cols->addColumn('10%')->add('LoremIpsum');
 *  $cols->addColumn('90%')->add('LoremIpsum');
 *
 * Auto
 *  $cols=$this->add('Columns');
 *  $cols->addColumn()->add('LoremIpsum');
 *  $cols->addColumn()->add('LoremIpsum');
 *
 * @license See http://agiletoolkit.org/about/license
 * 
**/
class View_Columns extends View {
    public $mode='auto';      // 'auto', 'grid' or 'pct'
    /**
     * Adds new column to the set.
     * Argument can be numeric for 12GS, percent for flexi design or omitted for equal columns
     */
    function addColumn($width='auto'){
        $c=$this->add('View_Columns_Column',null,'Columns');
        if(is_numeric($width)){
            $this->mode='grid';
            $c->addClass('span'.$width);
        }elseif(substr($width,-1)=='%'){
            if($this->mode!='pct'){
                $this->template->trySet('class','atk-flexy');
            }
            $this->mode='pct';
            $c->addStyle('width',$width);
        }
        return $c;
    }
    function recursiveRender(){
        // if auto mode, then calculate equal widths for columns
        if($this->mode=='auto'){
            $cnt=0;
            foreach($this->elements as $el)
               if($el instanceof View_Columns_Column) $cnt++;

            if($cnt)
               foreach($this->elements as $el)
                  if($el instanceof View_Columns_Column){
                     $el->setStyle('width',round(100/$cnt,2).'%');
                  }
            $this->template->trySet('class','atk-flexy');
        }
        return parent::recursiveRender();
    }
    function defaultTemplate(){
        return array('view/columns');
    }
}
class View_Columns_Column extends HtmlElement {}
