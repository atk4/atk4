<?php
/***********************************************************
  Implementation of Template-driven mailer

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
/**
 * Class for mail rendering and sending.
 * This class is designed to be based on mail templates. Usually you set all the mail parameters
 * such as from, to, bcc, subject and so on in the mail template, so in order to send the mail
 * you should do the following:
 *
 * $mail=$this->add('TMail')->loadTemplate('your_template')->send($to_address);
 *
 * However, you can redefine all the email parts after template load.
 *
 * $mail->loadTemplate('mail/template');
 * $mail->body="This is test e-mail";
 * $mail->send('somewhere@somehost.net');
 *
 * Or you can set the tags of the templates:
 *
 * $mail->body->setTag('server_name',$server_name);
 *
 * This method will set specified tag in all the message parts: subject, body, sign
 *
 * Multipart MIME messages are also supported. You can add attachments, as well as
 * add text and HTML part:
 * $mail
 *              ->setBodyType('both')   // use both HTML and text part
 *              ->setBody($html)                // default body is HTML for 'both' message type
 *              ->attachText($text);    // adding text part for plain-text mode
 *
 * For non MIME compatible mail readers plain text part is also added. Content of this part
 * depends on message type:
 * - text and both types: text part content
 * - html type: explanation message (see getBody() method for details)
 *
 * Created on 15.03.2007 by *Camper* (camper@adevel.com)
 * Changed on 08.04.2008 by *Camper* (camper@adevel.com)
 */
class TMail_Compat extends AbstractController{
    protected $headers=array();
    protected $mime=array();
    protected $plain_text="Sorry, this message can only be read with MIME-supporting mail reader.\n\n";
    protected $boundary=null;
    protected $template=null;
    protected $attrs=array();
    protected $body_type='text';

    public $version='1.0';

