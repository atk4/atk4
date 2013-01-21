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
 * ==[ About SMlite ]==========================================================
 * This class is a lightweight template engine. It's based around operating with
 * chunks of HTML code and the main aims are:
 *
 *  - completely remove any code from templates
 *  - make templates editable with HTML editor
 *
 * @author      Romans <romans@adevel.com>
 * @copyright   LGPL. See http://www.gnu.org/copyleft/lesser.html
 * @version     1.1
 * @compat      php5 (perhaps php4 untested)
 *
 * ==[ Version History ]=======================================================
 * 1.0          First public version (released with AModules3 alpha)
 * 1.1          Added support for "_top" tag
 *              Removed support for permanent tags
 *              Much more comments and other fixes
 *
 * ==[ Description ]===========================================================
 * SMlite templates are HTML pages containing tags to mark certain regions.
 * <html><head>
 *   <title>MySite.com - <?page_name?>unknown page<?/page_name?></title>
 * </head>
 *
 * Inside your application regions may be manipulated in a few ways:
 *
 *  - you can replace region with other content. Using this you can replace
 *   name of sub-page or put a date on your template.
 *
 *  - you can clone whole template or part of it. This is useful if you are
 *   working with objects
 *
 *  - you can manipulate with regions from different files.
 *
 * Traditional recipe to work with lists in our templates are:
 *
 *  1. clone template of generic line
 *  2. delete content of the list
 *  3. inside loop
 *   3a. insert values into cloned template
 *   3b. render cloned template
 *   3c. insert rendered HTML into list template
 *  4. render list template
 *
 * Inside the code I use terms 'region' and 'spot'. They refer to the same thing,
 * but I use 'spot' to refer to a location inside template (such as <?$date?>),
 * however I use 'region' when I am refering to a chunk of HTML code or sub-template.
 * Sometimes I also use term 'tag' which is like a pointer to region or spot.
 *
 * When template is loaded it's parsed and converted into array. It's possible to
 * cache parsed template serialized inside array.
 *
 * Tag name looks like this:
 *
 *  "misc/listings:student_list"
 *
 * Which means to seek tag <?student_list?> inside misc/listings.html
 *
 * You may have same tag several times inside template. For example you can
 * use tag <?$title?> inside <head><title> and <h1>.
 *
 * If you would set('title','My Title'); it will insert that value in
 * all those regions.
 *
 * ==[ AModules3 integration ]=================================================
 * Rule of thumb in object oriented programming is data / code separation. In
 * our case HTML is data and our PHP files are code. SMlite helps to completely
 * cut out the code from templates (smarty promotes idea about integrating
 * logic inside templates and I decided not to use it for that reason)
 *
* Inside AModules3, each object have it's own template or may have even several
* templates. When object is created, it's assigned to region inside template.
* Later object operates with assigned template.
*
* Each object is also assigned to a spot on their parent's template. When
* object is rendered, it's HTML is inserted into parent's template.
*
* ==[ Non-AModules3 integration ]=============================================
* SMlite have no strict bindings or requirements for AModules3. You are free
* to use it inside any other library as long as you follow license agreements.
*/

class SMlite extends AbstractModel {

    var $tags = array();
    /*
     * This array contains list of all tags found inside template.
     */

    var $top_tag = null;
    /*
     * When cloning region inside a template, it's tag becomes a top_tag of a new
     * template. Since SMlite 1.1 it's present in new template and can be used.
     */

    var $template=array();  // private
    /*
     * This is a parsed contents of the template.
     */

    var $settings=array();

    /**
     * Type of resource to look for pathFinder
     */
    var $template_type='template';

    /**
     * list of updated tags with values
     */
    public $updated_tag_list = array();

    private $cache;

    function getTagVal($tag) {
        return (isset($this->updated_tag_list[$tag]))?$this->updated_tag_list[$tag]:null;
    }

