<?php
/***********************************************************
  jQuery chain proxy. Returned by view->js(). Calls to this
  class will be converted into jQuery calls.

  Feel free to call _XX functions in this class

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
   This class represents sequentall calls to one jQuery object
 */
class jQuery_Chain extends AbstractModel {
    public $str='';
    public $prepend='';
    public $selector=null;
    public $enclose=false;
    public $preventDefault=false;
    public $base='';
    function __call($name,$arguments){
        if($arguments){
            $a2=$this->_flattern_objects($arguments,true);
            $this->str.=".$name(".$a2.")";
        }else{
            $this->str.=".$name()";
        }
        return $this;
    }
    /* convert reserved words or used methods into js calls, such as "execute" */
    function _fn($name,$arguments=array()){
        // Wrapper for functons which use reserved words
        return $this->__call($name,$arguments);
    }
    /* converting this object into string will produce JavaScript code */
    function __toString(){
        return $this->_render();
    }

    /* Some methods shouldn't be special! */
    function each(){
        return $this->__call('each',func_get_args());
    }

    /* Chain binds to parent object by default. Use this to use other selector $('selector') */
    function _selector($selector=null){
        $this->selector=$selector;
        return $this;
    }
    /* Use this to bind chain to document $(document)... */
    function _selectorDocument(){
        $this->selector='__atk_selector_document';
        return $this;
    }
    /* Use this to bind chain to window $(window)... */
    function _selectorWindow(){
        $this->selector='__atk_selector_window';
        return $this;
    }
    /* Use this to bind chain to "this" $(this)... */
    function _selectorThis(){
        $this->selector='__atk_selector_this';
        return $this;
    }
    /* Use this to bind chain to "region" $(region). Region is defined by ATK when reloading */
    function _selectorRegion(){
        $this->selector='__atk_selector_region';
        return $this;
    }
    /* Execute more JavaScript code before chain. Avoid using. */
    function _prepend($code){
        if(is_array($code)){
            $code=join(';',$code);
        }
        $this->prepend=$code.';'.$this->prepend;
        return $this;
    }
    function debug(){
        $this->debug=true;
        return $this;
    }
    /* Send chain in response to form submit, button click or ajaxec() function for AJAX control output */
    function execute(){
        if(isset($_POST['ajax_submit']) || $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest'){
            //if($this->api->jquery)$this->api->jquery->getJS($this->owner);

            $x=$this->api->template->get('document_ready');
            if(is_array($x))$x=join('',$x);
            echo $this->_render();
            $this->api->hook('post-js-execute');
            exit;
        }else return $this;
    }
    /* [private] used by custom json_encoding */
    function _safe_js_string($str) {
        $l=strlen($str);
        $ret="";
        for($i=0;$i<$l;++$i) {
            switch($str[$i]) {
                case "\r": $ret.="\\r"; break;
                case "\n": $ret.="\\n"; break;
                case "\"":     case "'":     case "<": case ">":  case "&":  case "\\":
                           $ret.='\x'.dechex( ord($str[$i] ) );
                           break;
                default:
                           $ret.=$str[$i];
                           break;
            }
        }
        return $ret;
    }
    /* [private] custom json_encoding. called on function arguments. */
    protected function _flattern_objects($arg,$return_comma_list=false){
        /*
         * This function is very similar to json_encode, however it will traverse array
         * before encoding in search of objects based on AbstractObject. Those would
         * be replaced with their json representation if function exists, otherwise
         * with string representation
         */
        if(is_object($arg)){
            if($arg instanceof jQuery_Chain){
                $r=$arg->_render();
                if(substr($r,-1)==';')$r=substr($r,0,-1);
                return $r;
            }elseif($arg instanceof AbstractView){
                return "'#".str_replace('/','_',$arg->name)."'";
            }else{
                return "'".$this->_safe_js_string((string)$arg)."'";    // indirectly call toString();
            }
        }elseif(is_array($arg)){
            $a2=array();
            // is array associative? (hash)
            $assoc=$arg!=array_values($arg);

            foreach($arg as $key=>$value){
                $value=$this->_flattern_objects($value);
                $key=$this->_flattern_objects($key);
                if(!$assoc || $return_comma_list){
                    $a2[]=$value;
                }else{
                    $a2[]=$key.':'.$value;
                }
            }
            if($return_comma_list){
                $s=join(',',$a2);
            }elseif($assoc){
                $s='{'.join(',',$a2).'}';
            }else{
                $s='['.join(',',$a2).']';
            }
        }elseif(is_string($arg)){
            $s="'".$this->_safe_js_string($arg)."'";
        }elseif(is_bool($arg)){
            $s=json_encode($arg);
        }elseif(is_numeric($arg)){
            $s=json_encode($arg);
        }elseif(is_null($arg)){
            $s=json_encode($arg);
        }else{
            throw $this->exception('Unable to encode value for jQuery Chain - unknown type')
                ->addMoreInfo('arg',$arg);
        }
        return $s;
    }
    /* Calls real redirect (from univ), but accepts page name. Use url() for 1st argument manually anyway. */
    function redirect($page=null,$arg=null){
        $url=$this->api->url($page,$arg);
        return $this->_fn('redirect',array($url));
    }
    /* Reload object. You can bind this to custom event and trigger it if object is not directly accessible. */
    function reload($arguments=array(),$fn=null,$url=null){
        if($fn)$fn->_enclose();
        $id=$this->owner;
        if(!$url)$url=$this->api->url(null,array('cut_object'=>$id->name));
        return $this->_fn('atk4_reload',array($url,$arguments,$fn));
    }
    /* Chain will not be called but will return callable function instead. */
    function _enclose($fn=null,$preventDefault=false){
        // builds structure $('obj').$fn(function(){ $('obj').XX; });
        if($fn===null)$fn=true;
        $this->enclose=$fn;
        $this->preventDefault=$preventDefault;
        return $this;
    }
    function _render(){
        $ret='';
        $ret.=$this->prepend;
        if($this->selector===false){
            $ret.="$";
        }elseif($this->selector==='__atk_selector_this'){
            $ret.="$(this)";
        }elseif($this->selector==='__atk_selector_document'){
            $ret.="$(document)";
        }elseif($this->selector==='__atk_selector_window'){
            $ret.="$(window)";
        }elseif($this->selector==='__atk_selector_region'){
            $ret.="$(region)";
        }else{
            if($this->str)$ret.="$('".($this->selector?$this->selector:'#'.str_replace('/','_',$this->owner->name))."')";
        }
        $ret.=$this->str;
        if($this->enclose===true){
            if($this->preventDefault){
                $ret="function(ev){ev.preventDefault();ev.stopPropagation(); ".$ret." }";
            }else{
                $ret="function(){ ".$ret." }";
            }
        }elseif($this->enclose){
            $ret="$('".($this->selector?$this->selector:'#'.$this->owner->name)."')".
                ".bind('".$this->enclose."',function(ev){ ev.preventDefault();ev.stopPropagation(); ".$ret." })";
        }
        if(@$this->debug){
            echo "<font color='blue'>".htmlspecialchars($ret).";</font><br/>";
            $this->debug=false;
        }
        return $ret;
    }
    /* Returns HTML for a link with text $text. When clicked will execute this chain. */
    function getLink($text){
        return '<a href="javascript:void(0)" onclick="'.$this->getString().'">'.$text.'</a>';
    }
    function getString(){
        return $this->_render();
    }
    /* Specify requirement for stylesheet. Will load dynamically. */
    function _css($file){
        $this->api->jquery->addStylesheet($file);
        return $this;
    }
    /* Specify requirement for extra javascript include. Will load dynamically. */
    function _load($file){
        $this->api->jquery->addInclude($file);
        return $this;
    }
}
