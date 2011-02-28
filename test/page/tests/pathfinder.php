<?php
/*
   This is a sample mockup page illustrating some UI design components.
   Please leave inact
   */
class page_tests_pathfinder extends Page {
	function init(){
		parent::init();

		$f=$this->frame('Performing path-finder tests');

		$types=array();
		$o='';
		// Show statistics first
		foreach($this->api->pathfinder->elements as $obj){
			if(!($obj instanceof PathFinder_Location))continue;

			$o.="<p>Location: ".$obj."<ul>";
			 $o.="<li><b>Web path</b>: ".$obj->getURL();
			 $o.="<li><b>File path</b>: ".$obj->getPath();
			 $o.="<li><b>Contents</b>:<br/><pre>".print_r($obj->contents,true)."</pre>";
			$o.="</ul></p>";
		}

		$f->add('Text')->set($o);

	}
}
