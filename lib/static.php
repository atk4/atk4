<?php

define('undefined','_amodules3_undefined_value');

if(!function_exists('lowlevel_error')){
function lowlevel_error($error,$lev=null){
    /*
     * This function will be called for low level fatal errors
     */
    echo "<font color=red>Low level error:</font> $error in <b>".caller_lookup()."()</b><br><br>Backtrace:<pre>";
    //$backtrace=print_r(debug_backtrace(),true);
    // restricting output by 100 symbols
    if(strlen($backtrace)>100)$backtrace=substr($backtrace,0,100).'<br>... Backtrace is too long, trimmed to first 100 symbols ...';
    echo $backtrace;
    exit;
}
};if(!function_exists('error_handler')){
function error_handler($errno, $errstr, $errfile, $errline){
	$errorType = Array (
                    E_ERROR               => "Error",
                    E_WARNING             => "Warning",
                    E_PARSE               => "Parsing Error",
                    E_NOTICE              => "Notice",
                    E_CORE_ERROR          => "Core Error",
                    E_CORE_WARNING        => "Core Warning",
                    E_COMPILE_ERROR       => "Compile Error",
                    E_COMPILE_WARNING     => "Compile Warning",
                    E_USER_ERROR          => "User Error",
                    E_USER_WARNING        => "User Warning",
                    E_USER_NOTICE         => "User Notice",
                    4096                  => "Runtime Notice" 
                    );
                    
	if((error_reporting() & $errno)!=0) {
	    $errfile=dirname($errfile).'/<b>'.basename($errfile).'</b>';
	    $str="<font style='font-family: verdana;  font-size:10px'><font color=blue>$errfile:$errline</font> <font color=red>[$errno] <b>$errstr</b></font></font>";
	    
	    switch ($errno) {
	        case 2:
	            if(strpos($errstr,'mysql_connect')!==false)break;
	        case 8:
	            if(substr($errstr,0,16)=='Undefined offset')break;
	            if(substr($errstr,0,15)=='Undefined index')break;
	        case 2048:
	            if(substr($errstr,0,15)=='var: Deprecated')break;
	            if(substr($errstr,0,17)=='Non-static method')break;
	        default:
	        	if(ini_get('display_errors') == 1 || ini_get('display_errors') == 'ON')
	            	echo "$str<br />\n";
	            if(ini_get('log_errors') == 1 || ini_get('log_errors') == 'ON')
	            	error_log(" $errfile:$errline\n[".$errorType[$errno]."] ".strip_tags($errstr),0);
	            break;
	    }
	}
}
/*
};if(!function_exists('htmlize_exception')){
function htmlize_exception($e,$msg){
    //$e->HTMLize();
    echo $e->getMessage()."<br>\n";
}
*/
};if(!function_exists('safe_array_merge')){
    // array_merge gives us an error when one of arguments is null. This function
    // acts the same as array_merge, but without warnings
    function safe_array_merge($a,$b=null){
        if(is_null($a)){
            $a=$b;
            $b=null;
        }
        if(is_null($b))return $a;
        return array_merge($a,$b);
    }
};if(!function_exists('hash_filter')){
    // array_merge gives us an error when one of arguments is null. This function
    // acts the same as array_merge, but without warnings
    function hash_filter($hash,$allowed_keys){
        // This function will filter only keys/values from hash which are
        // in allowed_keys as well.
        $result = array();
        foreach($allowed_keys as $key=>$newkey){
            if(is_int($key))$key=$newkey;
            if(isset($hash[$key])){
                $result[$newkey]=$hash[$key];
            }
        }
        return $result;
    }
};if(!function_exists('caller_lookup')){
    // sometimes i wonder, who called some specific function. Now you can find out
    // caller_lookup relies on backtrack info to pull out into about caller class
    function caller_lookup($shift=0,$file=false){
        // This function will filter only keys/values from hash which are
        // in allowed_keys as well.
        $bt=debug_backtrace();
        $shift+=3;
        @$r=(
                ($file?$bt[$shift]['file'].":".$bt[$shift]['line'].":":"").
                $bt[$shift]['class'].
                $bt[$shift]['type'].
                $bt[$shift]['function']);
        return $r;
    }
};if(!function_exists('__autoload')){
    function loadClass($class){
        $file = str_replace('_',DIRECTORY_SEPARATOR,$class).'.php';
        foreach (explode(PATH_SEPARATOR, get_include_path()) as $path){
            $fullpath = $path . DIRECTORY_SEPARATOR . $file;
            if (file_exists($fullpath)) {
                return $fullpath; 
            }
        }
        return false;
    }
    function __autoload($class){

        if(!$fullpath=loadClass($class)){
            lowlevel_error("Class is not defined and couldn't be loaded: $class. Consult documentation on __autoload()");
        }
        include_once($fullpath);
        if(class_exists($class))return;

        lowlevel_error("Class $class is not defined in $file");
    }
};if(!function_exists('format_time')){
	function format_time($s, $exclude_seconds = false){
		$m=floor($s/60);$s=$s%60;
		$h=floor($m/60);$m=$m%60;
		$d=floor($h/24);$h=$h%24;
		//if(!$h)return sprintf("%02d:%02d",$m,$s);
		if($exclude_seconds)return sprintf(($d>0?"%d day".($d>1?'s ':' '):'')."%d:%02d",$h,$m);
		return ($d>0?sprintf('%d day'.($d>1?'s ':' ')."%02d:%02d:%02d",$d,$h,$m,$s):sprintf("%02d:%02d:%02d",$h,$m,$s));
	}
};if(!function_exists('format_time_str')){
	function format_time_str($s, $exclude_seconds=false, $short=false){
		//by Camper
		$m=floor($s/60);$s=$s%60;
		$h=floor($m/60);$m=$m%60;
		$d=floor($h/24);$h=$h%24;
		//if(!$h)return sprintf("%02d:%02d",$m,$s);
		$result="";
		$result.=($d>0?$d." ".($short?"d ":"day".($d>1?'s ':' ')):'');
		$result.=($h>0?$h." ".($short?"h ":"hour".($h>1?"s ":" ")):"");
		$result.=($m>0?$m." ".($short?"m ":"minute".($m>1?"s ":" ")):"");
		if(!$exclude_seconds)$result.=($s>0?$s." ".($short?"s":"second".($s>1?"s":"")):"");
		return $result;
	}
};if(!function_exists('format_file_size')){
	function format_file_size($size_in_bytes){
		if($size_in_bytes<1024)return $size_in_bytes.' b';
		if($size_in_bytes/1024<1024)return round($size_in_bytes/1024). ' kb';
		if($size_in_bytes/(1024*1024)<1024)return round($size_in_bytes/(1024*1024)).' Mb';
		return round($size_in_bytes/(1024*1024*1024)).' Gb';
	}
};if(!function_exists('is_decimal_number')){
	function is_decimal_number($n) {
		return (string)(float)$n === (string)$n;
	}
}
