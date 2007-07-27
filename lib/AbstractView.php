<?php
abstract class AbstractView extends AbstractObject {

    /**
     * $template describes how this object is rendered. Template
     * can be either string or array or SMlite cloned object.
     * For containers, who have sub-elements which render themself
     * using SMlite this should be an object.
     */
    public $template=false;

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

    /**
     * Debug switch. Turn this on if you want this object to generate debug
     * information. Turn $api->debug to have all debug information
     */
    public $debug = null;

    /////////////// T E M P L A T E S ///////////////////////////
	function initializeTemplate($template_spot=null,$template_branch=null){
        /**
         * This class will be called BEFORE init(), so that it can prepare template
         * for us to use
         */
        if(!$template_spot)$template_spot='Content';
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
                    "loading $template_branch[0]<br />";
                    $this->template=$this->api->add('SMlite')   // or if it's string
                        ->loadTemplate($template_branch[0]);    // we'll use it as a file
                }
                // Now that we loaded it, let's see which tag we need to cut out
                $this->template=$this->template->cloneRegion($template_branch[1]);
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
        if(isset($template_spot)){
            $this->spot=$template_spot;
            if($this->owner && (isset($this->owner->template)) && (!empty($this->owner->template))){
		$this->owner->template->del($template_spot);
            }
        }


        // Cool, now let's set _name of this template
        if($this->template)$this->template->trySet('_name',$this->name);

    }
    function defaultTemplate(){
        return null;//"_top";
    }



    /////////////// H T M L   H e l p e r ///////////////////////
    function recursiveRender(){
        $cutting_here=false;
        $this->debug("Recursively rendering ".$this->__toString());
        if($this->hook('pre-recursive-render'))return;

        if(isset($_GET['cut_object']) && ($_GET['cut_object']==$this->name || $_GET['cut_object']==$this->short_name)){
            // If we are cutting here, render childs and then we are done
            unset($_GET['cut_object']);
            $cutting_here=true;
        }

        foreach($this->elements as $key=>$obj){
            if($obj instanceof AbstractView)$obj->recursiveRender();
        }

        if(!isset($_GET['cut_object'])){
            $this->render();
        }
        $this->hook('post-recursive-render');
        $this->debug("Rendering complete ".$this->__toString());
        if($cutting_here){
            $result=$this->owner->template->cloneRegion($this->spot)->render();
            $e=new RenderObjectSuccess($result);
            throw $e;
        }
    }
    function render(){
        /**
         * For visual objects, their default action while rendering is rely on SMlite engine.
         * For sake of simplicity and speed you can redefine this method with a simple call
         */
        if(!($this->template)){
            throw new BaseException("You should specify template for this object");
        }
        $this->output($this->template->render());
    }
    function output($txt){
        if((isset($this->owner->template)) && (!empty($this->owner->template)))
        	$this->owner->template->append($this->spot,$txt);
    }
    function region_render(){
        /**
         * if GET['ajax'] is set, we need only one chunk of a page
         */
        if($this->template_flush){
            echo $this->template->cloneRegion($this->template_flush)->render();
            return false;
        }
        $this->render();
        if($this->spot==$_GET['cut_region']){
            $this->owner->template_flush=$_GET['cut_region'];
        }
    }
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
	final function getDefaultTemplate(){
		throw new ObsoleteException("getDefaultTemplate is");
	}


    function frame($spot,$title=null,$p=null,$opt=''){
        /*
         * This function is just a shortcut in creating a frame
         */
        if(!$p)$p=$this;
        if(!isset($title)){
            $title=$spot;
            $spot='Content';
        }
        $f=$p->add('View','frame_'.(++$this->frame),$spot,array('shared','msgbox'));
        $f->template->set('title',$title);
        $f->template->trySet('opt',$opt);
        return $f;
    }
}
