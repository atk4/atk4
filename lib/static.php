<?php // vim:ts=4:sw=4:et
/**
 * Contains static functions. Agile Toolkit does not generally use
 * static functions, so please do not use any functions here.
 *
 * More Info
 *  @link http://agiletoolkit.org/learn/learn/understand/api
 *  @link http://agiletoolkit.org/doc/apicli
 */
/*
==ATK4===================================================
   This file is part of Agile Toolkit 4 
    http://agiletoolkit.org/
  
   (c) 2008-2012 Romans Malinovskis <romans@agiletoolkit.org>
   Distributed under Affero General Public License v3
   
   See http://agiletoolkit.org/about/license
 =====================================================ATK4=*/

define('undefined','_atk4_undefined_value');

if(!function_exists('lowlevel_error')){
    function lowlevel_error($error,$lev=null){
        /*
         * This function will be called for low level fatal errors
         */
        echo "<font color=red>Low level error:</font> $error in <b>".caller_lookup()."()</b><br><br>Backtrace:<pre>";
        $backtrace="backtrace disabled, it cashes browser";
        //$backtrace=print_r(debug_backtrace(),true);
        // restricting output by X symbols
        $x=4096; //4k
        if(strlen($backtrace)>$x)$backtrace=substr($backtrace,0,$x).
            "<br>... Backtrace is too long, trimmed to first $x symbols ...";
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
                    if(substr($errstr,0,15)=='Declaration of ')break;
                    if(substr($errstr,0,17)=='Non-static method')break;
                case 8192:
                    if(substr($errstr,-13)=='is deprecated')break;
                default:
                    if(ini_get('display_errors') == 1 || ini_get('display_errors') == 'ON')
                        echo "$str<br />\n";
                    if(ini_get('log_errors') == 1 || ini_get('log_errors') == 'ON')
                        error_log(" $errfile:$errline\n[".$errorType[$errno]."] ".strip_tags($errstr),0);
                    break;
            }
        }
    }
    set_error_handler("error_handler");

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
        if(is_null($a))return $b;
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
        if(isset($GLOBALS['atk_pathfinder'])){
            return $GLOBALS['atk_pathfinder']->loadClass($class);
        }
        $file = str_replace('_',DIRECTORY_SEPARATOR,$class).'.php';
        $file = str_replace('\\','/',$file);
        foreach (explode(PATH_SEPARATOR, get_include_path()) as $path){
            $fullpath = $path . DIRECTORY_SEPARATOR . $file;
            if (file_exists($fullpath)) {
                include_once($fullpath);
                return;
            }
        }
        lowlevel_error("Class is not defined and couldn't be loaded: $class. Consult documentation on __autoload()");
        return false;
    }
    function __autoload($class){
        loadClass($class);
        if(class_exists($class) || interface_exists($class))return;
        lowlevel_error("Class $class is not defined in included file");
    }
};if(!function_exists('unix_dirname')){
    function unix_dirname($path){
        $chunks=explode('/',$path);
        array_pop($chunks);
        if(!$chunks)return '/';
        return implode('/',$chunks);
    }
};if(!function_exists('htmlentities_utf8')){
    function htmlentities_utf8($string, $quote_style = ENT_COMPAT, $charset='UTF-8'){
        return htmlentities($string,$quote_style,$charset);
    }
};if(!function_exists('__')){
    function __($string){
        return $string;
    }
}
