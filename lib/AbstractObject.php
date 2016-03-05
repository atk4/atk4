<?php
/**
 * A base class for all objects/classes in Agile Toolkit.
 * Do not directly inherit from this class, instead use one of
 * AbstractModel, AbstractController or AbstractView.
 */
abstract class AbstractObject
{
    const DOC = 'core-features/objects';

    /**
     * Reference to the current model. Read only. Use setModel()
     *
     * @var Model
     */
    public $model;

    /**
     * Reference to the current controller. Read only. Use setController()
     *
     * @var Controller
     */
    public $controller;

    /**
     * Exception class to use when $this->exception() is called. When
     * you later call ->exception() method, you can either override
     * or postfix your exception with second argument.
     *
     *
     *  $default_exception='PathFinder' by default would
     *  return 'Exception_PathFinder' from exception().
     *
     *  $default_exceptoin='PathFinder' in combination with
     *  ->exception('Blah','_NotFound') will return
     *  'Exception_PathFinder_NotFound'
     *
     *  $default_exception='BaseException' in combination with
     *  ->exception('Blah', 'PathFinder')   will create
     *  'Exception_PathFinder' exception.
     *
     *  and finally
     *
     *  $default_exception='PathFinder' in combination with
     *  ->exception('Blah','NotFound') will return
     *  'Exception_NotFound';
     *
     * @todo implement test-cases for the above
     *
     * @var string
     */
    public $default_exception = 'BaseException';

    /**
     * Default controller to initialize when calling setModel()
     *
     * @var string
     */
    public $default_controller = null;

    /**
     * Setting this to true will output additional debug info about object
     *
     * @var boolean
     */
    public $debug = null;

    // {{{ Object hierarchy management

    /**
     * Unique object name
     *
     * @var string
     */
    public $name;

    /**
     * Name of the object in owner's element array
     *
     * @var string
     */
    public $short_name;

    /**
     * short_name => object hash of children objects
     *
     * @var array
     */
    public $elements = array();

    /**
     * Link to object into which we added this object
     *
     * @var AbstractObject
     */
    public $owner;

    /**
     * Always points to current Application
     *
     * @var App_CLI
     */
    public $app;

    /**
     * @deprecated 4.3.0 Left for compatibility with ATK 4.2 and lower, use ->app instead
     */
    public $api;

    /**
     * When this object is added, owner->elements[$this->short_name]
     * will be == $this;.
     *
     * @var boolean
     */
    public $auto_track_element = false;

    /**
     * To make sure you have called parent::init() properly.
     *
     * @var boolean
     */
    public $_initialized = false;



    /**
     * Initialize object. Always call parent::init(). Do not call directly.
     */
    public function init()
    {
        /*
         * This method is called for initialization
         */
        $this->_initialized = true;
    }

    /**
     * This is default constructor of ATK4. Please do not re-define it
     * and avoid calling it directly. Always use add() and init() methods.
     *
     * @param array $options will initialize class properties
     */
    public function __construct($options = array())
    {
        foreach ($options as $key => $val) {
            if ($key !== 'name') {
                $this->$key = $val;
            }
        }
    }

    /* \section Object Management Methods */
    /**
     * Clones associated controller and model. If cloning views, add them to
     * the owner.
     *
     * @return AbstractObject Copy of this object
     */
    public function __clone()
    {
        if ($this->model && is_object($this->model)) {
            $this->model = clone $this->model;
        }
        if ($this->controller && is_object($this->controller)) {
            $this->controller = clone $this->controller;
        }
    }

    /**
     * Converts into string "Object View(myapp_page_view)".
     *
     * @return string
     */
    public function __toString()
    {
        return 'Object '.get_class($this).'('.$this->name.')';
    }

