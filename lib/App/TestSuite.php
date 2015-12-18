<?php

/**
 * This class is designed to building test-scripts of your application. It will
 * attempt to be a good citizen and play nicely with PHPUnit as well as give
 * you ability to replace certain functionality with mock's.
 */
class App_TestSuite extends App_CLI {
    protected $pathfinder_class=['PathFinder','report_autoload_errors'=>false];

    function dbConnect(){
        throw $this->exception('Preventing you from connecting to live DB');
    }

}
