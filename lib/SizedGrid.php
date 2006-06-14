<?

class SizedGrid extends Grid{
	protected $width = '100%';
	
	public function setWidth($width = null){
		$this->width = is_null($width)?'100%':$width;
		return $this;
	}
	
	function precacheTemplate(){
		parent::precacheTemplate();
		$this->template->set('container_width', $this->width);
	}
	
	function defaultTemplate(){
        return array('szgrid', '_top');
    }
}