    /**
     * Removes object from parent and prevents it from rendering
     * \code
     * $view = $this->add('View');
     * $view -> destroy();
     * \endcode.
     */
    public function destroy($recursive = true)
    {
        if ($recursive) {
            foreach ($this->elements as $el) {
                if ($el instanceof self) {
                    $el->destroy();
                }
            }
        }
        /*
        if (@$this->model && $this->model instanceof AbstractObject) {
            $this->model->destroy();
            unset($this->model);
        }
        if (@$this->controller && $this->controller instanceof AbstractObject) {
            $this->controller->destroy();
            unset($this->controller);
        }
        */
        $this->owner->_removeElement($this->short_name);
    }

    /**
     * Remove child element if it exists.
     *
     * @param string $short_name short name of the element
     *
     * @return $this
     */
    public function removeElement($short_name)
    {
        if (is_object($this->elements[$short_name])) {
            $this->elements[$short_name]->destroy();
        } else {
            unset($this->elements[$short_name]);
        }

        return $this;
    }

    /**
     * Actually removes the element.
     *
     * @param string $short_name short name
     *
     * @return $this
     * @access private
     */
    public function _removeElement($short_name)
    {
        unset($this->elements[$short_name]);
        if ($this->_element_name_counts[$short_name] === 1) {
            unset($this->_element_name_counts[$short_name]);
        }

        return $this;
    }

    /**
     * Creates one more instance of $this object.
     *
     * @param array $properties Set initial properties for new object
     *
     * @return self
     */
    public function newInstance($properties = null)
    {
        return $this->owner->add(get_class($this), $properties);
    }

    /**
     * Creates new object and adds it as a child of current object.
     * Returns new object.
     *
     * @param array|string|object $class    Name of the new class. Can also be array with 0=>name and
     *                                      rest of array will be considered as $options or object.
     * @param array|string $options         Short name or array of properties.
     *                                      0=>name will be used as a short-name or your object.
     * @param string       $template_spot   Tag where output will appear
     * @param array|string $template_branch Redefine template
     *
     * @link http://agiletoolkit.org/learn/understand/base/adding
     *
     * @return AbstractObject
     */
    public function add(
        $class,
        $options = null,
        $template_spot = null,
        $template_branch = null
    ) {
        if (is_array($class)) {
            if (!$class[0]) {
                throw $this->exception('When passing class as array, use ["Class", "option"=>123] format')
                    ->addMoreInfo('class', var_export($class, true));
            }
            $o = $class;
            $class = $o[0];
            unset($o[0]);
            $options = $options ? array_merge($options, $o) : $o;
        }

        if (is_string($options)) {
            $options = array('name' => $options);
        }
        if (!is_array($options)) {
            $options = array();
        }

        if (is_object($class)) {
            // Object specified, just add the object, do not create anything
            if (!($class instanceof self)) {
                throw $this->exception(
                    'You may only add objects based on AbstractObject'
                );
            }
            if (!$class->short_name) {
                $class->short_name = str_replace('\\', '_', strtolower(get_class($class)));
            }
            if (!$class->app) {
                $class->api = // compatibility with ATK 4.2 and lower
                    $class->app = $this->app;
            }
            $class->short_name = $this->_unique_element($class->short_name);
            $class->name = $this->_shorten($this->name.'_'.$class->short_name);

            $this->elements[$class->short_name] = $class;
            if ($class instanceof AbstractView) {
                $class->owner->elements[$class->short_name] = true;
            }
            $class->owner = $this;
            if ($class instanceof AbstractView && !$this->template) {
                $class->initializeTemplate($template_spot, $template_branch);
            }

            return $class;
        }

        if (!is_string($class) || !$class) {
            throw $this->exception('Class is not valid')
                ->addMoreInfo('class', $class);
        }

        $class = str_replace('/', '\\', $class);

        if ($class[0] == '.') {
            // Relative class name specified, extract current namespace
            // and make new class name relative to this namespace
            $ns = get_class($this);
            $ns = substr($ns, 0, strrpos($ns, '\\'));
            $class = $ns.'\\'.substr($class, 2);
        }

        $short_name = isset($options['name'])
            ? $options['name']
            : str_replace('\\', '_', strtolower($class));

        // Adding same controller twice will return existing one
        if (isset($this->elements[$short_name])) {
            if ($this->elements[$short_name] instanceof AbstractController) {
                return $this->elements[$short_name];
            }
        }

        $short_name = $this->_unique_element($short_name);

        if (isset($this->elements[$short_name])) {
            throw $this->exception($class.' with requested name already exists')
                ->addMoreInfo('class', $class)
                ->addMoreInfo('new_short_name', $short_name)
                ->addMoreInfo('object', $this)
                ->addMoreInfo('counts', json_encode($this->_element_name_counts))
                ->addThis($this);
        }

        $class_name_nodash = str_replace('-', '', $class);
        /*
         * Even though this might break some applications,
         * your loading must be configured properly instead
         * of relying on this
         *
        if (!class_exists($class_name_nodash, false)
            && isset($this->app->pathfinder)
        ) {
            $this->app->pathfinder->loadClass($class);
        }*/
        $element = new $class_name_nodash($options);

        if (!($element instanceof self)) {
            throw $this->exception(
                'You can add only classes based on AbstractObject'
            );
        }

        $element->owner = $this;
        $element->api = // compatibility with ATK 4.2 and lower
            $element->app = $this->app;
        $element->name = $this->_shorten($this->name.'_'.$short_name);
        $element->short_name = $short_name;

        if (!$element->auto_track_element) {
            // dont store extra reference to models and controlers
            // for purposes of better garbage collection
            $this->elements[$short_name] = true;
        } else {
            $this->elements[$short_name] = $element;
        }

        // Initialize template before init() starts
        if ($element instanceof AbstractView) {
            $element->initializeTemplate($template_spot, $template_branch);
        }

        // Avoid using this hook. Agile Toolkit creates LOTS of objects,
        // so you'll get significantly slower code if you try to use this
        $this->app->hook('beforeObjectInit', array(&$element));

        // Initialize element
        $element->init();

        // Make sure init()'s parent was called. It's a popular coder's mistake.
        if (!$element->_initialized) {
            throw $element->exception(
                'You should call parent::init() when you override it'
            )
                ->addMoreInfo('object_name', $element->name)
                ->addMoreInfo('class', get_class($element));
        }

        // Great hook to affect children recursively
        $this->hook('afterAdd', array($element));

        return $element;
    }

