<?php // vim:ts=4:sw=4:et:fdm=marker
/**
 * A base class for all Visual objects in Agile Toolkit. The
 * important distinctive property of all Views is abiltiy
 * to render themselves (produce HTML) automatically and
 * recursively.
 *
 * @link http://agiletoolkit.org/learn/understand/view
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4 
    http://agiletoolkit.org/
  
   (c) 2008-2012 Romans Malinovskis <romans@agiletoolkit.org>
   Distributed under Affero General Public License v3
   
   See http://agiletoolkit.org/about/license
 =====================================================ATK4=*/
abstract class AbstractView extends AbstractObject {
    /**
     * $template is an SMLite object containing indexed HTML
     * template. 
     *
     * Example:
     *
     * $view->template->set('title', $my_title);
     *
     * Assuming you have tag <?$template?> in template file associated
     * with this view - will insert text into this tag.
     *
     * @see AbstractObject::add();
     * @see AbstractView::defaultTemplate();
     */
    public $template=false;

    /**
     * @internal
     *
     * $template_flush is set to a spot on the template, which
     * should be flushed out. When using AJAX we want to show
     * only certain region from our template. However several
     * childs may want to put their data. This property will
     * be set to region's name my call_ajax_render and if it's
     * set, call_ajax_render will echo it and return false.
     */
    public $template_flush=false;

    /**
     * $spot defines a place on a parent's template where render() will
     * output() resulting HTML
     *
     * @see output()
     * @see render()
     * @see AbstractObject::add();
     * @see defaultSpot();
     */
    public $spot;

    /**
     * When using setModel() with Views some views will want to populate
     * fields, columns etc corresponding to models meta-data. That is the
     * job of Controller. When you create a custom controller for your view
     * set this property to point at your controller and it will be used
     * automatically */
    public $default_controller=null;

    public $auto_track_element=true;

    // {{{ Basic Operations
    /** Duplicate view and it's template. Will not duplicate children */
    function __clone(){
        throw $this->exception('Can\'t clone Views');
        //parent::__clone();
        //if($this->template)$this->template=clone $this->template;
    }
    /** Get associated model. It's safe to access $object->model directly. */
    function getModel(){
        return $this->model;
    }
    /** Associate view with a model. Different models may behave differently. */
    function setModel($model,$actual_fields=undefined){
        parent::setModel($model);

        // Some models will want default controller to be associated
        if($this->model->default_controller){
            $this->controller = $this->model->setController($this->model->default_controller);
        }

        // Use our default controller if present
        if($this->default_controller){
            $this->controller = $this->setController($this->default_controller);
            if($this->controller->hasMethod('setActualFields'))$this->controller->setActualFields($actual_fields);
            if($this->controller->hasMethod('_bindView'))$this->controller->_bindView();
        }
        if($this->model instanceof Model_Table)$this->dq=$this->model->_dsql();    // compatibility

        return $this->model;
    }

    /** @internal  used by getHTML */
    public $_tsBuffer='';
    function _tsBuffer($t,$data){
        $this->_tsBuffer.=$data;
    }
    /** Converting View into string will render recursively and produce HTML. If argument is passed, JavaScript will be added 
        * into on_ready section of your document like when rendered normally. Note that you might require to destroy object 
        * if you don't want it's HTML to appear normally */
    function getHTML($destroy=true,$execute_js=true){
        $this->addHook('output',array($this,'_tsBuffer'));
        $this->recursiveRender();
        $this->removeHook('output',array($this,'_tsBuffer'));
        $ret=$this->_tsBuffer;
        $this->_tsBuffer='';
        if($execute_js && $this->api->jquery)$this->api->jquery->getJS($this);
        if($destroy)$this->destroy();
        return $ret;
    }

    // }}}

    // {{{ Template Setup

