<?php // vim:ts=4:sw=4:et:fdm=marker
/**
 * A base class for all objects/classes in Agile Toolkit.
 * Do not directly inherit from this class, instead use one of
 * AbstractModel, AbstractController or AbstractView
 * 
 * @link http://agiletoolkit.org/learn/intro
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
abstract class AbstractObject
{
    /** Reference to the current model. Read only. Use setModel() */
    public $model;

    /** Reference to the current controller. Read only. Use setController() */
    public $controller;

    /** Exception class to use when $this->exception() is called */
    public $default_exception='BaseException';

    /** Default controller to initialize when calling setModel() */
    public $default_controller=null;

    /** Setting this to true will output additional debug info about object */
    public $debug=null;

    // {{{ Object hierarchy management

    /** Unique object name */
    public $name;

    /** Name of the object in owner's element array */
    public $short_name;

    /** short_name => object hash of children objects */
    public $elements = array ();

    /** Link to object into which we added this object */
    public $owner;
    
    /** Always points to current API */
    public $api;

    /** 
     * When this object is added, owner->elements[$this->short_name] 
     * will be == $this; 
     */
    public $auto_track_element = false;

    /** To make sure you have called parent::init() properly */
    public $_initialized = false;

    /** 
     * Initialize object. Always call parent::init(). Do not call directly.
     *
     * @return void
     */
    function init()
    {
        /**
         * This method is called for initialization
         */
        $this->_initialized = true;
    }

    /* \section Object Management Methods */
    /** 
     * Clones associated controller and model. If cloning views, add them to
     * the owner.
     *
     * @return AbstractObject Copy of this object
     */
    function __clone()
    {
        if ($this->model && is_object($this->model)) {
            $this->model = clone $this->model;
        }
        if ($this->controller && is_object($this->controller)) {
            $this->controller = clone $this->controller;
        }
    }

    /** 
     * Converts into string "Object View(myapp_page_view)"
     *
     * @return string
     */
    function __toString()
    {
        return "Object " . get_class($this) . "(" . $this->name . ")";
    }

    /**
     * Removes object from parent and prevents it from rendering
     * \code
     * $view = $this->add('View');
     * $view -> destroy();
     * \endcode
     *
     * @return void
     */
    function destroy()
    {
        foreach ($this->elements as $el) {
            if ($el instanceof AbstractObject) {
                $el->destroy();
            }
        }
        if (@$this->model && $this->model instanceof AbstractObject) {
            $this->model->destroy();
            unset($this->model);
        }
        if (@$this->controller && $this->controller instanceof AbstractObject) {
            $this->controller->destroy();
            unset($this->controller);
        }
        $this->owner->_removeElement($this->short_name);
    }

    /**
     * Remove child element if it exists
     *
     * @param string $short_name short name of the element
     *
     * @return $this
     */
    function removeElement($short_name)
    {
        if (is_object($this->elements[$short_name])) {
            $this->elements[$short_name]->destroy();
        } else {
            unset($this->elements[$short_name]);
        }
        return $this;
    }

    /**
     * Actually removes the element
     *
     * @param string $short_name short name
     *
     * @return \AbstractObject
     * @private
     */
    function _removeElement($short_name)
    {
        unset($this->elements[$short_name]);
        return $this;
    }

    /** 
     * Creates one more instance of $this object
     *
     * @param array $properties Set initial properties for new object
     *
     * @return \AbstractObject
     */
    function newInstance($properties = null)
    {
        return $this->owner->add(get_class($this), $properties);
    }

    /** 
     * Creates new object and adds it as a child of current object.
     * Returns new object.
     *
     * @param string       $class           Name of the new class
     * @param array|string $options         Short name or array of properties
     * @param string       $template_spot   Tag where output will appear
     * @param array|string $template_branch Redefine template
     *
     * @link http://agiletoolkit.org/learn/understand/base/adding 
     * @return \AbstractObject
     */
    function add(
        $class,
        $options = null,
        $template_spot = null,
        $template_branch = null
    ) {

        if (is_string($options)) {
            $options = array('name' => $options);
        }
        if (!is_array($options)) {
            $options = array();
        }

        if (is_object($class)) {
            // Object specified, just add the object, do not create anything
            if (!($class instanceof AbstractObject)) {
                throw $this->exception(
                    'You may only add objects based on AbstractObject'
                );
            }
            if (!$class->short_name) {
                $class->short_name = str_replace('\\', '_', strtolower(get_class($class)));
            }
            if (!$class->api) {
                $class->api = $this->api;
            }
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
            throw $this->exception("Class is not valid")
                ->addMoreInfo('class', $class);
        }
        
        $class = str_replace('/', '\\', $class);

        if ($class[0] == '.') {
            // Relative class name specified, extract current namespace
            // and make new class name relative to this namespace
            $ns = get_class($this);
            $ns = substr($ns, 0, strrpos($ns, '\\'));
            $class = $ns . '\\' . substr($class, 2);
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

        $short_name = $this->_unique($this->elements, $short_name);

        if (isset($this->elements[$short_name])) {
            throw $this->exception($class." with requested name already exists")
                ->addMoreInfo('class', $class)
                ->addMoreInfo('name', $short_name)
                ->addThis($this);
        }

        $class_name_nodash = str_replace('-', '', $class);
        /*
         * Even though this might break some applications,
         * your loading must be configured properly instead
         * of relying on this
         *
        if (!class_exists($class_name_nodash, false)
            && isset($this->api->pathfinder)
        ) {
            $this->api->pathfinder->loadClass($class);
        }*/
        $element = new $class_name_nodash();

        if (!($element instanceof AbstractObject)) {
            throw $this->exception(
                "You can add only classes based on AbstractObject"
            );
        }

        foreach ($options as $key => $val) {
            if ($key !== 'name') {
                $element->$key = $val;
            }
        }

        $element->owner = $this;
        $element->api = $this->api;
        $element->name = $this->_shorten($this->name . '_' . $short_name);
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
        $this->api->hook('beforeObjectInit', array(&$element));

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
    function getElement($short_name)
    {
        if (!isset($this->elements[$short_name])) {
            throw $this->exception("Child element not found")
                ->addMoreInfo('element', $short_name);
        }

        return $this->elements[$short_name];
    }

    /** 
     * Find child element. Use in condition. 
     *
     * @param string $short_name Short name of the child element
     *
     * @return AbstractObject
     */
    function hasElement($short_name)
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
     * @return AbstractObject $this
     */
    function rename($short_name)
    {
        unset($this->owner->elements[$this->short_name]);
        $this->name = $this->name . '_' . $short_name;
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
    function setController($controller, $name = null)
    {
        $controller = $this->api->normalizeClassName($controller, 'Controller');
        return $this->add($controller, $name);
    }

    /**
     * Associate model with object.
     *
     * @param string|object $model Class or instance of model
     *
     * @return AbstractModel Newly added Model
     */
    function setModel($model)
    {
        $model = $this->api->normalizeClassName($model, 'Model');
        $this->model = $this->add($model);
        return $this->model;
    }

    /**
     * Return current model
     *
     * @return AbstractModel Currently associated model object
     */
    function getModel()
    {
        return $this->model;
    }
    // }}}

    // {{{ Session management: http://agiletoolkit.org/doc/session
    /** 
     * Remember data in object-relevant session data 
     *
     * @param string $key   Key for the data
     * @param mixed  $value Value
     *
     * @return mixed $value
     */
    function memorize($key, $value)
    {
        $this->api->initializeSession();

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
    function learn($key, $default = null)
    {
        $this->api->initializeSession(false);
        
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
     * @return AbstractObject $this
     */
    function forget($key = null)
    {
        $this->api->initializeSession();
        
        if (is_null($key)) {
            unset ($_SESSION['o'][$this->name]);
            unset ($_SESSION['s'][$this->name]);
        } else {
            unset ($_SESSION['o'][$this->name][$key]);
            unset ($_SESSION['s'][$this->name][$key]);
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
    function recall($key, $default = null)
    {
        $this->api->initializeSession(false);
        
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
    function exception($message = 'Undefined Exception', $type = null, $code = null)
    {
        if (! $type) {
            $type = $this->default_exception;
        } elseif ($type[0] == '_') {
            if ($this->default_exception == 'BaseException') {
                $type = 'Exception' . $type;
            } else {
                $type = $this->default_exception . $type;
            }
        } elseif ($type != 'BaseException') {
            $type = $this->api->normalizeClassName($type, 'Exception');
        }

        // Localization support
        $message = $this->api->_($message);

        if ($type == 'Exception') {
            $type = 'BaseException';
        }

        $e = new $type($message, $code);
        if (! ($e instanceof BaseException) ) {
            throw $e;
        }
        $e->owner = $this;
        $e->api = $this->api;
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
     * @return void Should stop execution
     * @obsolete
     */
    function fatal($error, $shift = 0)
    {
        return $this->upCall(
            'outputFatal', array (
                $error,
                $shift
            )
        );
    }

    /**
     * Records debug information.
     *
     * @param string $msg information
     *
     * @return void
     * @obsolete
     */
    function info($msg)
    {
        /**
         * Call this function to send some information to API. Example:
         *
         * $this->info("User tried buying traffic without enough money in bank");
         */
        if (!$this->api->hook('outputInfo', array($msg, $this))) {
            $this->upCall('outputInfo', $msg);
        }
    }

    /**
     * Turns on debug mode for this object. Using first argument as string
     * is obsolete.
     *
     * @param bool|string $msg  "true" to start debugging
     * @param string      $file obsolete
     * @param string      $line obsolete
     *
     * @return void
     */
    function debug($msg = true, $file = null, $line = null)
    {
        if ($msg === true) {
            $this->debug = true;
            return $this;
        }

        // The rest of this method is obsolete
        if ((isset($this->debug) && $this->debug)
            || (isset($this->api->debug) && $this->api->debug)
        ) {
            $this->upCall(
                'outputDebug', array (
                    $msg,
                    $file,
                    $line
                )
            );
        }
    }

    /**
     * Records warning.
     *
     * @param string $msg   information
     * @param int    $shift relative offset in backtrace
     *
     * @return void
     * @obsolete
     */
    function warning($msg, $shift = 0)
    {
        $this->upCall(
            'outputWarning', array (
                $msg,
                $shift
            )
        );
    }

    /**
     * Call specified method for this class and all parents up to api.
     *
     * @param string $type information
     * @param array  $args relative offset in backtrace
     *
     * @return void
     * @obsolete
     */
    function upCall($type, $args = array ())
    {
        /**
         * Try to handle something on our own and in case we are not able,
         * pass to parent. Such as messages, notifications and request for
         * additional info or descriptions are passed this way.
         */
        if (method_exists($this, $type)) {
            return call_user_func_array(
                array (
                    $this,
                    $type
                ), $args
            );
        }
        if (!$this->owner) {
            return false;
        }
        return $this->owner->upCall($type, $args);
    }
    // }}}

    // {{{ Hooks: http://agiletoolkit.org/doc/hooks
    public $hooks = array ();

    /** 
     * If priority is negative, then hooks will be executed in reverse order.
     *
     * @param string   $hook_spot Hook identifier to bind on
     * @param callable $callable  Will be called on hook()
     * @param array    $arguments Arguments are passed to $callable
     * @param int      $priority  Lower priority is called sooner
     *
     * @return AbstractObject $this
     */
    function addHook($hook_spot, $callable, $arguments = array(), $priority = 5)
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
            && ($callable instanceof AbstractObject
            && !$callable->hasMethod($hook_spot))
        ) {
            throw $this->exception('Hook does not exist');
        }
        if (is_object($callable) && !is_callable($callable)) {
            $callable = array($callable, $hook_spot);
            // short for addHook('test', $this); to call $this->test();
        }

        if ($priority >= 0) {
            $this->hooks[$hook_spot][$priority][] = array($callable,$arguments);
        } else {
            if (!$this->hooks[$hook_spot][$priority]) {
                $this->hooks[$hook_spot][$priority] = array();
            }
            array_unshift(
                $this->hooks[$hook_spot][$priority],
                array($callable, $arguments)
            );
        }

        return $this;
    }

    /**
     * Delete all hooks for specified spot
     *
     * @param string $hook_spot Hook identifier to bind on
     *
     * @return AbstractObject $this
     */
    function removeHook($hook_spot)
    {
        unset($this->hooks[$hook_spot]);
        return $this;
    }

    /** 
     * Execute all callables assigned to $hook_spot
     *
     * @param string $hook_spot Hook identifier
     * @param array  $arg       Additional arguments to callables
     *
     * @return mixed Array of responses or value specified to breakHook
     */
    function hook($hook_spot, $arg = array ())
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
            if (isset ($this->hooks[$hook_spot])) {
                if (is_array($this->hooks[$hook_spot])) {
                    krsort($this->hooks[$hook_spot]); // lower priority is called sooner
                    foreach ($this->hooks[$hook_spot] as $prio => $_data) {
                        foreach ($_data as $data) {

                            // Our extension
                            if (is_string($data[0])
                                && !preg_match(
                                    '/^[a-zA-Z_][a-zA-Z0-9_]*$/',
                                    $data[0]
                                )
                            ) {
                                $result = eval ($data[0]);
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
                                    $data[0] = array (
                                        'STATIC',
                                        $data[0]
                                    );
                                }
                                throw $this->exception(
                                    "Cannot call hook. Function might not exist."
                                )
                                    ->addMoreInfo('hook', $hook_spot)
                                    ->addMoreInfo('arg1', $data[0][0])
                                    ->addMoreInfo('arg2', $data[0][1]);
                            }
                            $return[] = $result;
                        }
                    }
                }
            }
        } catch (Exception_Hook $e) {
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
     *
     * @return void Never returns
     */
    function breakHook($return)
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
    function __call($method, $arguments)
    {
        if (($ret = $this->tryCall($method, $arguments))) {
            return $ret[0];
        }
        throw $this->exception(
            "Method is not defined for this object", 'Logic'
        )
            ->addMoreInfo('class', get_class($this))
            ->addMoreInfo("method", $method)
            ->addMoreInfo("arguments", $arguments);
    }

    /** 
     * Attempts to call dynamic method. Returns array containing result or false
     *
     * @param string $method    Name of the method
     * @param array  $arguments Arguments
     *
     * @return mixed
     */
    function tryCall($method, $arguments)
    {
        if ($ret = $this->hook('method-'.$method, $arguments)) {
            return $ret;
        }
        array_unshift($arguments, $this);
        if (($ret = $this->api->hook('global-method-'.$method, $arguments))) {
            return $ret;
        }
    }

    /** 
     * Add new method for this object.
     *
     * @param string|array $name     Name of new method of $this object
     * @param callable     $callable Callback
     *
     * @return AbstractObject $this
     */
    function addMethod($name, $callable)
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
    }

    /** 
     * Return if this object has specified method (either native or dynamic).
     *
     * @param string $name Name of the method
     *
     * @return bool
     */
    function hasMethod($name)
    {
        return method_exists($this, $name)
            || isset($this->hooks['method-'.$name])
            || isset($this->api->hooks['global-method-'.$name]);
    }

    /**
     * Remove dynamically registered method.
     *
     * @param string $name Name of the method
     *
     * @return AbstractObject $this
     */
    function removeMethod($name)
    {
        $this->removeHook('method-'.$name);
    }
    // }}}

    // {{{ Logger: to be moved out
    /**
     * Output string into log file.
     *
     * @param string $var var
     * @param string $msg msg
     *
     * @return void
     * @obsolete 
     */
    function logVar($var, $msg = "")
    {
        $this->api->getLogger()->logVar($var, $msg);
    }

    /**
     * Output string into info file.
     *
     * @param string $info info
     * @param string $msg  msg
     *
     * @return void
     * @obsolete 
     */
    function logInfo($info, $msg = "")
    {
        $this->api->getLogger()->logLine($msg.' '.$info."\n");
    }

    /**
     * Output string into error file.
     *
     * @param string $error error
     * @param string $msg   msg
     *
     * @return void
     * @obsolete 
     */
    function logError($error, $msg = "")
    {
        if (is_object($error)) {
            // we got exception object obviously
            $error = $error->getMessage();
        }
        $this->api->getLogger()->logLine($msg.' '.$error."\n", null, 'error');
    }
    // }}}


    /**
     * A handy shortcut for foreach(){ .. } code. Make your callable return
     * "false" if you would like to break the loop.
     *
     * @param string|callable $callable will be executed for each member
     *
     * @return AbstractObject $this
     */
    function each($callable)
    {
        if (! ($this instanceof Iterator)) {
            throw $this->exception('Calling each() on non-iterative object');
        }

        if (is_string($callable)) {
            foreach ($this as $obj) {
                if ($obj->$callable() === false) {
                    break;
                }
            }
            return $this;
        }

        foreach ($this as $obj) {
            if (call_user_func($callable, $obj) === false) {
                break;
            }
        }
        return $this;
    }

    /**
     * Method used internally for shortening object names.
     *
     * @param string $desired Desired name of new object.
     *
     * @return string Shortened name of new object.
     */
    function _shorten($desired)
    {
        if (strlen($desired) > $this->api->max_name_length
            && $this->api->max_name_length !== false
        ) {
            $len = $this->api->max_name_length - 10;
            if ($len < 5) {
                $len = $this->api->max_name_length;
            }

            $key = substr($desired, 0, $len);
            $rest = substr($desired, $len);

            if (!$this->api->unique_hashes[$key]) {
                $this->api->unique_hashes[$key] = dechex(crc32($key));
            }
            $desired = $this->api->unique_hashes[$key].'__'.$rest;
        };

        return $desired;
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
    function _unique(&$array, $desired = null)
    {
        if (!is_array($array)) {
            throw $this->exception('not array');
        }
        if ($desired === null) {
           $desired = 'undef';
        }
        $postfix = count($array);
        $attempted_key = $desired;
        while (array_key_exists($attempted_key, $array)) {
            // already used, move on
            $attempted_key = $desired . '_' . (++$postfix);
        }
        return $attempted_key;
    }

    /**
     * Always call parent if you redefine this/
     * 
     * @return void
     */
    function __destruct()
    {
    }

    /** 
     * Do not serialize objects
     *
     * @return mixed
     */
    function __sleep()
    {
        return array('name');
    }
}
