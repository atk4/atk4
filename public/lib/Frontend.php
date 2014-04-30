<?php

class Frontend extends ApiFrontend {
    function init()
    {
        parent::init();
        // config-default.php file is placed at root of project folder
        // $this->api->dbConnect();
        $this->title =  'Agiletoolkit 4.3';
        $this->add('jUI');

        $this->pathfinder->addLocation(array(
            'php'=>'lib'
            ))->setBasePath($this->pathfinder->base_location->base_path .'/shared');

        $l=$this->add('Layout_Fluid');
        
        $footer=$l->addFooter();
        $header=$l->addHeader();
        
        $header_menu=$header->add('Menu');

        $header_menu->addMenuItem('index',array('Home','icon'=>'home','swatch'=>'yellow'));

    }
}
