<?php
class Grid extends CompleteLister {
	protected $columns;
	protected $no_records_message="No matching records to display";
	private $table;
	private $id;

	public $last_column;
	public $sortby='0';
	public $sortby_db=null;
	public $not_found=false;

	public $displayed_rows=0;

	private $totals_title_field=null;
	private $totals_title="";
	public $totals_t=null;

	public $grid_update_compatibility=false;
	/**
	* Inline related property
	* If true - TAB key submits row and activates next row
	*/
	protected $tab_moves_down=false;
	/**
	* Inline related property
	* Wether or not to show submit line
	*/
	protected $show_submit=true;
	private $record_order=null;

	public $title_col=array();

	/**
	* $tdparam property is an array with cell parameters specified in td tag.
	* This should be a hash: 'param_name'=>'param_value'
	* Following parameters treated and processed in a special way:
	* 1) 'style': nested array, style parameter. items of this nested array converted to a form of
	* 		 style: style="param_name: param_value; param_name: param_value"
	* 2) OBSOLTE! wrap: possible values are true|false; if true, 'wrap' is added
	* 		use style/white-space property or simply format_wrap()
	*
	* All the rest are not checked and converted to a form of param_name="param_value"
	*
	* This is a tree-like array with the following structure:
	* array(
	* 		[level1]=>dataset_row=array(
	* 			[level2]=>field=array(
	* 				[level3]=>tdparam_elements=array(
	* 					param_name=>param_value
	* 				)
	* 			)
	* 		)
	* )
	*/
	protected $tdparam=array();

