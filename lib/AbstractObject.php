<?
abstract class AbstractObject {
	/*
	 * This class is a parent of ANY class of those in AModules.
	 */

	public $name; // Any object would have a name. Name is unique for all views.
	public $short_name;

	public $owner;
	public $api;

	/**
	 * Object may place number of hook-places in it's body. Plug-ins or other
	 * classes may set hooks, which would be called when hook is reached
	 */
	public $hooks = array ();

	/*
	 * Each object may have some childs.<F2>
	 */
	public $elements = array ();

	/////////////// I n i t i a l i z e  o b j e c t /////////////
	function init() {
		/**
		 * This method have a little new meaning now. When objects are created,
		 * API sends 'init' call into it's tree to initialize all stuff. If you
		 * don't catch that call, it will execute this function. Just place
		 * your initialization stuff here such as loading data from database.
		 * Don't forget to call parent::init();
		 */
	}
	function __toString() {
		return "Object " . get_class($this) . "(" . $this->name . ")";
	}
	/*function __get($var){
	if($this instanceof DummyObject){
		return $this;
	}else{
		// usually this causes error, but I guess we should get rid of it
		$this->api->logger->logLine("Property $var is not defined",null,"error");
		// ...and make further call to be secure
		return new DummyObject();
	}
	}*/
	function add($class, $short_name = null, $template_spot = null, $template_branch = null, $debug = null) {
		/**
		 * When you want to add element to your container, always use this
		 * function. It will initialize class, create object and make it a
		 * child of this object. Use is really simple:
		 */
		/* pre-add hook returns either empty object, or null */
		if (!is_null($hook_object = $this->hook('pre-add', array (
				$this,
				$class,
				$short_name
			)))) {
			// some properties are required for the dummy
			$hook_object->owner = $this;
			$hook_object->api = $this->api;
			return $hook_object;
		}
		if (is_object($class)) {
			// Object specified, just add the object, do not create anything
			if (!($class instanceof AbstractObject)) {
				throw new BaseException('You may only add objects based on AbstractObject');
			}
			if (!$class->short_name) {
				throw new BaseException('Cannot add existing object, without short_name');
			}
			if ($this->elements[$class->short_name])
				return $this->elements[$class->short_name];
			$this->elements[$class->short_name] = $class;
			$class->owner = $this;
			return $class;
		}
		if (!$short_name)
			$short_name = strtolower($class);

		if (isset ($this->elements[$short_name])) {
			if ($this->elements[$short_name] instanceof AbstractView) {
				// AbstractView classes shouldn't be created with the same name. If someone
				// would still try to do that, it should generate error. Obviously one of
				// those wouldn't be displayed or other errors would occur
				$this->warning("Element with name $short_name already exists in " . ($this->__toString()));
			}
			if ($this->elements[$short_name] instanceof AbstractController) {
				return $this->elements[$short_name];
			}
			// Model classes may be created several times and we are actually don't care about those.
		}

		if ($debug) {
			$element = new Debug($class);
		} else {
			if(!is_string($class) || !$class)throw new BaseException("Class is not valid");
			$element = new $class ();
		}

		if (!($element instanceof AbstractObject)) {
			throw new BaseException("You can add only classes based on AbstractObject (called from " . caller_lookup(1, true) . ")");
		}

		$element->owner = $this;
		$element->api = $this->api;
		$this->elements[$short_name] = $element;

		$element->name = $this->name . '_' . $short_name;
		$element->short_name = $short_name;

		if ($element instanceof AbstractView) {
			$element->initializeTemplate($template_spot, $template_branch);
		}

		/* this hook is called after object is added, we are not interested in its results */
		$this->hook('post-add', array (
			$this,
			$element
		));
		$element->init();
		$GLOBALS["lh"][$element->short_name]++;
		return $element;
	}

