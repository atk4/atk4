<?
class View_Tabs_jUItabs extends View {
	protected $tab_template=null;
	protected $tab_count=0;

	function init(){
		parent::init();
		$this->js(true)->_load('ui.atk4_tabs')->tabs(array('cache'=>false));
		$this->tab_template=$this->template->cloneRegion('tabs');
		//$this->js(true)->_selector('#'.$this->name.'_tabs')->univ()->bwtabs('#'.$this->name.'_content');
		$this->template->del('tabs');

	}
	function addTab($name,$title=null){
		if($title===null)$title=$name;

		$container=$this->add('View_HtmlElement',$name);

		$this->tab_template->set(array(
							  'url'=>'#'.$container->name,
							  'tab_name'=>$title,
							  'tab_id'=>$container->short_name,
							  ));
		$this->template->append('tabs',$this->tab_template->render());
		return $container;
	}
	
	function addTabURL($page,$title,$args=array()){
		$this->tab_template->set(array(
							  'url'=>$this->api->getDestinationURL($page,array('cut_page'=>1)),
							  'tab_name'=>$title,
							  'tab_id'=>basename($page),
							  ));
		$this->template->append('tabs',$this->tab_template->render());



		$this->tab_count++;
		return $this;
	}
	function defaultTemplate(){
		return array('tabs','_top');

	}
}
