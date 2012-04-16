<?php
/**
 * Implementation of abstract form's field
 *
 * @author		Romans <romans@adevel.com>
 * @copyright	See file COPYING
 * @version		$Id$
 */
abstract class Form_Field extends AbstractView {
	/**
	 * Description of the field shown next to it on the form
	 */
	public $error_template;    // template used to put errors on the field line
	public $error_mandatory;    // template used to mark mandatory fields
	public $caption;
	protected $value=null;        // use $this->get(), ->set().
	public $short_name=null;
	public $attr=array();
	public $no_save=null;

	public $field_prepend='';
	public $field_append='';

	public $comment='&nbsp;';
	protected $disabled=false;
	protected $mandatory=false;
	public $default_value=null;

	// Field customization
	private $separator='';

	public $show_input_only;
    public $form=null;

    public $button_prepend=null;
    public $button_append=null;

	function init(){
		parent::init();
		if(@$_GET[$this->owner->name.'_cut_field']==$this->name){
			$this->api->addHook('pre-render',array($this,'_cutField'));
		}
	}
    function setForm($form){
        $form->addHook('loadPOST',$this);
        $form->addHook('validate',$this);
        $this->form=$form;
        $this->form->data[$this->short_name] = $this->value;
        $this->value =& $this->form->data[$this->short_name];
        return $this;
    }

	function _cutField(){
		// this method is used by ui.atk4_form, when doing reloadField();
		unset($_GET['cut_object']);
		$this->recursiveRender();
		if($this->api->jquery)$this->api->jquery->getJS($this);
        throw new Exception_StopRender(
				$this->template->renderRegion($this->template->tags['before_field']).
				$this->getInput().
				$this->template->renderRegion($this->template->tags['after_field'])
				);
	}
	function setMandatory($mandatory=true){
		$this->mandatory=$mandatory;
		return $this;
	}
	function setReadonly($readonly=true){
		$this->readonly=$readonly;
		return $this;
	}
	function isMandatory(){
		return $this->mandatory;
	}
	function setCaption($_caption){
		$this->caption=$_caption;
		return $this;
	}
	function displayFieldError($msg=null){
		if(!isset($msg))$msg='Error in field "'.$this->caption.'"';

		$this->form->js(true)
			->atk4_form('fieldError',$this->short_name,$msg)
			->execute();

		$this->form->errors[$this->short_name]=$msg;
	}
	function setNoSave(){
		// Field value will not be saved into defined source (such as database)
		$this->no_save=true;
		return $this;
	}
	function disable(){
		// sets 'disabled' property and setNoSave()
		$this->setProperty('disabled',true);
		$this->setNoSave();
		$this->disabled=true;
		return $this;
	}
	function isDisabled(){
		return $this->disabled;
	}
	function set($value){
		// Use this function when you want to assign $this->value. If you use this function, your field will
		// operate in AJAX mode
		$this->value=$value;
		return $this;
	}
    /** Position can be either 'before' or 'after' */
    function addButton($label,$position='after'){
        if($position=='after'){
            return $this->afterField()->add('Button')->set($label);
        }else{
            return $this->beforeField()->add('Button')->set($label);
        }
        return $this;
    }
    function beforeField(){
        if(!$this->button_prepend)return $this->button_prepend=$this
            ->add('HtmlElement',null,'before_input')->addClass('input-cell');
        return $this->button_prepend;
    }
    function afterField(){
        if(!$this->button_append)return $this->button_append=$this
            ->add('HtmlElement',null,'after_input')->addClass('input-cell');
        return $this->button_append;
    }
    function aboveField(){
        return $this->add('HtmlElement',null,'before_field');
    }
    function belowField(){
        return $this->add('HtmlElement',null,'after_field');
    }
    function setComment($text=''){
        $this->belowField()->setElement('ins')->set($text);
        return $this;
    }
    function addComment($text=''){
        return $this->belowField()->setElement('ins')->set($text);
    }
	function get(){
		return $this->value;
	}
	function setProperty($property,$value){
		$this->attr[$property]=$value;
		return $this;
	}
	function setAttr($property,$value='true'){
		return $this->setProperty($property,$value);
	}
	function setFieldHint($var_args=null){
		/* Adds a hint after this field. Thes will call Field_Hint->set()
		   with same arguments you called this funciton.
		 */
		$hint=$this->add('Form_Hint',null,'after_field');
		call_user_func_array(array($hint,'set'), func_get_args());
		return $this;
	}
	function setFieldTitle($text){
		/* OBSOLETE 4.1 */
		$this->template->trySet('field_title',$text);
		return $this;
	}
	function clearFieldValue(){
		/* OBSOLETE 4.1, use set(null) */
		$this->value=null;
	}
	function loadPOST(){
		$gpc = get_magic_quotes_gpc();
		if ($gpc){
			if(isset($_POST[$this->name]))$this->set(stripslashes($_POST[$this->name]));
			else $this->set($this->default_value);
		} else {
			if(isset($_POST[$this->name]))$this->set($_POST[$this->name]);
			else $this->set($this->default_value);
		}
		$this->normalize();
	}
	function normalize(){
		/* Normalization will make sure that entry conforms to the field type. 
		   Possible trimming, roudning or length enforcements may happen */
		$this->hook('normalize');
	}
	function validate(){
		// NoSave fields should not be validated, disabled as well
		if($this->disabled || $this->no_save)return true;
		// we define "validate" hook, so actual validators could hook it here
		// and perform their checks
		if(is_bool($result = $this->hook('validate')))return $result;
	}
	/** @private - handles field validation callback output */
	function _validateField($caller,$condition,$msg){
		$ret=call_user_func($condition,$this);

		if($ret===false){
			if(is_null($msg))$msg='Error in '.$this->caption;
			$this->displayFieldError($msg);
		}elseif(is_string($ret)){
			$this->displayFieldError($ret);
		}
		return $this;
	}
	/** Executes a callback. If callabck returns string, shows it as error message. If callback returns "false" shows either
	  * $msg or a standard error message about field being incorrect */
	function validateField($condition,$msg=null){
		if(is_callable($condition)){
			$this->addHook('validate',array($this,'_validateField'),array($condition,$msg));
		}else{
			$this->addHook('validate',$s='if(!('.$condition.'))$this->displayFieldError("'.
						($msg?$msg:'Error in ".$this->caption."').'");');
		}
		return $this;
	}
	function _validateNotNull($field){
		if($field->get()==="")return false;
	}
	/** Adds asterisk to the field and validation */
	function validateNotNULL($msg=null){
		$this->setMandatory();
        if($msg){
            $msg=$this->api->_($msg);
        }else{
            $msg=sprintf($this->api->_('%s is a mandatory field'),$this->caption);
        }
		$this->validateField(array($this,'_validateNotNull'),$msg);
		return $this;
	}
	/** obsolete version of validateNotNULL */
	function setNotNull($msg=''){
		$this->validateNotNULL($msg);
		return $this;
	}
	function setDefault($default=null){
		/* OBSOLETE 4.1, use set() */
		$this->default_value=$default;
		return $this;
	}
	function getDefault(){
		/* OBSOLETE 4.1, use set() */
		return $this->default_value;
	}

