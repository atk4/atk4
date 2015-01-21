<?php
/**
 * App_Admin should be used for building your own application's administration
 * model. The benefit is that you'll have access to a number of add-ons which
 * are specifically written for admin system.
 *
 * Exporting add-ons, database migration, test-suites and other add-ons
 * have developed User Interface which can be simply "attached" to your
 * application's admin.
 *
 * This is done through hooks in the Admin Class. It's also important that
 * App_Admin relies on layout_fluid which makes it easier for add-ons to
 * add menu items, sidebars and foot-bars.
 */
class App_Admin extends App_Frontend {

    public $title='Agile Toolkit™ Admin';

    private $controller_install_addon;

    public $layout_class='Layout_Fluid';

    public $auth_config=array('admin'=>'admin');

    /** Array with all addon initiators, introduced in 4.3 */
    private $addons=array();

    function init() {
        parent::init();
        $this->add($this->layout_class);

        $this->menu = $this->layout->addMenu('Menu_Vertical');
        $this->menu->swatch='ink';

        //$m=$this->layout->addFooter('Menu_Horizontal');
        //$m->addItem('foobar');

        $this->add('jUI');

        $this->initSandbox();
    }

    private function initSandbox() {
        if ($this->pathfinder->sandbox) {
            $sandbox = $this->app->add('sandbox/Initiator');

            if ($sandbox->getGuardError()) {
                $this->sandbox->getPolice()->addErrorView($this->layout);
            }
        }
    }

    function initLayout() {
        if ($this->pathfinder->sandbox) {
            $this->initAddons();
        }else{
            if(preg_match('/^sandbox_/',$this->app->page)){
                $this->app->redirect('sandbox');
            }
        }

        $this->addLayout('mainMenu');

        parent::initLayout();

        $this->initTopMenu();


        if(!$this->pathfinder->sandbox && !$this->app->getConfig('production',false)){
            $this->menu->addItem(array('Install Developer Toools','icon'=>'tools'),'sandbox');
        }

        if(@$this->sandbox){
            $this->sandbox->initLayout();
        }
    }

    function initTopMenu() {
        $m=$this->layout->add('Menu_Horizontal',null,'Top_Menu');
        //$m->addClass('atk-size-kilo');
        $m->addItem('Admin','/');
        $m->addItem('AgileToolkit','/sandbox/dashboard');
        $m->addItem('Documentation','http://book.agiletoolkit.org/');
    }



    function page_sandbox($p){
        $p->title='Install Developer Tools';
        //$p->addCrumb('Install Developer Tools');

        $v=$p->add('View',null,null,array('view/developer-tools'));

        $v->add('Button')->set('Install Now')
            ->addClass('atk-swatch-green')->onClick(function(){

            $installation_dir = getcwd();
            if(file_exists($installation_dir).'/VERSION'){
                $installation_dir=dirname($installation_dir);
            }
            $path_d = $installation_dir . '/agiletoolkit-sandbox-d.phar';
            $path = $installation_dir . '/agiletoolkit-sandbox.phar';
            if (file_put_contents($path_d, file_get_contents("http://www4.agiletoolkit.org/dist/agiletoolkit-sandbox.phar")) == false){
                return 'update error';
            }else{
                if(rename($path_d,$path) == false){
                    // get version of a phar

                    return 'update error';
                }else{
                    $version=file_get_contents('phar://'.$path.'/VERSION');

                    return 'updated to '.$version;
                }
            }
        });
    }

    /**
     * @return array()
     * sandbox/Controller_AddonsConfig_Reflection
     * Return all registered in sandbox_addons.json addons
     */
    function getInstalledAddons() {
        if (!$this->controller_install_addon) {
            $this->controller_install_addon = $this->add('sandbox\\Controller_InstallAddon');
        }


        if ($this->controller_install_addon && $this->controller_install_addon->getSndBoxAddonReader())
            return $this->controller_install_addon->getSndBoxAddonReader()->getReflections();

        return array();
    }

    function getInitiatedAddons($addon_api_name=null) {
        if ($addon_api_name) {
            return $this->addons[$addon_api_name];
        }
        return $this->addons;
    }
    private function initAddons() {
        return;
        foreach ($this->getInstalledAddons() as $addon) {
            $this->initAddon($addon);
        }
    }
    private function initAddon($addon) {
        $base_path = $this->pathfinder->base_location->getPath();
        $init_class_path = $base_path.'/../'.$addon->get('addon_full_path').'/lib/Initiator.php';
        if (file_exists($init_class_path)) {
            include $init_class_path;
            $class_name = str_replace('/','\\',$addon->get('name').'\\Initiator');
            $init = $this->add($class_name,array(
                'addon_obj' => $addon,
                'base_path' => $base_path,
            ));
            if (!is_a($init,'Controller_Addon')) {
                throw $this->exception(
                    'Initiator of '.$addon->get('name').' is inherited not from \Controller_Addon'
                );
            }

            /**
             * initiators of all addons are accessible
             * from all around the project
             * through $this->app->getInitiatedAddons()
             */
            $this->addons[$init->api_var] = $init;
            if ($init->with_pages) {
                $init->routePages($init->api_var);
            }
        }
    }
}
