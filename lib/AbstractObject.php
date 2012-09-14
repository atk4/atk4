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
  
   (c) 2008-2011 Romans Malinovskis <romans@agiletoolkit.org>
   Distributed under Affero General Public License v3
   
   See http://agiletoolkit.org/about/license
 =====================================================ATK4=*/
abstract class AbstractObject {
    public $settings=array('extension'=>'.html');

    /** Reference to the current model. Read only. Use setModel() */
    public $model;

    /** Reference to the current controller. Read only. Use setController() */
    public $controller;

    /** Exception class to use when $this->exception() is called */
    public $default_exception='BaseException';

    /** Default controller to initialize when calling setModel() */
    public $default_controller=null;


    // {{{ Object hierarchy management: http://agiletoolkit.org/learn/understand/base/adding

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

    /** When this object is added, owner->elements[$this->short_name] will be == $this; */
    public $auto_track_element=false;

    public $_initialized=false;
    /** Initialize object. Always call parent */
    function init() {
        /**
         * This method is called for initialization
         */
        $this->_initialized=true;
    }

    /* \section Object Management Methods */
    /** Clones associated controller and model. If cloning views, add them to the owner */
    function __clone(){
        //$this->short_name=$this->_unique($this->owner->elements,$this->short_name);
        //$this->owner->add($this);

        if($this->model && is_object($this->model))$this->model=clone $this->model;
        if($this->controller && is_object($this->controller))$this->controller=clone $this->controller;

    }
    /* Converts into string "Object View(myapp_page_view)"   */
    function __toString() {
        return "Object " . get_class($this) . "(" . $this->name . ")";
    }
    /* 
        Removes object from parent and prevents it from renedring 
        \code
        $view = $this->add('View');
        $view -> destroy();
        \endcode
    */
    function destroy(){
        foreach($this->elements as $el)if($el instanceof AbstractObject){
            $el->destroy();
        }
        if(@$this->model && $this->model instanceof AbstractObject){
            $this->model->destroy();
            unset($this->model);
        }
        if(@$this->controller && $this->controller instanceof AbstractObject){
            $this->controller->destroy();
            unset($this->controller);
        }
        $this->owner->_removeElement($this->short_name);
    }
    /** Remove child element if it exists */
    function removeElement($short_name){
        if(is_object($this->elements[$short_name])){
            $this->elements[$short_name]->destroy();
        }
        else unset($this->elements[$short_name]);
        return $this;
    }
    function _removeElement($short_name){
        unset($this->elements[$short_name]);
        return $this;
    }
    function newInstance(){
        return $this->owner->add(get_class($this));
    }
    /** Creates new object and adds it as a child. Returns new object
     * http://agiletoolkit.org/learn/understand/base/adding */
    function add($class, $short_name = null, $template_spot = null, $template_branch = null) {

        if(is_array($short_name)){

            $di_config=$short_name;
            $short_name=@$di_config['name'];unset($di_config['name']);
        }else $di_config=array();

        if (is_object($class)) {
            // Object specified, just add the object, do not create anything
            if (!($class instanceof AbstractObject)) {
                throw $this->exception('You may only add objects based on AbstractObject');
            }
            if (!$class->short_name) {
                throw $this->exception('Cannot add existing object, without short_name');
            }
            $this->elements[$class->short_name] = $class;
            if($class instanceof AbstractView){
                $class->owner->elements[$class->short_name]=true;
            }
            $class->owner = $this;

            
            return $class;
        }elseif($class[0]=='.'){
            $tmp=explode('\\',get_class($this));
            if(!$tmp[1]){
                $ns='';
            }else{
                $ns=$tmp[0];
            }
            $class=$ns.'/'.substr($class,2);
        }
        if (!$short_name)
            $short_name = str_replace('/','_',strtolower($class));

        $short_name=$this->_unique($this->elements,$short_name);

        if (isset ($this->elements[$short_name])) {
            if ($this->elements[$short_name] instanceof AbstractView) {
                // AbstractView classes shouldn't be created with the same name. If someone
                // would still try to do that, it should generate error. Obviously one of
                // those wouldn't be displayed or other errors would occur
                throw $this->exception("Element with name already exists")
                    ->addMoreInfo('name',$short_name)
                    ->addThis($this);
            }
        }

        if(!is_string($class) || !$class)throw $this->exception("Class is not valid")
            ->addMoreInfo('class',$class);

        // Separate out namespace
        $class_name=str_replace('/','\\',$class);
        if(!class_exists($class_name,false) && isset($this->api->pathfinder))$this->api->pathfinder->loadClass($class_name);
        $element = new $class_name();

        if (!($element instanceof AbstractObject)) {
            throw $this->exception("You can add only classes based on AbstractObject");
        }

        foreach($di_config as $key=>$val){
            $element->$key=$val;
        }

        $element->owner = $this;
        $element->api = $this->api;
        $this->elements[$short_name]=$element;
        if(!$element->auto_track_element)
            $this->elements[$short_name]=true;  // dont store extra reference to models and controlers
            // for purposes of better garbage collection

        $element->name = $this->_shorten($this->name . '_' . $short_name);
        $element->short_name = $short_name;

        // Initialize template before init() starts
        if ($element instanceof AbstractView) {
            $element->initializeTemplate($template_spot, $template_branch);
        }

        // Avoid using this hook. Agile Toolkit creates LOTS of objects, so you'll get significantly
        // slower code if you try to use this
        $this->api->hook('beforeObjectInit',array(&$element));

        $element->init();


        // Make sure init()'s parent was called. Popular coder's mistake
        if(!$element->_initialized)throw $element->exception('You should call parent::init() when you override it')
            ->addMoreInfo('object_name',$element->name)
            ->addMoreInfo('class',get_class($element));

        return $element;
    }
    /** Find child element by their short name. Use in chaining. Exception if not found. */
    function getElement($short_name) {
        if (!isset ($this->elements[$short_name]))
            throw $this->exception("Child element not found")
                ->addMoreInfo('element',$short_name);

        return $this->elements[$short_name];
    }
    /** Find child element. Use in condition. */ 
    function hasElement($name){
        return isset($this->elements[$name])?$this->elements[$name]:false;
    }
    /** Names object accordingly. May not work on some objects */
    function rename($short_name){
        unset($this->owner->elements[$this->short_name]);
        $this->name = $this->name . '_' . $short_name;
        $this->short_name = $short_name;
        $this->owner->elements[$short_name]=$this;
        if(!$this->auto_track_element)
            $this->owner->elements[$short_name]=true; 
        return $this;
    }
    // }}} 

