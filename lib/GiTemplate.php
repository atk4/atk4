<?php
/**
 * ==[ About SMlite ]==========================================================
 * This class is a lightweight template engine. It's based around operating with
 * chunks of HTML code and the main aims are:.
 *
 *  - completely remove any code from templates
 *  - speed up template parsing and manipulation speed
 *
 * @author      Romans <romans@agiletoolkit.org>
 * @copyright   AGPL
 *
 * @version     2.0
 *
 * ==[ Version History ]=======================================================
 * 1.0          First public version (released with AModules3 alpha)
 * 1.1          Added support for "_top" tag
 *              Removed support for permanent tags
 *              Much more comments and other fixes
 * 2.0          Reimplemented template parsing, now doing it with regexps
 *
 * ==[ Description ]===========================================================
 * SMlite templates are HTML pages containing tags to mark certain regions.
 * <html><head>
 *   <title>MySite.com - {page_name}unknown page{/page_name}</title>
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
 * but I use 'spot' to refer to a location inside template (such as {$date}),
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
 * Which means to seek tag {student_list} inside misc/listings.html
 *
 * You may have same tag several times inside template. For example you can
 * use tag {$title} inside <head><title> and <h1>.
 *
 * If you would set('title','My Title'); it will insert that value in
 * all those regions.
 *
 * ==[ Agile Toolkit integration ]============================================
 * Rule of thumb in object oriented programming is data / code separation. In
 * our case HTML is data and our PHP files are code. SMlite helps to completely
 * cut out the code from templates (smarty promotes idea about integrating
 * logic inside templates and I decided not to use it for that reason)
 *
 * Inside Agile Toolkit, each object have it's own template or may have even several
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
class GiTemplate extends AbstractModel implements ArrayAccess
{
    // {{{ Setting Variables

    /**
     * This array contains list of all tags found inside template implementing
     * faster access when manipulating the template.
     *
     * @var array
     */
    public $tags = array();

    public $top_tags; // looks unused, see cloneRegion()

    /**
     * This is a parsed contents of the template organized inside an array. This
     * structure makes it very simple to modify any part of the array.
     *
     * @var array
     */
    public $template = array();

    public $template_source = null;

    /**
     * Settings are populated from the configuration file, if found.
     *
     * @var array
     */
    public $settings = array();

    /** @var string */
    public $default_exception = 'Exception_Template';

    /**
     * Which file template is loaded from.
     */
    public $origin_filename = null;

    public $template_file = null;

    public $template_type = null;  // redefine or set in config

    // }}}

    // {{{ Core methods - initialization

    /**
     * This function specifies default settings for SMlite.
     */
    public function getDefaultSettings()
    {
        return array(
                // by separating them with ':'
                'ldelim' => '{',                // tag delimiter
                'rdelim' => '}',
                'extension' => '.html',          // template file extension
                'template_type' => 'template',
                );
    }

    // Template creation, interface functions
    public function init()
    {
        parent::init();
        $this->cache = &$this->app->smlite_cache;
        $this->settings = array_merge(
            $this->getDefaultSettings(),
            $this->app->getConfig('template', array())
        );
    }

    /** Causes the template to be refreshed from it's original source */
    public function reload()
    {
        $f = $this->findTemplate($this->template_file);
        $template_source = implode('', file($f));
        $this->loadTemplateFromString($template_source);

        return $this;
    }

    public function __clone()
    {
        parent::__clone();
        $this->template = unserialize(serialize($this->template));

        unset($this->tags);
        $this->rebuildTags();
    }
    /**
     * Returns relevant exception class. Use this method with "throw".
     *
     * @param string $message Static text of exception.
     * @param string $type    Exception class or class postfix
     * @param string $code    Optional error code
     *
     * @return Exception_Template
     */
    public function exception($message = 'Undefined Exception', $type = null, $code = null)
    {
        $o = $this->owner ? $this->owner->__toString() : 'none';

        return parent::exception($message, $type, $code)
            ->addMoreInfo('owner', $o)
            ->addMoreInfo('template', $this->template_source)
            ;
    }

    // }}}

    // {{{ Tag manipulation

    /**
     * Returns true if specified tag is a top-tag of the template.
     *
     * Since Agile Toolkit 4.3 this tag is always called _top
     *
     * @param string $tag
     *
     * @return bool
     */
    public function isTopTag($tag)
    {
        return $tag == '_top';
    }

    /**
     * This is a helper method which populates an array pointing
     * to the place in the template referenced by a said tag.
     *
     * Because there might be multiple tags and getTagRef is
     * returning only one template, it will return the first
     * occurence:
     *
     * {greeting}hello{/},  {greeting}world{/}
     *
     * calling getTagRef('greeting',$template) will point
     * second argument towards &array('hello');
     */
    public function getTagRef($tag, &$template)
    {
        if ($this->isTopTag($tag)) {
            $template = &$this->template;

            return $this;
        }

        @list($tag, $ref) = explode('#', $tag);
        if (!$ref) {
            $ref = 1;
        }
        if (!isset($this->tags[$tag])) {
            throw $this->exception('Tag not found in Template')
                ->addMoreInfo('tag', $tag)
                ->addMoreInfo('tags', implode(', ', array_keys($this->tags)))
                ;
        }
        $template = reset($this->tags[$tag]);

        return $this;
    }

    /**
     * For methods which execute action on several tags, this method
     * will return array of templates. You can then iterate
     * through the array and update all the template values.
     *
     * {greeting}hello{/},  {greeting}world{/}
     *
     * calling getTagRefList('greeting',$template) will point
     * second argument towards array(&array('hello'),&array('world'));
     *
     * If $tag is specified as array, then $templates will
     * contain all occurences of all tags from the array.
     */
    public function getTagRefList($tag, &$template)
    {
        if (is_array($tag)) {
            // TODO: test
            $res = array();
            foreach ($tag as $t) {
                $template = array();
                $this->getTagRefList($t, $te);

                foreach ($template as &$tpl) {
                    $res[] = &$tpl;
                }

                return true;
            }
        }

        if ($this->isTopTag($tag)) {
            $template = &$this->template;

            return false;
        }

        @list($tag, $ref) = explode('#', $tag);
        if (!$ref) {
            if (!isset($this->tags[$tag])) {
                throw $this->exception('Tag not found in Template')
                    ->setTag($tag);
            }
            $template = $this->tags[$tag];

            return true;
        }
        if (!isset($this->tags[$tag][$ref - 1])) {
            throw $this->exception('Tag not found in Template')
                ->setTag($tag);
        }
        $template = array(&$this->tags[$tag][$ref - 1]);

        return true;
    }

    /**
     * Checks if template has defined a specified tag.
     */
    public function hasTag($tag)
    {
        if (is_array($tag)) {
            return true;
        }

        @list($tag, $ref) = explode('#', $tag);

        return isset($this->tags[$tag]) || $this->isTopTag($tag);
    }
    /**
     * Obsolete due to inconsistent naming.
     */
    public function is_set($tag)
    {
        return $this->hasTag($tag);
    }

    /**
     * This function will replace region refered by $tag to a new content.
     *
     * If tag is found inside template several times, all occurences are
     * replaced.
     *
     * ALTERNATIVE USE(2) of this function is to pass associative array as
     * a single argument. This will assign multiple tags with one call.
     * Sample use is:
     *
     *  set($_GET);
     *
     * would read and set multiple region values from $_GET array.
     */
    public function set($tag, $value = null, $encode = true)
    {
        if (!$tag) {
            return $this;
        }
        if (is_array($tag)) {
            if (is_null($value)) {
                foreach ($tag as $s => $v) {
                    $this->trySet($s, $v, $encode);
                }

                return $this;
            }
            if (is_array($value)) {
                throw $this->exception('No longer supported', 'Exception_Obsolete');
            }

            // This can now be used - multiple tags will be set to the value
        }

        if (is_array($value)) {
            return $this;
        }

        if ($encode) {
            $value = htmlspecialchars($value, ENT_NOQUOTES, 'UTF-8');
        }

        $this->getTagRefList($tag, $template);
        foreach ($template as $key => &$ref) {
            $ref = array($value);
        }

        return $this;
    }

    /**
     * Attempt to set value of a tag to a HTML variable. The value is
     * inserted as-is, while regular set() would HTML-encode the values
     * to avoid injection.
     */
    public function trySetHTML($tag, $value = null)
    {
        return $this->trySet($tag, $value, false);
    }

    public function setHTML($tag, $value = null)
    {
        return $this->set($tag, $value, false);
    }

    /**
     * Check if tag is present inside template. If it does, execute set();
     * See documentation for set().
     */
    public function trySet($tag, $value = null, $encode = true)
    {
        if (is_array($tag)) {
            return $this->set($tag, $value, $encode);
        }

        return $this->hasTag($tag) ? $this->set($tag, $value, $encode) : $this;
    }

    /**
     * Empty contents of specified region. If region contains sub-hierarchy,
     * it will be also removed.
     *
     * TODO: This does not dispose of the tags which were previously
     * inside the region. This causes some severe pitfalls for the users
     * and ideally must be checked and proper errors must be generated.
     */
    public function del($tag)
    {
        if ($this->isTopTag($tag)) {
            $this->loadTemplateFromString('');

            return $this;
        }

        $this->getTagRefList($tag, $template);
        foreach ($template as &$ref) {
            $ref = array();
            // TODO recursively go through template, and add tags
            // to blacklist, which would then be checked by hasTag()
        }

        return $this;
    }

    /**
     * Similar to del() but won't throw exception if tag is not present.
     */
    public function tryDel($tag)
    {
        if (is_array($tag)) {
            return $this->del($tag);
        }

        return $this->hasTag($tag) ? $this->del($tag) : $this;
    }

    /**
     * Get value of the tag. Note that this may contain an array
     * if tag contains a structure.
     */
    public function get($tag)
    {
        $template = array();
        $this->getTagRef($tag, $template);

        return $template;
    }

    // {{{ ArrayAccess support
    public function offsetExists($name)
    {
        return $this->hasTag($name);
    }
    public function offsetGet($name)
    {
        return $this->get($name);
    }
    public function offsetSet($name, $val)
    {
        $this->set($name, $val);
    }
    public function offsetUnset($name)
    {
        $this->del($name, null);
    }
    // }}}

    /**
     * Add more content inside a tag.
     */
    public function append($tag, $value, $encode = true)
    {
        if ($value instanceof URL) {
            $value = $value->__toString();
        }

        if ($encode) {
            $value = htmlspecialchars($value, ENT_NOQUOTES, 'UTF-8');
        }

        $this->getTagRefList($tag, $template);
        foreach ($template as $key => &$ref) {
            $ref[] = $value;
        }

        return $this;
    }

    public function appendHTML($tag, $value)
    {
        return $this->append($tag, $value, false);
    }

    public function eachTag($tag, $callable)
    {
        if (!$this->is_set($tag)) {
            return $this;
        }
        if ($this->getTagRefList($tag, $template)) {
            foreach ($template as $key => $templ) {
                $ref = $tag.'#'.($key + 1);
                $this->tags[$tag][$key] = array(call_user_func($callable, $this->recursiveRender($templ), $ref));
            }
        } else {
            $this->tags[$tag][0] = array(call_user_func($callable, $this->recursiveRender($template), $tag));
        }

        return $this;
    }
    public function cloneRegion($tag)
    {
        if ($this->isTopTag($tag)) {
            return clone $this;
        }

        $n = $this->newInstance();
        $n->template = unserialize(serialize(array('_top#1' => $this->get($tag))));
        $n->rebuildTags();
        $n->top_tags[] = $tag;
        $n->source = 'Clone ('.$tag.') of '.$this->source;

        return $n;
    }
    public function _getDumpTags(&$template)
    {
        $s = '';
        foreach ($template as $key => $val) {
            if (is_array($val)) {
                $s .= '<font color="blue">{'.$key.'}</font>'.
                    $this->_getDumpTags($val).'<font color="blue">{/'.$key.'}</font>';
            } else {
                $s .= htmlspecialchars($val);
            }
        }

        return $s;
    }
    public function dumpTags()
    {
        echo '"'.$this->_getDumpTags($this->template).'"';
    }

    // template loading and parsing
    public function findTemplate($template_name)
    {
        /*
         * Find template location inside search directory path
         */
        if (!$this->app) {
            throw new Exception_InitError('You should use add() to add objects!');
        }
        $f = $this->app->locatePath(
            $this->template_type ?: $this->settings['template_type'],
            $template_name.$this->settings['extension']
        );

        return $this->origin_filename = $f;
    }
    public function loadTemplate($template_name, $ext = null)
    {
        /*
         * Load template from file
         */
        if ($ext) {
            $tempext = $this->settings['extension'];
            $this->settings['extension'] = $ext;
        };
        $f = $this->findTemplate($template_name);

        /*if(file_exists($f.'.cache')){
            $this->template=unserialize(file_get_contents($f.'.cache'));
            $this->source='Loaded from file: '.$template_name;
            $this->rebuildTags();
            return $this;
        }*/

        $template_source = implode('', file($f));

        $this->template_file = $template_name;

        if ($this->app->compat_42 && strpos($template_source, '<?') !== false) {
            $this->settings['ldelim'] = '<\?';
            $this->settings['rdelim'] = '\?>';
        }

        $this->loadTemplateFromString($template_source);

        // TODO: implement proper caching
        //file_put_contents($f.'.cache',serialize($this->template));

        $this->source = 'Loaded from file: '.$template_name;
        if ($ext) {
            $this->settings['extension'] = $tempext;
        }

        return $this;
    }

    private $tag_cnt = array();
    public function regTag($tag)
    {
        if (!isset($this->tag_cnt[$tag])) {
            $this->tag_cnt[$tag] = 0;
        }

        return $tag.'#'.(++$this->tag_cnt[$tag]);
    }

    public function parseTemplateRecursive(&$input, &$template)
    {
        while (list(, $tag) = each($input)) {

            // Closing tag
            if ($tag[0] == '/') {
                return substr($tag, 1);
            }

            if ($tag[0] == '$') {
                $tag = substr($tag, 1);
                $full_tag = $this->regTag($tag);
                $template[$full_tag] = '';  // empty value
                $this->tags[$tag][] = &$template[$full_tag];

                // eat next chunk
                $chunk = each($input);
                if ($chunk[1]) {
                    $template[] = $chunk[1];
                }

                continue;
            }

            $full_tag = $this->regTag($tag);

            // Next would be prefix
            list(, $prefix) = each($input);
            $template[$full_tag] = $prefix ? array($prefix) : array();

            $this->tags[$tag][] = &$template[$full_tag];

            $rtag = $this->parseTemplateRecursive($input, $template[$full_tag]);

            $chunk = each($input);
            if ($chunk[1]) {
                $template[] = $chunk[1];
            }
        }
    }

    public function parseTemplate($str)
    {
        $tag = '/'.$this->settings['ldelim'].'([\/$]?[-_\w]*)'.$this->settings['rdelim'].'/';

        $input = preg_split($tag, $str, -1, PREG_SPLIT_DELIM_CAPTURE);

        list(, $prefix) = each($input);
        $this->template = array($prefix);

        $this->parseTemplateRecursive($input, $this->template);
    }

    /**
     * Initialize current template from the supplied string.
     *
     * @param string $str
     *
     * @return $this
     */
    public function loadTemplateFromString($str)
    {
        $this->template_source = $str;
        $this->source = 'string';
        $this->template = $this->tags = array();
        if (!$str) {
            return;
        }
        $this->tag_cnt = array();

        /* First expand self-closing tags {$tag} -> {tag}{/tag} */
        $str = preg_replace('/{\$([\w]+)}/', '{\1}{/\1}', $str);

        $this->parseTemplate($str);

        return $this;
    }
    public function rebuildTags()
    {
        $this->tags = array();

        $this->rebuildTagsRegion($this->template);
        //$this->template=unserialize(serialize($this->template));
        //$this->rebuildTagsRegion($this->template);
    }
    public function rebuildTagsRegion(&$template)
    {
        foreach ($template as $tag => &$val) {
            if (is_numeric($tag)) {
                continue;
            }

            @list($key, $ref) = explode('#', $tag);

            $this->tags[$key][$ref] = &$val;
            if (is_array($val)) {
                $this->rebuildTagsRegion($val);
            }
        }
        //echo "------------------------------------------<br/>";
        //var_dump($old,$new);
    }
    public function render($region = null)
    {
        if ($region) {
            return $this->recursiveRender($this->get($region));
        }

        return $this->recursiveRender($this->template);
    }
    public function renderRegion(&$chunk)
    {
        $result = ''; // you can replace this with array() for debug purposes
        if (!is_array($chunk)) {
            return $chunk;
        }
        foreach ($chunk as $key => $_chunk) {
            $tmp = $this->renderRegion($_chunk);
            if (is_array($result)) {
                $result[] = $tmp;
            } else {
                if (is_array($tmp)) {
                    $result = array($result, $tmp);
                } else {
                    $result .= $tmp;
                }
            }
        }

        return $result;
    }
    public function recursiveRender(&$template)
    {
        $s = '';
        foreach ($template as $val) {
            if (is_array($val)) {
                $s .= $this->recursiveRender($val);
            } else {
                $s .= $val;
            }
        }

        return $s;
    }
}