    function getDefaultSettings(){
        /*
         * This function specifies default settings for SMlite. Use
         * 2nd argument for constructor to redefine those settings.
         *
         * A small note why I decided on .html extension. I want to
         * point out that template files are and should be valid HTML
         * documents. With .html extension those files will be properly
         * rendered inside web browser, properly understood inside text
         * editor or will be properly treated with wysiwyg html editors.
         */
        return array(
                // by separating them with ':'
                'ldelim'=>'<?',                // tag delimiter
                'rdelim'=>'?>',
                'extension'=>'.html',          // template file extension
                );
    }

    // Template creation, interface functions
    function init(){
        parent::init();
        $path=array();
        $this->cache=&$this->api->smlite_cache;

        $this->settings=$this->getDefaultSettings();
        $this->settings['extension']=$this->api->getConfig('smlite/extension','.html');
    }
    function __clone(){
        if(!is_null($this->top_tag)&&is_object($this->top_tag))$this->top_tag=clone $this->top_tag;
        // may be some of the following lines are unneeded...
        $this->template=unserialize(serialize($this->template));
        $this->tags=unserialize(serialize($this->tags));
        $this->settings=unserialize(serialize($this->settings));
        $this->updated_tag_list=unserialize(serialize($this->updated_tag_list));
        // ...
        $this->rebuildTags();
    }
    function cloneRegion($tag){
        /*
         * Sometimes you will want to put branch into different class. This function will create
         * new class for you.
         */
        if($this->isTopTag($tag)){
            $class_name=get_class($this);
            $new=$this->add($class_name);
            $new->template=unserialize(serialize($this->template));
            $new->top_tag=$tag;
            $new->settings=$this->settings;
            $new->rebuildTags();
            return $new;
        }

        if(!$this->hasTag($tag)){
            $o=$this->owner?" for ".$this->owner->__toString():"";
            throw new BaseException("No such tag ($tag) in template$o. Tags are: ".join(', ',array_keys($this->tags)));
        }
        $class_name=get_class($this);
        $new=$this->add($class_name);
        try {
            $new->template=unserialize(serialize($this->tags[$tag][0]));
            if(is_string($new->template))$new->template=array($new->template);
        }catch(PDOException $e){
            var_Dump($this->tags);
            throw $this->exception('PDO got stuck in template')
                ->addMoreInfo('tag',$tag);
        }
        $new->top_tag=$tag;
        $new->settings=$this->settings;
        return $new->rebuildTags();
    }

    // Misc functions
    function dumpTags(){
        /*
         * This function is used for debug. It will output all tag names inside
         * current templates
         */
        echo "<pre>";
        var_Dump(array_keys($this->tags));
        echo "</pre>";
    }

