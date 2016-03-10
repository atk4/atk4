<?php
/**
 * A basic authentication class. Include inside your APP or
 * on a page. You may have multiple Auth instances. Supports
 * 3rd party plugins.
 *
 * Use:
 *
 * $auth=$this->add('Auth');
 * $auth->usePasswordEncryption();
 * $auth->setModel('User');
 * $auth->check();
 *
 * Auth accessible from anywhere through $this->app->auth;
 *
 * Auth has several extensions, enable them like this:
 *
 * $auth->add('auth/Controller_DummyPopup');        // allows you to pick user from list and bypass password
 * $auth->add('auth/Controller_Cookie');            // adds "remember me" checkbox
 *
 * See documentation on "auth" add-on for more information
 *  http://agiletoolkit.org/a/auth
 */
class Auth_Basic extends AbstractController
{
    /**
     * Info will contain data loaded about authenticated user.
     * This property can be accessed through $this->get() and should not be changed after authentication.
     *
     * @var array|bool
     */
    public $info = false;

    /**
     * This form is created when user is being asked about authentication.
     * If you are willing to change the way form looks, create it prior to calling check().
     * Your form must have compatible field names: "username" and "password".
     *
     * @var Form
     */
    public $form = null;

    /**
     * @var string|callable Which encryption to use. Few are built-in
     */
    protected $password_encryption = null;

    /**
     * @var array Array of allowed page names
     */
    protected $allowed_pages = array();

    /**
     * @var string Login field name in model
     */
    public $login_field = 'email';

    /**
     * @var string Password field name in model
     */
    public $password_field = 'password';

    /**
     * @var int Encyption algorithm
     */
    public $hash_algo = PASSWORD_DEFAULT;

    /**
     * @var array Encryption algorithm options
     */
    public $hash_options = array();

    /**
     * @var string Layout class
     */
    public $login_layout_class = 'Layout_Centered';

    /** @var App_Frontend */
    public $app;



    public function init()
    {
        parent::init();

        // Register as auth handler, if it's not set yet
        if (!isset($this->app->auth)) {
            $this->app->auth = $this;
        }

        if (!$this->app->hasMethod('initializeSession') && !session_id()) {
            // No session support
            return;
        }

        // Try to get information from the session. If user is authenticated, information will
        // be available there
        $this->info = (array) $this->recall('info', false);
    }

    /**
     * Configure this Auth controller with a generic Model based on static
     * collection of user/password combinations. Use this method if you
     * only want one or few accounts to access the system.
     *
     * @param string|array $user Either string username or associative array with data
     * @param string $pass Password if username is string
     *
     * @return $this
     */
    public function allow($user, $pass = null)
    {
        // creates fictional model to allow specified user and password
        // TODO: test this
        if ($this->model) {
            $this->model->table[] = array($this->login_field => $user, $this->password_field => $pass);

            return $this;
        }
        /** @type Model $m */
        $m = $this->add('Model');
        $m->setSource('Array', array(
                is_array($user)
                ? $user
                : array($this->login_field => $user, $this->password_field => $pass)
            ));
        $m->id_field = $this->login_field;
        $this->setModel($m);

        return $this;
    }
    /**
     * Associate model with authentication class. Username / password
     * check will be performed against the model in the following steps:
     * Model will attempt to load record where login_field matches
     * specified. Password is then loaded and verified using configured
     * encryption method.
     *
     * @param string|object $model
     * @param string $login_field
     * @param string $password_field
     *
     * @return Model
     */
    public function setModel($model, $login_field = 'email', $password_field = 'password')
    {
        parent::setModel($model);
        $this->login_field = $login_field;
        $this->password_field = $password_field;

        // Load model from session
        if ($this->info && $this->recall('id')) {
            if ($this->recall('class', false) == get_class($this->model)) {
                $this->debug('Loading model from cache');
                $this->model->set($this->info);
                $this->model->dirty = array();
                $this->model->id = $this->recall('id', null);
            } else {
                // Class changed, re-fetch data from database
                $this->debug('Class changed, loading from database');
                $this->model->tryLoad($this->recall('id'));
                if (!$this->model->loaded()) {
                    $this->logout();
                }

                $this->memorizeModel();
            }
        }

        $id = $this->hook('tryLogin', array($model, $login_field, $password_field));

        if ($id && is_numeric($id)) {
            $this->model->tryLoad($id);
            $this->memorizeModel();
        }

        $t = $this;

        // If model is saved, update our cache too, but don't store password
        $this->model->addHook('afterSave', function ($m) use ($t) {
            // after this model is saved, re-cache the info
            $tmp = $m->get();
            unset($tmp[$t->password_field]);
            if ($t->app instanceof App_Web) {
                $t->memorize('info', $tmp);
            }
        });

        $this->addEncryptionHook($this->model);

        if (strtolower($this->app->page) == 'logout') {
            $this->logout();
            $this->app->redirect('/');
        }

        return $this->model;
    }
    /**
     * Adds a hook to specified model which will encrypt password before save.
     * This method will be applied on $this->model, so you should not call
     * it manually. You can call it on a fresh model, however.
     *
     * @param Model $model
     */
    public function addEncryptionHook($model)
    {
        // If model is saved, encrypt password
        $t = $this;
        if (isset($model->has_encryption_hook) && $model->has_encryption_hook) {
            return;
        }
        $model->has_encryption_hook = true;
        $model->addHook('beforeSave', function ($m) use ($t) {
            if ($m->isDirty($t->password_field) && $m[$t->password_field]) {
                $m[$t->password_field] = $t->encryptPassword($m[$t->password_field], $m[$t->login_field]);
            }
        });
    }

