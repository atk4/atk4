<?
/*
 * This object add one simple element on the page such as div or tag. However it's extremely useful
 * for adding different properties, events or setting crlass
 */
class View_HtmlElement extends View {
	function setElement($element){
		$this->template->trySet('element',$element);
		return $this;
	}
	function setAttr($attribute,$value=null){
		if(is_array($attribute)&&is_null($value)){
			foreach($attribute as $a=>$b)$this->setAttr($a,$b);
			return $this;
		}
		$this->template->append('attributes',' '.$attribute.'="'.$value.'"');
		return $this;
	}
	function addClass($class){
		if(is_array($class)){
			foreach($class as $c)$this->addClass($class);
			return $this;
		}
		$this->template->append('class'," ".$class);
		return $this;
	}
	function addStyle($property,$style=null){
    	if(is_null($style)&&is_array($property)){
    		foreach($property as $k=>$v)$this->setStyle($k,$v);
    		return $this;
    	}
		$this->template->append('style',";".$property.':'.$style);
		return $this;
	}
	function setText($text){
		$this->template->trySet('Content',$text);
		return $this;
	}
	function set($text){
		return $this->setText($text);
	}
	function pageLink($page){
		$this->addClass('atk4-link');
		$this->setAttr('href',$this->api->getDestinationURL($page));
		return $this;
	}
	function defaultTemplate(){
		return array('htmlelement','_top');
	}
}