    // Operation with regions inside template
    function get($tag){
        /*
         * Finds tag and returns contents.
         *
         * THIS FUNTION IS DANGEROUS!
         *  - if you want a rendered region, use renderRegion()
         *  - if you want a sub-template use cloneRegion()
         *
         *  - if you want to copy part of template to other SMlite object,
         *   do not forget to call rebuildTags() if you plan to refer them.
         *   Not calling rebuildTags() will render template properly anyway.
         *
         * If tag is defined multiple times, first region is returned.
         */
        if($this->isTopTag($tag))return $this->template;
        $v=$this->tags[$tag][0];
        if(is_array($v) && count($v)==1)$v=array_shift($v);
        return $v;
    }
    function appendHTML($tag,$value){
        return $this->append($tag,$value,false);
    }
    function append($tag,$value,$encode=true){
        /*
         * This appends static content to region refered by a tag. This function
         * is useful when you are adding more rows to a list or table.
         *
         * If you have specified $delim, it will be used as a separator
         * between existing content and newly appended.
         *
         * If tag is used for several regions inide template, they all will be
         * appended with new data.
         */
        if(!is_bool($encode))throw $this->exception('$delim is depreciated. Now 3rd argument is boolean');
        if($value instanceof URL)$value=$value->__toString();
        // Temporary here until we finish testing
        if($encode && $value!=htmlspecialchars($value,ENT_NOQUOTES,'UTF-8') && $this->api->getConfig('html_injection_debug',false))throw $this->exception('Attempted to supply html string through append()')
            ->addMoreInfo('val',var_export($value,true))
            ->addMoreInfo('enc',var_export(htmlspecialchars($value,ENT_NOQUOTES,'UTF-8'),true))
            //->addAction('ignore','Ignore tag'.$tag)
            ;
        if($encode)$value=htmlspecialchars($value,ENT_NOQUOTES,'UTF-8');
        if($this->isTopTag($tag)){
            /*
            if ($this->template){
                $this->template[]=$delim;
            }
             */
            $this->template[]=$value;
            return $this;
        }
        if(!isset($this->tags[$tag]) || !is_array($this->tags[$tag])){
            throw $this->exception("Cannot append to tag $tag")
                ->addMoreInfo('by',$this->owner);
        }
        foreach($this->tags[$tag] as $key=>$_){

            if(!is_array($this->tags[$tag][$key])){
                //throw new BaseException("Problem appending '".htmlspecialchars($value)."' to '$tag': key=$key");
                $this->tags[$tag][$key]=array($this->tags[$tag][$key]);
            }
            /*
            if ($this->tags[$tag][$key]){
                $this->tags[$tag][$key][]=$delim;
            }
             */
            $this->tags[$tag][$key][]=$value;
        }
        return $this;
    }
    function setHTML($tag,$value=null){
        return $this->set($tag,$value,false);
    }
    function set($tag,$value=null,$encode=true){
        /*
         * This function will replace region refered by $tag to a new content.
         *
         * If tag is used several times, all regions are replaced.
         *
         * ALTERNATIVE USE(2) of this function is to pass associative array as
         * a single argument. This will assign multiple tags with one call.
         * Sample use is:
         *
         *  set($_GET);
         *
         * would read and set multiple region values from $_GET array.
         *
         * ALTERNATIVE USE(3) of this function is to pass 2 arrays. First array
         * will contain tag names and 2nd array will contain their values.
         */
        if(is_object($tag))$tag=$tag->get();
        if(is_array($tag)){
            if(is_null($value)){
                // USE(2)
                foreach($tag as $s=>$v){
                    $this->trySet($s,$v,$encode);
                }
                return $this;
            }
            if(is_array($value)){
                // USE(2)
                reset($tag);reset($value);
                while(list(,$s)=each($tag)){
                    list(,$v)=each($value);
                    $this->set($s,$v,$encode);
                }
                return $this;
            }
            $this->fatal("Incorrect argument types when calling SMlite::set(). Check documentation.");
        }
        if($value instanceof URL)$value=$value->__toString();
        if(is_array($value))return $this;

        if($encode && $value!=htmlspecialchars($value,ENT_NOQUOTES,'UTF-8') && $this->api->getConfig('html_injection_debug',false))throw $this->exception('Attempted to supply html string through set()')
            ->addMoreInfo('val',var_export($value,true))
            ->addMoreInfo('enc',var_export(htmlspecialchars($value,ENT_NOQUOTES,'UTF-8'),true))
            //->addAction('ignore','Ignore tag'.$tag)
            ;
        if($encode)$value=htmlspecialchars($value,ENT_NOQUOTES,'UTF-8');
        if($this->isTopTag($tag)){
            $this->template=$value;
            return $this;
        }
        if(!isset($this->tags[$tag])||!is_array($this->tags[$tag])){
            $o=$this->owner?$this->owner->__toString():"none";
            throw $this->exception("No such tag in template")
                ->addMoreInfo('tag',$tag)
                ->addMoreInfo('owner',$o)
                ->addMoreInfo('tags',join(', ',array_keys($this->tags)));
        }
        foreach($this->tags[$tag] as $key=>$_){
            $this->tags[$tag][$key]=$value;
        }
        $this->updated_tag_list[$tag] = $value;
        return $this;
    }
    /** Check if tag is present inside template */
    function hasTag($tag){
        if($this->isTopTag($tag))return true;
        return isset($this->tags[$tag]) && is_array($this->tags[$tag]);

    }
    function is_set($tag){
        return $this->hasTag($tag);
    }
    function trySetHTML($tag,$value=null){
        return $this->trySet($tag,$value,false);
    }
    function trySet($tag,$value=null,$encode=true){
        /*
         * Check if tag is present inside template. If it does, execute set(); See documentation
         * for set()
         */
        if(is_array($tag))return $this->set($tag,$value,$encode);
        return $this->hasTag($tag)?$this->set($tag,$value,$encode):$this;
    }
    function del($tag){
        /*
         * This deletes content of a region, however tag remains and you can still refer to it.
         *
         * If tag is defined multiple times, content of all regions are deleted.
         */
        if($this->isTopTag($tag)){
            $this->loadTemplateFromString('<?$'.$tag.'?>');
            return $this;
            //return $this->fatal("SMlite::del() is trying to delete top tag: $tag");
        }
        if(empty($this->tags[$tag])){
            $o=$this->owner?" for ".$this->owner->__toString():"";
            throw new BaseException("No such tag ($tag) in template$o. Tags are: ".join(', ',array_keys($this->tags)));
        }
        foreach($this->tags[$tag] as $key=>$val){
            $this->tags[$tag][$key]=array();
        }
        unset($this->updated_tag_list[$tag]);
        return $this;
    }
    function tryDel($tag){
        if(is_array($tag))return $this->del($tag);
        return $this->hasTag($tag)?$this->del($tag):$this;
    }
    function eachTag($tag,$callable){
        /*
         * This function will execute $callable($text,$tag) for each
         * occurance of $tag. This is handy if one tag appears several times on the page,
         * but needs custom processing. $text will be rendered part of the template. $tag
         * will be unique reference to a tag, containing #<num> allowing you to add objects
         * from the functions
         */
        if(!isset($this->tags[$tag]))return;

        foreach($this->tags as $tagx=>$arr){
            $tag_split=explode('#',$tagx);
            $t=$tag_split[0];
            if(!isset($tag_split[1]) || $t!=$tag)continue;
            $text=$this->tags[$tagx][0][0];
            $ret=call_user_func($callable,$this->renderRegion($text),$tagx);
            if($ret instanceof URL)$ret=$ret->__toString();
            $x=$this->tags[$tagx][0][0]=$ret;
        }
    }

