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
	public $from;
	public $bcc;
	public $subject;
	public $body;
	public $headers;
	protected $template=null;
	
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
		// gathering parts:
		// headers
		$this->headers=$this->template->cloneRegion('headers');
		if(!$this->headers->tags)$this->loadDefaultTemplate();
		$this->subject=$this->template->get('subject');
		// body
		$this->body=$this->template->cloneRegion('body');
		$sign=$this->body->cloneRegion('sign');
		$this->body->tryDel('sign');
		if($sign->tags)$this->sign=$sign;
		$this->from=$this->template->get('from');
		$this->bcc=$this->template->get('bcc');
		return $this;
	}
	function setTag($tag,$value){
		/**
		 * Sets the tag value throughout the template, including all parts
		 */
		$this->template->trySet($tag,$value);
		$this->body->trySet($tag,$value);
		$this->headers->trySet($tag,$value);
		$this->sign->trySet($tag,$value);
	}
	function loadDefaultTemplate(){
		/**
		 * Loads default template and sets sign and headers from it
		 */
		$template=$this->add('SMlite')->loadTemplate('mail/mail','.txt');
		$this->headers=$template->cloneRegion('headers');
		$this->sign=$template->cloneRegion('sign');
	}
	function getBody(){
		// returns the rendered mail body
		return is_string($this->body)?$this->body:$this->body->render();
	}
	function getSign(){
		return is_string($this->sign)?$this->sign:$this->sign->render();
	}
	function getHeaders(){
		// returns the rendered headers
		$this->headers->set('from',$this->from);
		if($this->bcc)$this->headers->set('bcc',$this->bcc);
		// headers should be separated by CRLF (\r\n), there should be no spaces
		// between lines (they are if we don't specify bcc)
		$headers=split("\n",$this->headers->render());
		foreach($headers as $id=>$header)if(trim($header)=='')unset($headers[$id]);
		return join("\n",$headers);
	}
	function send($address){
		// before sending we should set the X-B64 header
		$this->headers->set('xb64',base64_encode($address));
		// send an email with defined parameters
		mail($address, $this->subject, $this->getBody().$this->getSign(), $this->getHeaders());
	}
}
