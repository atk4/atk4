<?php
/**
 * Location component.
 * Draws a location bar on a page
 */
class sw_location extends sw_wrap { 
    function init(){
        parent::init();
        $item=$this->template->cloneRegion('path_part');
        $this->template->del('path');

		// adding common Home element
        $item->set('link','index');//$this->api->getConfig('base_path'));
        $item->set('content','Home');
        $this->template->append('path',$item->render());

		$location=$this->api->getConfig('menu');
        foreach($this->owner->path as $index=>$link){
            $location=$index==0?$location[$link]:$location['submenu'][$link];
            $title=is_array($location)?$location['title']:$location;
            if($title==$this->owner->title)continue;
            $item->set('link',$link);
            $item->set('content',$title);
            $this->template->append('path',$item->render());
        }
    }
}
