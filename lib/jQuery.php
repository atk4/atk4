<?php
/**
 * Implements basic interface to jQuery
 *
 * jQuery is an compatibility layer if jQuery UI is not used.
 */
class jQuery extends AbstractController
{
    private $chains = 0;

    public $included = array();

    public $chain_class = 'jQuery_Chain';

    public function init()
    {
        parent::init();

        $this->app->jquery = $this;

        if (!$this->app->template) {
            return;
        }

        if (!$this->app->template->is_set('js_include')) {
            throw $this->exception('Tag js_include must be defined in shared.html');
        }
        if (!$this->app->template->is_set('document_ready')) {
            throw $this->exception('Tag document_ready must be defined in shared.html');
        }

        $this->app->template->del('js_include');

        /* $config['js']['jquery']='https://code.jquery.com/jquery-2.1.4.min.js'; // to use CDN */
        if ($v = $this->app->getConfig('js/versions/jquery', null)) {
            $v = 'jquery-'.$v;
        } else {
            $v = $this->app->getConfig('js/jquery', 'jquery-2.0.3.min');   // bundled jQuery version
        }

        $this->addInclude($v);

        // Controllers are not rendered, but we need to do some stuff manually
        $this->app->addHook('pre-render-output', array($this, 'postRender'));
        $this->app->addHook('cut-output', array($this, 'cutRender'));
    }
    /* Locate javascript file and add it to HTML's head section */
    public function addInclude($file, $ext = '.js')
    {
        return $this->addStaticInclude($file, $ext);
    }
    public function addStaticInclude($file, $ext = '.js')
    {
        if (@$this->included['js-'.$file.$ext]++) {
            return;
        }

        if (strpos($file, 'http') !== 0 && $file[0] != '/') {
            $url = $this->app->locateURL('js', $file.$ext);
        } else {
            $url = $file;
        }

        $this->app->template->appendHTML(
            'js_include',
            '<script type="text/javascript" src="'.$url.'"></script>'."\n"
        );

        return $this;
    }
    /* Locate stylesheet file and add it to HTML's head section */
    public function addStylesheet($file, $ext = '.css', $locate = 'css')
    {
        return $this->addStaticStylesheet($file, $ext, $locate);
    }
    public function addStaticStylesheet($file, $ext = '.css', $locate = 'css')
    {
        //$file=$this->app->locateURL('css',$file.$ext);
        if (@$this->included[$locate.'-'.$file.$ext]++) {
            return;
        }

        if (strpos($file, 'http') !== 0 && $file[0] != '/') {
            $url = $this->app->locateURL($locate, $file.$ext);
        } else {
            $url = $file;
        }

        $this->app->template->appendHTML(
            'js_include',
            '<link type="text/css" href="'.$url.'" rel="stylesheet" />'."\n"
        );

        return $this;
    }
    /* Add custom code into onReady section. Will be executed under $(function(){ .. }) */
    public function addOnReady($js)
    {
        if (is_object($js)) {
            $js = $js->getString();
        }
        $this->app->template->appendHTML('document_ready', $js.";\n");

        return $this;
    }
    /* [private] use $object->js() instead */
    public function chain($object)
    {
        if (!is_object($object)) {
            throw new BaseException('Specify $this as argument if you call chain()');
        }

        return $object->add($this->chain_class);
    }
    /* [private] When partial render is done, this function includes JS for rendered region */
    public function cutRender()
    {
        $x = $this->app->template->get('document_ready');
        if (is_array($x)) {
            $x = implode('', $x);
        }
        if (!empty($x)) {
            echo '<script type="text/javascript">'.$x.'</script>';
        }

        return;
    }
    /* [private] .. ? */
    public function postRender()
    {
        //echo nl2br(htmlspecialchars("Dump: \n".$this->app->template->renderRegion($this->app->template->tags['js_include'])));
    }
    /* [private] Collect JavaScript chains from specified object and add them into onReady section */
    public function getJS($obj)
    {
        $this->hook('pre-getJS');

        $r = '';
        foreach ($obj->js as $key => $chains) {
            $o = '';
            foreach ($chains as $chain) {
                $o .= $chain->_render().";\n";
            }
            switch ($key) {
                case 'never':
                    // send into debug output
                    //if(strlen($o)>2)$this->addOnReady("if(console)console.log('Element','".$obj->name."','no action:','".str_replace("\n",'',addslashes($o))."')");
                    continue;

                case 'always':
                    $r .= $o;
                    break;
                default:
                    $o = '';
                    foreach ($chains as $chain) {
                        $o .= $chain->_enclose($key)->_render().";\n";
                    }
                    $r .= $o;
            }
        }
        if ($r) {
            $this->addOnReady($r);
        }

        return $r;
    }
}
