<?php
/***********************************************************
   ..

   Reference:
     http://atk4.com/doc/ref

 **ATK4*****************************************************
   This file is part of Agile Toolkit 4 
    http://www.atk4.com/
  
   (c) 2008-2011 Agile Technologies Ireland Limited
   Distributed under Affero General Public License v3
   
   If you are using this file in YOUR web software, you
   must make your make source code for YOUR web software
   public.

   See LICENSE.txt for more information

   You can obtain non-public copy of Agile Toolkit 4 at
    http://www.atk4.com/commercial/ 

 *****************************************************ATK4**/
/**
 * Changes the order of the grid rows.
 * Should be added to a Grid; column of type 'order' should also be defined
 * 
 * Created on 15.06.2006 by *Camper* (camper@adevel.com)
 */
class RecordOrder extends AbstractController{
	/**
	 * Field name by which the dataset will be ordered.
	 * By default this field value should by equal to id value. 
	 * You should take care of this by yourself
	 */
	private $field;
	
	function init(){
		parent::init();
		$this->api->addHook('post-init', array($this, 'process'));
	}
	function process(){
		if(isset($_GET[$this->name]))$this->act($_GET[$this->name], $_GET['action']);
	}
	function setField($field_name,$table=''){
		/**
		 * Sets the field by which dataset will be ordered
		 */
		$this->field=($table?$table.'.':'').$field_name;
		$this->owner->dq->order("coalesce($this->field, ".($table?$table.'.':'')."id)");
	}
	function getCell($id){
		if($this->recall('hl',null)){
			$left_pic='amodules3/templates/shared/move-here.gif';
			$left_act=$id==$this->recall('hl',null)?
				"":
				$this->ajax()->reloadRegion($this->owner->name, array($this->name=>$id, 'action'=>'mv', 
						'cut_object'=>$this->owner->name))->getString();
			$left_alt="Move marked row here";
			$right_pic='amodules3/templates/shared/xchg.gif';
			$right_act=$id==$this->recall('hl',null)?
				"":
				$this->ajax()->reloadRegion($this->owner->name, array($this->name=>$id, 'action'=>'xchg', 
						'cut_object'=>$this->owner->name))->getString();
			$right_alt="Exchange this row with marked";
		}else{
			$left_pic='amodules3/templates/shared/move-up.gif';
			$left_act=
				$this->ajax()->reloadRegion($this->owner->name, array($this->name=>$id, 'action'=>'up', 
						'cut_object'=>$this->owner->name))->getString();
			$left_alt="Move this row up once";
			$right_pic='amodules3/templates/shared/move-down.gif';
			$right_act=
				$this->ajax()->reloadRegion($this->owner->name, array($this->name=>$id, 'action'=>'dn', 
						'cut_object'=>$this->owner->name))->getString();
			$right_alt="Move this row down once";
		}
		$mark_pic=$id==$this->recall('hl',null)?'amodules3/templates/shared/unmark.gif':'amodules3/templates/shared/mark.gif';
		$mark_act="\n".($this->recall('hl',null)?
			$this->ajax()->reloadRegion($this->owner->name, array($this->name=>$id, 'action'=>'untag', 
						'cut_object'=>$this->owner->name))->getString():
			$this->ajax()->reloadRegion($this->owner->name, array($this->name=>$id, 'action'=>'tag', 
						'cut_object'=>$this->owner->name))->getString());
		$mark_alt=$this->recall('hl',null)?"Unmark this row":"Mark this row";
		$cell=
			'<img title="'.$left_alt.'" src="'.$left_pic.'" onclick="'.$left_act.'">' .
			'<img title="'.$mark_alt.'" src="'.$mark_pic.'" onclick="'.$mark_act.'">' .
			'<img title="'.$right_alt.'" src="'.$right_pic.'" onclick="'.$right_act.'">'
			;
		//Ajax:ajaxFunc() adds a \n to the end of function, that causes a problem with inlines
		$cell=implode('', explode("\n", $cell));
		return $cell; 
	}
	function act($id, $action){
		$aid=array();
		$ord=array();
		switch($action){
			case 'up':
				$aid[0]=$id;
				$ord[0]=$this->api->db->getOne("select coalesce($this->field,id) from ".$this->owner->dq->args['table'].
					" where id=$id");
				$row=$this->api->db->getHash("select id, coalesce($this->field,id) ord from ".$this->owner->dq->args['table'].
					" where coalesce($this->field,id)<{$ord[0]} order by coalesce($this->field,id) desc limit 1");
				$aid[1]=$row['id']; $ord[1]=$row['ord'];
				break;
			case 'dn':
				$aid[0]=$id;
				$ord[0]=$this->api->db->getOne("select coalesce($this->field,id) from ".$this->owner->dq->args['table'].
					" where id=$id");
				$row=$this->api->db->getHash("select id, coalesce($this->field,id) ord from ".$this->owner->dq->args['table'].
					" where coalesce($this->field,id)>{$ord[0]} order by coalesce($this->field,id) asc limit 1");
				$aid[1]=$row['id']; $ord[1]=$row['ord'];
				break;
			case 'mv':
				$aid[0]=$this->recall('hl');
				$ord[0]=$this->api->db->getOne("select coalesce($this->field,id) from ".$this->owner->dq->args['table'].
					" where id={$aid[0]}");
				$row=$this->api->db->getHash("select id, coalesce($this->field,id) ord from ".$this->owner->dq->args['table'].
					" where id=$id");
				$aid[1]=$row['id']; $ord[1]=$row['ord'];
				$ord[1]+=$ord[0]-$ord[1]>0?-1:1;
				$inc=$ord[0]-$ord[1]>0?"-1":"+1";
				$cond=$ord[0]-$ord[1]>0?"<=":">=";
				$this->api->db->query("update ".$this->owner->dq->args['table']." set " .
						"$this->field=$this->field $inc where coalesce($this->field,id) $cond ".$ord[1]);
				break;
			case 'xchg':
				$aid[0]=$this->recall('hl');
				$ord[0]=$this->api->db->getOne("select coalesce($this->field,id) from ".$this->owner->dq->args['table'].
					" where id={$aid[0]}");
				$row=$this->api->db->getHash("select id, coalesce($this->field,id) ord from ".$this->owner->dq->args['table'].
					" where id=$id");
				$aid[1]=$row['id']; $ord[1]=$row['ord'];
				break;
			case 'tag':
				$this->memorize('hl', $id);
				break;
			case 'untag':
				$this->forget('hl');
				break;
			default:
		}
		//if second pair is null - it is probably the edge of a grid, so we exit
		if(!$ord[1]||!$aid[1])return;
		if($action!='tag'&&$action!='untag'){
			//changing marked/ordered row
			$this->api->db->query("update ".$this->owner->dq->args['table']." set $this->field={$ord[1]} where id={$aid[0]}");
			//changing exchanged row
			if($action!='mv'){
				$this->api->db->query("update ".$this->owner->dq->args['table']." set $this->field={$ord[0]} where id={$aid[1]}");
			}
			$this->forget('hl');
		}
	}
}
