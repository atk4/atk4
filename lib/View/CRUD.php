<?php // vim:ts=4:sw=4:et:fdm=marker
/**
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
/**
 * CRUD stands for Create, Read, Update and Delete. This view combines
 * both "Grid", "Form" and "VirtualPage" to bring you a seamless editing
 * control. You would need to supply a model.
 *
 * IMPORTANT NOTE: While you can disable adding and editing, if you do that
 * you must simply use Grid!
 *
 * @link http://agiletoolkit.org/
 */
class View_CRUD extends View
{
    /**
     * After CRUD is initialized, this will point to a form object IF
     * CRUD goes into editing mode. Typically the same code is initialized
     * for editing pop-up, but only form is rendered. You can enhance the
     * form all you want
     *
     * IMPORTANT: check isEditing() method
     */
    public $form=null;

    /**
     * After CRUD is initialized, this will point do a Grid object, IF
     * CRUD is in "read" mode. You can add more columns or actions to the
     * grid.
     *
     * IMPORTANT: check isEditing() method
     */
    public $grid=null;

    /**
     * By default, CRUD will simply use "Grid" class, but if you would like
     * to use your custom grid class for listing, specify it inside associative
     * array as second argument to add()
     *
     * $this->add('CRUD', array('grid_class'=>'MyGrid'));
     */
    public $grid_class='Grid';

    /**
     * By default, CRUD will simply use "Form" class for editing and adding,
     * but if you would like to use your custom form, specify it inside
     * associative array as second argument to add()
     *
     * $this->add('CRUD', array('grid_class'=>'MyGrid'));
     */
    public $form_class='Form';

    /**
     * Grid will contain an "Add X" button and will allow user to add records
     *
     * $this->add('CRUD', array('allow_add'=>false')); // to disable
     */
    protected $allow_add=true;

    /**
     * Grid will contain "EDIT" button for each row allowing usir to edit
     * records
     *
     * $this->add('CRUD', array('allow_edit'=>false')); // to disable
     */
    protected $allow_edit=true;

    /**
     * Grid will contain a "DELETE" button for each row. If you don't want
     * thes set this option to false
     *
     * $this->add('CRUD', array('allow_del'=>false')); // to disable
     */
    protected $allow_del=true;

    /**
     * For ->setModel('User'), your add button would contain "Add User". If
     * you want add button and frames to use different label, then change
     * this property.
     *
     * If you set this to 'false' then CRUD will not attempt to change
     * default label ("Add")
     */
    public $entity_name=null;

    /**
     * This points to a Button object, which you can change if you want
     * a different label or anything else on it
     */
    public $add_button;

    /**
     * VirtualPage object will be used to display popup content. That is to ensure
     * that none of your other content you put AROUND the CRUD would mess
     * with the forms.
     *
     * If isEditing() then you can add more stuff on this page, by calling
     * virtual_page->getPage()->add('Hello!');
     */
    public $virtual_page=null;

    /**
     * When clicking on EDIT or ADD the frameURL is used. If you want to pass
     * some arguments to it, put your hash here.
     */
    public $frame_options=null;

    /**
     * This is set to ID of the model when are in editing mode. In theory
     * this can also be 0, so use is_null()
     */
    public $id=null;

    /**
     * {@inheritdoc}
     *
     * CRUD's init() will create either a grid or form, depending on
     * isEditing(). You can then do the necessary changes after
     *
     * Note, that the form or grid will not be populated until you
     * call setModel()
     *
     * @return void
     */
    function init()
    {
        parent::init();

        // Virtual Page would receive 3 types of requests - add, delete, edit
        $this->virtual_page = $this->add('VirtualPage', array(
            'frame_options'=>$this->frame_options
        ));


        if ($_GET['edit']) {
            $_GET[$this->name.'_id'] = $_GET['edit'];
        }

        if (isset($_GET[$this->name.'_id'])) {
            $this->api->stickyGET($this->name.'_id');
            $this->id=$_GET[$this->name.'_id'];
        }

        if ($this->isEditing()) {
            $this->form = $this
                ->virtual_page
                ->getPage()
                ->add($this->form_class);

            return;
        }

        $this->grid=$this->add($this->grid_class);

        // Left for compatibility
        $this->js('reload', $this->grid->js()->reload());

        if ($this->allow_add) {
            $this->add_button = $this->grid->addButton('Add');
        }
    }