    /**
     * Find child element by its short name. Use in chaining.
     * Exception if not found.
     *
     * @param string $short_name Short name of the child element
     *
     * @return AbstractObject
     */
    public function getElement($short_name)
    {
        if (!isset($this->elements[$short_name])) {
            throw $this->exception('Child element not found')
                ->addMoreInfo('element', $short_name);
        }

        return $this->elements[$short_name];
    }

    /**
     * Find child element. Use in condition.
     *
     * @param string $short_name Short name of the child element
     *
     * @return AbstractObject|bool
     */
    public function hasElement($short_name)
    {
        return isset($this->elements[$short_name])
            ? $this->elements[$short_name]
            : false;
    }

    /**
     * Names object accordingly. May not work on some objects.
     *
     * @param string $short_name Short name of the child element
     *
     * @return $this
     */
    public function rename($short_name)
    {
        unset($this->owner->elements[$this->short_name]);
        $this->name = $this->name.'_'.$short_name;
        $this->short_name = $short_name;

        if (!$this->auto_track_element) {
            $this->owner->elements[$short_name] = true;
        } else {
            $this->owner->elements[$short_name] = $this;
        }

        return $this;
    }
    // }}}

    // {{{ Model and Controller handling
    /**
     * Associate controller with the object.
     *
     * @param string|object $controller Class or instance of controller
     * @param string|array  $name       Name or property for new controller
     *
     * @return AbstractController Newly added controller
     */
    public function setController($controller, $name = null)
    {
        $controller = $this->app->normalizeClassName($controller, 'Controller');

        return $this->add($controller, $name);
    }