    // {{{ Model and Controller handling
    function setController($controller){
        if(is_string($controller)&&substr($controller,0,strlen('Controller'))!='Controller')
            $controller=preg_replace('|^(.*/)?(.*)$|','\1Controller_\2',$controller);
        return $this->add($controller);
    }
    function setModel($model){
        if(is_string($model)&&substr($model,0,strlen('Model'))!='Model'){
            $model=preg_replace('|^(.*/)?(.*)$|','\1Model_\2',$model);
        }
        $this->model=$this->add($model);
        return $this->model;
    }
    function getModel(){
        return $this->model;
    }
    // }}}

    // {{{ Session management: http://agiletoolkit.org/doc/session
    /** Remember object-relevant session data */
    function memorize($name, $value) {
        if (!isset ($value))
            return $this->recall($name);
        $this->api->initializeSession();
        return $_SESSION['o'][$this->name][$name] = $value;
    }
    /** Remember one of the supplied arguments, which is not-null */
    function learn($name, $value1 = null, $value2 = null, $value3 = null) {
        if (isset ($value1))
            return $this->memorize($name, $value1);
        if (isset ($value2))
            return $this->memorize($name, $value2);
        return $this->memorize($name, $value3);
    }
    /** Forget session data for arg $name. Null forgets all data relevant to this object */
    function forget($name = null) {
        $this->api->initializeSession();
        if (isset ($name)) {
            unset ($_SESSION['o'][$this->name][$name]);
        } else {
            unset ($_SESSION['o'][$this->name]);
        }
    }
    /** Returns session data for this object. If not set, $default is returned */
    function recall($name, $default = null) {
        $this->api->initializeSession(false);
        if (!isset ($_SESSION['o'][$this->name][$name])||is_null($_SESSION['o'][$this->name][$name])) {
            return $default;
        } else {
            return $_SESSION['o'][$this->name][$name];
        }
    }
    // }}}

    // {{{ Exception handling: http://agiletoolkit.org/doc/exception
    function exception($message,$type=null){
        if(!$type){
            $type=$this->default_exception;
        }elseif($type[0]=='_'){
            $type=$this->default_exception.'_'.substr($type,1);
        }else{
            $type='Exception_'.$type;
        }


        // Localization support
        $message=$this->api->_($message);

        if($type=='Exception')$type='BaseException';
        $e=new $type($message);
        $e->owner=$this;
        $e->api=$this->api;
        $e->init();
        return $e;
    }
    // }}}

    // {{{ Code which can be potentially obsoleted
    /** @obsolete */
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
    /** @obsolete */
    function info($msg) {
        /**
         * Call this function to send some information to API. Example:
         *
         * $this->info("User tried buying traffic without enough money in bank");
         */

        if(!$this->api->hook('outputInfo',array($msg,$this)))
            $this->upCall('outputInfo', $msg);
    }
    /** @obsolete */
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
    /** @obsolete */
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
    // }}} 

    // {{{ Hooks: http://agiletoolkit.org/doc/hooks
    public $hooks = array ();

