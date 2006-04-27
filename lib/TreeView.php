<?php
/*
 * Created on 14.04.2006 by *Camper*
 */
class TreeView extends Lister{
	protected $row_t;
	protected $id_field;
	private $parent_field;
	private $display_field = array();
	private $root_value;
	private $level_field = 'tv_level';
	private $collapsed=false;
	private $display_buttons=true;
	
	function init(){
		parent::init();
		$this->row_t=$this->template->cloneRegion('row');
	}
    function setSource($table, $id_field = 'id', $parent_field = 'parent_id', $root_value = null, $db_fields="*"){
    	parent::setSource($table, $db_fields);
    	$this->id_field = $id_field;
    	$this->parent_field = $parent_field;
    	$this->root_value = $root_value;
    	$this->dq->order($this->parent_field);
    	return $this;
    }
	function defaultTemplate(){
		return array('treeview', '_top');
	}
    function fetchRow(){
        if(is_array($this->data)){
            return $this->getNextItem();
        }
        return false;
    }
    function display($format, $name, $prefix = ''){
    	$this->display_field[]=array('name'=>$name, 'prefix'=>$prefix, 'format'=>$format);
    	return $this;
    }
    function getNextItem(){
    	return (bool)($this->current_row=array_shift($this->data));
    }
    function hideButtons(){
    	$this->display_buttons=false;
    	return $this;
    }
    function collapseAll(){
    	$this->collapsed=true;
    	return $this;
    }
    function expandAll(){
    	$this->collapsed=false;
    	return $this;
    }
    function recurseData($parent_id, $level = 0){
    	foreach($this->temp_data as $key=>$row){
    		if($row['displayed'])continue;
    		foreach($row as $field=>$value){
    			if($field == $this->parent_field && $value == $parent_id){
    				$this->temp_data[$key]['displayed'] = true;
    				$row[$this->level_field] = $level;
    				$row['collapsed']=$this->collapsed;
    				$this->data[] = $row;
    				if(!$this->collapsed)$this->recurseData($row[$this->id_field], $level+1);
    			}
    		}
    	}
    }
    function getNextLevel(){
    	$row = array_shift($this->data);
    	array_unshift($this->data, $row);
    	return $row[$this->level_field];
    }
    function getData($parent_id){
    	if(is_null($parent_id)||$parent_id=='')$this->dq->where($this->parent_field." is null");
    	else $this->dq->where($this->parent_field."=$parent_id");
    	$this->dq->do_select();
    	$this->temp_data = $this->dq->do_getAllHash();
    	$this->recurseData($parent_id);
    }
    function formatItem(){
       	$this->current_row['caption']="";
        foreach($this->display_field as $tmp=>$field){
	        $formatters = split(',',$field['format']);
        	$this->current_row['caption'].=$field['prefix'];
	        foreach($formatters as $formatter){
	            if(method_exists($this,$m="format_".$formatter)){
	                $this->$m($field);
	            }else throw new BaseException("TreeView does not know how to format type: ".$formatter);
	        }
        }
    }
    function format_text($field){
    	$this->current_row['caption'].=$this->current_row[$field['name']];
    }
    function format_link($field){
    	$caption=$this->current_row[$field['name']];
    	$this->current_row['caption'].="<a href=".
    		$this->api->getDestinationURL($this->api->page.'_'.$field['name'], 
    			array('id'=>$this->current_row[$this->id_field])).">" .
    		$caption."</a>";
    }
    function renderBranch($parent_id){
    	//executing query for a branch
    	$this->getData($parent_id);
    	$prev_level = 0;
    	$level_on = $this->template->get('level_on');
    	$level_off = $this->template->get('level_off');
    	$this->template->del('rows');
        while($this->fetchRow()){
            $this->formatItem();
            //var_dump($this->current_row);echo "<br><br>";
           	$this->template->del('level_off');
           	$this->template->del('level_on');

            if($this->current_row[$this->level_field]>$prev_level){
            	$this->row_t->set('level_on', $level_on);
            }
            if($this->getNextLevel()<$this->current_row[$this->level_field]){
            	//we may need to change level more then by 1
            	$diff=$this->current_row[$this->level_field]-$this->getNextLevel();
            	$off="";
            	while($diff>0){
            		$off.=$level_off;
            		$diff--;
            	}
           		$this->row_t->set('level_off', $off);
            }
            //adding a branch expand button
            $this->row_t->set('button_id', 'ec_'.$this->current_row[$this->id_field]);
            $this->row_t->set('ec', $this->getButton(true, $this->current_row[$this->id_field]));
            
            //$this->row_t->set('ec', $button->render());
            $this->row_t->set('parent', 'p_'.$this->current_row[$this->id_field]);
            $this->row_t->set('content', $this->current_row['caption']);
            $this->template->append('rows',$this->row_t->render());
            $prev_level = $this->current_row[$this->level_field];
        }
        return $this->template->render();
    }
    private function getButton($expand, $id){
		/*$button=$this->add('Button', 'ec_'.$this->current_row[$this->id_field], 'ec');
		$button->setLabel($this->current_row['collapsed']?'+':'-')
			->onClick()->ajaxFunc("alert('!')");*/
		if($this->display_buttons){
	    	$onclick="aasn('p_".$id."','".
					$this->api->getDestinationURL($this->api->page, array(
	                    		'ec'=>$id,
	                            'cut_object'=>$this->name, 'ec_action'=>$expand?'expand':'collapse'
	                            ))."')";
			//$onclick="alert(document.getElementById('ec_".$id."').name)";
	    	$button="<input class=tv_button type=button id=button_".$id.
				" onclick=\"$onclick\"".
				" value=".($expand?'+':'-').">";
		}else $button='';
		return $button;
    }
    function render(){
		if($_GET['ec']){
			$ajax=$this->add('Ajax');
			if($_GET['ec_action']=='expand'){
				//echo 'ec_'.$_GET['ec'];
				$ajax->setInnerHTML('ec_'.$_GET['ec'], $this->getButton(false, $_GET['ec']));
				$ajax->setInnerHTML('p_'.$_GET['ec'], $this->renderBranch($_GET['ec']));
				//echo $this->getButton(false, $_GET['ec']);
			}elseif($_GET['ec_action']=='collapse'){
				$ajax->setInnerHTML('p_'.$_GET['ec'], '');
				$ajax->setInnerHTML('ec_'.$_GET['ec'], $this->getButton(true, $_GET['ec']));
			}
		}else
    		$this->output($this->renderBranch($this->root_value));
    }
}	