    /**
     * Associate model with object.
     *
     * @param string|object $model Class or instance of model
     *
     * @return AbstractModel Newly added Model
     */
    public function setModel($model)
    {
        $model = $this->app->normalizeClassName($model, 'Model');
        $this->model = $this->add($model);

        return $this->model;
    }

    /**
     * Return current model.
     *
     * @return AbstractModel Currently associated model object
     */
    public function getModel()
    {
        return $this->model;
    }
    // }}}

    // {{{ Session management: http://agiletoolkit.org/doc/session
    /**
     * Remember data in object-relevant session data.
     *
     * @param string $key   Key for the data
     * @param mixed  $value Value
     *
     * @return mixed $value
     */
    public function memorize($key, $value)
    {
        if (!session_id()) {
            $this->app->initializeSession();
        }

        if ($value instanceof Model) {
            unset($_SESSION['o'][$this->name][$key]);
            $_SESSION['s'][$this->name][$key] = serialize($value);

            return $value;
        }

        unset($_SESSION['s'][$this->name][$key]);
        $_SESSION['o'][$this->name][$key] = $value;

        return $value;
    }

    /**
     * Similar to memorize, but if value for key exist, will return it.
     *
     * @param string $key     Data Key
     * @param mixed  $default Default value
     *
     * @return mixed Previously memorized data or $default
     */
    public function learn($key, $default = null)
    {
        if (!session_id()) {
            $this->app->initializeSession(false);
        }

        if (!isset($_SESSION['o'][$this->name][$key])
            || is_null($_SESSION['o'][$this->name][$key])
        ) {
            if (is_callable($default)) {
                $default = call_user_func($default);
            }

            return $this->memorize($key, $default);
        } else {
            return $this->recall($key);
        }
    }

    /**
     * Forget session data for arg $key. If $key is omitted will forget all
     * associated session data.
     *
     * @param string $key Optional key of data to forget
     *
     * @return $this
     */
    public function forget($key = null)
    {
        if (!session_id()) {
            $this->app->initializeSession(false);
        }

        // Prevent notice generation when using custom session handler
        if (!isset($_SESSION)) {
            return $this;
        }

        if (is_null($key)) {
            unset($_SESSION['o'][$this->name]);
            unset($_SESSION['s'][$this->name]);
        } else {
            unset($_SESSION['o'][$this->name][$key]);
            unset($_SESSION['s'][$this->name][$key]);
        }

        return $this;
    }

    /**
     * Returns session data for this object. If not previously set, then
     * $default is returned.
     *
     * @param string $key     Data Key
     * @param mixed  $default Default value
     *
     * @return mixed Previously memorized data or $default
     */
    public function recall($key, $default = null)
    {
        if (!session_id()) {
            $this->app->initializeSession(false);
        }

        if (!isset($_SESSION['o'][$this->name][$key])
            || is_null($_SESSION['o'][$this->name][$key])
        ) {
            if (!isset($_SESSION['s'][$this->name][$key])) {
                return $default;
            }
            $v = $this->add(unserialize($_SESSION['s'][$this->name][$key]));
            $v->init();

            return $v;
        }

        return $_SESSION['o'][$this->name][$key];
    }
    // }}}

