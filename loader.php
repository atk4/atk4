<?php
// This sets initial path for the include. Further includes will be initialized through PathFinder
set_include_path('.'.PATH_SEPARATOR.'.'.DIRECTORY_SEPARATOR.'lib'.PATH_SEPARATOR.dirname(__FILE__).'/lib');
if (version_compare(PHP_VERSION, '5.3.0') < 0) {
    echo 'PHP 5.3.0 is required';
    exit;
}

/**
 * In the event of missing Composer, we shall load this method for the initial
 * bootstraping of our base classes. Once pathFinder is initialized, it will
 * unregister this loader and will place it's own, more advanced loader
 */

function agile_toolkit_temporary_load_class($class) {
    $file = str_replace('_',DIRECTORY_SEPARATOR,$class).'.php';
    $file = str_replace('\\','/',$file);
    foreach (explode(PATH_SEPARATOR, get_include_path()) as $path){
        $fullpath = $path . DIRECTORY_SEPARATOR . $file;
        if (file_exists($fullpath)) {
            include_once($fullpath);

            // add to loading log
            $GLOBALS['agile_toolkit_temporary_load_class_log'][$class]=$fullpath;

            return;
        }
    }
    trigger_error('Class "'.$class.'" could not be loaded. Unable to start Agile Toolkit');
}

// register temporary loader
spl_autoload_register('agile_toolkit_temporary_load_class');


require'static.php';