    /**
     * Destroy object
     */
    public function destroy()
    {
        unset($this->app->auth);
        parent::destroy();
    }

    /**
     * Auth memorizes data about a logged-in user in session. You can either use this function to access
     * that data or $auth->model (preferred) $auth->get('username') will always point to the login field
     * value ofthe user regardless of how your field is named.
     *
     * @param string $property
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($property = null, $default = null)
    {
        if (is_null($property)) {
            return $this->info;
        }
        if (!isset($this->info[$property])) {
            return $default;
        }

        return $this->info[$property];
    }

    /**
     * Return array of all authenticated session info
     *
     * @return array
     */
    public function getAll()
    {
        return $this->info;
    }

    /**
     * Specify page or array of pages which will exclude authentication. Add your registration page here
     * or page containing terms and conditions.
     *
     * @param string|array $page
     *
     * @return $this
     */
    public function allowPage($page)
    {
        if (is_array($page)) {
            foreach ($page as $p) {
                $this->allowPage($p);
            }

            return $this;
        }
        $this->allowed_pages[] = $page;

        return $this;
    }

    /**
     * Return array of all allowed page names
     *
     * @return array
     */
    public function getAllowedPages()
    {
        return $this->allowed_pages;
    }

    /**
     * Verifies if the specified page is allowed to be accessed without
     * authentication.
     *
     * @param string $page
     *
     * @return bool
     */
    public function isPageAllowed($page)
    {
        if ($this->hook('isPageAllowed', array($page)) === true) {
            return true;
        }

        return in_array($page, $this->allowed_pages) || in_array(str_replace('_', '/', $page), $this->allowed_pages);
    }

    /**
     * Specifies how password will be encrypted when stored. It's recommended
     * that you do not specify encryption method, in which case a built-in
     * password_hash() will be used, which is defined by PHP.
     *
     * Some other values are "sha256/salt", "md5", "rot13". Note that if your
     * application is already using 'md5' or 'sha1', you can remove the
     * argument entirely and your user passwords will keep working and will
     * automatically be "upgraded" to password_hash when used.
     *
     * If you are having trouble with authentication, use auth->debug()
     *
     * @param string|callable $method
     *
     * @return $this
     */
    public function usePasswordEncryption($method = 'php')
    {
        $this->password_encryption = $method;

        return $this;
    }

