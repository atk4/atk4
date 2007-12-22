<?php
class Reloadable extends AbstractController {
    /*
     * When you add Reloadable to your object, it will become 
     * capable of reloading by ajax instruction. The rendering of
     * the field is going to change - additional surrounding div
     * will be placed arround and the element will know how to
     * properly render itself
     */
    function init(){
        parent::init();
        $this->owner->reloadable=$this;
        if($this->owner instanceof Form_Field){
            // If we are reloading field, everything is much different. Rendering of an object
            // is nothing regular, it's a <tr> element. So we need to be a bit smarter about
            // placing our <div>
            if(!$this->isCut()){
                $this->owner->template->append('field_input_pre','<div id="RR_'.$this->owner->name.'">');
                $this->owner->template->append('field_input_pre','<div id="RD_'.$this->owner->name.'" style="display: none; position:absolute; width:200;font-weight: bold; background: white"><table cellspacing=0 cellpadding=0 border=0><tr><td valign=top><img alt="" src="amodules3/img/loading.gif"></td><td>&nbsp;</td><td class="smalltext" align=center><b>Loading. Stand by...</b></td></tr></table></div><!-- RD close -->');
                $this->owner->template->append('field_input_post','</div><!-- RR close -->');
                return;
            }
        }
        $this->owner->addHook('pre-recursive-render',array($this,'preRecursiveRender'));
        $this->owner->addHook('post-recursive-render',array($this,'postRecursiveRender'));
    }
    function isCut(){
        return(isset($_GET['cut_object']) && ($_GET['cut_object']==$this->owner->name || $_GET['cut_object']==$this->owner->short_name));
    }
    function renderLoadingDiv(){
        $this->owner->output('<div id="RD_'.$this->owner->name.'" style="display: none; position:absolute; width:200;font-weight: bold; background: white"><table cellspacing=0 cellpadding=0 border=0><tr><td valign=top><img alt="" src="amodules3/img/loading.gif"></td><td>&nbsp;</td><td class="smalltext" align=center><b>Loading. Stand by...</b></td></tr></table></div>');
    }
    function preRecursiveRender(){
        /*
         * If cut_object is present, then we are currently reloading
         * this object only and NOT surrounding div.
         */
        if($this->isCut()){
            $this->renderLoadingDiv();
            if($this->owner instanceof Form_Field){
                // cut the template crap
                $this->owner->template->loadTemplateFromString('<?$field_input?>');
            }
        }else{
            $this->owner->output('<div id="RR_'.$this->owner->name.'">');
            $this->renderLoadingDiv();
        }
    }
    function postRecursiveRender(){
        if(!$this->isCut()){
            $this->owner->output('</div>');
        }
    }
}
