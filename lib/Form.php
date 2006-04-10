<?
include_once'Form/Field.php';
/**
 * This class implements generic form, which you can actually use without
 * redeclaring it. Just add fields, buttons and use execute method.
 *
 * @author		Romans <romans@adevel.com>
 * @copyright	See file COPYING
 * @version		$Id$
 */
class Form extends AbstractView {
    public $errors=array();
                            // Here we will have a list of errors occured in the form, when we tried to submit it.
                            //  field_name => error

    public $template_chunks=array();
                            // Those templates will be used when rendering form and fields

    private $data = array(); // This array holds list of values prepared for fields before their initialization. When fields
                            // are initialized they will look into this array to see if there are default value for them.
                            // Afterwards fields will link to $this->data, so changing $this->data['fld_name'] would actually
                            // affect field's value.
                            //  You should use $this->set() and $this->get() to read/write individual field values. You
                            //  should use $this->setStaticSource() to load values from hash
                            //  AAAAAAAAAA: this array is no more!

    public $last_field = null;  // contains reference to last-added filed

    public $bail_out = false;   // if this is true, we won't load data or submit or validate anything.
    public $loaded_from_db = false;     // if true, update() will try updating existing row. if false - it would insert new

    public $dq = null;
    function init(){
        /**
         * During form initialization it will go through it's own template and search for lots of small template
         * chunks it will be using. If those chunk won't be in template, it will fall back to default values. This way
         * you can re-define how form will look, but only what you need in particular case. If you don't specify template
         * at all, form will work with default look.
         */
        parent::init();

        // commonly replaceable chunks
        $this->grabTemplateChunk('form_line');      // template for form line, must contain field_caption,field_input,field_error
        if($this->template->is_set('hidden_form_line'))
            $this->grabTemplateChunk('hidden_form_line');
        $this->grabTemplateChunk('field_error');    // template for error code, must contain field_error_str
        $this->grabTemplateChunk('form');           // template for whole form, must contain form_body, form_buttons, form_action,
                                                    //  and form_name

        // ok, other grabbing will be done by field themselves as you will add them to the form.
        // They will try to look into this template, and if you don't have apropriate templates
        // for them, they will use default ones.
        $this->template_chunks['form']->del('form_body');
        $this->template_chunks['form']->del('form_buttons');
        $this->template_chunks['form']->set('form_name',$this->name);

        // After init method have been executed, it's safe for you to add controls on the form. BTW, if
        // you want to have default values such as loaded from the table, then intialize $this->data array
        // to default values of those fields.
        $this->api->addHook('pre-exec',array($this,'loadData'));
    }
    function defaultTemplate(){
        return array('form','form');
    }
    function grabTemplateChunk($name){
        if($this->template->is_set($name)){
            $this->template_chunks[$name] = $this->template->cloneRegion($name);
        }else{
            //return $this->fatal('missing form tag: '.$name);
            // hmm.. i wonder what ? :)
        }
    }

    function addField($type,$name,$caption=null){
        if($caption===null)$caption=$name;

        $this->last_field=$this->add('Form_Field_'.$type,$name)
            ->setCaption($caption);

        $this->last_field->short_name = $name;

        return $this;
    }
    function addComment($comment){
        $this->add('Text','c'.count($this->elements),'form_body')->set("<tr><td colspan=2 align=center>$comment</td></tr>");
        return $this;
    }
    function addSeparator($separator="<hr>"){
        return $this->addComment($separator);
    }



    // Operating with field values
    function get($field){
        return $this->elements[$field]->value;
    }
    function clearData(){
        $this->downCall('clearFieldValue');
    }
    function setSource($table,$db_fields=null){
        if($db_fields===null){
            $db_fields=array();
            foreach($this->elements as $key=>$el){
                if(!($el instanceof Form_Field))continue;
                if($el->no_save)continue;
                $db_fields[]=$key;
            }
        }
        $this->dq = $this->api->db->dsql()
            ->table($table)
            ->field('*',$table)
            ->limit(1);
        return $this;
    }
    function set($field_or_array,$value='undefined_value'){
        // We use undefined, because 2nd argument of "null" is meaningfull
        if($value==='undefined_value'){
            if(is_array($field_or_array)){
                foreach($field_or_array as $key=>$val){
                    if(isset($this->elements[$key]))$this->set($key,$val);
                }
                return $this;
            }else{
                $value=$field_or_array;
                $field_or_array=$this->last_field->short_name;
            }
        }

        if(!isset($this->elements[$field_or_array]))
            throw new BaseException("Trying to set value for non-existang field $field_or_array");
        $this->elements[$field_or_array]->value=$value;

        return $this;
    }