    /**
     * Manually encrypt password
     *
     * @param string $password
     * @param string $salt
     *
     * @return string|bool Returns false on failure, encrypted string otherwise
     */
    public function encryptPassword($password, $salt = null)
    {
        if (!is_string($this->password_encryption) && is_callable($this->password_encryption)) {
            $e = $this->password_encryption;

            return $e($password, $salt);
        }
        if ($this->password_encryption) {
            $this->debug("Encrypting password: '$password' with ".$this->password_encryption.' salt='.$salt);
        }
        switch ($this->password_encryption) {
            case null:
                return $password;
            case 'php':
                // returns false on failure
                return password_hash($password, $this->hash_algo, $this->hash_options);
            case 'sha256/salt':
                if ($salt === null) {
                    throw $this->exception(
                        'sha256 requires salt (2nd argument to encryptPassword and is normaly an email)'
                    );
                }
                $key = $this->app->getConfig('auth/key', $this->app->name);
                if ($this->password_encryption) {
                    $this->debug('Using key '.$key);
                }

                return hash_hmac('sha256', $password.$salt, $key);
            case 'sha1':
                return sha1($password);
            case 'md5':
                return md5($password);
            case 'rot13':
                return str_rot13($password);
            default:
                throw $this->exception('No such encryption method')
                    ->addMoreInfo('encryption', $this->password_encryption);
        }
    }

    /**
     * Call this function to perform a check for logged in user. This will also display a login-form
     * and will verify user's credential. If you want to handle log-in form on your own, use
     * auth->isLoggedIn() to check and redirect user to a login page.
     *
     * check() returns true if user have just logged in and will return "null" for requests when user
     * continues to use his session. Use that to perform some calculation on log-in
     *
     * @return bool
     */
    public function check()
    {
        if ($this->isPageAllowed($this->app->page)) {
            return;
        }      // no authentication is required

        // Check if user's session contains autentication information
        if (!$this->isLoggedIn()) {
            $this->memorizeURL();

            // Brings up additional verification methods, such as cookie
            // authentication, token or OpenID. In case of successful login,
            // breakHook($user_id) must be used
            $user_id = $this->hook('check');
            if (!is_array($user_id) && !is_bool($user_id) && $user_id) {
                $this->model->load($user_id);
                //$this->loggedIn();
                $this->memorizeModel();

                return true;
            }

            /*
            $this->debug('User is not authenticated yet');

            // No information is present. Let's see if cookie is set
                }
            }else $this->debug("No permanent cookie");
             */
            $this->processLogin();

            return true;
        } else {
            $this->debug('User is already authenticated');
        }
    }

    /**
     * Add additional info to be stored in user session.
     *
     * @param string|array $key
     * @param mixed $val
     *
     * @return $this
     */
    public function addInfo($key, $val = null)
    {
        if (is_array($key)) {
            foreach ($key as $a => $b) {
                $this->addInfo($a, $b);
            }

            return $this;
        }

        $this->debug("Gathered info: $key=$val");
        $this->info[$key] = $val;

        return $this;
    }

    /**
     * This function determines - if user is already logged in or not. It does it by
     * looking at $this->info, which was loaded during init() from session.
     *
     * @return bool
     */
    public function isLoggedIn()
    {
        return $this->model->loaded();
    }

