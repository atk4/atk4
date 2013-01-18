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

    // for localization
    public $msg_bt_add='Add';
    public $msg_bt_edit='Edit';
    public $msg_bt_delete='Delete';
    public $msg_dlg_new='New';
    public $msg_dlg_edit='Edit';
    public $msg_bt_save='Save';

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
            $this->add_button = $this->grid->addButton($this->msg_bt_add);
            $this->add_button->js('click')->univ()
                ->frameURL($this->msg_dlg_new,$this->api->url(null,array($this->name=>'new')),$this->frame_options);
        }
    }
    function setController($controller){
        if($this->form){
            $this->form->setController($controller);
            $this->form->addSubmit($this->msg_bt_save);
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
            if(($id=$_GET[$this->name])!='new'){
                if(!$this->allow_edit)throw $this->exception('Editing not allowed');
                $m->load($id);
            }
            if(!$m->loaded() && !$this->allow_add)throw $this->exception('Adding not allowed');

            $this->form->onSubmit(array($this,'formSubmit'));

            return $m;
        }
        $m=$this->grid->getModel();
        if(!$this->allow_add && $this->add_button)$this->add_button->destroy();
        if($this->allow_edit)$this->grid->addColumn('button','edit',$this->msg_bt_edit);
        if($this->allow_del)$this->grid->addColumn('delete','delete',$this->msg_bt_delete);
        if($id=@$_GET[$this->grid->name.'_edit']){
            $this->js()->univ()->frameURL($this->msg_dlg_edit,$this->api->url(null,array($this->name=>$id)),$this->frame_options)->execute();
        }
        return $this;
    }
    function formSubmit($form){
        try {
            $form->update();
            $this->api->addHook('pre-render',array($this,'formSubmitSuccess'));
        } catch (Exception_ValidityCheck $e){
            $form->displayError($e->getField(), $e->getMessage());
        }
    }
    function formSubmitSuccess(){
        $this->form->js(null,$this->js()->trigger('reload'))->univ()->closeDialog()->execute();
    }
}