    // {{{ Exception handling: http://agiletoolkit.org/doc/exception
    /**
     * Returns relevant exception class. Use this method with "throw".
     *
     * @param string $message Static text of exception.
     * @param string $type    Exception class or class postfix
     * @param string $code    Optional error code
     *
     * @return BaseException
     */
    public function exception($message = 'Undefined Exception', $type = null, $code = null)
    {
        if ($type === null) {
            $type = $this->default_exception;
        } elseif ($type[0] == '_') {
            if ($this->default_exception == 'BaseException') {
                $type = 'Exception_'.substr($type, 1);
            } else {
                $type = $this->default_exception.'_'.substr($type, 1);
            }
        } elseif ($type != 'BaseException') {
            $type = $this->app->normalizeClassName($type, 'Exception');
        }

        // Localization support
        $message = $this->app->_($message);

        if ($type == 'Exception') {
            $type = 'BaseException';
        }

        $e = new $type($message, $code);
        if (!($e instanceof BaseException)) {
            throw $e;
        }
        $e->owner = $this;
        $e->app = $this->app;
        $e->api = $e->app; // compatibility with ATK 4.2 and lower
        $e->init();

        return $e;
    }
    // }}}

    // {{{ Code which can be potentially obsoleted.
    /**
     * Reports fatal error. Use ->exception instead.
     *
     * @param string $error error text
     * @param int    $shift relative offset in backtrace
     *
     * @obsolete
     */
    public function fatal($error, $shift = 0)
    {
        return $this->upCall(
            'outputFatal',
            array(
                $error,
                $shift,
            )
        );
    }

    /**
     * Records debug information.
     *
     * @param string $msg information
     *
     * @obsolete
     */
    public $_info = array();
    public function info($msg)
    {
        /*
         * Call this function to send some information to Application. Example:
         *
         * $this->info("User tried buying traffic without enough money in bank");
         */
        $args = func_get_args();
        array_shift($args);
        $this->_info[] = vsprintf($msg, $args);
    }

    /**
     * Turns on debug mode for this object. Using first argument as string
     * is obsolete.
     *
     * @param bool|string $msg  "true" to start debugging
     * @param string      $file obsolete
     * @param string      $line obsolete
     */
    public function debug($msg = true, $file = null, $line = null)
    {
        if (is_bool($msg)) {
            $this->debug = $msg;

            return $this;
        }

        if (is_object($msg)) {
            throw $this->exception('Do not debug objects');
        }

        // The rest of this method is obsolete
        if ((isset($this->debug) && $this->debug)
            || (isset($this->app->debug) && $this->app->debug)
        ) {
            $this->app->outputDebug($this, $msg, $file, $line);
        }
    }

    /**
     * Records warning.
     *
     * @param string $msg   information
     * @param int    $shift relative offset in backtrace
     *
     * @obsolete
     */
    public function warning($msg, $shift = 0)
    {
        $this->upCall(
            'outputWarning',
            array(
                $msg,
                $shift,
            )
        );
    }

    /**
     * Call specified method for this class and all parents up to app.
     *
     * @param string $type information
     * @param array  $args relative offset in backtrace
     *
     * @obsolete
     */
    public function upCall($type, $args = array())
    {
        /*
         * Try to handle something on our own and in case we are not able,
         * pass to parent. Such as messages, notifications and request for
         * additional info or descriptions are passed this way.
         */
        if (method_exists($this, $type)) {
            return call_user_func_array(
                array(
                    $this,
                    $type,
                ),
                $args
            );
        }
        if (!$this->owner) {
            return false;
        }

        return $this->owner->upCall($type, $args);
    }
    // }}}

    // {{{ Hooks: http://agiletoolkit.org/doc/hooks
    public $hooks = array();

    /**
     * If priority is negative, then hooks will be executed in reverse order.
     *
     * @param string                  $hook_spot Hook identifier to bind on
     * @param AbstractObject|callable $callable  Will be called on hook()
     * @param array                   $arguments Arguments are passed to $callable
     * @param int                     $priority  Lower priority is called sooner
     *
     * @return $this
     */
    public function addHook($hook_spot, $callable, $arguments = array(), $priority = 5)
    {
        if (!is_array($arguments)) {
            throw $this->exception('Incorrect arguments');
        }
        if (is_string($hook_spot) && strpos($hook_spot, ',') !== false) {
            $hook_spot = explode(',', $hook_spot);
        }
        if (is_array($hook_spot)) {
            foreach ($hook_spot as $h) {
                $this->addHook($h, $callable, $arguments, $priority);
            }

            return $this;
        }
        if (!is_callable($callable)
            && ($callable instanceof self
            && !$callable->hasMethod($hook_spot))
        ) {
            throw $this->exception('Hook does not exist');
        }
        if (is_object($callable) && !is_callable($callable)) {
            $callable = array($callable, $hook_spot);
            // short for addHook('test', $this); to call $this->test();
        }

        $this->hooks[$hook_spot][$priority][] = array($callable, $arguments);

        return $this;
    }

