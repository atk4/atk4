<?
/*
 * Created on 02.03.2006 by camper@adevel.com
 */
trigger_error("SortableGrid is obsolete. use Grid->makeSortable()");
exit;
class SortableGrid extends Grid{
	private $sort_cols = array();
	private $sort_seq = array();

	function init(){
		parent::init();
		//$this->api->addHook('pre-render', array($this, 'useSort'));
	}
	/**
	 * This method is protected to be overrided if needed.
	 * It doesn't have to be public
	 */
	protected function useSort(){
		if(isset($_REQUEST['clearsort']))$this->clearSort();
		if(isset($this->dq)){
			//merging stored sort_cols with recently added
			if(is_array($this->recall("sort_cols"))){
				$this->sort_cols = array_merge($this->sort_cols, $this->recall("sort_cols"));
			}
			//if(!is_array($this->sort_cols))$this->sort_cols = array();
			$this->sort_seq = $this->recall("sort_seq");
			if(isset($_REQUEST['sortby'])){
				$this->sort_cols[$_REQUEST['sortby']] = $_REQUEST['sortorder'];
				$this->sort_seq[] = $_REQUEST['sortby'];
			}
			if(is_array($this->sort_seq)){
				$this->sort_seq = array_unique($this->sort_seq);
				foreach($this->sort_seq as $col){
					$order = $this->sort_cols[$col];
					if($order > 0)$this->dq->order($col, $order == 2);
				}
			}
			echo "<br>".$this->dq->select();
			$this->memorize("sort_cols", $this->sort_cols);
			$this->memorize("sort_seq", $this->sort_seq);
		}
		//print_r($this->sort_cols);
	}
	function addColumn($type,$name,$descr=null,$sortable=false){
		if($descr===null)$descr=$name;
		$this->columns[$name]=array(
				'type'=>$type,
				'descr'=>$descr
				);
		if($sortable)$this->sort_cols[$name] = 0; //0 - none, 1- ascending, 2 - descending
		return $this;
	}
	function clearSort(){
		foreach($this->sort_cols as &$value)$value = 0;
		$this->sort_seq = array();
		$this->forget("sort_cols");
		$this->forget("sort_seq");
	}

	function precacheTemplate(){
		// pre-cache our template for row
		$this->useSort();
		$row = $this->row_t;
		$col = $row->cloneRegion('col');
		$header = $this->template->cloneRegion('header');
		$header_col = $header->cloneRegion('col');

		$row->set('grid_name',$this->name);
		$row->set('row_id','<?$id?>');
		$row->set('odd_even','<?$odd_even?>');
		$row->del('cols');
		$header->del('cols');
		foreach($this->columns as $name=>$column){
			$col->del('content');
			$col->set('content','<?$'.$name.'?>');

			// some types needs control over the td
			$col->set('tdparam','<?tdparam_'.$name.'?>nowrap<?/?>');

			$row->append('cols',$col->render());
			$header_col->set('descr',$column['descr']);
			$header_col->del('sort_icon');
			$header_col->set('sort_icon', '');
			//setting "sort" icon
			if(isset($this->sort_cols[$name])){

				if(is_array($this->sort_seq)){
					$sorder = array_search($name, $this->sort_seq);
					$sorder = $sorder === false ? "" : " (".($sorder + 1) .")";
				}else $sorder = "";
				switch($this->sort_cols[$name]){
					case 0:
						$header_col->set('sort_icon',
							"&nbsp;<a href=\"".$this->api->getDestinationURL(
								$this->api->page,array('sortby'=>$name,	'sortorder'=>'1'))
							."\"><img src=\"amodules3/img/sort0.gif\" border=0></a>$sorder");
						break;
					case 1:
						$header_col->set('sort_icon',
							"&nbsp;<a href=\"".$this->api->getDestinationURL(
								$this->api->page,array('sortby'=>$name,	'sortorder'=>'2'))
							."\"><img src=\"amodules3/img/sort1.gif\" border=0></a>$sorder");
						break;
					case 2:
						$header_col->set('sort_icon',
							"&nbsp;<a href=\"".$this->api->getDestinationURL(
								$this->api->page,array('sortby'=>$name,	'sortorder'=>'0'))
							."\"><img src=\"amodules3/img/sort2.gif\" border=0></a>$sorder");
						break;
				}
			}

			$header->append('cols',$header_col->render());
		}
		$this->row_t = $this->api->add('SMlite');
		$this->row_t->tmp_template=$row->render();
		$this->row_t->parseTemplate($this->row_t->template);
		$this->template->set('header',$header->render());
		//var_dump(htmlspecialchars($this->row_t->tmp_template));
	}

}
