<?php // vim:ts=4:sw=4:et:fdm=marker
/**
 * A base class for all Visual objects in Agile Toolkit. The
 * important distinctive property of all Views is abiltiy
 * to render themselves (produce HTML)
 *
 * @link http://agiletoolkit.org/learn/understand/view
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4 
    http://agiletoolkit.org/
  
   (c) 2008-2011 Romans Malinovskis <atk@agiletech.ie>
   Distributed under Affero General Public License v3
   
   See http://agiletoolkit.org/about/license
 =====================================================ATK4=*/
abstract class AbstractView extends AbstractObject {
    /**
     * $template describes how this object is rendered. Template
     * can be either string or array or SMlite cloned object.
     * For containers, who have sub-elements which render themself
     * using SMlite this should be an object.
     */
    public $template=false;
    protected $template_branch=array();

    /**
     * $template_flush is set to a spot on the template, which
     * should be flushed out. When using AJAX we want to show
     * only certain region from our template. However several
     * childs may want to put their data. This property will
     * be set to region's name my call_ajax_render and if it's
     * set, call_ajax_render will echo it and return false.
     */
    public $template_flush=false;

    /**
     * $spot defines a place on a parent's template where object
     * will be inserted
     */
    public $spot;

    protected $controller=null; // View should initialize itself with the help of Controller instance

    // {{{ Basic Operations
    /** Duplicate view and it's template. Will not duplicate children */
    function __clone(){
        parent::__clone();
        if($this->template)$this->template=clone $this->template;
        if($this->controller)$this->controller=clone $this->controller;
    }
    /** Uses appropriate controller for this view to bind it with model */
    function setModel($model,$actual_fields=null){
        $c=$this->add('Controller');
        if(is_string($model)){
            $c->setModel('Model_'.$model);
        }else{
            $c->setModel($model);
        }
        if($actual_fields)$c->setActualFields($actual_fields);
        $this->setController($c);
        return $c;
    }
    /** Get associated model */
    function getModel(){
        return $this->getController()->getModel();
    }
    /** Manually specify controller for view */
    function setController($controller){
        if(is_object($controller)){
            $this->controller=$controller;
            $this->controller->owner=$this;
        }else{
            $this->controller=$this->add($controller);
        }
        if(method_exists($this->controller,'_bindView'))$this->controller->_bindView();
        return $this;
    }
    function getController(){
        return $this->controller;
    }

    public $_tsBuffer='';
    function _tsBuffer($data){
        $this->_tsBuffer.=$data;
    }
    /** Converting View into string will render recursively and produce HTML. Avoid using this. */
    function __toString(){
        $this->addHook('output',array($this,'_tsBuffer'));
        $this->recursiveRender();
        $this->removeHook('output',array($this,'_tsBuffer'));
        $ret=$this->_tsBuffer;
        $this->_tsBuffer='';
        return $ret;
    }

    // }}}

    // {{{ Template Setup

    /** [private] Called automatically during init */
    function initializeTemplate($template_spot=null,$template_branch=null){
        if(!$template_spot)$template_spot=$this->defaultSpot();
        $this->spot=$template_spot;
        if(!isset($template_branch))$template_branch=$this->defaultTemplate();
        if(isset($template_branch)){
            $this->template_branch=$template_branch;

            // template branch would tell us what kind of template we have to use. Let's
            // look at several cases
            if(is_object($template_branch)){        // it might be already SMlite instance (object)
                $this->template=$template_branch;   // so we just use that
            }else if(is_array($template_branch)){       // it might be array with [0]=template, [1]=tag
                if(is_object($template_branch[0])){     // if [0] is object, we'll use that
                    $this->template=$template_branch[0];
                }else{
                    "loading $template_branch[0]<br />";
                    $this->template=$this->api->add('SMlite')   // or if it's string
                        ->loadTemplate($template_branch[0]);    // we'll use it as a file
                }
                // Now that we loaded it, let's see which tag we need to cut out
                $this->template=$this->template->cloneRegion(isset($template_branch[1])?$template_branch[1]:'_top');
            }else{  // brach could be just a string - a region to clone off parent
                if(isset($this->owner->template)){
                    $this->template=$this->owner->template->cloneRegion($template_branch);
                }else{
                    $this->template=$this->add('SMlite');
                }
            }
            $this->template->owner=$this;
        }

        // Now that the template is loaded, let's take care of parent's template
        if($this->owner && (isset($this->owner->template)) && (!empty($this->owner->template))){
            $this->owner->template->del($this->spot);
        }

        // Cool, now let's set _name of this template
        if($this->template)$this->template->trySet('_name',$this->name);

        $this->initTemplateTags();
    }
    /** [private] Lets API auto-fill some tags in all views (such as tempalte tag) */
    function initTemplateTags(){
        if($this->template && $this->api && method_exists($this->api, 'setTags')){
            $this->api->setTags($this->template);
        }
    }

    /** Redefine to return default template, when 3rd argument of add() is omitted */
    function defaultTemplate(){
        return $this->spot;
    }
    /** Redefine if object needs to output elsewhere, not into Content */
    function defaultSpot(){
        return 'Content';
    }
    /** [private] returns actual template branch in same format as defaultTemplate() */
    function templateBranch(){
        return $this->template_branch;
    }
    // }}}

