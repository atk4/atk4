<?php
/**
 * A base class for all Visual objects in Agile Toolkit. The
 * important distinctive property of all Views is abiltiy
 * to render themselves (produce HTML) automatically and
 * recursively.
 */
abstract class AbstractView extends AbstractObject
{
    /**
     * $template is an object containing indexed HTML template.
     *
     * Example:
     *
     * $view->template->set('title', $my_title);
     *
     * Assuming you have tag <?$title?> in template file associated
     * with this view - will insert text into this tag.
     *
     * @see AbstractObject::add();
     * @see AbstractView::defaultTemplate();
     *
     * @var Template
     */
    public $template = false;

    /**
     * @internal
     *
     * $template_flush is set to a spot on the template, which
     * should be flushed out. When using AJAX we want to show
     * only certain region from our template. However several
     * childs may want to put their data. This property will
     * be set to region's name my call_ajax_render and if it's
     * set, call_ajax_render will echo it and return false.
     *
     * @var string
     */
    public $template_flush = false;

    /**
     * $spot defines a place on a parent's template where render() will
     * output() resulting HTML.
     *
     * @see output()
     * @see render()
     * @see AbstractObject::add();
     * @see defaultSpot();
     *
     * @var string
     */
    public $spot;

    /**
     * When using setModel() with Views some views will want to populate
     * fields, columns etc corresponding to models meta-data. That is the
     * job of Controller. When you create a custom controller for your view
     * set this property to point at your controller and it will be used.
     * automatically.
     *
     * @var string
     */
    public $default_controller = null;

    /**
     * @var boolean
     */
    public $auto_track_element = true;

    /**
     * @var array of jQuery_Chains
     */
    public $js = array();

    /**
     * Using dq property looks obsolete, but left for compatibility
     *
     * @see self::setModel()
     * @var DB_dsql
     */
    public $dq;


    // {{{ Basic Operations

    /**
     * For safety, you can't clone views. Use $view->newInstance instead.
     */
    public function __clone()
    {
        throw $this->exception('Can\'t clone Views');
    }
    /**
     * Associate view with a model. Additionally may initialize a controller
     * which would copy fields from the model into the View.
     *
     * @param object|string $model Class without "Model_" prefix or object
     * @param array|string|null $actual_fields List of fields in order to populate
     *
     * @return AbstractModel object
     */
    public function setModel($model, $actual_fields = UNDEFINED)
    {
        parent::setModel($model);

        // Some models will want default controller to be associated
        if ($this->model->default_controller) {
            $this->controller
                = $this->model->setController($this->model->default_controller);
        }

        // Use our default controller if present
        if ($this->default_controller) {
            $this->controller = $this->setController($this->default_controller);
        }

        if ($this->controller) {
            if ($this->controller->hasMethod('setActualFields')) {
                $this->controller->setActualFields($actual_fields);
            }
            if ($this->controller->hasMethod('_bindView')) {
                $this->controller->_bindView();
            }
        }

        if ($this->model instanceof SQL_Model) {
            $this->dq = $this->model->_dsql();    // compatibility
        }

        return $this->model;
    }

    /** @internal  used by getHTML */
    public $_tsBuffer = '';
    /** @internal accumulates output for getHTML */
    public function _tsBuffer($t, $data)
    {
        $this->_tsBuffer .= $data;
    }

    /**
     * Converting View into string will render recursively and produce HTML.
     * If argument is passed, JavaScript will be added into on_ready section
     * of your document like when rendered normally. Note that you might
     * require to destroy object if you don't want it's HTML to appear normally.
     *
     * @param bool $destroy    Destroy object preventing it from rendering
     * @param bool $execute_js Also capture JavaScript chains of object
     *
     * @return string HTML
     */
    public function getHTML($destroy = true, $execute_js = true)
    {
        $this->addHook('output', array($this, '_tsBuffer'));
        $this->recursiveRender();
        $this->removeHook('output', array($this, '_tsBuffer'));
        $ret = $this->_tsBuffer;
        $this->_tsBuffer = '';
        if ($execute_js && isset($this->app->jquery)) {
            /** @type App_Web $this->app */
            $this->app->jquery->getJS($this);
        }
        if ($destroy) {
            $this->destroy();
        }

        return $ret;
    }
    // }}}

    // {{{ Template Setup

