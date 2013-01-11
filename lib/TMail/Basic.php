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
class TMail_Basic extends AbstractModel {
    public $mail_template=null;

    public $template_class='TMail_Template';
    public $master_template='shared';

    public $boundary;

    public $args=array();

    public $version='2.0';

    function init(){
        parent::init();
        $master_template = $this->add($this->template_class)->loadTemplate('shared','.mail');
        $this->template=$master_template->cloneRegion('body');
        $this->headers=$master_template->cloneRegion('headers');

        $this->boundary=str_replace('.','',uniqid('atk4tmail',true));

        if($t=$this->api->getConfig('tmail/transport',false)){
            $this->addTransport($t);
        }
    }
    function extractEmail($fuzzy_email){
        preg_match('/^(?:"?([^@"]+)"?\s)?<?([^>]+@[^>]+)>?$/',$fuzzy_email,$m);
        return $m;
    }
    function defaultTemplate(){
        return array('shared');
    }
    function addTransport($t){
        return $this->add('TMail_Transport_'.$t);
    }
    function addPart($p){
        return $this->add('TMail_Part_'.$p);
    }
    /* Setting Content Separatelly */
    function setText($text){
        $this->addPart('Text')->set($text);
    }
    function setHTML($html){
        $this->addPart('HTML')->set($html);
    }
    function loadTemplate($template,$junk=null){
        return $this->setTemplate($template);
    }
    function setTemplate($template){
        $t=$this->add($this->template_class)->loadTemplate($template,'.mail');

        if($t->is_set('subject')){
            $s=trim($t->cloneRegion('subject')->render());
            $this->set('subject',$s);
            $t->del('subject');
        }

        if($t->is_set('html')){
            $this->setText($t->cloneRegion('text'));
            $this->setHtml($t->cloneRegion('html'));
        }elseif($t->is_set('body')){
            $this->set($t->cloneRegion('body'));
        }else{
            $this->set($t);
        }
    }
    function setTag($arg,$val=null){
        return $this->set($arg,$val);
    }
    function set($arg,$val=null){
        if(is_array($arg)){
            $this->args=array_merge($this->args,$arg);
        }else{
            if($val===false){
                unset($this->args[$arg]);
            }elseif(is_null($val)){
                $this->addPart('Both')->set($arg);
            }else{
                $this->args[$arg]=$val;
            }
        }
        return $this;
    }
    function get($arg){
        return $this->args[$arg];
    }
    function render(){
        $this->template->set('body_parts','');
        foreach($this->elements as $el){
            if($el instanceof TMail_Part){
                $this->template->appendHTML('body_parts',$el->render());
            }
        }
        $this->template->set('boundary',$this->boundary);
        $this->headers
            ->set('boundary',$this->boundary)
            ->setHTML($this->args);
    }
    function send($to,$from=null){
        if(is_null($from) && isset($this->args['from']))$from=$this->args['from'];
        if(is_null($from))$from=$this->api->getConfig('tmail/from');

        if(!isset($this->args['from_formatted']))$this->args['from_formatted']=$from;
        if(!isset($this->args['to_formatted']))$this->args['to_formatted']=$to;

        $from=$this->extractEmail($from);$from=$from[2];
        $to=$this->extractEmail($to);$to=$to[2];


        $this->render();
        $body = $this->template->render();
        $headers = trim($this->headers->render());
        $subject = $this->args['subject'];

        // TODO: should we use mb_encode_mimeheader ?
        if(!($res=$this->hook('send',array($to,$from,$subject,$body,$headers)))){
            return mail($to,$subject,$body,$headers,'-f '.$from);
        }
        return $res;
    }
}

class TMail_Part extends AbstractModel {
    public $template=null;
    public $content;
    public $auto_track_element=true;
    function init(){
        parent::init();

        // Initialize template of this part
        $t=$this->defaultTemplate();
        $this->template=$this->add($this->owner->template_class)
            ->loadTemplate($t[0],'.mail');

        if($t[1])$this->template=$this->template->cloneRegion($t[1]);
    }
    function set($content){
        $this->content=$content;
    }
    function render(){
        $c=$this->content;
        if($c instanceof SMLite){
            $c->set($this->owner->args);
            $c=$c->render();
        }

        $this->template->setHTML($this->owner->args);
        $this->template->setHTML('Content',$c);
        $this->template->set('boundary',$this->owner->boundary);

        return $this->template->render();
    }
    function defaultTemplate(){
        return array('shared','body_part');
    }
}
class TMail_Part_HTML extends TMail_Part {
}
class TMail_Part_Text extends TMail_Part {
    function init(){
        parent::init();
        $this->template->set('contenttype','text/plain');
    }
}
class TMail_Part_Both extends TMail_Part {
    function render(){
        $html=parent::render();
        $this->template->set('contenttype','text/plain');
        $c=$this->content;
        if($this->content instanceof SMLite)$this->content=$this->content->render();
        $this->content=strip_tags($this->content);
        $plain=parent::render();
        $this->content=$c;
        return $plain.$html;
    }
}

class TMail_Part_Attachment extends TMail_Part {
}


/**
  * Generic implementation of TMail transport.
  */
class TMail_Transport extends AbstractController {
    function init(){
        parent::init();

        $this->owner->addHook('send',array($this,'send'));
    }
}
/**
  * Uses default sending routine
  */
class TMail_Transport_Fallback extends TMail_Transport {
    function send($tm,$to,$from,$subject,$body,$headers){
        $this->breakHook(false);
    }
}
/**
  * Discards email as it's being sent out
  */
class TMail_Transport_Discard extends TMail_Transport {
    function send($tm,$to,$from,$subject,$body,$headers){
        $this->breakHook(true);
    }
}
class TMail_Transport_Echo extends TMail_Transport {
    function send($tm,$to,$from,$subject,$body,$headers){
        echo "to: $to<br/>";
        echo "from: $from<br/>";
        echo "subject: $subject<br/>";
        echo "<textarea cols=100 rows=30>$body</textarea><hr/>";
        echo "<textarea cols=100 rows=10>$headers</textarea><hr/>";
    }
}
class TMail_Transport_DBStore extends TMail_Transport {
    public $model=null;

    function setModel($m){
        if(is_string($m))$m='Model_'.$m;
        $this->model=$this->add($m);
        return $this->model;
    }
    function send($tm,$to,$from,$subject,$body,$headers){
        if(!$this->model)throw $this->exception('Must use setModel() on DBStore Transport');
        $data=array(
                'to'=>$to,
                'from'=>$from,
                'subject'=>$subject,
                'body'=>$body,
                'headers'=>$headers
                );

        $this->model->unloadData()->set($data)->update();
        return $this;
    }
}

class TMail_Template extends SMLite {
    public $template_type='mail';
    function init(){
        parent::init();
    }
}