    // {{{ Rendering, see http://agiletoolkit.org/learn/understand/api/exec
    /** [private] Recursively renders all views. Calls render() for all or for the one being cut */
    function recursiveRender(){
        $cutting_here=false;

        if(isset($_GET['cut_object']) && ($_GET['cut_object']==$this->name || $_GET['cut_object']==$this->short_name)){
            // If we are cutting here, render childs and then we are done
            unset($_GET['cut_object']);
            $cutting_here=true;
        }

        foreach($this->elements as $key=>$obj){
            if($obj instanceof AbstractView){
                $obj->recursiveRender();
                $obj->moveJStoParent();
            }
        }

        if(!isset($_GET['cut_object'])){
            if(isset($_GET['cut_region'])){
                $this->region_render();
            }else{
                $this->render();
            }
        }

        if($cutting_here){
            $result=$this->owner->template->cloneRegion($this->spot)->render();
            if($this->api->jquery)$this->api->jquery->getJS($this);
            throw new Exception_StopRender($result);
        }
        // if template wasn't cut, we move all JS chains to parent

    }
    /** [private] Append our chains to owner's chains. JS chains bubble up to API or object being cut */
    function moveJStoParent(){
        $this->owner->js=array_merge_recursive($this->owner->js,$this->js);
    }
    /** Default render. Redefine if your object needs to dynamically generate data through heavy computation */
    function render(){
        /**
         * For visual objects, their default action while rendering is rely on SMlite engine.
         * For sake of simplicity and speed you can redefine this method with a simple call
         */
        if(!($this->template)){
            throw $this->exception("You should specify template for this object")
                ->addMoreInfo('object',$this->name);
        }
        $this->output($this->template->render());
    }
    /** Call from render where you would use echo. Bypasses template, hence $this->template->set is better */
    function output($txt){
        if(!$this->hook('output',array($txt))){
            if((isset($this->owner->template)) && (!empty($this->owner->template)))
                $this->owner->template->append($this->spot,$txt);
        }
    }
    /** When cutting, perform selective render for a region */
    function region_render(){
        /**
         * if GET['ajax'] is set, we need only one chunk of a page
         */
        if($this->template_flush){
            if($this->api->jquery)$this->api->jquery->getJS($this);
            throw new Exception_StopRender($this->template->cloneRegion($this->template_flush)->render());
        }
        $this->render();
        if($this->spot==$_GET['cut_region']){
            $this->owner->template_flush=$_GET['cut_region'];
        }
    }
    /** When cutting, perform selective render for an object */
    function object_render(){
        /**
         * if GET['cut'] is set, then only particular object will be rendered
         */
        if($this->name==$_GET['cut_object'] || $this->short_name==$_GET['cut_object']){
            $this->downCall('render');
            if($this->template)echo $this->template->render();
            else $this->render();
            return false;
        }
    }
    // }}}

    // {{{ Object JavaScript Interface
    public $js=array();
    /** Creates and binds (if $when) JavaScript chain to object */
    function js($when=null,$code=null,$instance=null){
        /*
           This function is designed for particular object interaction with javascript. We are assuming
           that any View object have HTML presence. We are also assuming that it at least adds
           one dag to the render tree with ID=$this->name.

           First argument $when represents when code must be executed.
         * true -> code will be executed immediatelly
         * 'click' -> code will be executed if HTML is clicked
         * null, false -> code will not be executed, only returned

         No matter $when you specify code to execute, function will return JS object, which
         can be either chained, but if used as a string, it will return a proper JS code.

         1. Calling with arguments:

         $this->js();					// does nothing
         $this->js(true,'alert(123)');	// does alert(123) after DOM is ready

         2. When events are used, generated code will use different format
         $this->js('click','alert(123)');	// $('#name').click(function(){ alert(123); });

         This is very useful when you are trying to make multiple objects interract

         $this->js('click',$form->js()->submit());
        // $('#name').click(function(){ $('#form').submit(); });

        3. calling js() will return jQuery_Chain object, which you can subsequentally call
        to perform multiple actions.

        $this->js(true)->parent()->find('.current')->removeClass('current');
        //    $('#name').parent().find('.current').removeClass('current');

        4. 3rd argument - instance

        Sometimes you wish to get back and continue same chain.
        $grid->js(null,$this->js()->hide(),'refresh');

        In this case - grid might be pre-set non-executable chains for several actions. Example
        above will add additional code to that chain which will hide $this element.

        See individual component documentation for more information


         */
        // Create new jQuery_Chain object
        if(!isset($this->api->jquery))throw new BaseException("requires jQuery or jUI support");

        // Substitute $when to make it better work as a array key
        if($when===true)$when='always';
        if($when===false || $when===null)$when='never';


        if($instance && isset($this->js[$when][$instance])){
            $js=$this->js[$when][$instance];
        }else{
            $js=$this->api->jquery->chain($this);
        }

        if($code)$js->_prepend($code);

        if($instance){
            $this->js[$when][$instance]=$js;
        }else{
            $this->js[$when][]=$js;
        }
        return $js;
    }


    /* frame(): Obsolete. ->add('Frame') or Use Controller_Compat_Frame */
    /* ajax(): Obsolete. ->js()->univ() or Use Controller_Compat_Frame */
}
