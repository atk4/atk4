<?php
/**
 * Base component for wrapping content into something
 */
class sw_wrap extends sw_component {
    function init(){
        parent::init();

        $this->surroundBy();
        $this->template->trySet('_page',$this->owner->page);
        $this->template->trySet('_parent',$this->owner->parent);
        $this->template->trySet('_title',$this->owner->title);
        $this->template->trySet('_base',$this->api->base_path);
		$this->template->trySet('_subdir', $this->owner->subdir);
    }
}
