<?php
/***********************************************************
  ..

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
/*
   After looking at many HTML sanitization classes, I believe they are still missing the point
   and trying to duplicate functionality. The HTML we receive is almost as good as XML. In fact
   the XML libraries know how to deal with improper HTML code and they can convert them into
   a valid XML. From there we only need to get the markup we want, and destroy all the tags
   we don't like (whitelist).

   That is what XSLT are. We include a XSLT template which can clean up your user-inputted code
   to make sure it's safe.

   Of course you can customize the XSLT
 */

class System_HTMLSanitizer extends AbstractController {
    public $xslt='htmlsanitize';
    function sanitize($html_string){

        // First HTML may use badly written templates, such as <li> without closing them off.
        // We are using DOMDocument to convert them into valid XML

        if(!$html_string)return '';

        $html=new DOMDocument();
        libxml_use_internal_errors(true);
        $html->loadHTML($html_string);

        /*
           $body=$html_dom->getElementsByTagName('body')->item(0);

           $input_xml = $html_dom->saveXML($body);
         */

        // Next we need to transform our XML using XSLT
        $xslt = new DOMDocument();
        $xslt_file = $this->api->locatePath('xslt','htmlsanitize.xml');
        $xslt->load($xslt_file);

        $proc = new XSLTProcessor();
        $proc->importStylesheet($xslt);
        return $proc->transformToXML($html);

    }
}