	/////////////// D a t a   s e s s i o n i n g ////////////////
	function learn($name, $value1 = null, $value2 = null, $value3 = null) {
		/**
		 * Learn is a handy function when your object wants to store some
		 * data inside session with multiple initializers. Let's say there are
		 * various ways to specify a value: by $_GET['set_color'],
		 * $this->api->config['default_color'] and hard-coded color "#FF1212".
		 *
		 * Call this function and specify all arguments in order of priority.
		 * learn() will find first non-null argument and memorize it's value.
		 * It also will return that value.
		 *
		 * If all arguments are null, you'll get recalled version of a variable.
		 */

		if (isset ($value1))
			return $this->memorize($name, $value1);
		if (isset ($value2))
			return $this->memorize($name, $value2);
		return $this->memorize($name, $value3);
	}
	function memorize($name, $value) {
		/**
		 * Memorize is a handy function when your object wants to store some
		 * data inside session. This function also ensures your data will not
		 * get mixed up with other object data or other projects.
		 *
		 * If you have multiple possible values, you might want using learn()
		 */
		if (!isset ($value))
			return $this->recall($name);
		return $_SESSION['o'][$this->name][$name] = $value;
	}
	function forget($name = null) {
		/**
		 * If you want to reset some memorized value, call this function. If
		 * you want to forget all the data memorized by object call
		 * $this->forget(); without arguments.
		 */

		if (isset ($name)) {
			unset ($_SESSION['o'][$this->name][$name]);
		} else {
			unset ($_SESSION['o'][$this->name]);
		}
	}
	function recall($name, $default = null) {
		/**
		 * Load previously memorized data. You should specify name and you also
		 * can specify default value if value is not present.
		 *
		 * If you want $default value to be memorized as well, see learn()
		 */
		if (!isset ($_SESSION['o'][$this->name][$name])||is_null($_SESSION['o'][$this->name][$name])) {
			return $default;
		} else {
			return $_SESSION['o'][$this->name][$name];
		}
	}
	function getCachedGet($name, $default = null) {
		/**
		 * See if $this->long_name.'_'.$name is passed through get. If yes,
		 * learn it into $name. If no, try to recall $name
		 *
		 * FIXME: this will not try to recall name! Also long_name does not exist anymore.
		 */
		return $this->learn($name, @ $_GET[$this->long_name . '_' . $name], $default);
	}

	function getElement($short_name, $obligatory = true) {
		if (!isset ($this->elements[$short_name]))
			if ($obligatory)
				throw new BaseException($this->__toString() . " does not have child $short_name");
			else
				return null;
		return $this->elements[$short_name];
	}
	function hasElement($name){
		return isset($this->elements[$name])?$this->elements[$name]:false;
	}
	function removeElement($short_name){
		if(isset($this->elements[$short_name]))$this->elements[$short_name]=null;
		return $this;
	}
	function getName(){
		return $this->name;
	}

	/////////////// M e s s a g e   h a n d l i n g //////////////
	function fatal($error, $shift = 0) {
		/**
		 * If you have fatal error in your object use the following code:
		 *
		 * return $this->fatal("Very serious problem!");
		 *
		 * This line will notify parent about fatal error and return null to
		 * the caller. Caller don't have to handle error messages, just throw
		 * everything up.
		 *
		 * Fatal calls are intercepted by API. Or if you want you can intercept
		 * them yourself.
		 *
		 * TODO: record debug_backtrace depth so we could point acurately at
		 * the function/place where fatal is called from.
		 */

		return $this->upCall('outputFatal', array (
			$error,
			$shift
		));
	}
	function info($msg) {
		/**
		 * Call this function to send some information to API. Example:
		 *
		 * $this->info("User tried buying traffic without enough money in bank");
		 */

		if(!$this->api->hook('outputInfo',array($msg,$this)))
			$this->upCall('outputInfo', $msg);
	}
	function debug($msg, $file = null, $line = null) {
		/**
		 * Use this function to send debug information. Information will only
		 * be sent if you enable debug localy (per-object) by setting
		 * $this->debug=true or per-apllication by setting $api->debug=true;
		 *
		 * You also may enable debug globaly:
		 * $this->api->debug=true;
		 * but disable for object
		 * $object->debug=false;
		 */
		if ((isset ($this->debug) && $this->debug) || (isset ($this->api->debug) && $this->api->debug)) {
			$this->upCall('outputDebug', array (
				$msg,
				$file,
				$line
			));
		}
	}
	function warning($msg, $shift = 0) {
		$this->upCall('outputWarning', array (
			$msg,
			$shift
		));
	}