    /**
     * This function verifies credibility of supplied authenication data.
     * It will search based on user and verify the password. It's also
     * possible that the function will re-hash user password with
     * updated hash.
     *
     * if default authentication method is used, the function will
     * automatically determine hash used for password generation and will
     * upgrade to a new php5.5-compatible syntax.
     *
     * This function return false OR the id of the record matching user.
     *
     * @param string $user
     * @param string $password
     *
     * @return mixed
     */
    public function verifyCredentials($user, $password)
    {
        $this->debug('verifying credentials for '.$user.' '.$password);

        // First, perhaps model has a method for verifying credentials.
        if ($this->model->hasMethod('verifyCredentials')) {
            return $this->model->verifyCredentials($user, $password);
        }

        // If password field is not defined in the model for security
        // reasons, let's add it for authentication purpose.
        $password_existed = true;
        if (!$this->model->hasElement($this->password_field)) {
            $this->model->addField($this->password_field)->type('password');
            $password_existed = false;
        }

        // Attempt to load user data by username. If not found, return false
        /** @type Model $data User model */
        $data = $this->model->newInstance();

        $data->tryLoadBy($this->login_field, $user);
        if (!$data->loaded()) {
            $this->debug('user with login '.$user.' could not be loaded');
            if (!$password_existed) {
                $data->getElement($this->password_field)->destroy();
            }

            return false;
        }

        $hash = $data[$this->password_field];

        $this->debug('loaded user entry with hash: '.$hash);

        // Verify password first
        $result = false;
        $rehash = false;

        if ($this->password_encryption == 'php') {

            // Get information about the hash
            $info = password_get_info($hash);

            // Backwards-compatibility with older ATK encryption methods
            if ($info['algo'] == 0) {

                // Determine type of hash by length
                if (strlen($hash) == 64) {
                    $this->password_encryption = 'sha256/salt';
                } elseif (strlen($hash) == 32) {
                    $this->password_encryption = 'md5';
                } elseif (strlen($hash) == 40) {
                    $this->password_encryption = 'sha1';
                } else {
                    $this->password_encryption = null;
                    $this->debug('Unable to identify password hash type, using plain-text matching');
                    /*
                    $this->password_encryption='php';
                    $data->unload();
                    if (!$password_existed) {
                        $data->getElement($this->password_field)->destroy();
                    }
                    return false;
                     */
                }

                // Convert password hash
                $this->debug('Old password found with algo='.$this->password_encryption);
                $ep = $this->encryptPassword($password, $user);
                $this->password_encryption = 'php';

                $rehash = true;
                $result = $hash == $ep;
            } else {
                $result = password_verify($password, $ep = $data[$this->password_field]);
                $this->debug('Native password hash with info: '.json_encode($info));
                $rehash = password_needs_rehash(
                    $hash,
                    $this->hash_algo,
                    $this->hash_options
                );
            }

            if ($result) {
                $this->debug('Verify is a SUCCESS');

                if ($rehash) {
                    $hash = $data[$this->password_field] = $password;
                    $data->setDirty($this->password_field);
                    $data->save();
                    $this->debug('Rehashed into '.$data[$this->password_field]);
                }
            }
        } else {
            $ep = $this->encryptPassword($password, $user);
            $result = $hash == $ep;
            $this->debug('Attempting algo='.$this->password_encryption.' hash='.$hash.' newhash='.$ep);
        }

        if (!$result) {
            $this->debug('Verify is a FAIL');
            $data->unload();
            if (!$password_existed) {
                $data->getElement($this->password_field)->destroy();
            }

            return false;
        }

        // Leave record loaded, but hide password
        $data[$this->password_field] = '';
        $data->dirty[$this->password_field] = false;

        return $data[$this->model->id_field];

        /*
        if (!$password_existed) {
            $data->getElement($this->password_field)->destroy();
        }
        */
    }

    /**
     * Memorize current URL. Called when the first unsuccessful check is executed.
     */
    public function memorizeURL()
    {
        if ($this->app->page !== 'index' && !$this->recall('page', false)) {
            $this->memorize('page', $this->app->page);
            $g = $_GET;
            unset($g['page']);
            $this->memorize('args', $g);
        }
    }

    /**
     * Return originalally requested URL.
     *
     * @return string
     */
    public function getURL()
    {
        $p = $this->recall('page');

        // If there is a login page, no need to return to it
        if ($p == 'login') {
            return $this->app->url('/');
        }

        $url = $this->app->url($p, $this->recall('args', null));
        $this->forget('page');
        $this->forget('args');

        return $url;
    }

    /**
     * Rederect to page user tried to access before authentication was requested.
     */
    public function loginRedirect()
    {
        $this->debug('to Index');
        $this->app->redirect($this->getURL());
    }

    /**
     * This function is always executed after successfull login through a normal means (login form or plugin).
     *
     * It will create cache model data.
     *
     * @param string $user
     * @param string $pass
     */
    public function loggedIn($user = null, $pass = null)
    {
        //$username,$password,$memorize=false){
        $this->hook('loggedIn', array($user, $pass));
        $this->app->redirect($this->getURL());
    }

    /**
     * Store model in session data so that it can be retrieved faster.
     */
    public function memorizeModel()
    {
        if (!$this->model->loaded()) {
            throw $this->exception('Authentication failure', 'AccessDenied');
        }

        // Don't store password in model / memory / session
        $this->model['password'] = null;
        unset($this->model->dirty['password']);

        // Cache memory. Should we use controller?
        $this->info = $this->model->get();
        $this->info['username'] = $this->info[$this->login_field];

        if ($this->app->hasMethod('initializeSession') || session_id()) {
            $this->memorize('info', $this->info);
            $this->memorize('class', get_class($this->model));
            $this->memorize('id', $this->model->id);
        }

        $this->hook('login');
    }

