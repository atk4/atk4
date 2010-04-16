<?php
class DBlite_debug2 extends Debug {
	function __construct(){
		echo "me is born";
		parent::__construct('DBlite_mysql');
	}
	function dsql(){
		$f=new Debug('DBlite_dsql');
		$f->db=$this;
		$f->api=$this->api;
		return $f;
	}
}