    /**
     * Called automatically during init for template initalization.
     *
     * @param string       $template_spot   Where object's output goes
     * @param string|array $template_branch Where objects gets it's template
     *
     * @return AbstractView $this
     *
     * @internal
     */
    public function initializeTemplate($template_spot = null, $template_branch = null)
    {
        if ($template_spot === null) {
            $template_spot = $this->defaultSpot();
        }
        $this->spot = $template_spot;
        if (@$this->owner->template
            && !$this->owner->template->is_set($this->spot)
        ) {
            throw $this->owner->template->exception(
                'Spot is not found in owner\'s template'
            )->addMoreInfo('spot', $this->spot);
        }
        if (!isset($template_branch)) {
            $template_branch = $this->defaultTemplate();
        }
        if (isset($template_branch)) {
            // template branch would tell us what kind of template we have to
            // use. Let's look at several cases:

            if (is_object($template_branch)) {
                // it might be already template instance (object)
                $this->template = $template_branch;
            } elseif (is_array($template_branch)) {
                // it might be array with [0]=template, [1]=tag
                if (is_object($template_branch[0])) {
                    // if [0] is object, we'll use that
                    $this->template = $template_branch[0];
                } else {
                    $this->template = $this->app->add('Template');
                    /** @type Template $this->template */
                    $this->template->loadTemplate($template_branch[0]);
                }
                // Now that we loaded it, let's see which tag we need to cut out
                $this->template = $this->template->cloneRegion(
                    isset($template_branch[1]) ? $template_branch[1] : '_top'
                );
            } else {
                // brach could be just a string - a region to clone off parent
                if (isset($this->owner->template)) {
                    $this->template
                        = $this->owner->template->cloneRegion($template_branch);
                } else {
                    $this->template = $this->add('Template');
                }
            }

            /** @type Template $this->template */
            $this->template->owner = $this;
        }

        // Now that the template is loaded, let's take care of parent's template
        if ($this->owner
            && (isset($this->owner->template))
            && (!empty($this->owner->template))
        ) {
            $this->owner->template->del($this->spot);
        }

        // Cool, now let's set _name of this template
        if ($this->template) {
            $this->template->trySet('_name', $this->getJSID());
        }
    }

    /**
     * This method is called to automatically fill in some of the tags in this
     * view. Normally the call is bassed to $app->setTags(), however you can
     * extend and add more tags to fill.
     */
    public function initTemplateTags()
    {
        if ($this->template
            && $this->app->hasMethod('setTags')
        ) {
            /** @type App_Web $this->app */
            $this->app->setTags($this->template);
        }
    }

    /**
     * This method is commonly redefined to set a default template for an object.
     * If you return string, object will try to clone specified region off the
     * parent. If you specify array, it will load and parse a separate template.
     *
     * This is overriden by 4th argument in add() method
     *
     * @return string Template definition
     */
    public function defaultTemplate()
    {
        return $this->spot;
    }

    /**
     * Normally when you add a view, it's output is placed inside <?$Content?>
     * tag of its parent view. You can specify a different tag as 3rd argument
     * for the add() method. If you wish for object to use different tag by
     * default, you can override this method.
     *
     * @return string Tag / Spot in $this->owner->template
     */
    public function defaultSpot()
    {
        return 'Content';
    }
    // }}}

    // {{{ Rendering, see http://agiletoolkit.org/learn/understand/api/exec
    /**
     * Recursively renders all views. Calls render() for all or for the one
     * being cut. In some cases you may want to redefine this function instead
     * of render(). The difference is that this function is called before
     * sub-views are rendered, but render() is called after.
     *
     * function recursiveRender(){
     *   $this->add('Text')->set('test');
     *   return parent::recursiveRender(); // will render Text also
     * }
     *
     * When cut_object is specified in the GET arguments, then output
     * of HTML would be limited to object with matching $name or $short_name.
     *
     * This method will be called instead of default render() and it will
     * stop rendering process and output object's HTML once it finds
     * a suitable object. Exception_StopRender is used to terminate
     * rendering process and bubble up to the APP. This exception is
     * not an error.
     */
    public function recursiveRender()
    {
        if ($this->hook('pre-recursive-render')) {
            return;
        }

        $cutting_here = false;
        $cutting_output = '';

        $this->initTemplateTags();

        if (isset($_GET['cut_object'])
            && ($_GET['cut_object'] == $this->name
            || $_GET['cut_object'] == $this->short_name)
        ) {
            // If we are cutting here, render childs and then we are done
            unset($_GET['cut_object']);
            $cutting_here = true;

            $this->addHook('output', function ($self, $output) use (&$cutting_output) {
                $cutting_output .= $output;
            });
        }

        if ($this->model
            && is_object($this->model)
            && $this->model->loaded()
        ) {
            $this->modelRender();
        }

        foreach ($this->elements as $key => $obj) {
            if ($obj instanceof self) {
                $obj->recursiveRender();
                $obj->moveJStoParent();
            }
        }

        if (!isset($_GET['cut_object'])) {
            if (isset($_GET['cut_region'])) {
                $this->region_render();
            } else {
                $this->render();
            }
        }

        if ($cutting_here) {
            //$result=$this->owner->template->cloneRegion($this->spot)->render();
            if (isset($this->app->jquery)) {
                /** @type App_Web $this->app */
                $this->app->jquery->getJS($this);
            }
            throw new Exception_StopRender($cutting_output);
        }
        // if template wasn't cut, we move all JS chains to parent
    }

