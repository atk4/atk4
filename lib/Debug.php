<?php
class Debug {
	/*
	 */
	private $the_real_thing=null;
	private $the_real_api=null;
	function __construct($class_name){
		$this->the_real_thing = new $class_name();
	}

	function __call($m, $a){
		if($this->the_real_api){
			$this->the_real_api->debug(get_class($this->the_real_thing).": Calling $m from ".caller_lookup()." (from ".caller_lookup(1).")");
		}
		$result = call_user_func_array(array($this->the_real_thing,$m),$a);
		if($this->the_real_api){
			$this->the_real_api->debug(get_class($this->the_real_thing).": Returned from $m [".$this->the_real_api->bigfatfoobar."]");
		}
		return $result;
	}

	function __set($n, $v){
		if($n==='api'){
			$this->the_real_api=$v;
			$this->the_real_api->debug=1;
		}

		if($this->the_real_api){
			$this->the_real_api->debug(get_class($this->the_real_thing).": Setting $n to $v from ".caller_lookup()." (from ".caller_lookup(1).")");
		}

		$this->the_real_thing->$n=$v;
	}

	function __get($n){
		if($this->the_real_api){
			$this->the_real_api->debug(get_class($this->the_real_thing).": Reading $n=".$this->the_real_thing->$n." from ".caller_lookup()." (from ".caller_lookup(1).")");
		}


		return $this->the_real_thing->$n;
	}

	function __isset($n){
		return isset($this->the_real_thing->$n);
	}
	function __unset($n){
		unset($this->the_real_thing->$n);
	}
}