    /** @internal Called automatically during init for template initalization */
    function initializeTemplate($template_spot=null,$template_branch=null){
        if(!$template_spot)$template_spot=$this->defaultSpot();
        $this->spot=$template_spot;
        if($this->owner->template && 
                !$this->owner->template->is_set($this->spot))throw
            $this->exception('Spot is not found in owner\'s template')
                ->addMoreInfo('spot',$this->spot);
        if(!isset($template_branch))$template_branch=$this->defaultTemplate();
        if(isset($template_branch)){

            // template branch would tell us what kind of template we have to use. Let's
            // look at several cases
            if(is_object($template_branch)){        // it might be already SMlite instance (object)
                $this->template=$template_branch;   // so we just use that
            }else if(is_array($template_branch)){       // it might be array with [0]=template, [1]=tag
                if(is_object($template_branch[0])){     // if [0] is object, we'll use that
                    $this->template=$template_branch[0];
                }else{
                    $this->template=$this->api->add('SMlite');
                    $this->template->loadTemplate($template_branch[0]);    // we'll use it as a file
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
        if($this->template)$this->template->trySet('_name',str_replace('/','_',$this->name));

    }
    /** @internal Lets API auto-fill some tags in all views (such as tempalte tag) */
    function initTemplateTags(){
        if($this->template && $this->api && method_exists($this->api, 'setTags')){
            $this->api->setTags($this->template);
        }
    }

    /** Redefine to return default template, when 4th argument of add() is omitted */
    function defaultTemplate(){
        return $this->spot;
    }
    /** Default tag in parent's template where output is inserted, when 3rd argument of add() is omitted */
    function defaultSpot(){
        return 'Content';
    }
    // }}}

    // {{{ Rendering, see http://agiletoolkit.org/learn/understand/api/exec
    /** Recursively renders all views. Calls render() for all or for the one being cut. In some cases
     * you may want to redefine this function instead of render(). The difference is that this function
     * is called before sub-views are rendered, but render() is called after.
     *
     * function recursiveRender(){
     *   $this->add('Text')->set('test');
     *   return parent::recursiveRender();
     * }
     **/
    function recursiveRender(){
        $cutting_here=false;
        $this->initTemplateTags();

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
    /** @internal Append our chains to owner's chains. JS chains bubble up to API or object being cut */
    function moveJStoParent(){
        $this->owner->js=array_merge_recursive($this->owner->js,$this->js);
    }
    /** Default render. Generates HTML presentation of the view based on $this->template and passes
     * it to output() function which then inserts output into parent's template */
    function render(){
        /**
         * For visual objects, their default action while rendering is rely on SMlite engine.
         * For sake of simplicity and speed you can redefine this method with a simple call
         */
        if(!($this->template)){
            throw $this->exception("You should specify template for this object")
                ->addMoreInfo('object',$this->name);
        }
        if($this->model && is_object($this->model) && $this->model->loaded())$this->template->set($this->model->get());
        $this->output($this->template->render());
    }
    /** Low level output function which append's to the parent object's template. Normally you wouldn't want
     * to use this function but should modify $this->template instead. */
    function output($txt){
        if(!$this->hook('output',array($txt))){
            if((isset($this->owner->template)) && (!empty($this->owner->template)))
                $this->owner->template->append($this->spot,$txt,false);
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
            $this->recursiveRender();
            if($this->template)echo $this->template->render();
            else $this->render();
            return false;
        }
    }
    // }}}

    // {{{ Object JavaScript Interface
    public $js=array();
    /**
     * Function js() will return jQuery chain and, if first argument was specified, bind
     * the chain to a certain enent. Use js() to bind views with JavaScript plugins and
     * calls defined in univ() chain or in 3rd party plugins.
     *
     * js([action], [other_chain]);
     *
     * Action can represent javascript event, such as "click" or "mouseenter". If you
     *  specify action = true, then the event will ALWAYS be executed on pageload. It
     * will also be executed if respective view is being reloaded by js()->reload()
     * (Do not make mistake by specifying "true" instead of true)
     *
     * action = false will still return jQuery chain but will not bidn it. You can bind
     * it by passing to a different object's js() call as 2nd argument or by executing
     * chain.
     *
     * 1. Calling with arguments:
     *
     * $view->js();					    // does nothing
     * $a = $view->js()->hide();        // creates chain for hiding $view but does not
     *                                  // bind to event yet.
     *
     * 2. Binding existing chains
     * $img->js('mouseenter', $a);      // binds previously defined chain to event on
     *                                  // event of $img.
     *
     * Produced code: $('#img_id').click(function(ev){ ev.preventDefault(); $('view1').hide(); });
     *
     * 3. $button->js('click',$form->js()->submit());
     *                                  // clicking button will result in form submit
     *
     * 4. $view->js(true)->find('.current')->text($text);
     *
     * Will convert calls to jQuery chain into JavaScript string:
     *  $('#view').find('.current').text('abc');    // The $text will be json-encoded
     *                                              // to avoid JS injection.
     *
     * 5. ON YOUR OWN RISK
     *
     *  $view->js(true,'alert(123)');
     *
     *  Will inject javascript un-escaped portion of javascript into chain. If you need to have
     *  a custom script then put it into file instead, save into templates/js/myfile.js and then
     *  include:
     *
     *  $view->js()->_load('myfile');
     *
     *  It's highly suggested to bind your libraries with jQuery namespace by registered them
     *  as plugins, this way you can call your function easily:
     *
     *  $view->js(true)->_load('myfile')->myplugin('myfunc',array($arg,$arg));
     */
    function js($when=null,$code=null,$instance=null){
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
    // }}}
}
