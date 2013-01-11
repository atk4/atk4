<?php // vim:ts=4:sw=4:et:fdm=marker
/*
 * Undocumented
 *
 * @link http://agiletoolkit.org/
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
/**
  * Really fast template parser.
  *
  * This parser is based on SMlite, but is 2-3 times faster than it. Symantically it 
  * works in the same way, but 
  */

class GiTemplate extends AbstractModel {

    public $template=array();
    // Parsed template consists of String, String, String, String, String.
    //   If there is tag on any of those, it will have reference from $tags array
    public $tags=array();

    public $top_tags=array('_top');

    public $default_exception='Exception_Template';

    public $template_file=null;

    public $source='';

    public $template_type='template';

    function __clone(){
        parent::__clone();

        $x=unserialize(serialize($this->template));
        unset($this->template);
        $this->template=$x;

        unset($this->tags);
        $this->rebuildTags();
    }
    function isTopTag($tag){
        return in_array($tag,$this->top_tags);
    }
    function getTagRef($tag,&$template){
        if($this->isTopTag($tag)){
            $template=&$this->template;
            return $this;
        }
        @list($tag,$ref)=explode('#',$tag);
        if(!$ref)$ref=1;
        if(!isset($this->tags[$tag][$ref-1])){
            throw $this->exception('Tag not found in Template')
                ->setTag($tag);
        }
        $template=$this->tags[$tag][$ref-1];
        return $this;
    }
    function getTagRefList($tag,&$template){
        if($this->isTopTag($tag)){
            $template=&$this->template;
            return false;
        }
        @list($tag,$ref)=explode('#',$tag);
        if(!$ref){
            if(!isset($this->tags[$tag])){
                throw $this->exception('Tag not found in Template')
                    ->setTag($tag);
            }
            $template=$this->tags[$tag];
            return true;
        }
        if(!isset($this->tags[$tag][$ref-1])){
            throw $this->exception('Tag not found in Template')
                ->setTag($tag);
        }
        $template=&$this->tags[$tag][$ref-1];
        return false;
    }
    function is_set($tag){
        var_Dump($tag);
        if($this->isTopTag($tag))return true;
        @list($tag,$ref)=explode('#',$tag);
        if(!$ref)$ref=1;
        var_Dump(isset($this->tags[$tag][$ref-1]));
        return isset($this->tags[$tag][$ref-1]);
    }
    function cloneRegion($tag){
        if($this->isTopTag($tag))return clone $this;

        $n=$this->owner->add(get_class($this));
        $n->template=$this->get($tag);
        $n->rebuildTags();
        $n->top_tags[]=$tag;
        $n->source='Clone ('.$tag.') of '.$this->source;
        return $n;
    }
    function dumpTags(){
        throw $this->exception('Requested to dump tags');
    }
    function get($tag){
        $template=array();
        $this->getTagRef($tag,$template);
        return $template;
    }
    function append($tag,$value,$delim=false){
        $this->getTagRef($tag,$template);
        if($delim)$template[]=$delim;
        $template[]=$value;
        return $this;
    }
    function set($tag,$value=null){
        if(is_array($tag)){
            if(is_null($value)){
                // USE(2)
                foreach($tag as $s=>$v){
                    $this->trySet($s,$v);
                }
                return $this;
            }
            if(is_array($value)){
                // USE(2)
                reset($tag);reset($value);
                while(list(,$s)=each($tag)){
                    list(,$v)=each($value);
                    $this->set($s,$v);
                }
                return $this;
            }
            throw $this->exception("Incorrect argument types when calling Template->set()");
        }

        if($this->getTagRefList($tag,$template)){
            foreach($template as $key=>&$ref){
                //var_Dump($template[$key]);
                $ref=array($value);
            }
        }else{
            $template=array($value);
        }
        return $this;
    }
    function trySet($tag,$value=null){
        if($this->is_set($tag) || is_array($tag))return $this->set($tag,$value);
        return $this;
    }
    function del($tag){
        if($this->getTagRefList($tag,$template)){
            foreach($template as $ref){
                $ref=array();
            }
        }else{
            $template=array();
        }
        return $this;
    }
    function tryDel($tag){
        if($this->is_set($tag))return $this->del($tag);
        return $this;
    }
    function eachTag($tag,$callable){
        if(!$this->is_set($tag))return $this;
        if($this->getTagRefList($tag,$template)){
            foreach($template as $key=>$templ){
                $ref=$tag.'#'.($key+1);
                $this->tags[$tag][$key]=array(call_user_func($callable,$this->recursiveRender($templ),$ref));
            }
        }else{
            $this->tags[$tag][0]=array(call_user_func($callable,$this->recursiveRender($template),$tag));
        }
        return $this;
    }

