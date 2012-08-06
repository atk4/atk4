<?php
/**
 * Implementation of jQuery UI Tabs
 *
 * Use: 
 *
 * 12-GS:
 *  $cols=$this->add('Columns');
 *  $cols->addColumn(4)->add('LoremIpsum');
 *  $cols->addColumn(6)->add('LoremIpsum');
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
    public $mode='auto';        // either 'grid' or 'pct'
    /** Adds new column to the set. Argument can be numeric for 12GS, percent for flexi design or omitted for equal columns */
    function addColumn($width='auto'){
        $c=$this->add('View_Columns_Column',null,'Columns');
        //if($width!='auto')$c->template->trySet('width',$width);
        if(is_numeric($width)){
            $this->mode='grid';
            $c->addClass('span'.$width);
        }elseif(substr($width,-1,1)=='%'){
            if($this->mode!='pct'){
                $this->template->trySet('class','atk-flexy');
            }
            $this->mode='pct';
            $c->addStyle('width',$width);
        }
        return $c;
    }
    function recursiveRender(){
        if($this->mode=='auto'){
            $cnt=0;
            foreach($this->elements as $el)if($el instanceof View_Columns_Column)$cnt++;

            if($cnt)foreach($this->elements as $el)if($el instanceof View_Columns_Column){
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