    /**
     * Returns if CRUD is in editing mode or not. It's preferable over
     * checking if($grid->form)
     *
     * @param string $mode Specify which editing mode you expect
     *
     * @return boolean true if editing.
     */
    function isEditing($mode = null)
    {
        $page_mode = $this->virtual_page->isActive();

        // Requested edit, but not allowed
        if ($page_mode=='edit' && !$this->allow_edit) {
            throw $this->exception('Editing is not allowed');
        }

        // Requested add but not allowed
        if ($page_mode=='add' && !$this->allow_add) {
            throw $this->exception('Adding is not allowed');
        }

        // Request matched argument exactly
        if (!is_null($mode)) {
            return $mode === $page_mode;
        }

        // Argument was blank, then edit/add is OK
        return $page_mode=='edit' || $page_mode=='add';
    }

    /**
     * Obsolete YEEE
     *
     * @obsolete
     */
    function setController($controller){
        if($this->form){
            $this->form->setController($controller);
            $this->form->addSubmit('Save');
        }elseif($this->grid){
            $this->grid->setController($controller);
        }
        $this->initComponents();
    }

    /**
     * Assign model to your CRUD and specify list of fields to use from model
     *
     * {@inheritdoc}
     *
     * @param string|object $model       Same as parent
     * @param array         $fields      Specify list of fields for form and grid
     * @param array         $grid_fields Overide list of fields for the grid
     *
     * @return AbstractModel $model
     */
    function setModel($model, $fields = null, $grid_fields = null)
    {
        $model=parent::setModel($model);

        if ($this->entity_name === null) {

            // Calculates entity name
            $this->entity_name = str_replace(
                array('Model_', '_'),
                array('', ' '),
                get_class($this->model)
            );
        }

        if (!$this->isEditing()) {
            $this->configureGrid(is_null($grid_fields)?$fields:$grid_fields);
        }

        if ($this->allow_add) {
            if ($this->configureAdd($fields)) {
                return $model;
            }
        }

        if ($this->allow_edit) {
            if ($this->configureEdit($fields)) {
                return $model;
            }
        }

        if ($this->allow_del) {
            $this->configureDel();
        }

        return $model;
    }

    /**
     * Adds expander to the crud, which edits references under the specified
     * name. Returns object of nested CRUD when active, or null
     *
     * The format of $options is the following:
     * array (
     *   'view_class' => 'CRUD',  // Which View to use inside expander
     *   'view_options' => ..     // Second arg when adding view.
     *   'fields' => array()      // Used as second argument for setModel()
     *   'extra_fields' => array() // Third arguments to setModel() used by CRUDs
     *   'label'=> 'Click Me'     // Label for a button inside a grid
     * )
     *
     * @param string $name    Name of the reference. If you leave blank adds all
     * @param array  $options Customizations, see above
     *
     * @return View_CRUD|null Returns crud object, when expanded page is rendered
     */
    function addRef($name, $options = array())
    {
        if (!$this->model) {
            throw $this->exception('Must set CRUD model first');
        }

        if (!is_array($options)) {
            throw $this->exception('Must be array');
        }

        if ($this->isEditing('ex_'.$name)) {

            if ($_GET['id']) {
                $this->id = $_GET[$this->name.'_id'] = $_GET['id'];
                $this->api->stickyGET($this->name.'_id');
            }

            $view_class = (is_null($options['view_class']))?
                get_class($this):
                $options['view_class'];

            $subview=$this->virtual_page->getPage()->add(
                $view_class,
                $options['view_options']
            );

            $subview->setModel(
                $this->model->load($this->id)->ref($name),
                $options['fields'],
                $options['extra_fields']
            );
            return $subview;
        }

        if ($this->isEditing()) {
            return;
        }

        $this->grid->addColumn('expander', 'ex_'.$name, $options['label']?:$name);
        $this->grid->columns['ex_'.$name]['page']
            = $this->virtual_page->getURL('ex_'.$name);
    }