	function init(){
		parent::init();
		//$this->add('Reloadable');
		$this->api->addHook('pre-render',array($this,'precacheTemplate'));

		$this->sortby=$this->learn('sortby',$_GET[$this->name.'_sort']);
		//$this->api->addHook('post-submit', array($this,'submitted'), 3);
	}
	function defaultTemplate(){
		return array('grid','grid');
	}
	/**
	 * Returns the ID value of the expanded content on the basis of GET parameters
	 */
	function getExpanderId(){
		return $_GET['expanded'].'_expandedcontent_'.$_GET['id'];
	}
	function addColumn($type,$name=null,$descr=null){
		if($name===null){
			$name=$type;
			$type='text';
		}
		if($descr===null)$descr=ucwords(str_replace('_',' ',$name));
		$this->columns[$name]=array(
				'type'=>$type,
				'descr'=>$descr
				);

		$this->last_column=$name;

		$subtypes=explode(',',$type);
		foreach($subtypes as $subtype){
			if(method_exists($this,$m='init_'.$subtype))$this->$m($name);
		}

		return $this;
	}
	function removeColumn($name){
		unset($this->columns[$name]);
		if($this->last_column==$name)$this->last_column=null;
		return $this;
	}
	function addButton($label,$name=null,$return_button=false){
		$button=$this->add('Button','gbtn'.count($this->elements),'grid_buttons');
		$button->setLabel($label);
		if($return_button)return $button;
		return $button;
	}
	function addQuickSearch($fields,$class='QuickSearch'){
		return $this->add($class,null,'quick_search')
			->useFields($fields);
	}
	function makeSortable($db_sort=null){
		// Sorting
		$reverse=false;
		if(substr($db_sort,0,1)=='-'){
			$reverse=true;
			$db_sort=substr($db_sort,1);
		}
		if(!$db_sort)$db_sort=$this->last_column;

		if($this->sortby==$this->last_column){
			// we are already sorting by this column
			$info=array('1',$reverse?0:("-".$this->last_column));
			$this->sortby_db=$db_sort;
		}elseif($this->sortby=="-".$this->last_column){
			// We are sorted reverse by this column
			$info=array('2',$reverse?$this->last_column:'0');
			$this->sortby_db="-".$db_sort;
		}else{
			// we are not sorted by this column
			$info=array('0',$reverse?("-".$this->last_column):$this->last_column);
		}
		$this->columns[$this->last_column]['sortable']=$info;

		return $this;
	}
	function makeTitle(){
		$this->title_col[]=$this->last_column;
		return $this;
	}
	function format_number($field){
	}
	function format_text($field){
		$this->current_row[$field] = $this->current_row[$field];
	}
	function format_shorttext($field){
		$text=$this->current_row[$field];
		//TODO counting words, tags and trimming so that tags are not garbaged
		if(strlen($text)>60)$text=substr($text,0,28).' <b>~~~</b> '.substr($text,-28);;
		$this->current_row[$field]=$text;
		$this->tdparam[$this->getCurrentIndex()][$field]['title']=$this->current_row[$field.'_original'];
	}
	function format_html($field){
		$this->current_row[$field] = htmlentities($this->current_row[$field]);
	}
	function format_money($field){
		$m=(float)$this->current_row[$field];
		$this->current_row[$field]=number_format($m,2);
		if($m<0){
			$this->setTDParam($field,'style/color','red');
		}else{
			$this->setTDParam($field,'style/color',null);
		}
	}
	function format_totals_number($field){
		return $this->format_number($field);
	}
	function format_totals_money($field){
		return $this->format_money($field);
	}
	function format_totals_text($field){
		// This method is mainly for totals title displaying
		if($field==$this->totals_title_field){
			$this->setTDParam($field,'style/font-weight','bold');
			//$this->current_row[$field]=$this->totals_title.':';
		}
		else $this->current_row[$field]='-';
	}
	function format_time($field){
		$this->current_row[$field]=format_time($this->current_row[$field]);
	}
	function format_date($field){
		if(!$this->current_row[$field])$this->current_row[$field]='-'; else
		$this->current_row[$field]=date($this->api->getConfig('locale/date','d/m/Y'),
			strtotime($this->current_row[$field]));
	}
	function format_datetime($field){
		if(!$this->current_row[$field])$this->current_row[$field]='-'; else
		$this->current_row[$field]=date($this->api->getConfig('locale/datetime','d/m/Y H:i:s'),
			strtotime($this->current_row[$field]));
	}
	function format_nowrap($field){
		$this->tdparam[$this->getCurrentIndex()][$field]['style']='nwhite-space: nowrap';
	}
	function format_wrap($field){
		$this->tdparam[$this->getCurrentIndex()][$field]['style']='white-space: wrap';
	}
	function format_template($field){
		$this->current_row[$field]=$this->columns[$field]['template']
			->set($this->current_row)
			->trySet('_value_',$this->current_row[$field])
			->render();
	}
	function format_widget($field, $widget, $params=array(), $widget_json=null){
		$class=$this->name.'_'.$field.'_expander';
		$params=array(
				'class'=>$class."_".$field." $widget lister_cell"
				)+$params;
		$this->js(true)->_tag('.'.$class.'_'.$field)->_load($widget)->activate($widget_json);
		/*
		$this->api->add('jUI')->addWidget($widget)->activate('.'.$class.'_'.$field,$widget_json);
		*/
		$this->tdparam[$this->getCurrentIndex()][$field]=$params;
		if(!$this->current_row[$field]){
			$this->current_row[$field]=$this->columns[$field]['descr'];
		}
	}
	function format_inline_widget($field, $idfield='id'){
		$this->format_widget(
				$field,
				'inline',
				array(
					'width'=>'0',
					'id'=>$this->name.'_'.$field.'_'.$this->current_row[$idfield],
					'rel'=>$this->api->getDestinationURL($this->api->page.'_'.$field,
						array('expander'=>$field,
							'cut_object'=>$this->api->page.'_'.$field,
							'expanded'=>$this->name,
							'id'=>$this->current_row[$idfield])
						)
					)
				);

		$this->current_row[$field]='';
	}
	function format_expander_widget($field, $idfield='id'){
		/*
		$this->format_widget(
				$field,
				'expander',
				array(
					'id'=>$this->name.'_'.$field.'_'.$this->current_row[$idfield],
					'rel'=>$this->api->getDestinationURL($this->api->page.'_'.$field,
						array('expander'=>$field,
							'cut_object'=>$this->api->page.'_'.$field,
							'expanded'=>$this->name,
							'id'=>$this->current_row[$idfield]
							)
						)
					)
				);
				*/
		$class=$this->name.'_'.$field.'_expander';
		if(!$this->current_row[$field]){
			$this->current_row[$field]=$this->columns[$field]['descr'];
		}
//		.'<a class=" '.$class.' expander"
		$this->current_row[$field]='<button type="button" class="ui-state-default ui-corner-all '.$class.'"
			id="'.$this->name.'_'.$field.'_'.$this->current_row[$idfield].'"
			rel="'.$this->api->getDestinationURL($this->api->page.'/'.$field,
						array('expander'=>$field,
							'cut_object'=>$this->api->page.'_'.$field,
							'expanded'=>$this->name,
							'id'=>$this->current_row[$idfield]
							)
						).'"
			>'.$this->current_row[$field].'</button>';
	}
	function init_expander_widget($field){
		$class=$this->name.'_'.$field.'_expander';
		$this->js(true)->_selector('.'.$class)->atk4_expander();
	}
	function init_expander($field){
		$class=$this->name.'_'.$field.'_expander';
		$this->js(true)->_selector('.'.$class)->atk4_expander();
	}
	function format_expander($field, $idfield='id'){
		$n=$this->name.'_'.$field.'_'.$this->current_row[$idfield];
		$tdparam=array(
			'id'=>$n,
			'style'=>array(
				'cursor'=>'pointer',
				'color'=>'blue',
				'white-space'=>'nowrap',
			),
			'onclick'=>$this->ajax()->openExpander($this,$this->current_row[$idfield],$field)->getString(),
		);
		$this->tdparam[$this->getCurrentIndex()][$field]=$tdparam;
		if(!$this->current_row[$field]){
			$this->current_row[$field]='['.$this->columns[$field]['descr'].']';
		}
	}
	function format_inline($field, $idfield='id'){
		/**
		* Formats the InlineEdit: field that on click should substitute the text
		* in the columns of the row by the edit controls
		*
		* The point is to set an Id for each column of the row. To do this, we should
		* set a property showing that id should be added in prerender
		*/
		$col_id=$this->name.'_'.$field.'_inline';
		$show_submit=$this->show_submit?'true':'false';
		$tab_moves_down=$this->tab_moves_down?'true':'false';
		//setting text non empty
		$text=$this->current_row[$field]?$this->current_row[$field]:'null';
		$tdparam=array(
			'id'=>$col_id.'_'.$this->current_row[$idfield],
			'style'=>array(
				'cursor'=>'hand'
			),
			'title'=>$this->current_row[$field.'_original']
		);
		$this->tdparam[$this->getCurrentIndex()][$field]=$tdparam;
		$this->current_row[$field]=$this->ajax()->ajaxFunc(
			'inline_show(\''.$this->name.'\',\''.$col_id.'\','.$this->current_row[$idfield].', \''.
			$this->api->getDestinationURL(null, array(
			'cut_object'=>$this->api->page, 'submit'=>$this->name)).
			'\', '.$tab_moves_down.', '.$show_submit.')'
		)->getLink($text);
	}
	function format_nl2br($field) {
		$this->current_row[$field] = nl2br($this->current_row[$field]);
	}
	function format_order($field, $idfield='id'){
		$n=$this->name.'_'.$field.'_'.$this->current_row[$idfield];
		$this->tdparam[$this->getCurrentIndex()][$field]=array(
			'id'=>$n,
			'style'=>array(
				'cursor'=>'hand'
			)
		);
		$this->current_row[$field]=$this->record_order->getCell($this->current_row['id']);
	}
	function format_reload($field,$args=array()){
		/**
		* Useful for nested Grids in expanders
		* Formats field as a link by clicking on which the whole expander area
		* is reloaded by specified page contents.
		* Page address is similar to expander field
		*
		* To return expander's previous content see Ajax methods:
		* - Ajax::reloadExpander()
		* - Ajax::reloadExpandedRow()
		* - Ajax::reloadExpandedField()
		*
		* WARNING!
		* As these Ajax methods use the current $_GET['id'] value to return
		* the previuos expander state, clicked row ID is passed through $_GET['row_id']
		*/
		$this->current_row[$field]='<a href="javascript:void(\''.$this->current_row['id'].'\')" ' .
			'onclick="'.$this->ajax()
			->reloadExpander($this->api->page.'_'.$field,array('row_id'=>$this->current_row['id']))
			->getString().'"><u>'.($this->current_row[$field]==null?$field:$this->current_row[$field]).'</u></a>';
	}
	function format_link($field){
		$this->current_row[$field]='<a href="'.$this->api->getDestinationURL($field,
			array('id'=>$this->current_row['id'])).'">'.
			$this->columns[$field]['descr'].'</a>';
	}
	function format_delete($field){
		$l=$this->name.'_'.$field."_label";
		if(isset($this->columns[$field]['del_frame'])){
			$f=$this->columns[$field]['del_frame'];
			$confirm=$this->columns[$field]['del_confirm'];
		}else{
			$f=$this->columns[$field]['del_frame']=$this->add('FloatingFrame',$field,'Misc');
			$confirm = $f->frame("Delete record?")
				->add('Form');

			$confirm->addLabel("<div id='".$l."'>Error..?</div>");
			$confirm->addField('hidden','id','Hidden');
			$confirm->addButton('Delete')->submitForm($confirm);
			$confirm->addButton('Cancel')->setVisibility($f,false);

			$this->columns[$field]['del_confirm']=$confirm;

			if($confirm->isSubmitted()){
				$this->dq->where('id',$confirm->get('id'))->do_delete();
				$this->ajax()
					->setVisibility($f,false)
					->displayAlert("Record ".$confirm->get('id')." deleted")
					->reload($this)
					->execute();
			}


			$f->recursiveRender();
		}
		$this->current_row[$field]=
			$this->ajax()->setFieldValue($confirm->getElement('id')->name,$this->current_row['id'])->setInnerHTML($l,"Delete \\'".$this->getRowTitle()."\\'?")->setVisibility($f,true)->getLink('delete');
	}
	function format_button($field){
		$this->current_row[$field]='<button type="button" class="ui-state-default ui-corner-all" '.
		'onclick="$(this).univ().ajaxec(\''.$this->api->getDestinationURL(null,
			array($field=>$this->current_row['id'])).'\')">'.
			$this->columns[$field]['descr'].'</button>';
	}
	function format_confirm($field){
		$this->current_row[$field]='<button type="button" class="ui-state-default ui-corner-all" '.
		'onclick="$(this).univ().confirm(\'Are you sure?\').ajaxec(\''.$this->api->getDestinationURL(null,
			array($field=>$this->current_row['id'])).'\')">'.
			$this->columns[$field]['descr'].'</button>';
	}
	function format_prompt($field){
		$this->current_row[$field]='<button type="button" class="ui-state-default ui-corner-all" '.
		'onclick="value=prompt(\'Enter value: \');$(this).univ().ajaxec(\''.$this->api->getDestinationURL(null,
			array($field=>$this->current_row['id'])).'&value=\'+value)">'.
			$this->columns[$field]['descr'].'</button>';
	}
	function format_checkbox($field){
		$this->current_row[$field] = '<input type="checkbox" id="cb_'.
			$this->current_row['id'].'" name="cb_'.$this->current_row['id'].
			'" value="'.$this->current_row['id'].'"'.
			($this->current_row['selected']=='Y'?" checked ":" ").//'" onclick="'.
			//$this->onClick($field).
			'/>';
		$this->setTDParam($field,'width','10');
		$this->setTDParam($field,'align','center');
	}
	function addRecordOrder($field,$table=''){
		if(!$this->record_order){
			$this->record_order=$this->add('RecordOrder');
			$this->record_order->setField($field,$table);
		}
		return $this;
	}
	function staticSortCompare($row1,$row2){
		if($this->sortby[0]=='-'){
			return strcmp($row2[substr($this->sortby,1)],$row1[substr($this->sortby,1)]);
		}
		return strcmp($row1[$this->sortby],$row2[$this->sortby]);
	}
	function setStaticSource($data){
		$this->data=$data;
		if($this->sortby){
			usort($this->data,array($this,'staticSortCompare'));
		}
		return $this;
	}
	function setSource($table,$db_fields=null){
		parent::setSource($table,$db_fields);
		//we always need to calc rows
		$this->dq->calc_found_rows();
		return $this;
	}
	function execQuery(){
		if($this->sortby){
			$desc=false;
			$order=$this->sortby_db;
			if(substr($this->sortby_db,0,1)=='-'){
				$desc=true;
				$order=substr($order,1);
			}
			if($order)$this->dq->order($order,$desc);
		}
		return parent::execQuery();
	}
	function setTemplate($template){
		// This allows you to use Template
		$this->columns[$this->last_column]['template']=$this->add('SMlite')
			->loadTemplateFromString($template);
		return $this;
	}

	function submitted(){
		// checking if this Grid was requested
		if($_GET['expanded']==$this->name&&$_GET['grid_action']=='return_field'){
			echo $this->getFieldContent($_GET['expander'],$_GET['id']);
			exit;
		}
		// checking if this Grid was requested
		if($_GET['expanded']==$this->name&&$_GET['grid_action']=='return_row'){
			echo $this->getRowContent($_GET['id'],(isset($_GET['datatype'])?$_GET['datatype']:'ajax'));
			exit;
		}
		if($_GET['submit']==$this->name){
			//return;// false;
			//saving to DB
			if($_GET['action']=='update'){
				if(!$this->grid_update_compatibility)
					throw new BaseException('Grid::update is unsafe. If you wish to continue using it, '.
							'set $grid->grid_update_compatibility');
				$this->update();
			}
			$row=$this->getRowContent($_GET['id']);
			echo $row;
			exit;
		}
	}
	function update(){
		foreach($_GET as $name=>$value){
			if(strpos($value,'%'))$value=urldecode($value);
			if(strpos($name, 'field_')!==false){
				$this->dq->set(substr($name, 6),$value);
			}
		}
		$idfield=$this->dq->args['fields'][0];
		if($idfield=='*')$idfield='id';
		$this->dq->where($idfield, $_GET['id']);
		$this->dq->do_update();
	}

	/**
	* Returns the properly formatted row content.
	* Used firstly with Ajax::reloadExpandedRow() and in inline edit
	* @param $datatype can be 'ajax' or 'jquery'. Regulates result contents
	*/
	function getRowContent($id,$datatype='jquery'){

		// if DB source set
		if(isset($this->dq)){
			// *** Getting required record from DB ***
			$idfield=$this->dq->args['fields'][0];
			if($idfield=='*'||strpos($idfield,',')!==false)$idfield='id';
			$this->dq->where($idfield,$id);
			//we should switch off the limit or we won't get any value
			$this->dq->limit(1);
			#zak: This fix is if grid is not using the $this->api->db database but some else it hsould be depending only on $this->dq
			$row_data=$this->dq->do_getHash(); //$this->api->db->getHash($this->dq->select());
		}
		// if static source set
		elseif(isset($this->data)){
			$found=false;
			foreach($this->data as $index=>$row){
				if($row['id']==$id){
					$row_data=$row;
					$found=true;
					break;
				}
			}
			// no data found, returning empty string
			if(!$found)return "";
		}
		else return "";

		// *** Initializing template ***
		$this->precacheTemplate(false);

		// *** Rendering row ***
		$this->current_row=$row_data;
		$this->formatRow();

		// *** Combining result string ***
		$func='formatRowContent_'.$datatype;
		return $this->$func($id);
	}
	protected function formatRowContent_html($id){
		$this->row_t->set($this->current_row);
		return $this->rowRender($this->current_row);
	}
	protected function formatRowContent_ajax($id){
		$result="";
		foreach($this->columns as $name=>$column){
			$result.=$this->current_row[$name]."<t>".$this->current_row[$name.'_original'].
				// appending styles as structured string
				"<t>".$this->getFieldStyle($name,$id).
				"<row_end>";
		}
		return $result;
	}
	protected function formatRowContent_jquery($id){
		$result=array();
		$i=1;
		foreach($this->columns as $name=>$column){
			$result[$i]['data']=array('actual'=>$this->current_row[$name],
				'original'=>$this->current_row[$name.'_original']);
			$result[$i]['params']=$this->tdparam[$this->getCurrentIndex()][$name];
			$i++;
		}
		$result=json_encode($result);
		return $result;
	}
	function getColumns(){
		return $this->columns;
	}
	function getFieldStyle($field,$id){
		/**
		* Returns the structured string with row styles. Used along with getRowContent()
		* in row redrawing
		*/
		$style=$this->tdparam[$this->getCurrentIndex()][$field];
		$tdparam=null;
		if(is_array($style)){
			// now we should convert style elements' names to JS compatible
			$tdparam=array();
			foreach($style as $key=>$value){
				switch($key){
					//case 'background-color':$tdparam[]="$key:$value";break;
					case 'style': case 'css':
						// style is a nested array
						foreach($value as $k=>$v){
							$tdparam[]="$k::$v";
						}
						break;

					default:
						$tdparam[]="$key::$value";
				}
			}
		}
		return (is_array($tdparam)?join($tdparam,'<s>'):'');
	}
	function getRowTitle(){
		$title=' ';
		foreach($this->title_col as $col){
			$title.=$this->current_row[$col];
		}
		if($title==' '){
			return "Row #".$this->current_row['id'];
		}
		return substr($title,1);
	}
	function getFieldContent($field,$id){
		/**
		* Returns the properly formatted field content.
		* Used firstly with Ajax::reloadExpandedField()
		*/

		// *** Getting required record from DB ***
		$idfield=$this->dq->args['fields'][0];
		if($idfield=='*'||strpos($idfield,',')!==false)$idfield='id';
		$this->dq->where($idfield,$id);
		//we should switch off the limit or we won't get any value
		$this->dq->limit(1);
		$row_data=$this->api->db->getHash($this->dq->select());

		// *** Initializing template ***
		$this->precacheTemplate(false);

		// *** Rendering row ***
		$this->current_row=(array)$row_data;
		$row=$this->formatRow();

		// *** Returning required field value ***
		return $row[$field];
	}
	function formatRow(){
		// Support for StdObject grids
		if(!is_array($this->current_row))$this->current_row=(array)$this->current_row;
		if(!$this->columns)throw new BaseException('No column defined for grid');
		foreach($this->columns as $tmp=>$column){ // $this->cur_column=>$column){
			$this->current_row[$tmp.'_original']=$this->current_row[$tmp];
			$formatters = explode(',',$column['type']);
			foreach($formatters as $formatter){
				if(method_exists($this,$m="format_".$formatter)){
					$this->$m($tmp);
				}else throw new BaseException("Grid does not know how to format type: ".$formatter);
			}
			// setting cell parameters (tdparam)
			$this->applyTDParams($tmp);
			if($this->current_row[$tmp]=='')$this->current_row[$tmp]='&nbsp;';
		}
		return $this->current_row;
	}
	function applyTDParams($field,$totals=false){
		// setting cell parameters (tdparam)
		$tdparam=$this->tdparam[$this->getCurrentIndex()][$field];
		$tdparam_str='';
		if(is_array($tdparam)){
			// wrap is replaced by style property
			unset($tdparam['wrap']);
			if(is_array($tdparam['style'])){
				$tdparam_str.='style="';
				foreach($tdparam['style'] as $key=>$value)$tdparam_str.=$key.':'.$value.';';
				$tdparam_str.='" ';
				unset($tdparam['style']);
			}
			//walking and combining string
			foreach($tdparam as $id=>$value)$tdparam_str.=$id.'="'.$value.'" ';
			if($totals)$this->totals_t->set("tdparam_$field",trim($tdparam_str));
			else $this->row_t->set("tdparam_$field",trim($tdparam_str));
		}
	}
	function setTotalsTitle($field,$title="Total:"){
		$this->totals_title_field=$field;
		$this->totals_title=$title;
		return $this;
	}
	function formatTotalsRow(){
		foreach($this->columns as $tmp=>$column){
			$formatters = explode(',',$column['type']);
			$all_failed=true;
			foreach($formatters as $formatter){
				if(method_exists($this,$m="format_totals_".$formatter)){
					$all_failed=false;
					$this->$m($tmp);
				}
			}
			// setting cell parameters (tdparam)
			$this->applyTDParams($tmp,true);
			if($all_failed)$this->current_row[$tmp]='-';
		}
	}
	function updateTotals(){
		parent::updateTotals();
		foreach($this->current_row as $key=>$val){
			if ((!empty($this->totals_title_field)) and ($key==$this->totals_title_field)) {
				$this->totals[$key]=$this->totals_title;
			}
		}
	}
	/**
	 * Adds paginator to the grid
	 * @param $ipp row count per page
	 * @param $name if set, paginator will get the name specified. Useful for saving
	 * 		different page numbers for different filtering conditions
	 */
	function addPaginator($ipp=25,$name=null){
		// adding ajax paginator
		$this->paginator=$this->add('Paginator', $name, 'paginator', array('paginator', 'ajax_paginator'));
		// depending on where this grid is rendered...
		if($_GET['expanded']){
			// in expanded region
			$this->paginator->region($this->getExpanderId());
			$this->paginator->cutObject($this->owner->name);
			$this->api->stickyGET('expanded');
			$this->api->stickyGET('expander');
			$this->api->stickyGET('id');
		}else{
			// on the page directly
			$this->paginator->region($this->name);//$_GET['expanded'].'_expandedcontent_'.$_GET['id']);
			$this->paginator->cutObject($this->name);
		}
		$this->paginator->ipp($ipp);
		$this->current_row_index=$this->paginator->skip-1;
		return $this;
	}
	function precacheTemplate($full=true){
		// pre-cache our template for row
		// $full=false used for certain row init
		$row = $this->row_t;
		$col = $row->cloneRegion('col');

		$row->set('row_id','<?$id?>');
		$row->trySet('odd_even','<?$odd_even?>');
		$row->del('cols');

		if($full){
			$header = $this->template->cloneRegion('header');
			$header_col = $header->cloneRegion('col');
			$header_sort = $header_col->cloneRegion('sort');

			if($t_row = $this->totals_t){
				$t_col = $t_row->cloneRegion('col');
				$t_row->del('cols');
			}

			$header->del('cols');
		}

		if(count($this->columns)>0){
			foreach($this->columns as $name=>$column){
				$col->del('content');
				$col->set('content','<?$'.$name.'?>');

				if(isset($t_row)){
					$t_col->del('content');
					$t_col->set('content','<?$'.$name.'?>');
					$t_col->trySet('tdparam','<?tdparam_'.$name.'?>style="white-space: nowrap"<?/?>');
					$t_row->append('cols',$t_col->render());
				}

				// some types needs control over the td

				$col->set('tdparam','<?tdparam_'.$name.'?>style="white-space: nowrap"<?/?>');

				$row->append('cols',$col->render());

				if($full){
					$header_col->set('descr',$column['descr']);
					if(isset($column['sortable'])){
						$s=$column['sortable'];
						// calculate sortlink
						$l = $this->api->getDestinationURL(null,array($this->name.'_sort'=>$s[1]));

						$header_sort->trySet('order',$column['sortable'][0]);
						$sicons=array('vertical','top','bottom');
						$header_sort->trySet('sorticon',$sicons[$column['sortable'][0]]);
						$header_sort->set('sortlink',$l);
						$header_col->set('sort',$header_sort->render());
					}else{
						$header_col->del('sort');
						$header_col->tryDel('sort_del');
					}
					$header->append('cols',$header_col->render());
				}
			}
		}
		$this->row_t = $this->api->add('SMlite');
		$this->row_t->loadTemplateFromString($row->render());

		if(isset($t_row)){
			$this->totals_t = $this->api->add('SMlite');
			$this->totals_t->loadTemplateFromString($t_row->render());
		}

		if($full)$this->template->set('header',$header->render());
		// for certain row: required data is in $this->row_t
		//var_dump(htmlspecialchars($this->row_t->tmp_template));

	}
	function render(){
		if(($this->dq&&$this->dq->foundRows()==0)||(!isset($this->dq)&&empty($this->data))){
			$def_template = $this->defaultTemplate();
			//$not_found=$this->add('SMlite')->loadTemplate($def_template[0])->cloneRegion('not_found');
			//$this->template->set('no_records_message',$this->no_records_message);
			//$this->template->del('rows');
			//$this->template->del('totals');
			//$this->template->set('header','<tr class="header">'.$not_found->render().'</tr>');
			$this->totals=false;
			$this->template->del('full_table');
//    		return true;
		}else{
			$this->template->del('not_found');
		}
		parent::render();

	}

	public function setWidth( $width ){
		$this->template->set('container_style', 'margin: 0 auto; width:'.$width.((!is_numeric($width))?'':'px'));
		return $this;
	}
	public function setNoRecords($message){
		$this->no_records_message=$message;
		return $this;
	}
	public function getCurrentIndex($idfield='id'){
		// TODO: think more to optimize this method
		if(is_array($this->data))return array_search(current($this->data),$this->data);
		// else it is dsql dataset...
		return $this->current_row[$idfield];
	}
	public function setTDParam($field,$path,$value){
		// adds a parameter. nested ones can be specified like 'style/color'
		$path=explode('/',$path);
		$current_position=&$this->tdparam[$this->getCurrentIndex()][$field];
		if(!is_array($current_position))$current_position=array();
		foreach($path as $part){
			if(array_key_exists($part,$current_position)){
				$current_position=&$current_position[$part];
			}else{
				$current_position[$part]=array();
				$current_position=&$current_position[$part];
			}
		}
		$current_position=$value;
	}
	public function setTabMovesDown($down=true){
		$this->tab_moves_down=$down;
		return $this;
	}
	public function setShowSubmit($show=true){
		$this->show_submit=$show;
		return $this;
	}
	/**
	* Sets inline properties.
	* @param $props - hash with properties: array('tab_moves_down'=>false/true,'show_submit'=>false/true,etc)
	* 	hash keys should replicate local properties names
	*/
	public function setInlineProperties($props){
		foreach($props as $key=>$val){
			$this->$key=$val;
		}
		return $this;
	}
	function addOrder(){
		return $this->add('Order','columns')
			->useArray($this->columns)
			;
	}
	/**
	 * Adds column on the basis of Model definition
	 * If $type is passed - column type is replaced by this value
	 */
	function addSelectable($field){
		$this->js_widget=null;
		$this->js(true)
			->_load('ui.atk4_checkboxes')
			->atk4_checkboxes(array('dst_field'=>$field));
		$this->addColumn('checkbox','selected');

		$this->addOrder()
			->useArray($this->columns)
			->move('selected','first')
			->now();
	}
	function addFormatter($field,$formatter){
		/*
		   * add extra formatter to existing field
		   */
		if(!isset($this->columns[$field])){
			throw new BaseException('Cannot format nonexistant field '.$field);
		}
		$this->columns[$field]['type'].=','.$formatter;
		if(method_exists($this,$m='init_'.$formatter))$this->$m($field);
	}
}