    // Modifying existing field properties and behavior
    function setProperty($property,$value=null){
        // Add property to field TAG
        $this->last_field->setProperty($property,$value);
        return $this;
    }

    function validateNotNULL($msg=''){
        $this->last_field->addHook('validate','if(!$this->value)$this->displayFieldError("'.
                    ($msg?$msg:'Please, fill ".$this->caption."').'");');
        return $this;
    }
    function setNotNull($msg=''){
        $this->validateNotNULL($msg);
        return $this;


        // TODO: mark field so that it have that red asterisk
    }
    function setNoSave(){
        $this->last_field->setNoSave();
        return $this;
    }
    function setValueList($list){
        $this->last_field->setValueList($list);
        return $this;
    }


    function addSubmit($label,$name=null){
        $field = $this->add('Form_Submit',isset($name)?$name:$label)
            ->setLabel($label)
            ->setNoSave();
        return $this;
    }
    function addAjaxButtonAction($label,$name=null){

        // Now add the regular button first
        $field = $this->add('Form_Button',isset($name)?$name:$label)
            ->setLabel($label)
            ->setNoSave();

        // And teach it to use AJAX
        return $field->onclick = $field->add('Ajax')->useProgressIndicator($this->name.'_loading');
    }
    function addCondition($field,$value=null){
        $this->dq
            ->set($field,$value)
            ->where($field,$value);
        return $this;
    }
    function addConditionFromGET($field,$get_field=null){
        // If GET pases an argument you need to put into your where clause, this is the function you should use.
        if(!isset($get_field))$get_field=$field;
        $this->api->stickyGET($get_field);
        return $this->addCondition($field,$_GET[$get_field]);
    }
    function loadData(){
        /**
         * This call will be sent to fields, and they will initialize their values from $this->data
         */
        if($this->bail_out)return;
        if($this->dq){
            // we actually initialize data from database
            $data = $this->dq->do_getHash();
            if($data){
                $this->set($data);
                $this->loaded_from_db=true;
            }
        }
    }
    function update(){
        if(!$this->dq)throw BaseException("Can't save, query was not initialized");
        foreach($this->elements as $short_name => $element)
        	if($element instanceof Form_Field)if(!is_null($element->value)){
            $this->dq->set($short_name, $element->value);
        }
        if($this->loaded_from_db){
            // id is present, let's do update
            return $this->dq->do_update();
        }else{
            // id is not present
            return $this->dq->do_insert();
        }
    }
    function submitted(){
        /**
         * Default down-call submitted will automatically call this method if form was submitted
         */
        // We want to give flexibility to our controls and grant them a chance to
        // hook to those spots here.
        // On Windows platform mod_rewrite is lowercasing all the urls.

        if($_GET['submit']!=$this->name)return false;
        if($this->bail_out)return false;

        $this->downCall('loadPOST');
        $this->downCall('validate');

        return empty($this->errors);
    }
    function isSubmitted(){
        // This is alternative way for form submission. After  form is initialized you can call this method. It will
        // hurry up all the steps, but you will have ready-to-use form right away and can make submission handlers
        // easier
        $this->loadData();
        $result = $_POST && $this->submitted();
        $this->bail_out=true;
        return $result;
    }
    function render(){
        // Assuming, that child fields already inserted their HTML code into 'form'/form_body using 'form_line'
        // Assuming, that child buttons already inserted their HTML code into 'form'/form_buttons

        // We don't have anything else to do!
        $this->template_chunks['form']
            ->set('form_action',$this->api->getDestinationURL(null,array('submit'=>$this->name)));
        $this->owner->template->append($this->spot,$r=$this->template_chunks['form']->render());
    }
    function isClicked($name){
        return $this->api->isClicked($this->name.'_'.$name);
    }
}
?>