	function getInput($attr=array()){
		// This function returns HTML tag for the input field. Derived classes should inherit this and add
		// new properties if needed
		return $this->getTag('input',
				array_merge(array(
						'name'=>$this->name,
						'id'=>$this->name,
						'value'=>$this->value,
						),$attr,$this->attr)
				);
	}
	function setSeparator($separator){
		$this->separator = $separator;
		return $this;
	}
	function render(){
		if($this->show_input_only){
			$this->output($this->getInput());
			return;
		}
		if(!$this->error_template)$this->error_template = $this->form->template_chunks['field_error'];
		if((!property_exists($this, 'mandatory_template')) || (!$this->mandatory_template))$this->mandatory_template=$this->form->template_chunks['field_mandatory'];
		$this->template->trySet('field_caption',$this->caption?($this->caption.$this->separator):'');
		$this->template->trySet('field_name',$this->name);
		$this->template->trySet('field_comment',$this->comment);
		// some fields may not have field_imput tag at all...
        if($this->button_prepend || $this->button_append){
            $this->field_prepend.='<div class="input-cell expanded">';
            $this->field_append='</div>'.$this->field_append;
            $this->template->trySetHTML('input_row_start','<div class="input-row">');
            $this->template->trySetHTML('input_row_stop','</div>');
        }
		$this->template->trySetHTML('field_input',$this->field_prepend.$this->getInput().$this->field_append);
		$this->template->trySetHTML('field_error',
				isset($this->form->errors[$this->short_name])?
				$this->error_template->set('field_error_str',$this->form->errors[$this->short_name])->render()
				:''
				);
		if (is_object($this->mandatory_template)) {
			$this->template->trySet('field_mandatory',$this->isMandatory()?$this->mandatory_template->render():'');
		}
		$this->output($this->template->render());
	}