    /**
     * Delete all hooks for specified spot.
     *
     * @param string $hook_spot Hook identifier to bind on
     *
     * @return $this
     */
    public function removeHook($hook_spot)
    {
        unset($this->hooks[$hook_spot]);

        return $this;
    }

    /**
     * Execute all callables assigned to $hook_spot.
     *
     * @param string $hook_spot Hook identifier
     * @param array  $arg       Additional arguments to callables
     *
     * @return mixed Array of responses or value specified to breakHook
     */
    public function hook($hook_spot, $arg = array())
    {
        if (!is_array($arg)) {
            throw $this->exception(
                'Incorrect arguments, or hook does not exist'
            );
        }
        $return = array();
        if ($arg === UNDEFINED) {
            $arg = array();
        }

        try {
            if (isset($this->hooks[$hook_spot])) {
                if (is_array($this->hooks[$hook_spot])) {
                    krsort($this->hooks[$hook_spot]); // lower priority is called sooner
                    $hook_backup = $this->hooks[$hook_spot];
                    while ($_data = array_pop($this->hooks[$hook_spot])) {
                        foreach ($_data as $prio => &$data) {

                            // Our extension
                            if (is_string($data[0])
                                && !preg_match(
                                    '/^[a-zA-Z_][a-zA-Z0-9_]*$/',
                                    $data[0]
                                )
                            ) {
                                $result = eval($data[0]);
                            } elseif (is_callable($data[0])) {
                                $result = call_user_func_array(
                                    $data[0],
                                    array_merge(
                                        array($this),
                                        $arg,
                                        $data[1]
                                    )
                                );
                            } else {
                                if (!is_array($data[0])) {
                                    $data[0] = array(
                                        'STATIC',
                                        $data[0],
                                    );
                                }
                                throw $this->exception(
                                    'Cannot call hook. Function might not exist.'
                                )
                                    ->addMoreInfo('hook', $hook_spot)
                                    ->addMoreInfo('arg1', $data[0][0])
                                    ->addMoreInfo('arg2', $data[0][1]);
                            }
                            $return[] = $result;
                        }
                    }

                    $this->hooks[$hook_spot] = $hook_backup;
                }
            }
        } catch (Exception_Hook $e) {
            $this->hooks[$hook_spot] = $hook_backup;

            return $e->return_value;
        }

        return $return;
    }

    /**
     * When called from inside a hook callable, will stop execution of other
     * callables on same hook. The passed argument will be returned by the
     * hook method.
     *
     * @param mixed $return What would hook() return?
     */
    public function breakHook($return)
    {
        $e = $this->exception(null, 'Hook');
        $e->return_value = $return;
        throw $e;
    }
    // }}}

    // {{{ Dynamic Methods: http://agiletoolkit.org/learn/dynamic
    /**
     * Call method is used to display exception for non-existant methods and
     * provides ability to extend objects with addMethod().
     *
     * @param string $method    Name of the method
     * @param array  $arguments Arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        if (($ret = $this->tryCall($method, $arguments))) {
            return $ret[0];
        }
        throw $this->exception(
            'Method is not defined for this object',
            'Logic'
        )
            ->addMoreInfo('class', get_class($this))
            ->addMoreInfo('method', $method)
            ->addMoreInfo('arguments', var_export($arguments, true));
    }

    /**
     * Attempts to call dynamic method. Returns array containing result or false.
     *
     * @param string $method    Name of the method
     * @param array  $arguments Arguments
     *
     * @return mixed
     */
    public function tryCall($method, $arguments)
    {
        if ($ret = $this->hook('method-'.$method, $arguments)) {
            return $ret;
        }
        array_unshift($arguments, $this);
        if (($ret = $this->app->hook('global-method-'.$method, $arguments))) {
            return $ret;
        }
    }

