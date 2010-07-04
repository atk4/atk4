<?
/*
 * jQuery is an compatibility layer if jQuery UI is not used.

 * by romans
 */
class jQuery_plugin extends AbstractController {
	private $active=array();
	function init(){
		parent::init();
		$this->api->jquery
			->addInclude(basename($this->short_name).'/jquery.'.basename($this->short_name))
			->addStylesheet(basename($this->short_name).'/jquery.'.basename($this->short_name))
			;
	}
	function activate($tag=null,$param=null){
		if($thdropdownis->active[$tag])return;
		if(!$tag)$tag=".".$this->short_name;
		$this->api->jquery->addOnReady($o='$("'.$tag.'").'.$this->prefix.$this->short_name.'('.($param?"{".$param."}":'').')');
		$this->active[$tag]=true;
	}
}
class jQuery extends AbstractController {
	private $chains=0;
	function init(){
		parent::init();

		$this->api->jquery=$this;

		if(!$this->api->template->is_set('js_include'))
			throw new BaseException('Tag js_include must be defined in shared.html');
		if(!$this->api->template->is_set('document_ready'))
			throw new BaseException('Tag document_ready must be defined in shared.html');


		$this->api->template->del('js_include');

		$this->addInclude('jquery-'.$this->api->getConfig('js/versions/jquery','1.4.2'));

		// Controllers are not rendered, but we need to do some stuff manually
		$this->api->addHook('pre-render-output',array($this,'postRender'));
		$this->api->addHook('cut-output',array($this,'cutRender'));
	}
	function addInclude($file,$ext){
		$url=$this->api->locateURL('js',$file.$ext);
		$this->api->template->append('js_include',
			'<script type="text/javascript" src="'.$url.'"></script>'."\n");
		return $this;
	}
	function addStylesheet($file,$ext='.css'){
		//$file=$this->api->locateURL('css',$file.$ext);

		$this->api->template->append('js_include',
				'<link type="text/css" href="'.$this->api->locateURL('css',$file.$ext).'" rel="stylesheet" />'."\n");
		return $this;
	}
	function addOnReady($js){
		if(is_object($js))$js=$js->getString();
		$this->api->template->append('document_ready', $js.";\n");
		return $this;
	}
	function chain($object){
		if(!is_object($object))throw new BaseException("Specify \$this as argument if you call chain()");
		return $object->add('jQuery_Chain');
	}
	function addPlugin($name){
		return $this->add('jQuery_plugin',$name);
	}
	function cutRender(){
		$x=$this->api->template->get('document_ready');
		if(is_array($x))$x=join('',$x);
		echo '<script type="text/javascript">'.$x.'</script>';
		return;
	}
	function postRender(){
		//echo nl2br(htmlspecialchars("Dump: \n".$this->api->template->renderRegion($this->api->template->tags['js_include'])));
	}


	function getJS($obj){

		$r='';
		foreach($obj->js as $key=>$chains){
			$o='';
			foreach($chains as $chain){
				$o.=$chain->_render().";\n";
			}
			switch($key){
				case 'never':
					// send into debug output
					//if(strlen($o)>2)$this->addOnReady("if(console)console.log('Element','".$obj->name."','no action:','".str_replace("\n",'',addslashes($o))."')");
					continue;

				case 'always':
					$r.=$o;
					break;
				default:
					$o='';
					foreach($chains as $chain){
						$o.=$chain->_enclose($key)->_render().";\n";
					}
					$r.=$o;
			}
		}
		if($r)$this->addOnReady($r);
	}
}
