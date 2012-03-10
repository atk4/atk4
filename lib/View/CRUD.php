<?php
class View_CRUD extends View {
    public $form=null;
    public $grid=null;

    public $grid_class='Grid';
    public $form_class='Form';

	public $allow_add=true;
	public $allow_edit=true;
	public $allow_del=true;

    public $add_button;
    
    public $frame_options=null;
    function init(){
        parent::init();


        if(isset($_GET[$this->name]) && ($this->allow_edit||$this->allow_add)){
            $this->api->stickyGET($this->name);

            $this->form=$this->add($this->form_class);
            $_GET['cut_object']=$this->name;

            return;
        }

        $this->grid=$this->add($this->grid_class);
        $this->js('reload',$this->grid->js()->reload());
		if($this->allow_add){
			$this->add_button = $this->grid->addButton('Add');
			$this->add_button->js('click')->univ()
				->frameURL('New',$this->api->getDestinationURL(null,array($this->name=>'new')),$this->frame_options);
		}
    }
    function setController($controller){
        if($this->form){
            $m=$this->form->setController($controller);
            $this->form->addSubmit('Save');
        }elseif($this->grid){
            $this->grid->setController($controller);
        }
        $this->initComponents();
    }
    function setModel($model,$fields=null,$grid_fields=null){
        parent::setModel($model);

        if($this->form){
            $m=$this->form->setModel($this->model,$fields);
            $this->form->addSubmit();
        }else{
            $m=$this->grid->setModel($this->model,$grid_fields?$grid_fields:$fields);
        }
        $this->initComponents();
        return $m;
    }
    function initComponents(){
        if($this->form){
            $m=$this->form->getModel();
            if(($id=$_GET[$this->name])!='new' && $this->allow_edit){
				if(!$this->allow_edit)throw $this->exception('Editing not allowed');
                $m->loadData($id);
            }
			if(!$m->isInstanceLoaded() && !$this->allow_add)throw $this->exception('Adding not allowed');

            $this->form->onSubmit(array($this,'formSubmit'));

            return $m;
        }
        $m=$this->grid->getModel();
        if(!$this->allow_add && $this->add_button)$this->add_button->destroy();
        if($this->allow_edit)$this->grid->addColumn('button','edit');
        if($this->allow_del)$this->grid->addColumn('delete','delete');
        if($id=@$_GET[$this->grid->name.'_edit']){
            $this->js()->univ()->frameURL('Edit',$this->api->getDestinationURL(null,array($this->name=>$id)))->execute();
        }
        return $this;
    }
	function formSubmit($form){
		$form->update();
		$this->api->addHook('pre-render',array($this,'formSubmitSuccess'));
	}
	function formSubmitSuccess(){
		$this->form->js(null,$this->js()->trigger('reload'))->univ()->closeDialog()->execute();
	}
}