    /**
     * Add new method for this object.
     *
     * @param string|array $name     Name of new method of $this object
     * @param callable     $callable Callback
     *
     * @return $this
     */
    public function addMethod($name, $callable)
    {
        if (is_string($name) && strpos($name, ',') !== false) {
            $name = explode(',', $name);
        }
        if (is_array($name)) {
            foreach ($name as $h) {
                $this->addMethod($h, $callable);
            }

            return $this;
        }
        if (is_object($callable) && !is_callable($callable)) {
            $callable = array($callable, $name);
        }
        if ($this->hasMethod($name)) {
            throw $this->exception('Registering method twice');
        }
        $this->addHook('method-'.$name, $callable);

        return $this;
    }

    /**
     * Return if this object has specified method (either native or dynamic).
     *
     * @param string $name Name of the method
     *
     * @return bool
     */
    public function hasMethod($name)
    {
        return method_exists($this, $name)
            || isset($this->hooks['method-'.$name])
            || isset($this->app->hooks['global-method-'.$name]);
    }

    /**
     * Remove dynamically registered method.
     *
     * @param string $name Name of the method
     *
     * @return $this
     */
    public function removeMethod($name)
    {
        $this->removeHook('method-'.$name);

        return $this;
    }
    // }}}

    // {{{ Logger: to be moved out
    /**
     * Output string into log file.
     *
     * @param string $var var
     * @param string $msg msg
     *
     * @obsolete
     */
    public function logVar($var, $msg = '')
    {
        $this->app->getLogger()->logVar($var, $msg);
    }

    /**
     * Output string into info file.
     *
     * @param string $info info
     * @param string $msg  msg
     *
     * @obsolete
     */
    public function logInfo($info, $msg = '')
    {
        $this->app->getLogger()->logLine($msg.' '.$info."\n");
    }

    /**
     * Output string into error file.
     *
     * @param string $error error
     * @param string $msg   msg
     *
     * @obsolete
     */
    public function logError($error, $msg = '')
    {
        if (is_object($error)) {
            // we got exception object obviously
            $error = $error->getMessage();
        }
        $this->app->getLogger()->logLine($msg.' '.$error."\n", null, 'error');
    }
    // }}}

    /**
     * A handy shortcut for foreach(){ .. } code. Make your callable return
     * "false" if you would like to break the loop.
     *
     * @param string|callable $callable will be executed for each member
     *
     * @return $this
     */
    public function each($callable)
    {
        if ($this instanceof Iterator) {

            if (is_string($callable)) {
                foreach ($this as $obj) {
                    $obj->$callable();
                }

                return $this;
            }

            foreach ($this as $obj) {
                if (call_user_func($callable, $obj) === false) {
                    break;
                }
            }

        } else {
            throw $this->exception('Calling each() on non-iterative object');
        }

        return $this;
    }

