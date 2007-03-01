<?php
/**
 * Lister object
 */
class sw_list extends sw_wrap { 
    function init(){
        parent::init();

        $i=$this->cloneRegion('item');
        if($this->template->is_set('separator')){
            $separator=$this->cloneRegion('separator')->render();
        }else{
            $separator=null;
        }
        $this->template->del('items');

        $need_separator=false;
        // Now find all "item" inside the ref template
        foreach(array_keys($this->ref->template) as $tag){
            list($class,$junk)=split('#',$tag);
            if($class!='item')continue;
            $item_tpl=$this->ref->cloneRegion($tag);

            $this->grabTags($item_tpl);
            $i->set($this->data);

            $i->set('text',$item_tpl->render());

            if($need_separator && $separator){
                $this->template->append('items',$separator);
            }
            $need_separator=true;

            //$this->tmpl['item']->set('text',$this->ref->get($tag));
            $this->template->append('items',$this->tmpl['item']->render());
        }
    }
}