	function getTag($tag, $attr=null, $value=null){
		/**
		 * Draw HTML attribute with supplied attributes.
		 *
		 * Short description how this getTag may be used:
		 *
		 * Use get tag to build HTML tag.
		 * echo getTag('img',array('src'=>'foo.gif','border'=>0);
		 *
		 * The unobvius advantage of this function is ability to merge
		 * attribute arrays. For example, if you have function, which
		 * must display img tag, you may add optional $attr argument
		 * to this function.
		 *
		 * function drawImage($src,$attr=array()){
		 *     echo getTag('img',array_merge(array('src'=>$src),$attr));
		 * }
		 *
		 * so calling drawImage('foo.gif') will echo: <img src="foo.gif">
		 *
		 * The benefit from such a function shows up when you use 2nd argument:
		 *
		 * 1. adding additional attributes
		 * drawImage('foo.gif',array('border'=>0'));
		 * --> <img src="foo.gif" border="0">
		 * (NOTE: you can even have attr templates!)
		 *
		 * 2. adding no-value attributes, such as nowrap:
		 * getTag('td',arary('nowrap'=>true));
		 * --> <td nowrap>
		 *
		 * 3. disabling some attributes.
		 * drawImage('foo.gif',array('src'=>false));
		 * --> <img>
		 *
		 * 4. re-defining attributes
		 * drawImage('foo.gif',array('src'=>'123'));
		 * --> <img src="123">
		 *
		 * 5. or you even can re-define tag itself
		 * drawImage('foo.gif',array(
		 *                      ''=>'input',
		 *                      'type'=>'picture'));
		 * --> <input type="picture" src="foo.gif">
		 *
		 * 6. xml-valid tags without closing tag
		 * getTag('img/',array('src'=>'foo.gif'));
		 * --> <img src=>"foo.gif"/>
		 *
		 * 7. closing tags
		 * getTag('/td');
		 * --> </td>
		 *
		 * 8. using $value will add $value after tag followed by closing tag
		 * getTag('a',array('href'=>'foo.html'),'click here');
		 * --> <a href="foo.html">click here</a>
		 *
		 * 9. you may not skip attribute argument.
		 * getTag('b','text in bold');
		 * --> <b>text in bold</b>
		 *
		 * 10. nesting
		 * getTag('a',array('href'=>'foo.html'),getTag('b','click here'));
		 * --> <a href="foo.html"><b>click here</b></a>
		 */

		if(is_string($attr)){
			$value=$attr;
			$attr=null;
		}
		if(!$attr){
			return "<$tag>".($value?$value."</$tag>":"");
		}
		$tmp = array();
		if(substr($tag,-1,1)=='/'){
			$tag = substr($tag,0,-1);
			$postfix = '/';
		} else $postfix = '';
		foreach ($attr as $key => $val) {
			if($val === false) continue;
			if($val === true) $tmp[] = "$key";
			elseif($key === '')$tag=$val;
			else $tmp[] = "$key=\"".htmlspecialchars($val)."\"";
		}
		return "<$tag ".join(' ',$tmp).$postfix.">".($value?$value."</$tag>":"");
	}

	function setSource(){
		return call_user_func_array(array($this->form,'setSource'),func_get_args());
	}
	function addField(){
		return call_user_func_array(array($this->form,'addField'),func_get_args());
		//throw new ObsoleteException('$form->addField() now returns Field object and not Form. Do not chain it.');
	}
}

///////// Because many fields are really simple extenions of the base-line field, they are
///////// defined here.

class Form_Field_Line extends Form_Field {
	function getInput($attr=array()){
		return parent::getInput(array_merge(array('type'=>'text'),$attr));
	}
}
// Visually different fields
class Form_Field_Search extends Form_Field {
	// WARNING: <input type=search> is safari extention and is will not validate as valid HTML
	function getInput($attr=array()){
		return parent::getInput(array_merge(array('type'=>'search'),$attr));
	}
}
class Form_Field_Checkbox extends Form_Field {
    public $true_value=1;
    public $false_value=0;
	function init(){
		parent::init();
		$this->default_value='';
	}
	function getInput($attr=array()){
		$this->template->trySet('field_caption','');
		$this->template->tryDel('label_container');
		if(strpos('<',$this->caption)!==false){
			// HTML in label
			$label=$this->caption;
		}else{
			$label='<label for="'.$this->name.'">'.$this->caption.'</label>';
		}
		return parent::getInput(array_merge(
					array(
						'type'=>'checkbox',
						'value'=>$this->true_value,
						'checked'=>(boolean)($this->true_value==$this->value)
					     ),$attr
					)).' &ndash; '.$label;
	}
	function loadPOST(){
		if(isset($_POST[$this->name])){
			$this->set($this->true_value);
		}else{
			$this->set($this->false_value);
		}
	}
}
class Form_Field_Password extends Form_Field {
	function normalize(){
		// user may have entered spaces accidentally in the password field.
		// Clean them up.
		$this->set(trim($this->get()));
		parent::normalize();
	}
	function getInput($attr=array()){
		return parent::getInput(array_merge(
					array(
						'type'=>'password',
					     ),$attr
					));
	}
}
class Form_Field_Hidden extends Form_Field {
	function getInput($attr=array()){
		return parent::getInput(array_merge(
					array(
						'type'=>'hidden',
					     ),$attr
					));
	}
	function render(){
        if($this->owner == $this->form){
            $this->form->template_chunks['form']->append('Content',$this->getInput());
        }else $this->output($this->getInput());
	}
}
class Form_Field_Readonly extends Form_Field {
	function init(){
		parent::init();
		$this->setNoSave();
	}

