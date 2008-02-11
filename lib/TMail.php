<?php
/**
 * Class for mail rendering and sending.
 * This class is designed to be based on mail templates. Usually you set all the mail parameters
 * such as from, to, bcc, subject and so on in the mail template, so in order to send the mail
 * you should do the following:
 * 
 * $mail=$this->add('TMail')->loadTemplate('your_template');
 * $mail->send($to_address);
 * 
 * However, you can redefine all the email parts after template load. E.g. you can read headers,
 * but insert your own body (in this case you should take care of the whole body, sign included):
 * 
 * $mail->loadTemplate('mail/template');
 * $mail->body="This is test e-mail";
 * $mail->send('somewhere@somehost.net');
 * 
 * Or you can set the tags of the templates:
 * 
 * $mail->body->set('server_name',$server_name);
 * 
 * Created on 15.03.2007 by *Camper* (camper@adevel.com)
 */
class TMail extends AbstractController{
	protected $headers=array();
	protected $template=null;
	protected $is_html=false;
	protected $attrs=array();
	
	function loadTemplate($template,$type='.txt'){
		// loads the template from the specified file
		// the template should contain the following tags:
		// headers - contains the nested
		// 		from
		//		bcc
		// subject
		// body - can contain any tags you want
		// sign
		// 
		// look at the provided sample template in templates/kt2/mail
		$this->template=$this->add('SMlite')->loadTemplate($template,$type);
		$this->is_html=$type=='.html';
		// gathering parts:
		// headers
		$this->set('subject',$this->template->cloneRegion('subject'));
		// body
		$this->body=$this->template->cloneRegion('body');
        $this->sign=$sign=$this->body->cloneRegion('sign');
		$this->body->tryDel('sign');
		if($sign->render()!='')$this->sign=$sign;
		$this->from=$this->template->get('from');
		// TODO: fix this damn bcc getting
		$this->bcc=array();
		return $this;
	}
	function setTag($tag,$value){
		/**
		 * Sets the tag value throughout the template, including all parts
		 * Some parts could be strings, not templates
		 */
		$this->template->trySet($tag,$value);
		if($this->get('subject') instanceof SMlite)$this->get('subject')->trySet($tag,$value);
		if($this->body instanceof SMlite)$this->body->trySet($tag,$value);
        if (is_object($this->sign)){
            $this->sign->trySet($tag,$value);
        }
		return $this;
	}
	function setIsHtml($is_html=true){
		$this->is_html=$is_html;
		return $this;
	}
	function set($tag,$value){
		/**
		 * Sets the mail attribute
		 */
		$this->attrs[$tag]=$value;
		return $this;
	}
	function loadDefaultTemplate(){
		/**
		 * Loads default template and sets sign and headers from it
		 */
		$template=$this->add('SMlite')->loadTemplate('mail/mail','.txt');
		if($this->is_html)$template->set('content_type','text/html');
		$this->sign=$template->cloneRegion('sign');
	}
	function getBody(){
		// returns the rendered mail body
		if(is_null($this->body)){
			$this->body='';
			// this is unnormal situation, notifying developer
			$this->api->logger->logLine("Email body is null: ".$this->from." >> ".
				date($this->api->getConfig('locale/timestamp','Y-m-d H:i:s')."\n"),null,'error');
		}
		return is_string($this->body)?$this->body:$this->body->render();
	}
	function getSign(){
        return is_object($this->sign)?$this->sign->render():$this->sign;
	}
	function getHeaders($x64=null){
		// returns the rendered headers
		$this->headers[]="Mime-Version: 1.0";
		$this->headers[]="From: ".$this->get('from',false);
		if($this->get('bcc')!=false)$this->headers[]="Bcc: ".$this->get('bcc',false);
		$this->headers[]="Reply-To: ".$this->get('reply-to',false);
		$this->headers[]="Sender: ".$this->get('from',false);
		$this->headers[]="Errors-To: ".$this->get('errors-to',false);
		if(!is_null($x64))$this->headers[]="X-B64: $x64";
		$this->headers[]="Content-Type: ".($this->is_html?'text/html':'text/plain')."; charset=\"UTF-8\"";
		$this->headers[]="Content-Transfer-Encoding: 8bit";
		
		// headers should be separated by CRLF (\r\n), there should be no spaces
		// between lines (they are if we don't specify bcc)
		return join("\n",$this->headers);
	}
	function get($tag,$plain=true){
		if(isset($this->attrs[$tag]))$value=$this->attrs[$tag];
		else
		// some tags can be replaced be others
		switch($tag){
			case 'reply-to': $value=$this->get('from'); break;
			case 'errors-to': $value=$this->get('from'); break;
			case 'bcc': $value=false;	// not set by default
		}
		// if plain, we need it for rendering. converting arrays
		if(!$plain){
			if(is_array($value))$value=join(',',$value);
			if($value instanceof SMlite)$value=$value->render();
		}
		return $value;
	}
	function setBody($body){
		$this->body=$body;
		return $this;
	}
	function getFromAddr(){
		// as $this->from could contain the address including name ("admin" <admin@domain.com>)
		// we need this method to extract address only
		$m=array();
		preg_match('/^\w+@[a-zA-Z_]+?\.[a-zA-Z]{2,3}$/',
			$this->get('from'),$m);
		return $m[0];
	}
	/**
	 * Does the actual send by calling mail() function
	 */
	function send($address,$add_params=null){
		// send an email with defined parameters
		mail($address, $this->get('subject'), 
			//($this->is_html?'<html>':'').
			$this->getBody().$this->getSign(),//.($this->is_html?'</html>':''), 
			$this->getHeaders(base64_encode($address)),
			'-r '.$this->from.' '.$add_params);
	}
}