    function findTemplate($template_name){
        /*
         * Find template location inside search directory path
         */
        $f=$this->api->locatePath($this->template_type,$template_name.$this->settings['extension']);
        return join('',file($f));
    }
    function loadTemplate($template_name,$ext=null){
        /*
         * Load template from file
         */
        if($ext){
            $tempext=$this->settings['extension'];
            $this->settings['extension']=$ext;
        };
        $this->tmp_template = $this->findTemplate($template_name);
        $this->template_file=$template_name;

        if(!isset($this->tmp_template))
            throw $this->exception("Template not found")
               ->setTemplate($template_name.$this->settings['extension']);

        $this->loadTemplateFromString($this->tmp_template);
        $this->source='file '.$template_name;
        if($ext){ $this->settings['extension']=$tempext; }
        return $this;
    }
    function loadTemplateFromString($str){
        $this->source='string';
        $this->template=$this->tags=array();
        if(!$str){
            return;
        }


        /* First expand self-closing tags <?$tag?> -> <?tag?><?/tag?> */
        $str=preg_replace('/<\?\$([\w]+)\?>/s','<?\1?><?/\1?>',$str);

        var_Dump($str);
        /* Next fix short ending tag <?tag?>  <?/?> -> <?tag?>  <?/?> */
        $x=preg_replace_callback('/<\?([^\/][^>]*)\?>(?:(?:(?!<\?\/\?>).)++|(?R))*<\?\/\?>/s',function($x){
                var_Dump($x);
                /*return preg_replace('/(.*<\?([^\/][\w]+)\?>)(.*?)(<\?\/?\?>)/s','\1\3<?/\2?>',$x[0]); */
                },$str);
        var_Dump($str);

        /* Finally recursively build tag structure */
        $this->recursiveParse($x);

        $this->tags['_top'][]=&$this->template;
        return $this;
    }
    function recursiveParse($x){

        if(is_array($x)){
            // Called recursively
            $tmp2=$this->template;$this->template=array();
        }else{
            $x=array(4=>$x);
            $tmp2=null;
        }
        $y=preg_replace_callback('/(.*?)(<\?([^\/$][\w]+)\?>)(.*?)(<\?\/(\3)?\?>)(.*?)/s',array($this,'recursiveParse'),$x[4]);
        $this->template[]=$y;
        if($tmp2===null)return;
        $tmp=$this->template;
        $this->template=$tmp2;

        $this->template[]=$x[1];
        $this->template[$x[3].'#'.count($this->tags[$x[3]])]=$tmp;
        $this->tags[$x[3]][]=&$this->template[$x[3].'#'.count($this->tags[$x[3]])];
        return '';
    }
    function rebuildTags(){
        $this->tags=array();
        $old=$this->template;
        $this->template=array();
        $this->rebuildTagsRegion($old,$this->template);
        //$this->template=unserialize(serialize($this->template));
        //$this->rebuildTagsRegion($this->template);
    }
    function rebuildTagsRegion(&$old,&$new){
        //var_dump($old,$new);
        foreach($old as $tag=>$val){
            if(is_numeric($tag)){
                $new[]=$val;
                continue;
            }
            @list($key,$ref)=explode('#',$tag);

            $new[$c=$key.'#'.count($this->tags[$key])]=array();
            $this->tags[$key][]=&$new[$c];
            $this->rebuildTagsRegion($old[$tag],$new[$c]);
        }
        //echo "------------------------------------------<br/>";
        //var_dump($old,$new);
    }
    function render($region=null){
        if($region)return $this->recursiveRender($this->get($region));
        return $this->recursiveRender($this->template);
    }
    function recursiveRender(&$template){
        $s='';
        foreach($template as $val){
            if(is_array($val)){
                $s.=$this->recursiveRender($val);
            }else{
                $s.=$val;
            }
        }
        return $s;
    }
}

class Exception_Template extends BaseException {
    function init(){
        parent::init();
        if($this->owner->template_file)
            $this->addMoreInfo('file',$this->owner->template_file);

        $keys=array_keys($this->owner->tags);
        if($keys)$this->addMoreInfo('keys',implode(', ',$keys));

        if($this->owner->source)$this->addMoreInfo('source',$this->owner->source);
    }
    function setTag($t){
        $this->addMoreInfo('tag',$t);
        return $this;
    }
}