	function getInput($attr=array()){
		if (isset($this->value_list)){
			return $this->value_list[$this->value];
		} else {
			return $this->value;
		}
	}
	function setValueList($list){
		$this->value_list = $list;
		return $this;
	}

}
class Form_Field_Time extends Form_Field {
	function getInput($attr=array()){
		return parent::getInput(array_merge(array('type'=>'text',
						'value'=>format_time($this->value)),$attr));
	}
}
class Form_Field_Date extends Form_Field {
	private $sep = '-';
	private $is_valid = false;

	/*function getInput($attr=array()){
	  return parent::getInput(array_merge(array('type'=>'text',
	  'value'=>($this->is_valid ? date('Y-m-d', $this->value) : $this->value)),$attr));
	  }*/
	private function invalid(){
		return $this->displayFieldError('Not a valid date');
	}
	function validate(){
		//empty value is ok
		if($this->value==''){
			$this->is_valid=true;
			return parent::validate();
		}
		//checking if there are 2 separators
		if(substr_count($this->value, $this->sep) != 2){
			$this->invalid();
		}else{
			$c = explode($this->sep, $this->value);
			//day must go first, month should be second and a year should be last
			if(strlen($c[0]) != 4 ||
					$c[1] <= 0 || $c[1] > 12 ||
					$c[2] <= 0 || $c[2] > 31)
			{
				$this->invalid();
			}
			//now attemting to convert to date
			if(strtotime($this->value)==''){
				$this->invalid();
			}else{
				//$this->set(strtotime($this->value));
				$this->set($this->value);
				$this->is_valid=true;
			}
		}
		return parent::validate();
	}
}
class Form_Field_Text extends Form_Field {
	function init(){
		$this->attr=array('rows'=>5);
		parent::init();
	}
	function setFieldHint($text){
		return parent::setFieldHint('<br/>'.$text);
	}
	function getInput($attr=array()){

		return
			parent::getInput(array_merge(array(''=>'textarea'),$attr)).
			htmlspecialchars(stripslashes($this->value),ENT_COMPAT,'ISO-8859-1',false).
			$this->getTag('/textarea');
	}
}

class Form_Field_Number extends Form_Field_Line {
	function normalize(){
		$v=$this->get();

		// get rid of  TODO

		$this->set($v);
	}
}
class Form_Field_Money extends Form_Field_Line {
	function getInput($attr=array()){
		return parent::getInput(array_merge(array('value'=>number_format($this->value,2)),$attr));
	}
}

// The following clases operates with predefined value lists.
// User is picking one or several options
class Form_Field_ValueList extends Form_Field {
	/*
	 * This is abstract class. Use this as a base for all the controls
	 * which operate with predefined values such as dropdowns, checklists
	 * etc
	 */
	public $value_list=array(
			0=>'No available options #1',
			1=>'No available options #2',
			2=>'No available options #3'
			);
    public $empty_text='';
    function setModel($m){
        $ret=parent::setModel($m);

        $this->setValueList(array('foo','bar'));
        return $ret;
    }
    /** Default value which is displayed on a null-value option. Set to "Select.." or "Pick one.." */
    function setEmptyText($empty_text){
        $this->empty_text=$empty_text;
        return $this;
    }
	function getValueList(){

        if($this->model){
            $title=$this->model->getTitleField();
            $id=$this->model->id_field;
            if ($this->empty_text){
                $res=array(''=>$this->empty_text);
            } else {
                $res = array();
            }
			foreach($this->model as $row){
				$res[$row[$id]]=$row[$title];
			}
			return $this->value_list=$res;
		}

        if($this->empty_text && isset($this->value_list[''])){
            $this->value_list['']=$this->empty_text;
        }
		return $this->value_list;
	}
	function setValueList($list){
		$this->value_list = $list;
		return $this;
	}
	function loadPOST(){
		$data=$_POST[$this->name];
		if(is_array($data))$data=join(',',$data);
		$gpc = get_magic_quotes_gpc();
		if ($gpc){
			if(isset($_POST[$this->name]))$this->set(stripslashes($data));
		} else {
			if(isset($_POST[$this->name]))$this->set($data);
		}
	}
}
class Form_Field_Dropdown extends Form_Field_ValueList {
    public $empty_value='';