    /**
     * Adds button to the crud, which opens a new frame and returns page to
     * you. Add anything into the page as you see fit. The ID of the record
     * will be inside $crud->id
     *
     * @param string $name    Unique name, also button and title default
     * @param array  $options Options
     *
     * @return Page|null Returns object if clicked on popup.
     */
    function addFrame($name, $options = array())
    {
        if (!$this->model) {
            throw $this->exception('Must set CRUD model first');
        }

        if (!is_array($options)) {
            throw $this->exception('Must be array');
        }

        if ($this->isEditing('fr_'.$name)) {

            if ($_GET['fr_'.$name]) {
                $this->id = $_GET[$this->name.'_id'] = $_GET['fr_'.$name];
                $this->api->stickyGET($this->name.'_id');
            }

            return $this->virtual_page->getPage();
        }

        if ($this->isEditing()) {
            return;
        }

        $this
            ->virtual_page
            ->addColumn('fr_'.$name, $options['title']?:$name, $options['label'], $this->grid)
            ;
    }

    /**
     * Configures necessary components when CRUD is in the adding mode
     *
     * @param array $fields List of fields for add form
     *
     * @return void|Model If model, then bail out, no greed needed
     */
    function configureAdd($fields)
    {
        // We are actually in the frame!
        if ($this->isEditing('add')) {
            $m = $this->form->setModel($this->model, $fields);
            $this->form->addSubmit('Add');
            $this->form->onSubmit(array($this,'formSubmit'));
            return $m;
        } elseif ($this->isEditing()) return;

        // Configure Add Button on Grid and JS
        $this->add_button->js('click')->univ()
            ->frameURL(
                $this->entity_name===false?
                'New Record':
                'Adding new '.$this->entity_name,
                $this->virtual_page->getURL('add')
            );

        if ($this->entity_name !== false) {
            $this->add_button->setLabel('Add '.$this->entity_name);
        }
    }

    /**
     * Configures necessary components when CRUD is in the editing mode
     *
     * @param array $fields List of fields for add form
     *
     * @return void|Model If model, then bail out, no greed needed
     */
    function configureEdit($fields)
    {
        // We are actually in the frame!
        if ($this->isEditing('edit')) {
            $m = $this->form->setModel($this->model, $fields);
            $m->load($this->id);
            $this->form->addSubmit();
            $this->form->onSubmit(array($this,'formSubmit'));
            return $m;
        } elseif ($this->isEditing()) return;


        $this
            ->virtual_page
            ->addColumn('edit', 'Editing '.$this->entity_name, 'Edit', $this->grid)
            ;
    }

    /**
     * Configures grid's model itself
     *
     * @param array $fields List of fields for grid
     *
     * @return void
     */
    function configureGrid($fields)
    {
        $this->grid->setModel($this->model, $fields);
    }

    /**
     * Configures deleting functionality for grid
     *
     * @return void
     */
    function configureDel()
    {
        $this->grid->addColumn('delete', 'delete');
    }


    /**
     * Called after on post-init hook when form is submitted
     *
     * @param Form $form Form which was submitted
     *
     * @return void
     */
    function formSubmit($form)
    {
        try {
            $form->update();
            $self = $this;
            $this->api->addHook('pre-render', function () use ($self) {
                $self->formSubmitSuccess()->execute();
            });
        } catch (Exception_ValidityCheck $e) {
            $form->displayError($e->getField(), $e->getMessage());
        }
    }


    /**
     * Returns JavaScript action which should be executed on form successfull
     * submission
     *
     * @return jQuery_Chain to be executed on successful submit
     */
    function formSubmitSuccess()
    {
        return $this->form->js(null, $this->js()->trigger('reload'))
            ->univ()->closeDialog();
    }
}
