<?php
/**
 * jQuery UI support
 */
class jUI extends jQuery
{
    /**
     * @var bool
     */
    private $atk4_initialised = false;

    // {{{ Inherited properties

    /** @var App_Web */
    public $app;

    // }}}

    /**
     * Initialization
     */
    public function init()
    {
        parent::init();
        if (@$this->app->jui) {
            throw $this->exception('Do not add jUI twice');
        }
        $this->app->jui = $this;

        $this->addDefaultIncludes();

        $this->atk4_initialised = true;
    }

    /**
     * Adds default includes
     */
    public function addDefaultIncludes()
    {
        $this->addInclude('start-atk4');

        /* $config['js']['jqueryui']='https://code.jquery.com/ui/1.11.4/jquery-ui.min.js'; // to use CDN */
        if ($v = $this->app->getConfig('js/versions/jqueryui', null)) {
            $v = 'jquery-ui-'.$v;
        } else {
            $v = $this->app->getConfig('js/jqueryui', 'jquery-ui-1.11.4.min');  // bundled jQueryUI version
        }

        $this->addInclude($v);

        $this->addInclude('ui.atk4_loader');
        $this->addInclude('ui.atk4_notify');
        $this->addInclude('atk4_univ_basic');
        $this->addInclude('atk4_univ_jui');
    }

    /**
     * Adds includes
     *
     * @param string $file
     * @param string $ext
     *
     * @return $this
     */
    public function addInclude($file, $ext = '.js')
    {
        if (strpos($file, 'http') === 0) {
            parent::addOnReady('$.atk4.includeJS("'.$file.'")');

            return $this;
        }
        $url = $this->app->locateURL('js', $file.$ext);

        if (!$this->atk4_initialised) {
            return parent::addInclude($file, $ext);
        }

        parent::addOnReady('$.atk4.includeJS("'.$url.'")');

        return $this;
    }

    /**
     * Adds stylesheet
     *
     * @param string $file
     * @param string $ext
     * @param bool $template
     *
     * @return $this
     */
    public function addStylesheet($file, $ext = '.css', $template = false)
    {
        $url = $this->app->locateURL('css', $file.$ext);
        if (!$this->atk4_initialised || $template) {
            return parent::addStylesheet($file, $ext);
        }

        parent::addOnReady('$.atk4.includeCSS("'.$url.'")');
    }

    /**
     * Adds JS chain to DOM onReady event
     *
     * @param jQuery_Chain|string $js
     *
     * @return $this
     */
    public function addOnReady($js)
    {
        if ($js instanceof jQuery_Chain) {
            $js = $js->getString();
        }
        if (!$this->atk4_initialised) {
            return parent::addOnReady($js);
        }

        $this->app->template->append('document_ready', '$.atk4(function(){ '.$js."; });\n");

        return $this;
    }
    /**
     * Matches each symbol of PHP date format standard with jQuery equivalent
     * codeword
     *
     * This function handles all the common codewords between PHP and Datepicker
     * date format standards. Plus, I added support for character escaping:
     * d m \o\f Y becomes dd mm 'of' yy
     * 
     * You may still have problems with symbols like 'W', 'L' that have no
     * equivalent handled by Datepicker.
     *
     * @author Tristan Jahier
     * @author Imants Horsts
     * @link http://stackoverflow.com/a/16725290/1466341
     */
    function dateformat_PHP_to_jQueryUI($php_format)
    {
        $MAP = array(
            // Day
            'd' => 'dd',
            'D' => 'D',
            'j' => 'd',
            'l' => 'DD',
            'N' => '',
            'S' => '',
            'w' => '',
            'z' => 'o',
            // Week
            'W' => '',
            // Month
            'F' => 'MM',
            'm' => 'mm',
            'M' => 'M',
            'n' => 'm',
            't' => '',
            // Year
            'L' => '',
            'o' => '',
            'Y' => 'yy',
            'y' => 'y',
            // Time
            'a' => '',
            'A' => '',
            'B' => '',
            'g' => '',
            'G' => '',
            'h' => '',
            'H' => '',
            'i' => '',
            's' => '',
            'u' => ''
        );
        $jui_format = "";
        $escaping = false;
        for ($i = 0; $i < strlen($php_format); $i++) {
            $char = $php_format[$i];
            if($char === '\\') { // PHP date format escaping character
                $i++;
                if (!$escaping) {
                    $jui_format .= '\'';
                }
                $jui_format .= $php_format[$i];
                $escaping = true;
            } else {
                if ($escaping) {
                    $jui_format .= '\'';
                    $escaping = false;
                }
                $jui_format .= isset($MAP[$char]) ? $MAP[$char] : $char;
            }
        }
        return $jui_format;
    }
}
