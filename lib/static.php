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

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/

define('undefined', '_atk4_undefined_value');
define('UNDEFINED', '_atk4_undefined_value');

if(!function_exists('error_handler')){
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
                    if(strpos($errstr,'Undefined offset')===0)break;
                    if(strpos($errstr,'Undefined index')===0)break;
                case 2048:
                    if(strpos($errstr,'var: Deprecated')===0)break;
                    if(strpos($errstr,'Declaration of ')===0)break;
                    if(strpos($errstr,'Non-static method')===0)break;
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
};