    /**
     * This method will find private methods started with test_ in
     * the current class and will execute each method in succession
     * by passing $t argument to it. Before each test execution
     * takes place, $t->prepareForTest($test) will be called. It must
     * return non-false for test to be carried out.
     *
     * $test will be an array containing keys for 'name', 'object' and
     * 'class'
     */
    public function runTests(Tester $tester = null)
    {
        $test = array('object' => $this->name, 'class' => get_class($this));

        foreach (get_class_methods($this) as $method) {
            if (strpos($method, 'test_') === 0) {
                $test['name'] = substr($method, 5);
            } else {
                continue;
            }

            if ($tester && $tester->prepareForTest($test) === false) {
                continue;
            }

            if ($tester) {
                /** @var Model */
                $r = $tester->results;
                $r->unload();
                $r->set($test);
            }

            // Proceed with test
            $me = memory_get_peak_usage();
            $ms = microtime(true);
            $this->_ticks = 0;
            declare (ticks = 1);
            register_tick_function(array($this, '_ticker'));

            // Execute here
            try {
                $result = $this->$method($tester);
            } catch (Exception $e) {
                unregister_tick_function(array($this, '_ticker'));
                $time = microtime(true) - $ms;
                $memory = (memory_get_peak_usage()) - $me;
                $ticks = $this->_ticks;

                if ($e instanceof Exception_SkipTests) {
                    if ($tester) {
                        $r['exception'] = 'SKIPPED';
                        $r->saveAndUnload();
                    }

                    return array(
                        'skipped' => $e->getMessage(),
                        );
                }

                if ($tester) {
                    $r['time'] = $time;
                    $r['memory'] = $memory;
                    $r['ticks'] = $ticks;
                    $r['exception'] = $e;
                    $r->saveAndUnload();
                }

                continue;
            }

            // Unregister
            unregister_tick_function(array($this, '_ticker'));
            $time = microtime(true) - $ms;
            $memory = (memory_get_peak_usage()) - $me;
            $ticks = $this->_ticks - 3;     // there are always minimum of 3 ticks

            if ($tester) {
                $r['time'] = $time;
                $r['memory'] = $memory;
                $r['ticks'] = $ticks;
                $r['is_success'] = true;
                $r['result'] = $result;
                $r->saveAndUnload();
            }
        }
    }
    private $_ticks;
    public function _ticker()
    {
        ++$this->_ticks;
    }

    /**
     * Method used internally for shortening object names.
     *
     * @param string $desired Desired name of new object.
     *
     * @return string Shortened name of new object.
     */
    public function _shorten($desired)
    {
        if (strlen($desired) > $this->app->max_name_length
            && $this->app->max_name_length !== false
        ) {
            $len = $this->app->max_name_length - 10;
            if ($len < 5) {
                $len = $this->app->max_name_length;
            }

            $key = substr($desired, 0, $len);
            $rest = substr($desired, $len);

            if (!$this->app->unique_hashes[$key]) {
                $this->app->unique_hashes[$key] = dechex(crc32($key));
            }
            $desired = $this->app->unique_hashes[$key].'__'.$rest;
        };

        return $desired;
    }

    private $_element_name_counts = array();
    public function _unique_element($desired = null)
    {
        $postfix = @++$this->_element_name_counts[$desired];

        return $desired.($postfix > 1 ? ('_'.$postfix) : '');
    }

    /**
     * This funcion given the associative $array and desired new key will return
     * the best matching key which is not yet in the array.
     * For example, if you have array('foo'=>x,'bar'=>x) and $desired is 'foo'
     * function will return 'foo_2'. If 'foo_2' key also exists in that array,
     * then 'foo_3' is returned and so on.
     *
     * @param array  &$array  Reference to array which stores key=>value pairs
     * @param string $desired Desired key for new object
     *
     * @return string unique key for new object
     */
    public function _unique(&$array, $desired = null)
    {
        if (!is_array($array)) {
            throw $this->exception('not array');
        }
        $postfix = count($array);
        $attempted_key = $desired;
        while (array_key_exists($attempted_key, $array)) {
            // already used, move on
            $attempted_key = ($desired ?: 'undef').'_'.(++$postfix);
        }

        return $attempted_key;
    }

    /**
     * Always call parent if you redefine this/.
     */
    public function __destruct()
    {
    }

    /**
     * Do not serialize objects.
     *
     * @return mixed
     */
    public function __sleep()
    {
        return array('name');
    }
}