    /**
     * Manually Log in as specified users. Will not perform password check or redirect.
     *
     * @param mixed $id
     *
     * @return $this
     */
    public function loginByID($id)
    {
        $this->model->load($id);
        $this->memorizeModel();

        return $this;
    }

    /**
     * Manually Log in with specified condition.
     *
     * @param string $field
     * @param mixed  $value
     *
     * @return $this
     */
    public function loginBy($field, $value)
    {
        $this->model->tryLoadBy($field, $value);
        $this->memorizeModel();

        return $this;
    }

    /**
     * Manually Log in as specified users by using login name.
     *
     * @param string $user
     *
     * @return $this
     */
    public function login($user)
    {
        if (is_object($user)) {
            if (!$this->model) {
                throw $this->exception('Auth Model should be set');
            }
            $c = get_class($this->model);

            if (!$user instanceof $c) {
                throw $this->exception('Specified model with incompatible class')
                ->addMoreInfo('required', $c)
                ->addMoreInfo('supplied', get_class($user));
            }

            $this->model = $user;
            $this->memorizeModel();

            return $this;
        }

        $this->model->tryLoadBy($this->login_field, $user);
        $this->memorizeModel();

        return $this;
    }

    /**
     * Manually log out user.
     *
     * @return $this
     */
    public function logout()
    {
        $this->hook('logout');

        $this->model->unload();

        // maybe can use $this->app->destroySession() here instead?
        $this->forget('info');
        $this->forget('id');

        setcookie(session_name(), '', time() - 42000, '/');
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $this->info = false;

        return $this;
    }

    /**
     * Creates log-in form.
     * Override if you want to use your own form. If you need to change template used by a log-in form,
     * add template/default/page/login.html.
     *
     * @param Page $page
     *
     * @return Form
     */
    public function createForm($page)
    {
        /** @type Form $form */
        $form = $page->add('Form', null, null, array('form/minimal'));

        /** @type Field $email */
        $email = $this->model->hasField($this->login_field);
        $email = $email ? $email->caption() : 'E-mail';

        /** @type Field $password */
        $password = $this->model->hasField($this->password_field);
        $password = $password ? $password->caption() : 'Password';

        $form->addField('Line', 'username', $email);
        $form->addField('Password', 'password', $password);
        $form->addSubmit('Login')->addClass('atk-jackscrew')->addClass('atk-swatch-green');

        //$form->add('View',null,'button_row_left')->addClass('atk-jackscrew');

        return $form;
    }

    /**
     * Do not override this function.
     *
     * @return Page
     */
    public function showLoginForm()
    {
        $this->app->template->trySet('page_title', 'Login');
        if ($this->app->layout && $this->login_layout_class) {
            $this->app->layout->destroy();
            $this->app->add($this->login_layout_class);
            /** @type Page $p */
            $p = $this->app->layout->add('Page', null, null, array('page/login'));
        } else {
            /** @type Page $p */
            $p = $this->app->add('Page', null, null, array('page/login'));
        }
        $this->app->page_object = $p;

        // hook: createForm use this to build basic login form
        $this->form = $this->hook('createForm', array($p));

        // If no hook, build standard form
        if (!is_object($this->form)) {
            $this->form = $this->createForm($p);
        }

        $this->hook('updateForm');
        $f = $this->form;
        if ($f->isSubmitted()) {
            $id = $this->verifyCredentials($f->get('username'), $f->get('password'));
            if ($id) {
                $this->loginByID($id);
                $this->loggedIn($f->get('username'), $f->get('password'));
                exit;
            }
            $f->getElement('password')->displayFieldError('Incorrect login information');
        }

        return $p;
    }

    /**
     * Do not override this function.
     */
    public function processLogin()
    {
        $this->memorizeURL();
        $this->app->template->tryDel('Menu');
        $this->showLoginForm();

        $this->app->hook('post-init');
        $this->app->hook('pre-exec');

        if (isset($_GET['submit']) && $_POST) {
            $this->app->hook('submitted');
        }

        $this->app->hook('post-submit');
        $this->app->execute();
        exit;
    }
}
