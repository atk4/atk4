<?php
/***********************************************************
  jQuery UI support

  Reference:
  http://agiletoolkit.org/doc/ref

==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
=====================================================ATK4=*/
class jUI extends jQuery {
    private $atk4_initialised=false;

    function init(){

        parent::init();
        if (@$this->api->jui) {
            throw $this->exception('Do not add jUI twice');
        }
        $this->api->jui=$this;

        $this->addDefaultIncludes();

        $this->atk4_initialised=true;
    }
    function addDefaultIncludes(){
        $this->addInclude('start-atk4');

        /* $config['js']['jquery']='http://code.jquery.com/jquery-1.8.2.min.js'; // to use CDN */
        if($v=$this->api->getConfig('js/versions/jqueryui',null))$v='jquery-ui-'.$v;
        else($v=$this->api->getConfig('js/jqueryui','jquery-ui-1.10.3.min'));  // bundled jQueryUI version

        $this->addInclude($v);

        $this->addInclude('ui.atk4_loader');
        $this->addInclude('ui.atk4_notify');
        $this->addInclude('atk4_univ_basic');
        $this->addInclude('atk4_univ_jui');
    }
    function addInclude($file,$ext='.js'){
        if(strpos($file,'http')===0){
            parent::addOnReady('$.atk4.includeJS("'.$file.'")');
            return $this;
        }
        $url=$this->api->locateURL('js',$file.$ext);

        if(!$this->atk4_initialised){
            return parent::addInclude($file,$ext);
        }

        parent::addOnReady('$.atk4.includeJS("'.$url.'")');
        return $this;
    }
    function addStylesheet($file,$ext='.css',$template=false){
        $url=$this->api->locateURL('css',$file.$ext);
        if(!$this->atk4_initialised || $template){
            return parent::addStylesheet($file,$ext);
        }

        parent::addOnReady('$.atk4.includeCSS("'.$url.'")');
    }
    function addOnReady($js){
        if(is_object($js))$js=$js->getString();
        if(!$this->atk4_initialised){
            return parent::addOnReady($js);
        }

        $this->api->template->append('document_ready', "$.atk4(function(){ ".$js."; });\n");
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
