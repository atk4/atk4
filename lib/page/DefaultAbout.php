<?
abstract class page_DefaultAbout extends Page {
	var $about_this;
	function init(){
		parent::init();
		$this->api->addHook('post-init',array($this,'aboutAModules3'));
	}
	function aboutAModules3(){
		$msg=$this->frame('About AModules3');
		$t=$msg->add('Text');
		$text="This web application was developed using <a href=\"_blank\" href=\"http://adevel.com/amodules3/\">AModules3 framework</a>. AModules3 is a free software. It is licensed under LGPL and goes together with your application. Please, feel free to use it in your applications and give it some popularity.</p><p><h3>AModules3 developers</h3>";
		if(file_exists($f=dirname(__FILE__).'/../../CREDITS')){
			$tmp=nl2br(htmlspecialchars(file_get_contents($f)));
			$tmp=preg_replace("/^(.*):/m",'<b>\1:</b>',$tmp);
			$text.=$tmp;
		}
		$t->set($text);
	}
}