    /**
     * When model is specified for a view, values of the model is
     * inserted inside the template if corresponding tags exist.
     * This is used as default values and filled out before
     * the actual render kicks in.
     */
    public function modelRender()
    {
        $this->template->set($this->model->get());
    }

    /**
     * Append our chains to owner's chains. JS chains bubble up to
     * app, which plugs them into template. If the object is being
     * "cut" then only relevant chains will be outputed.
     */
    public function moveJStoParent()
    {
        /** @type AbstractView $this->owner */
        $this->owner->js = array_merge_recursive($this->owner->js, $this->js);
    }

    /**
     * Default rendering method. Generates HTML presentation of $this view.
     * For most views, rendering the $this->template would be sufficient.
     *
     * If your view requires to do some heavy-duty work, please be sure to do
     * it inside render() method. This way would save some performance in cases
     * when your object is not being rendered.
     *
     * render method relies on method output(), which appeends HTML chunks
     * to the parent's template.
     */
    public function render()
    {
        if (!($this->template)) {
            throw $this->exception('You should specify template for this object')
                ->addMoreInfo('object', $this->name)
                ->addMoreInfo('spot', $this->spot);
        }
        $this->output(($render = $this->template->render()));
        if (@$this->debug) {
            echo '<font color="blue">'.htmlspecialchars($render).'</font>';
        }
    }

    /**
     * Low level output function which append's to the parent object's
     * template. For normal objects, you simply need to specify a suitable
     * template.
     *
     * @param string $txt HTML chunk
     */
    public function output($txt)
    {
        if (!is_null($this->hook('output', array($txt)))) {
            if (isset($this->owner->template)
                && !empty($this->owner->template)
            ) {
                $this->owner->template->append($this->spot, $txt, false);
            } elseif ($this->owner instanceof App_CLI) {
                echo $txt;
            }
        }
    }

    /**
     * When "cut"-ing using cut_region we need to output only a specified
     * tag. This method of cutting is mostly un-used now, and should be
     * considered obsolete.
     *
     * @deprecated 4.3.1
     */
    public function region_render()
    {
        throw $this->exception('cut_region is now obsolete');

        /*
        if ($this->template_flush) {
            if ($this->app->jquery) {
                $this->app->jquery->getJS($this);
            }
            throw new Exception_StopRender(
                $this->template->cloneRegion($this->template_flush)->render()
            );
        }
        $this->render();
        if ($this->spot == $_GET['cut_region']) {
            $this->owner->template_flush = $_GET['cut_region'];
        }
        */
    }

    // }}}

