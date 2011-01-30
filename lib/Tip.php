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
 * This class shows help on the pages in the 'Tip of the day' style
 * Tips are displayed from the DB or static sources.
 *
 * Tip datasource structure are in doc/tip_of_theday_db.pdf
 *
 * If no user datasource set - lastreads are not stored. This mode is useful for context
 * help displaying.
 *
 * Created on 07.09.2006 by *Camper* (camper@adevel.com)
 */
class Tip extends Lister{
	protected $show=true;
	public $user_dq=null;
	public $user_id=null;
	public $user_data=null;
	protected $tip_id=null;
	protected $section=null;
	protected $seen_tips=null;
	public $types=array('regular'=>'regular','trigger'=>'trigger','announce'=>'announce');

	function init(){
		parent::init();
		$this->safe_html_output=false;
		$this->last_read=$this->recall($this->name.'_last_read',true);
		$this->api->addHook('pre-exec',array($this,'processActions'),1);
	}
	function processActions(){
		$this->show($_GET[$this->name.'_hide_tips']?$_GET[$this->name.'_hide_tips']==2:
			$this->recall($this->name.'_hidden',$_COOKIE[$this->name.'_show_tips']=='N'?1:2)==2,
			isset($_GET[$this->name.'_hide_tips']));
		if(isset($_GET[$this->name.'_show_tip'])){
			//lastreads should be stored
			if($this->last_read!==true)$this->setSeenTips($_GET[$this->name.'_show_tip']=='prev');
		}
		if(!isset($_GET[$this->name.'_show_tip']))$this->tip_id=$this->getTip(true);
		elseif(is_int($_GET[$this->name.'_show_tip']))$this->tip_id=$this->getTip($_GET[$this->name.'_show_tip']);
		else $this->tip_id=$this->getTip($_GET[$this->name.'_show_tip']=='next');
		$this->applyDQ();
		//if there was an action - show tip and exit
		if($_GET[$this->name.'_show_tip']){
			$this->execQuery();
		}
	}
	function show($show=true,$store_state=true){
		/**
		 * Hides tips. Updates user data if present
		 */
		$this->show=$show;
		$this->memorize($this->name.'_hidden',$show?'2':'1');
		//store to user data
		if($store_state){
			if(isset($this->user_dq))$this->user_dq->set('show_tips',$this->show?'Y':'N')->do_update();
			elseif(isset($this->user_data))$this->user_data[$this->name.'_show_tips']=$this->show?'Y':'N';
			else{
				//store to cookies. no need to check if cookies enabled
				setcookie($this->name.'_show_tips', $this->show?'Y':'N', time()+60*60*24*30);
			}
		}
		$this->initializeTemplate(null, array('tipoftheday',$this->show?'TipShow':'TipHide'));
	}
	function setSection($section){
		/**
		 * Sets the section by which locate triiger tips
		 * Could by any valid string
		 */
		$this->section=$section;
		return $this;
	}
	function setSeenTips($backward=false){
		/**
		 * Saves lastread to datasource
		 * Trigger tips not remembered
		 * if $backward==true - erases last seen tip
		 */
		if($this->getTipType($this->last_read)=='trigger')return;
		$seen_tips=$this->getSeenTips();
		if($backward===true){
			if(strpos($seen_tips,','.$this->last_read)!==false)
				$seen_tips=str_replace(','.$this->last_read,'',$seen_tips);
			elseif(strpos($seen_tips,$this->last_read.',')!==false)
				$seen_tips=str_replace($this->last_read.',','',$seen_tips);
			elseif($seen_tips==$this->last_read||$this->last_read===true)$seen_tips='';
		}else{
			if(strpos($seen_tips,','.$this->last_read)===false&&($seen_tips!=$this->last_read||$seen_tips==''))
				$seen_tips.=($seen_tips==''?'':',').$this->last_read;
		}
   		//this condition is a tricky thing for proper displaying
		if(!$_GET[$this->name.'_hide_tips'])$this->seen_tips=$seen_tips;
		if(isset($this->user_data)){
			//saving to data and return
			$this->user_data[$this->name.'_seen_tips']=$seen_tips;
		}
		elseif(isset($this->user_dq)){
			$this->user_dq->set('seen_tips',$seen_tips)->set('user_id',$this->user_id);
			if($this->user_dq->field('id')->do_getOne()==null)$this->user_dq->do_insert();
			else $this->user_dq->do_update();
		}
		//no user data - storing in Cookies
		//checking cookies same way as in ApiAdmin
		elseif(isset($_COOKIE[$this->api->name])){
			setcookie($this->name.'_seen_tips',$seen_tips,time()+60*60*24*30);
		}
		//cookies disabled - storing in Session
		else $this->memorize($this->name.'_seen_tips',$seen_tips);
	}
	function getTipType($id){
		if(isset($this->data)){
			foreach($this->data as $tip){
				if($tip['id']==$id)return $tip['type'];
			}
			throw new BaseException('No tip with ID="'.$this->tip_id.'" present in static data');
		}
		if(isset($this->dq))return $this->api->db->dsql()->table(substr($this->dq->args['table'],strlen(DTP)))
			->where('id',$id)->field('type')->do_getOne();
	}
	function getSeenTips(){
		if($this->seen_tips)return $this->seen_tips;
		if(isset($this->user_data)){
			$this->seen_tips=$this->user_data[$this->name.'_seen_tips'];
		}
		elseif(isset($this->user_dq)){
			$result=$this->user_dq->do_getHash();
			$this->seen_tips=$result['seen_tips'];
		}
		//checking cookies same way as in ApiAdmin
		elseif($_COOKIE[$this->api->name]){
			$this->seen_tips=$_COOKIE[$this->name.'_seen_tips'];
		}
		else $this->seen_tips=$this->recall($this->name.'_seen_tips','');
		return $this->seen_tips;
	}
	function setUserSource($table,$db_fields="*",$user_id=null){
		/**
		 * Sets the DB source for users lastreads. $user_id should be specified to make it work
		 */
		if(!$this->api->db)throw new BaseException('DB must be initialized if you want to use setSource');
		$this->user_dq = $this->api->db->dsql();
		$this->user_id=$user_id;

		$this->user_dq->table($table);
		$this->user_dq->field($db_fields);
		$this->user_dq->where('user_id',$this->user_id);
		return $this;
	}
	function setStaticUserSource($data){
		/**
		 * Sets the static source for users lastreads.
		 * Should contain only needed user data!
		 */
		$this->user_data=$data;
		return $this;
	}
	function applyDQ(){
		/**
		 * Filters sources to the current tip
		 */
		if(is_array($this->data)){
			if($this->tip_id==null)return;
			while($this->current_row=array_shift($this->data)){
				if($this->current_row['id']==$this->tip_id)return;
			}
			throw new BaseException('No tip with ID="'.$this->tip_id.'" present in static data');
		}
		elseif(isset($this->dq))$this->dq->where('id',$this->tip_id);
	}
	function execQuery(){
		if(isset($this->dq)){
			parent::execQuery();
			$this->current_row=$this->dq->do_fetchHash();
		}
		if(isset($this->user_dq))$this->user_dq->do_select();
	}
	function getTip($id=true){
		/**
		 * Returns the tip ID by the following conditions:
		 * $id === true: next tip
		 * $id === false: previous tip
		 * $id is int: appropriate tip by $id
		 */
   		$seen_tips=$this->getSeenTips();

		if($id===true){
			if(is_array($this->data)){
				$id=null; $break=false;
				foreach($this->data as $tip){
					//comparing sections
					$break=
						($tip['sections']==''||strpos($tip['sections'],$this->section)!==false)&&
					//comparing to seen tips
						((strpos($seen_tips,','.$tip['id'])===false&&
							strpos($seen_tips,$tip['id'].',')===false&&
							$seen_tips!=$tip['id'])||
						$seen_tips=='')
					;
					if($break==true){
						$id=$tip['id'];
						break;
					}
				}
			}
			elseif(isset($this->dq)){
				$query="select id from ".$this->dq->args['table']." where " .
					($this->section?"(sections like '%$this->section%') ":"(sections='') ") .
					($seen_tips!=''?"and id not in (".$seen_tips.") ":"").
					"order by coalesce(ord, id)";
				$id=$this->api->db->getOne($query);
			}
		}
		elseif($id===false){
			if(is_array($this->data)){
				$id==null; $break=false;
				//reversing an array cause we are going back
				$this->data=array_reverse($this->data);
				foreach($this->data as $tip){
					//comparing sections
					$break=
						($tip['sections']==''||strpos($tip['sections'],$this->section)!==false)&&
					//comparing to seen tips
						((strpos($seen_tips,','.$tip['id'])!==false||
							strpos($seen_tips,$tip['id'].',')!==false||
							$seen_tips==$tip['id'])||
							$seen_tips=='')
					;
					if($break==true){
						$id=$tip['id'];
						break;
					}
				}
				if($id==null)$id=$this->last_read;
			}
			elseif(isset($this->dq)){
		   		$query="select id from ".$this->dq->args['table']." where " .
					"(sections like '%$this->section%' or sections='') " .
					($seen_tips!=''?"and id in (".$seen_tips.") ":"").
					"order by coalesce(ord, id) desc";
				$id=$this->getTipType($this->last_read)=='trigger'?$this->api->db->getOne($query):
					$seen_tips==''?$this->last_read:$this->api->db->getOne($query);
			}
		}
		$this->memorize($this->name.'_last_read',$id);
		return $id;
	}
	function defaultTemplate(){
		return array('tipoftheday',$this->recall($this->name.'_hidden',false)?'TipHide':'TipShow');
	}
	function render(){
		if((!$this->current_row)||empty($this->current_row)&&$this->show){
			$this->show(false,false);
		}
		//setting actions on prev/next urls if possible
		$this->template->trySet('hide_url',$this->api->getDestinationURL(null,
			array($this->name.'_hide_tips'=>1,'cut_object'=>$this->name)));
		$this->template->trySet('show_url',$this->api->getDestinationURL(null,
			array($this->name.'_hide_tips'=>2,'cut_object'=>$this->name,$this->name.
			'_show_tip'=>$this->show?null:'prev')));
		$this->template->trySet('prev_url',$this->api->getDestinationURL(null,
			array($this->name.'_show_tip'=>'prev','cut_object'=>$this->name)));
		$this->template->trySet('next_url',$this->api->getDestinationURL(null,
			array($this->name.'_show_tip'=>'next','cut_object'=>$this->name)));
		$this->template->trySet('tip_name',$this->name);
		$this->formatRow();
		$this->template->set($this->current_row);
		$this->output('<div id="'.$this->name.'">'.$this->template->render().'</div>');
	}
}