    function emptyValue($v){
        $this->empty_value=$v;
        return $this;
    }
	function validate(){
		if(!$this->value)return parent::validate();
        $this->getValueList(); //otherwise not preloaded?
		if(!isset($this->value_list[$this->value])){
			/*
			   if($this->api->isAjaxOutput()){
			   $this->ajax()->displayAlert($this->short_name.": This is not one of the offered values")
			   ->execute();
			   }
			 */
			$this->form->errors[$this->short_name]="This is not one of the offered values";
		}
		return parent::validate();
	}
	function getInput($attr=array()){
		$output=$this->getTag('select',array_merge(array(
						'name'=>$this->name,
						'id'=>$this->name,
						),
					$attr,
					$this->attr)
				);

        foreach($this->getValueList() as $value=>$descr){
            // Check if a separator is not needed identified with _separator<
            $output.=
                $this->getOption($value)
                .htmlspecialchars($descr)
                .$this->getTag('/option');
        }
		$output.=$this->getTag('/select');
		return $output;
	}
	function getOption($value){
		return $this->getTag('option',array(
					'value'=>$value,
					'selected'=>$value == $this->value
					));
	}
}
class Form_Field_CheckboxList extends Form_Field_ValueList {
	/*
	 * This field will create 2 column of checkbox+labels from defived value
	 * list. You may check as many as you like and when you save their ID
	 * values will be stored coma-separated in varchar type field.
	 *
	 $f->addField('CheckboxList','producers','Producers')->setValueList(array(
	 1=>'Mr John',
	 2=>'Piter ',
	 3=>'Michail Gershwin',
	 4=>'Bread and butter',
	 5=>'Alex',
	 6=>'Benjamin',
	 7=>'Rhino',
	 ));

	 */

	var $columns=2;
	function validate(){
		return true;
	}
	function getInput($attr=array()){
		$output='<table class="atk-checkboxlist" border=0 id="'.$this->name.'"><tbody>';
		$column=0;
		$current_values=explode(',',$this->value);
		$i=0;//Skai
		foreach($this->getValueList() as $value=>$descr){
			if($column==0){
				$output.="<tr><td align=\"left\">";
			}else{
				$output.="</td><td align=\"left\">";
			}

			$output.=
				$this->getTag('input',array(
							'type'=>'checkbox',
							'value'=>$value,
							'name'=>$this->name.'['.$i++.']',//Skai
							'checked'=>in_array($value,$current_values)
							)).htmlspecialchars($descr);
			$column++;
			if($column==$this->columns){
				$output.="</td></tr>";
				$column=0;
			}
		}
		$output.="</tbody></table>";
		return $output;
	}

	function loadPOST(){
		$data=$_POST[$this->name];
		if(is_array($data))
			$data=join(',',$data);
		else
			$data='';

		$gpc = get_magic_quotes_gpc();
		if ($gpc){
			$this->set(stripslashes($data));
		} else {
			$this->set($data);
		}
	}
}

class Form_Field_Radio extends Form_Field_ValueList {
	function validate(){
		if(!isset($this->value_list[$this->value])){
			/*
			   if($this->api->isAjaxOutput()){
			   echo $this->ajax()->displayAlert($this->short_name.":"."This is not one of offered values")->execute();
			   }
			 */
			$this->displayFieldError("This is not one of offered values");
		}
		return parent::validate();
	}
	function getInput($attr=array()){
		$output = '<div id="'.$this->name.'" class="atk-form-options">';
		foreach($this->getValueList() as $value=>$descr){
			$output.=
				"<div>".$this->getTag('input',
						array_merge(
							array(
								'id'=>$this->name.'_'.$value,
								'name'=>$this->name,
								'type'=>'radio',
								'value'=>$value,
								'checked'=>$value == $this->value
							     ),
							$this->attr,
							$attr
							))
				."<label for='".$this->name.'_'.$value."'>".htmlspecialchars($descr)."</label></div>";
		}
		$output .= '</div>';
		return $output;
	}
}