	/////////////// C r o s s   c a l l s ///////////////////////
	function upCall($type, $args = array ()) {
		/**
		 * Try to handle something on our own and in case we are not
		 * able, pass to parent. Such as messages, notifications and request
		 * for additional info or descriptions are passed this way.
		 */
		if (method_exists($this, $type)) {
			return call_user_func_array(array (
				$this,
				$type
			), $args);
		}
		if (!$this->owner)
			return false;
		return $this->owner->upCall($type, $args);
	}
	function downCall($type, $args = array()) {
		/**
		 * Unlike upCallHandler, this will pass call down to all childs. This
		 * one is useful for a "render" or "submitted" calls.
		 */
		foreach (array_keys($this->elements) as $key) {
			if (!($this->elements[$key] instanceof AbstractController)) {
				$this_result = $this->elements[$key]->downCall($type, $args);
				if ($this_result === false)
					return false;
			}
		}
		if (method_exists($this, $type)) {
			return call_user_func_array(array (
				$this,
				$type
			), $args);
		}
		return null;
	}

	/////////////// Hooking /////////////////////////////////////
	function addHook($hook_spot, $callable, $priority = 5) {
		$this->hooks[$hook_spot][$priority][] = $callable;
		return $this;
	}
	function hook($hook_spot, $arg = array ()) {
		if (isset ($this->hooks[$hook_spot])) {
			if (is_array($this->hooks[$hook_spot])) {
				foreach ($this->hooks[$hook_spot] as $prio => $_data) {
					foreach ($_data as $data) {

						// Our extentsion.
						if (is_string($data) && !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $data)) {
							$result = eval ($data);
						}
						elseif (is_callable($data)) {
							// TODO, make sure __toString is executed from sprintf.
							// documentation says only about printf, while making
							// the same thing work in sprintf would be logical
							$result = call_user_func_array($data, $arg);
						} else {
							if (!is_array($data))
								$data = array (
									'STATIC',
									$data
								);
							throw new BaseException(sprintf("Cannot call %s->%s. Ensure that function exist.", $data[0], $data[1]));
						}
						if ($result !== null)
							return $result;
					}
				}
			}
		}
	}
	function hook_wrap($method_name, $hook_spot = null) {
		if (!isset ($hook_spot))
			$hook_spot = $method_name;
		$result = $this->hook($hook_spot . '-pre');
		if (!isset ($result))
			$result = $this-> $method_name ();
		$this->hook($hook_spot . '-post');
		return $result;
	}
	/* destruction to free up memory */
	function selfDestruct() {
		if ($this->elements) {
			foreach ($this->elements as $element_id => $element) {
				$element->selfDestruct();
				if (!isset ($GLOBALS["lh"][$element->short_name])) {
					$GLOBALS["lh"][$element->short_name] = "was not properly initialized!";
				} else {
					$GLOBALS["lh"][$element->short_name]--;
				}
				unset ($this->elements[$element_id]);
				unset ($element);
			}
		}
	}
	/////// LOGGER ////////
	function logVar($var,$msg=""){
		$this->api->getLogger()->logVar($var,$msg);
	}
	function logInfo($info,$msg=""){
		$this->api->getLogger()->logLine($msg.' '.$info."\n");
	}
	function logError($error,$msg=""){
		if(is_object($error)){
			// we got exception object obviously
			$error=$error->getMessage();
		}
		$this->api->getLogger()->logLine($msg.' '.$error."\n",null,'error');
	}
	// AJAX
	/**
	 * Returns the AJAX instance
	 * @param $instance - if false, returns new instance, similar to call ->add('Ajax')
	 * 	- if set to any string - create/returns corresponding AJAX instance from the object's elements
	 */
	function ajax($instance=false){
		throw new BaseException("You can call js() or ajax() only for Views (derived from AbstractView)");
	}
}
