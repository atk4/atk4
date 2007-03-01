<?php
/**
 * Base component for rendering static web objects
 * 
 */
class sw_component extends View {
    var $ref;    // link to region defined inside main template, with content

    var $content;   // same thing but rendered

    var $data=array();  // this contains structured we grabed from content

    var $tmpl=array();  // this contains collected template clones

	function init(){
		parent::init();
		$this->initNested($this->template->template);
	}
	function initNested(&$template){
        foreach(array_keys($template) as $item){
            list($class,$junk)=split('#',$item);
            if(is_numeric($class))continue;
            if(isset($this->tagmatch[$class]))$class=$this->tagmatch[$class];
            if(!$class){
                continue;
            }
            if($class[0]=='_')continue;
           	if(class_exists('sw_'.$class,false)){
           		$nested=&$this->api->add('sw_'.$class,$item,$item,$item);
           		$nested->downCall('render');
           		$nested_content=$nested->template->render();
           		$template[$item]=$nested_content;
           		return true;//$nested_content;
           	}else{
           		$nested=$this->initNested(&$template[$item]);
           	}
        }
	}

    function render(){
        if($this->api->getConfig('debug',false))$this->output('<div style="border: 1px dashed red">');
        parent::render();
        if($this->api->getConfig('debug',false))$this->output('</div>');
    }
    function grab($tag,$default=null,$template=null){
        if(!$template)$template=$this->template;
        if($template->is_set($tag)){
            list($class,$junk)=split('#',$tag);
            if(isset($this->tagmatch[$class]))$class=$this->tagmatch[$class];
           	if(!class_exists('sw_'.$class,false)){
	            $this->data[$tag]=$template->get($tag);
	            $template->del($tag);
           	}
        }else $this->data[$tag]=$default;
    }
    function cloneRegion($tag){
        $this->tmpl[$tag]=$this->template->cloneRegion($tag);
        $this->template->del($tag);
        return $this->tmpl[$tag];
    }
    function surroundBy($template,$tag){
        $this->ref = $this->template;
        list($class,$junk)=split('#',$tag);

        // This class will clone component's region from the component library
        $this->template=$this->owner->components->cloneRegion($class);

        // this is what originally was inside the tags such as
        // <!mytag!>this will become content<!/!>
        $this->content=$this->ref->render();

        // we are putting supplied content into the component. Now this will
        // only happen if a) content was specified and b) component have
        // tag for content
        if($this->content)$this->template->trySet('content',$this->content);
    }
    function grabTags($t){
        foreach(array_keys($t->tags) as $tag){
            if(strpos($tag,'#'))continue;
            $this->grab($tag,null,$t);
        }
    }
}