    // {{{ Object JavaScript Interface
    /**
     * Views in Agile Toolkit can assign javascript actions to themselves. This
     * is done by calling $view->js() method.
     *
     * Method js() will return jQuery_Chain object which would record all calls
     * to it's non-existant methods and convert them into jQuery call chain.
     *
     * js([action], [other_chain]);
     *
     * Action can represent javascript event, such as "click" or "mouseenter".
     * If you specify action = true, then the event will ALWAYS be executed on
     * pageload. It will also be executed if respective view is being reloaded
     * by js()->reload()
     *
     * (Do not make mistake by specifying "true" instead of true)
     *
     * action = false will still return jQuery chain but will not bind it.
     * You can bind it by passing to a different object's js() call as 2nd
     * argument or output the chain in response to AJAX-ec call by calling
     * execute() method.
     *
     * 1. Calling with arguments:
     *
     * $view->js();                   // does nothing
     * $a = $view->js()->hide();      // creates chain for hiding $view but does not
     *                                // bind to event yet.
     *
     * 2. Binding existing chains
     * $img->js('mouseenter', $a);    // binds previously defined chain to event on
     *                                // event of $img.
     *
     * Produced code: $('#img_id').click(function(ev){ ev.preventDefault();
     *    $('view1').hide(); });
     *
     * 3. $button->js('click',$form->js()->submit());
     *                                // clicking button will result in form submit
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
     * Will inject javascript un-escaped portion of javascript into chain.
     * If you need to have a custom script then put it into file instead,
     * save into templates/js/myfile.js and then  include:
     *
     *  $view->js()->_load('myfile');
     *
     * It's highly suggested to bind your libraries with jQuery namespace by
     * registered them as plugins, this way you can call your function easily:
     *
     *  $view->js(true)->_load('myfile')->myplugin('myfunc',array($arg,$arg));
     *
     * This approach is compatible with jQuery UI Widget factory and will keep
     * your code clean
     *
     * @param string|bool|null          $when     Event when chain will be executed
     * @param array|jQuery_Chain|string $code     JavaScript chain(s) or code
     * @param string                    $instance Obsolete
     *
     * @link http://agiletoolkit.org/doc/js
     *
     * @return jQuery_Chain
     */
    public function js($when = null, $code = null, $instance = null)
    {
        // Create new jQuery_Chain object
        if (!isset($this->app->jquery)) {
            throw new BaseException('requires jQuery or jUI support');
        }

        /** @type App_Web $this->app */

        // Substitute $when to make it better work as a array key
        if ($when === true) {
            $when = 'always';
        }
        if ($when === false || $when === null) {
            $when = 'never';
        }

        if ($instance !== null && isset($this->js[$when][$instance])) {
            $js = $this->js[$when][$instance];
        } else {
            $js = $this->app->jquery->chain($this);
        }

        if ($code) {
            $js->_prepend($code);
        }

        if ($instance !== null) {
            $this->js[$when][$instance] = $js;
        } else {
            $this->js[$when][] = $js;
        }

        return $js;
    }

    /**
     * @return string
     */
    public function getJSID()
    {
        return str_replace('/', '_', $this->name);
    }

    /**
     * Views in Agile Toolkit can assign javascript actions to themselves. This
     * is done by calling $view->js() or $view->on().
     *
     * on() method implements implementation of jQuery on() method.
     *
     * on(event, [selector], [other_chain])
     *
     * Returned is a javascript chain wich is executed when event is triggered
     * on specified selector (or all of the view if selector is ommitted).
     * Optional other_chain argument can contain one or more chains (in array)
     * which will also be executed.
     *
     * The chain returned by on() will properly select affected element. For
     * example if the following view would contain multiple <a> elements, then
     * only the clicked-one will be hidden.
     *
     * on('click','a')->hide();
     *
     *
     * Other_chain can also be specified as a Callable. In this case the
     * executable code you have specified here will be called with several
     * arguments:
     *
     * function($js, $data){
     *   $js->hide();
     * }
     *
     *
     * In this case javascript method is executed on a clicked event but
     * in a more AJAX-way
     *
     * If your method returns a javascript chain, it will be executed
     * instead. You can execute both if you embed $js inside returned
     * chain.
     *
     * The third argument passed to your method contains
     */
    public function on($event, $selector = null, $js = null)
    {
        /** @type App_Web $this->app */

        if (!is_string($selector) && is_null($js)) {
            $js = $selector;
            $selector = null;
        }

        if (is_callable($js)) {
            /** @type VirtualPage $p */
            $p = $this->add('VirtualPage');

            $p->set(function ($p) use ($js) {
                /** @type VirtualPage $p */
                // $js is an actual callable
                $js2 = $p->js()->_selectorRegion();

                $js3 = call_user_func($js, $js2, $_POST);

                // If method returns something, execute that instead
                if ($js3) {
                    $p->js(null, $js3)->execute();
                } else {
                    $js2->execute();
                }
            });

            $js = $this->js()->_selectorThis()->univ()->ajaxec($p->getURL(), true);
        }

        if ($js) {
            $ret_js = $this->js(null, $js)->_selectorThis();
        } else {
            $ret_js = $this->js()->_selectorThis();
        }

        $on_chain = $this->js(true);
        $fired = false;

        $this->app->jui->addHook(
            'pre-getJS',
            function ($app) use ($event, $selector, $ret_js, $on_chain, &$fired) {
                if ($fired) {
                    return;
                }
                $fired = true;

                $on_chain->on($event, $selector, $ret_js->_enclose(null, true));
            }
        );

        return $ret_js;
    }
    // }}}
}
