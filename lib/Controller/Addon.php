<?php
/**
 * Addon Controller is used to help installation of your add-on. If your
 * add-on needs to perform some specific actions during the installation,
 * such as creating a symling for asset access or.
 */
class Controller_Addon extends AbstractController
{
    /** @var string */
    public $atk_version = '4.3';

    /** @var string */
    public $namespace = __NAMESPACE__;

    /** @var string|object|array */
    public $addon_base_path = null; // should be only string, but $app->locate can return object and array too

    /** @var bool */
    public $has_assets = false;

    /** @var string */
    public $addon_name;

    // object with information from json file
    public $addon_obj;

    /** @var array */
    public $addon_private_locations = array();

    /** @var array */
    public $addon_public_locations = array();

    /** @var bool */
    public $with_pages = false;

    /** @var PathFinder_Location */
    public $location;

    public $api_var; // ???

    public $base_path; // ???

    public function init()
    {
        parent::init();
        $this->app->requires('atk', $this->atk_version);

        if (!$this->addon_name) {
            throw $this->exception('Addon name must be specified in it\'s Controller');
        }

        $this->namespace = substr(get_class($this), 0, strrpos(get_class($this), '\\'));

        $this->addon_base_path = $this->app->locatePath('addons', $this->namespace);

        if (count($this->addon_private_locations) || count($this->addon_public_locations)) {
            $this->addAddonLocations($this->base_path);
        }
    }

    /**
     * This routes certain prefixes to an add-on. Call this method explicitly
     * from init() if necessary.
     */
    public function routePages($page_prefix)
    {
        if ($this->app instanceof App_Frontend) {
            /** @type App_Frontend $this->app */
            $this->app->routePages($page_prefix, $this->namespace);
        }
    }

    public function addAddonLocations($base_path)
    {
        $this->app->pathfinder->addLocation($this->addon_private_locations)
                ->setBasePath($base_path.'/../'.$this->addon_obj->get('addon_full_path'));

        $this->app->pathfinder->addLocation($this->addon_public_locations)
                ->setBasePath($base_path.'/'.$this->addon_obj->get('addon_public_symlink'))
                ->setBaseURL($this->app->url('/').$this->addon_obj->get('addon_symlink_name'));
    }

    /**
     * This defines the location data for the add-on. Call this method
     * explicitly from init() if necessary.
     */
    public function addLocation($contents, $public_contents = null)
    {
        $this->location = $this->app->pathfinder->addLocation($contents);
        $this->location->setBasePath($this->addon_base_path);

        // If class has assets, those have probably been installed
        // into the public location
        // TODO: test
        if ($this->has_assets) {
            if (is_null($public_contents)) {
                $public_contents = array(
                    'public' => '.',
                    'js' => 'js',
                    'css' => 'css',
                );
            }

            $this->location = $this->app->pathfinder->public_location
                ->addRelativeLocation($this->addon_base_path, $contents);
        }

        return $this->location;
    }

    /**
     * This method will rely on location data to link.
     */
    public function installAssets()
    {

        // Creates symlink inside /public/my-addon/ to /vendor/my/addon/public

        // TODO: if $this->namespace contains slash, then created
        // this folder under $app->pathfinder->public_location
        //
        // TODO: create a symlink such as $this->namespace pointing
        // to
        //
        // TODO: if this already exist, don't mess it up. Also, resolve
    }

    /**
     * Addon may requrie user to have license for ATK or some other
     * piece of software to function properly. This is will be called
     * during installation and then later on ocassionally, but not
     * on production environment.
     *
     * Agile Toolkit provides universal way for checking licenses. If
     * you are building commercial product with Agile Toolkit, then
     * you need to use unique identifier for $software. Provide the name
     * of your public key certificate and also supply md5sum of that
     * certificate as last parameter, to make sure developer wouldn't
     * simply substitute public key with another one.
     *
     * Public key must be bundled along with the release of your software
     *
     * This method will return true / false. If you have multiple public
     * keys (expired ones), you can call this method several times.
     *
     * The information about the private key specifically issued to the
     * user will be stored in configuration file.
     */
    public function licenseCheck($type, $software = 'atk', $pubkey = null, $pubkey_md5 = null)
    {
        // TODO: move stuff here from App_Web -> licenseCheck
        //
        // TODO: we might need to hardcode hey signature or MD
    }

    public function installDatabase()
    {
        // TODO: If add-on comes with some database requirement, then this
        // method should execute the migrations which will install and/or
        // upgrade the database.
    }

    public function checkConfiguration()
    {
        // Addon may requrie user to add some stuff into configuration file.
        //
        // This method must return 'true' or 'false' if some configuration
        // options are missing.
        //
        // This method must not complain about optional arguments. If you are
        // introducing a new configuration options in a new version of your
        // add-on, then you must always provide reasonable defaults.
        //
        // This method can still return false, while your defaults should
        // prevent application from crashing.
        //
        // Admin will redirect user to your add-on configuration page
        // if admin is logging in or at least provide some useful
        // information.
    }
}
