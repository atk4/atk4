<?php
/**
 * Api_Admin should be used for building your own application's administration
 * model. The benefit is that you'll have access to a number of add-ons which
 * are specifically written for admin system.
 *
 * Exporting add-ons, database migration, test-suites and other add-ons
 * have developed User Interface which can be simply "attached" to your
 * application's admin.
 *
 * This is done through hooks in the Admin Class. It's also important that
 * Api_Admin relies on layout_fluid which makes it easier for add-ons to
 * add menu items, sidebars and foot-bars.
 */
class Api_Admin extends ApiFrontend {

    public $title='Agile Toolkit™ Admin';
    private $no_sendbox;

    function init() {
        parent::init();


        $this->add('Layout_Fluid');

        $this->menu = $this->layout->addMenu();
        $this->layout->addFooter();

        $this->add('jUI');
       
        if ($this->no_sendbox===false) {
            $this->police = $this->add('Controller_Police');
            $this->layout->add('sandbox/View_Toolbar',null,'Toolbar');
        }
                   

    }

    function initLayout() {

        $this->addAddonsLocations();
        $this->initAddons();

       // $this->add('sandbox/Initiator');

        parent::initLayout();

        // TODO - remove dependency on get arguments in generic code

        if (is_object($this->police)) {
            if ($_GET['debug']) {
                 $this->police->addDebugView($this->page_object);
            }
            try {
                $this->police->guard();
            } catch (Exception $e) {
                $this->police->addErrorView($this->page_object);
            }
        }
        


    }


    function addAddonsLocations() {
        $base_path = $this->pathfinder->base_location->getPath();
        $file = $base_path.'/../../sandbox_addons.json';
        if (file_exists($file)) {
            $json = file_get_contents($file);
            $objects = $this->addons = json_decode($json);
            foreach ($objects as $obj) {
                // Private location contains templates and php files YOU develop yourself
                /*$this->private_location = */
                $this->api->pathfinder->addLocation(array(
                    'docs'      => 'docs',
                    'php'       => 'lib',
                    'page'      => 'page',
                    'template'  => 'templates',
                ))
                        ->setBasePath($base_path.'/'.$obj->addon_full_path)
                ;

                $addon_public = $obj->addon_symlink_name;
                // this public location cotains YOUR js, css and images, but not templates
                /*$this->public_location = */
                $this->api->pathfinder->addLocation(array(
                    'js'     => 'js',
                    'css'    => 'css',
                    'public' => './',
                    //'public'=>'.',  // use with < ?public? > tag in your template
                ))
                        ->setBasePath($this->app_base_path.'/'.$obj->addon_public_symlink)
                        ->setBaseURL($this->api->url('/').$addon_public) // $this->api->pm->base_path
                ;
            }
        }
    }
    function initAddons() {
        $base_path = $this->pathfinder->base_location->getPath();
        $file = $base_path.'/sandbox_addons.json';
        if (file_exists($file)) {
            $json = file_get_contents($file);
            $objects = json_decode($json);
            foreach ($objects as $obj) {
                // init addon
                $init_class_path = $base_path.'/'.$obj->addon_full_path.'/lib/Initiator.php';
                if (file_exists($init_class_path)) {
                    $class_name = str_replace('/','\\',$obj->name.'\\Initiator');
                    $init = $this->add($class_name,array(
                        'addon_obj' => $obj,
                    ));
                }
            }
        }
    }
}