    /** If priority is negative, then hooks will be executed in reverse order */
    function addHook($hook_spot, $callable, $arguments=array(), $priority = 5) {
        if(!is_array($arguments)){
            // Backwards compatibility
            $priority=$arguments;
            $arguments=array();
        }
        if(is_string($hook_spot) && strpos($hook_spot,',')!==false)$hook_spot=explode(',',$hook_spot);
        if(is_array($hook_spot)){
            foreach($hook_spot as $h){
                $this->addHook($h,$callable,$arguments, $priority);
            }
            return $this;
        }
        if(is_object($callable) && !is_callable($callable)){
            $callable=array($callable,$hook_spot);  // short for addHook('test',$this); to call $this->test();
        }
        if($priority>=0){
            $this->hooks[$hook_spot][$priority][] = array($callable,$arguments);
        }else{
            if(!$this->hooks[$hook_spot][$priority])
                $this->hooks[$hook_spot][$priority]=array();
            array_unshift($this->hooks[$hook_spot][$priority],array($callable,$arguments));
        }
        return $this;
    }
    function removeHook($hook_spot) {
        unset($this->hooks[$hook_spot]);
        return $this;
    }
    function hook($hook_spot, $arg = array ()) {
        $return=array();
        try{
            if (isset ($this->hooks[$hook_spot])) {
                if (is_array($this->hooks[$hook_spot])) {
                    foreach ($this->hooks[$hook_spot] as $prio => $_data) {
                        foreach ($_data as $data) {

                            // Our extentsion.
                            if (is_string($data[0]) && !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $data[0])) {
                                $result = eval ($data[0]);
                            } elseif (is_callable($data[0])) {
                                $result = call_user_func_array($data[0], array_merge(array($this),$arg,$data[1]));
                            } else {
                                if (!is_array($data[0]))
                                    $data[0] = array (
                                            'STATIC',
                                            $data[0]
                                            );
                                throw $this->exception("Cannot call hook. Function might not exist")
                                    ->addMoreInfo('hook',$hook_spot)
                                    ->addMoreInfo('arg1',$data[0][0])
                                    ->addMoreInfo('arg2',$data[0][1]);
                            }
                            $return[]=$result;
                        }
                    }
                }
            }
        }catch(Exception_Hook $e){
            return $e->return_value;
        }
        return $return;
    }
    function breakHook($return){
        $e=$this->exception(null,'Hook');
        $e->return_value=$return;
        throw $e;
    }
    // }}}

    // {{{ Dynamic Methods: http://agiletoolkit.org/learn/dynamic
    /* Call method is used to display exception for non-existant methods and also ability to extend objects with addMethod */
    function __call($method,$arguments){
        if($ret=$this->tryCall($method,$arguments))return $ret[0];
        throw $this->exception("Method is not defined for this object",'Logic')
            ->addMoreInfo('class',get_class($this))
            ->addMoreInfo("method",$method)
            ->addMoreInfo("arguments",$arguments);
    }
    /** [private] attempts to call method, returns array containing result or false */
    function tryCall($method,$arguments){
        if($ret=$this->hook('method-'.$method,$arguments))return $ret;
        array_unshift($arguments,$this);
        if($ret=$this->api->hook('global-method-'.$method,$arguments))return $ret;
    }
    /** Add new method for this object */
    function addMethod($name,$callable){
        if(is_string($name) && strpos($name,',')!==false)$name=explode(',',$name);
        if(is_array($name)){
            foreach($name as $h){
                $this->addMethod($h,$callable);
            }
            return $this;
        }
        if(is_object($callable) && !is_callable($callable)){
            $callable=array($callable,$name);
        }
        if($this->hasMethod($name))
            throw $this->exception('Registering method twice');
        $this->addHook('method-'.$name,$callable);
    }
    /** Return if this object have specified method */
    function hasMethod($name){
        return method_exists($this,$name)
            || isset($this->hooks['method-'.$name])
            || isset($this->api->hooks['global-method-'.$name]);
    }
    function removeMethod($name){
        $this->removeHook('method-'.$name);
    }
    
    // }}}

    // {{{ Logger: to be moved out 
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
    // }}}

    function _shorten($desired){
        if(strlen($desired)>$this->api->max_name_length){

            $len=$this->api->max_name_length-10;
            if($len<5)$len=$this->api->max_name_length;

            $key=substr($desired,0,$len);
            $rest=substr($desired,$len);

            if(!$this->api->unique_hashes[$key]){
                $this->api->unique_hashes[$key]=count($this->api->unique_hashes)+1;
            }
            $desired=$this->api->unique_hashes[$key].'__'.$rest;
        };

        return $desired;
    }
    /**
     * This funcion given the associative $array and desired new key will return
     * the best matching key which is not yet in the arary. For example if you have
     * array('foo'=>x,'bar'=>x) and $desired is 'foo' function will return 'foo_2'. If 
     * 'foo_2' key also exists in that array, then 'foo_3' is returned and so on.
     */
    function _unique(&$array,$desired=null){
        $postfix=1;$attempted_key=$desired;
        if(!is_array($array))throw $this->exception('not array');
        while(array_key_exists($attempted_key,$array)){
            // already used, move on
            $attempted_key=($desired?$desired:'undef').'_'.(++$postfix);
        }       
        return $attempted_key;
    }


    /** Always call parent if you redefine this */
    function __destruct(){
    }
    function __sleep(){
        return array('name');
    }
}