    function init(){
        parent::init();
        $this->headers['Mime-Version']="1.0";
        $this->headers['Content-Transfer-Encoding']="8bit";
        $this->setBodyType('text');
    }
    function reset(){
        // required due to feature of AModules: controllers are added once
        // if we use two add()s of controller, content should be set to null
        $this->template=null;
        $this->elements['SMlite']=null;
        $this->mime=array();
        $this->boundary=null;
        $this->headers=array();
        $this->attrs=array();
        //$this->body_type=null;
        return $this;
    }
    function loadTemplate($template,$type='.txt'){
        // loads the template from the specified file
        // the template should contain the following tags:
        // headers - contains the nested
        //              from
        //              bcc
        // subject
        // body - can contain any tags you want
        // sign
        //
        // look at the provided sample template in templates/kt2/mail
        $this->reset();
        $this->template=$this->getTemplateEngine();
        $this->template->template_type='mail';
        $this->template->loadTemplate($template,$type);
        if($type=='.html'){
            $this->setBodyType('html');
        }
        // gathering parts:
        // headers
        $this->set('subject',$this->template->cloneRegion('subject'));
        // body
        $this->body=$this->template->cloneRegion('body');
        $this->sign=$sign=$this->body->cloneRegion('sign');
        $this->body->tryDel('sign');
        if($sign->render()!='')$this->sign=$sign;
        if($this->template->is_set('from'))$this->set('from',$this->template->cloneRegion('from'));
        return $this;
    }
    function setTag($tag,$value=null){
        /**
         * Sets the tag value throughout the template, including all parts
         * Some parts could be strings, not templates
         */
        if(is_null($value)&&is_array($tag)){
            foreach($tag as $k=>$v)$this->setTag($k,$v);
            return $this;
        }
        $this->template->trySet($tag,$value);
        foreach($this->attrs as $key=>$attr){
            if($attr instanceof SMlite)$this->get($key)->trySet($tag,$value);
        }
        if($this->get('subject') instanceof SMlite){
            $this->get('subject')->trySet($tag,$value);
        }
        if($this->body instanceof SMlite)$this->body->trySet($tag,$value);
        if($this->sign instanceof SMlite)$this->sign->trySet($tag,$value);
        return $this;
    }
    function setIsHtml($is_html=true){
        $this->body_type=($is_html?'html':'text');
        return $this;
    }
    /**
     * Sets the body type. Possible values:
     * - text: plain text
     * - html: HTML only
     * - both: text and HTML
     */
    function setBodyType($type){
        if($type=='html'||$type=='text'||$type='both')$this->body_type=$type;
        else throw new MailException("Unsupported body type: $type");
        return $this;
    }
    function set($tag,$value){
        /**
         * Sets the mail attribute
         */
        $this->attrs[$tag]=$value;
        return $this;
    }
    function setHeader($name,$value=null){
        // sets the message header
        if(is_null($value)&&is_array($name)){
            $this->headers=array_merge($this->headers,$name);
        }else{
            $this->headers[$name]=$value;
        }
        return $this;
    }
    function getTemplateEngine(){
        return $this->add('SMlite');
    }
    function loadDefaultTemplate(){
        /**
         * Loads default template and sets sign and headers from it
         */
        $template=$this->getTemplateEngine()->loadTemplate('mail/mail','.txt');
        if($this->is_html)$template->set('content_type','text/html');
        $this->sign=$template->cloneRegion('sign');
    }
    /**
     * Returns the rendered mail body, sign included
     */
    function getBody(){
        // first we should render the body if it was not rendered before
        if(is_null($this->body)){
            $this->set('body','');
            // this is unnormal situation, notifying developer
            $this->api->logger->logLine("Email body is null: ".$this->get('from')." >> ".
                    date($this->api->getConfig('locale/timestamp','Y-m-d H:i:s')."\n"),null,'error');
        }
        //if(!isset($this->mime['text'])&&!isset($this->mime['html'])){
        $this->setBody(is_object($this->body)?$this->body->render():$this->body);
        //}
        // sign should be added to all parts
        if(isset($this->mime['text']))$this->mime['text']['content'].=$this->getSign();
        if(isset($this->mime['html'])){
            $this->mime['html']['content'].=$this->getSign();
            // HTML should be converted to base 64
            //$this->mime['html']['content']=base64_encode($this->mime['html']['content']);
            // no, it should be splitted
            //$this->mime['html']['content']=wordwrap($this->mime['html']['content'],76,"\r\n",false);
        }
        // now as we have all the needed parts set
        $result='';
        // first we have to add a simple text for non-Mime readers
        if(count($this->mime)==0)$result.=$this->plain_text;
        // adding mail parts 
        foreach($this->mime as $name=>$att){
            list($type,)=explode(';',$att['type']);
            // $name is a file name/part name, $att is a hash with type and content
            // depending on the type adding a header
            switch($type){
                case 'text/plain':
                    if(count($this->mime)>0)$result.="\n\n--".$this->getBoundary()."\n".
                        "Content-Type: ".$att['type']."; ";
                    //$result.="charset=UTF-8";
                    break;

                case 'text/html':
                    if(count($this->mime)>0)$result.="\n\n--".$this->getBoundary()."\n".
                        "Content-Type: ".$att['type']."; ";
                    $result.=
                        "Content-Transfer-Encoding: 8bit";
                    $att['content']=rtrim(wordwrap($att['content']));
                    break;
            }
            if(isset($att['attachment'])){
                $att['content']=rtrim(chunk_split($att['content']));
                $result.="name=$name\n" .
                    "Content-transfer-encoding: base64\nContent-Disposition: attachment;";
            }
            $result.="\n\n";
            $result.=$att['content'];
        }
        // if there were any attachments, trailing boundary should be added
        if(count($this->mime)>0)$result.="\n--".$this->getBoundary()."\n";
        return ltrim($result);
    }
    function getSign(){
        return is_object($this->sign)?$this->sign->render():$this->sign;
    }
    function getHeaders(){
        // returns the rendered headers
        $this->headers['From']=$this->get('from',false);
        $this->headers['Bcc']=$this->get('bcc',false);
        $this->headers['Reply-To']=$this->get('reply-to',false);
        $this->headers['Sender']=$this->get('from',false);
        $this->headers['Errors-To']=$this->get('errors-to',false);
        $this->headers['Content-Type']=$this->get('content-type');

        $result='';
        // headers should be separated by LF (\n), there should be no spaces
        // between lines
        foreach($this->headers as $header=>$value){
            if($value)$result.="$header: $value\n";
        }
        return $result;
    }
    function getBoundary(){
        // returns the boundary code for multipart messages
        if(is_null($this->boundary)){
            $this->boundary=md5($this->get('subject',false).date('YmdHis'));
        }
        return $this->boundary;
    }
    function get($tag,$plain=true){
        $value=null;
        if(isset($this->attrs[$tag]))$value=$this->attrs[$tag];
        else
            // some tags can be replaced be others
            switch($tag){
                case 'reply-to': $value=$this->get('from'); break;
                case 'errors-to': $value=$this->get('from'); break;
                case 'bcc': $value=false; break;        // not set by default
                case 'content-type':
                            if(count($this->mime)>0)$value='multipart/mixed; boundary='.$this->getBoundary();
                            elseif($this->body_type=='html')$value='text/html; charset="UTF-8"';
                            else $value='text/plain';
                            break;
            }
        // if plain, we need it for rendering. converting arrays
        if(!$plain){
            if(is_array($value))$value=join(',',$value);
            if($value instanceof SMlite)$value=$value->render();
        }
        return $value;
    }
    /**
     * Sets the body of the message.
     * Behaviour of this method depends on the body type specified with setBodyType():
     * - text: plain text mime part is set
     * - html: html mime part only is set
     * - both: html mime part only is set, text part should be added separately
     *
     * This method does NOT accept SMlite object as a parameter.
     */
    function setBody($body){
        if(is_object($body))throw new MailException("Body cannot be an object");
        switch($this->body_type){
            case 'text':
                $this->plain_text=$body;
                break;
            case 'html':
                $this->attachHtml($body);
                break;
            case 'both':
                //$this->attachText("Text part is not set");
                $this->attachHtml($body);
                break;
        }
        return $this;
    }
    /**
     * Attaches a saved file
     * @param $file any valid path to a file
     * @param $type valid mime type. e.g.:
     * audio/mpeg
     * image/jpeg
     * application/zip
     * audio/wav
     * etc.
     * @param $name optional, sets the filename for message
     * @param $asstring if set to true, $file contains contents, not filename
     */
    function attachFile($file,$type,$name=null,$asstring=false){
        $content=$asstring?$file:file_get_contents($file);
        if(!$content)throw new MailException("Error reading attachment: ".($asstring?$name:$file));
        if(is_null($name)){
            if($asstring)$name='file_'.count($this->mime);
            else $name=basename($file);
        }
        // encoding content
        $this->mime['"'.$name.'"']=array(
                'type'=>$type,
                'content'=>base64_encode($content),
                'attachment'=>true
                );
        return $this;
    }
    /**
     * Attaches a provided HTML string as a HTML file
     * @param $html any valid HTML code as a string
     */
    function attachHTML($html){
        $this->mime['html']=array(
                'type'=>'text/html; charset=UTF-8',
                'content'=>$html,
                );
        // sign could be added to HTML after, converting performed in getBody()
        return $this;
    }
    function attachText($text){
        $this->mime['text']=array(
                'type'=>'text/plain; charset=UTF-8',
                'content'=>$text,
                );
        return $this;
    }
    function getFromAddr(){
        // as $this->from could contain the address including name ("admin" <admin@domain.com>)
        // we need this method to extract address only
        $m=array();
        preg_match('/^\w+@[a-zA-Z_]+?\.[a-zA-Z]{2,3}$/',$this->get('from'),$m);
        return $m[0];
    }
    /**
     * Does the actual send by calling mail() function
     */
    function send($address,$add_params=null){
        if (is_array($address)){
            foreach ($address as $a){
                $this->send($a, $add_params);
            }
            return;
        }
        // checking if from is set
        if(!$this->get('from'))$this->set('from',$this->api->getConfig('mail/from','nobody@agiletoolkit.org'));
        // send an email with defined parameters
        $this->headers['X-B64']=base64_encode($address);
        mail($address, $this->get('subject',false),
                //($this->is_html?'<html>':'').
                $this->getBody(),//.($this->is_html?'</html>':''),
                $this->getHeaders(),
                '-f '.$this->get('from',false).' '.$add_params);
    }
}
class MailException extends BaseException{}