    // template loading and parsing
    function findTemplate($template_name){
        /*
         * Find template location inside search directory path
         */
        if(!$this->api)throw new Exception_InitError('You should use add() to add objects!');
        $f=$this->api->locatePath($this->template_type,$template_name.$this->settings['extension']);
        return join('',file($f));
    }
    function loadTemplateFromString($template_string){
        $this->template=array();
        $this->tags=array();
        $this->updated_tag_list = array();

        $this->tmp_template=$template_string;
        $this->parseTemplate($this->template);
        return $this;
    }
    function loadTemplate($template_name,$ext=null){
        /*
         * Load template from file
         */
        if(!$this->api)throw new Exception('Broken Link');
        if($this->cache[$template_name.$ext]){
            $this->template=unserialize($this->cache[$template_name.$ext]);
            $this->rebuildTags();
            return $this;
        }

        if($ext){
            $tempext=$this->settings['extension'];
            $this->settings['extension']=$ext;
        };
        $this->tmp_template = $this->findTemplate($template_name);

        if(!isset($this->tmp_template))
            throw new SMliteException("Template not found (".$template_name.$this->settings['extension'].") in (".  $this->settings['templates'].")");

        $this->parseTemplate($this->template);
        if($ext){ $this->settings['extension']=$tempext; }

        $this->cache[$template_name.$ext]=serialize($this->template);

        return $this;
    }
    function parseTemplate(&$template,$level=0,$pc=0){
        /*
         * private function
         *
         * This is a main function, which actually parses template. It's recursive and it
         * calls itself. Empty array should be passed
         */
        // TODO when we go into sublevel, we should set the number of
        // the tag so that there is NO double numbers in template COMPLETELY
        // May be this way is dirty, need to look for better solution...
        $c=pow(10,$level)+$pc;
        while(strlen($this->tmp_template)){
            $text = $this->myStrTok($this->tmp_template,$this->settings['ldelim']);
            if($text!=='')$template[]=$text;
            $tag=trim($this->myStrTok($this->tmp_template,$this->settings['rdelim']));
            if(isset($tag)&&$tag){
                if($tag[0]=='$'){
                    $tag = substr($tag,1);
                    $template[$tag.'#'.$c]=array();
                    $this->registerTag($tag,$c,$template[$tag.'#'.$c]);
                }elseif($tag[0]=='/'){
                    $tag = substr($tag,1);
                    return $tag;
                }elseif(substr($tag,-1)=='/'){
                    $tag = substr($tag,0,-1);
                    $template[$tag.'#'.$c]=array();
                    $this->registerTag($tag,$c,$template[$tag.'#'.$c]);
                }else{
                    $template[$tag.'#'.$c]=array();
                    $this->registerTag($tag,$c,$template[$tag.'#'.$c]);
                    $xtag = $this->parseTemplate($template[$tag.'#'.$c],$level+1,$c);
                    if($xtag && $tag!=$xtag){
                        throw new BaseException("Tag missmatch. $tag is closed with $xtag");
                    }
                }
            }
            $c++;
        }
        return "end_of_file";
    }
    function registerTag($key,$npk,&$ref){
        if(!$key)return;
        if(isset($npk))$this->tags[$key.'#'.$npk][]=&$ref;
        $this->tags[$key][]=&$ref;
    }
    function isTopTag($tag){
        return
            (isset($this->top_tag) && ($tag==$this->top_tag)) ||
            ($tag=='_top');
    }

