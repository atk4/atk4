<?php
class ReloadableView extends AbstractView {
    /*
     * This is a type of view which can be reloaded through
     * $ajax->reload. The idea of this view is that it will be rendered inside
     * <div id=<?$_name?>> ... </div> when it's inserted into other
     * template, however if it's requested through cut_object, it's returned
     * without this div tag to avoid duplicate IDs on the page. It's
     * advisable to use this as a parent object instead of View or AbstractView
     * if you plan to reload your element. Also - if you cannot inherit your
     * class from this - don't worry - initialize a simple element of this
     * class and add yours into it's Content.
     */
    function renderLoadingDiv(){
        $this->output('<div id="RD_'.$this->name.'" style="display: none; position:absolute; width:100; background: red;color: white; font-weight: bold"> <center>....Loading....</center></div>');
    }
    function recursiveRender(){
        if($_GET['cut_object']){
            $this->renderLoadingDiv();
            return parent::recursiveRender();
        }
        $this->output('<div id="RR_'.$this->name.'">');
        $this->renderLoadingDiv();
        $result = parent::recursiveRender();
        $this->output('</div>');
        return $result;
    }
    function reload($ajax=null,$args=array()){
        if(!$ajax)$ajax=$this->add('Ajax');
        $args['cut_object']=$this->name;
        $url=$this->api->getDestinationURL(null,$args);
        $ajax->setVisibility("RD_".$this->name);
        $ajax->loadRegionURL("RR_".$this->name,$url);
        return $ajax;
    }
}
?>