    // rebuild tags of existing array structure
    function rebuildTags(){
        /*
         * This function walks through template and rebuilds list of tags. You need it in case you
         * changed already parsed template.
         */
        $this->tags=array();
        $this->updated_tag_list = array();
        $this->rebuildTagsRegion($this->template);
        return $this;
    }
    function rebuildTagsRegion(&$branch){
        if(!isset($branch))throw new BaseException("Cannot rebuild tags, because template is empty");
        if(!is_array($branch))throw $this->exception('System problem with SMLite. Incorrect use of branch');
        foreach($branch as $key=>$val){
            if(is_int($key))continue;
            list($real_key,$junk)=explode('#',$key);
            $this->registerTag($real_key,null,$branch[$key]);
            $this->registerTag($key,null,$branch[$key]);
            if(is_array($branch[$key]))$this->rebuildTagsRegion($branch[$key]);
        }
    }

    // Template rendering (array -> string)
    function render(){
        /*
         * This function should be used to convert template into string representation.
         */
        return $this->renderRegion($this->template);
    }
    function renderRegion(&$chunk){
        $result = '';
        if(!is_array($chunk))return $chunk;
        foreach($chunk as $key=>$_chunk){
            if(is_array($result)){
                $result[]=$this->renderRegion($_chunk);
            }else{
                $tmp=$this->renderRegion($_chunk);
                if(is_array($tmp)){
                    $result=array($result);
                    $result[]=$tmp;
                }else{
                    $result.=$tmp;
                }
            }
        }
        return $result;
    }

    // Misc functions
    function myStrTok(&$string,$tok){
        if(!$string)return '';
        $pos = strpos($string,$tok);
        if($pos===false){
            $chunk=$string;
            $string='';
            return $chunk;  // nothing left
        }
        $chunk = substr($string,0,$pos);
        $string = substr($string,$pos+strlen($tok));
        return $chunk;
    }
}
