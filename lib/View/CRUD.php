<?php
/**
 * CRUD stands for Create, Read, Update and Delete. This view combines
 * both "Grid", "Form" and "VirtualPage" to bring you a seamless editing
 * control. You would need to supply a model.
 *
 * IMPORTANT NOTE: While you can disable adding and editing, if you do that
 * you must simply use Grid!
 */
class View_CRUD extends View
{
    /**
     * After CRUD is initialized, this will point to a form object IF
     * CRUD goes into editing mode. Typically the same code is initialized
     * for editing pop-up, but only form is rendered. You can enhance the
     * form all you want.
     *
     * IMPORTANT: check isEditing() method
     *
     * @var Form
     */
    public $form = null;

    /**
     * After CRUD is initialized, this will point do a Grid object, IF
     * CRUD is in "read" mode. You can add more columns or actions to the
     * grid.
     *
     * IMPORTANT: check isEditing() method
     *
     * @var Grid
     */
    public $grid = null;

    /**
     * By default, CRUD will simply use "Grid" class, but if you would like
     * to use your custom grid class for listing, specify it inside associative
     * array as second argument to add().
     *
     * $this->add('CRUD', array('grid_class'=>'MyGrid'));
     *
     * @var string
     */
    public $grid_class = 'Grid';

    /**
     * By default, CRUD will simply use "Form" class for editing and adding,
     * but if you would like to use your custom form, specify it inside
     * associative array as second argument to add().
     *
     * $this->add('CRUD', array('form_class'=>'MyForm'));
     *
     * @var string
     */
    public $form_class = 'Form';

    /**
     * You can pass additional options for grid using this array.
     *
     * $this->add('CRUD', array('grid_options'=>array('show_header'=>false)));
     *
     * @var array
     */
    public $grid_options = array();

    /**
     * You can pass additional options for form using this array.
     *
     * $this->add('CRUD', array('form_options'=>array('js_widget'=>'ui.atk4_form')));
     *
     * @var array
     */
    public $form_options = array();

    /**
     * Grid will contain an "Add X" button and will allow user to add records.
     *
     * $this->add('CRUD', array('allow_add'=>false')); // to disable
     *
     * @var bool
     */
    public $allow_add = true;

    /**
     * Grid will contain "EDIT" button for each row allowing usir to edit
     * records.
     *
     * $this->add('CRUD', array('allow_edit'=>false')); // to disable
     *
     * @var bool
     */
    public $allow_edit = true;

    /**
     * Grid will contain a "DELETE" button for each row. If you don't want
     * thes set this option to false.
     *
     * $this->add('CRUD', array('allow_del'=>false')); // to disable
     *
     * @var bool
     */
    public $allow_del = true;

    /**
     * For ->setModel('User'), your add button would contain "Add User". If
     * you want add button and frames to use different label, then change
     * this property.
     *
     * If you set this to 'false' then CRUD will not attempt to change
     * default label ("Add")
     *
     * @var string
     */
    public $entity_name = null;

    /**
     * This points to a Button object, which you can change if you want
     * a different label or anything else on it.
     *
     * @var Button
     */
    public $add_button;

    /**
     * VirtualPage object will be used to display popup content. That is to ensure
     * that none of your other content you put AROUND the CRUD would mess
     * with the forms.
     *
     * If isEditing() then you can add more stuff on this page, by calling
     * virtual_page->getPage()->add('Hello!');
     *
     * @var VirtualPage
     */
    public $virtual_page = null;

    /**
     * When clicking on EDIT or ADD the frameURL is used. If you want to pass
     * some arguments to it, put your hash here.
     *
     * @var array
     */
    public $frame_options = null;

    /**
     * This is set to ID of the model when are in editing mode. In theory
     * this can also be 0, so use is_null().
     *
     * @var mixed
     */
    public $id = null;

    /**
     * Contains reload javascript, used occassionally throughout the object.
     *
     * @var jQuery_Chain
     */
    public $js_reload = null;

    // {{ type-hint inherited properties
    /** @var View */
    public $owner;

    /** @var App_Web */
    public $app;
    // }}

    /**
     * {@inheritdoc}
     *
     * CRUD's init() will create either a grid or form, depending on
     * isEditing(). You can then do the necessary changes after
     *
     * Note, that the form or grid will not be populated until you
     * call setModel()
     */
    public function init()
    {
        parent::init();

        $this->js_reload = $this->js()->reload();

        // Virtual Page would receive 3 types of requests - add, delete, edit
        $this->virtual_page = $this->add('VirtualPage', array(
            'frame_options' => $this->frame_options,
        ));
        /** @type VirtualPage $this->virtual_page */

        $name_id = $this->virtual_page->name.'_id';

        /*
        if ($_GET['edit'] && !isset($_GET[$name_id])) {
            $_GET[$name_id] = $_GET['edit'];
        }
         */

        if (isset($_GET[$name_id])) {
            $this->app->stickyGET($name_id);
            $this->id = $_GET[$name_id];
        }

        if ($this->isEditing()) {
            $this->form = $this
                ->virtual_page
                ->getPage()
                ->add($this->form_class, $this->form_options)
                //->addClass('atk-form-stacked')
                ;
            /** @type Form $this->form */

            $this->grid = new Dummy();
            /** @type Grid $this->grid */

            return;
        }

        $this->grid = $this->add($this->grid_class, $this->grid_options);
        /** @type Grid $this->grid */

        $this->form = new Dummy();
        /** @type Form $this->form */

        // Left for compatibility
        $this->js('reload', $this->grid->js()->reload());

        if ($this->allow_add) {
            $this->add_button = $this->grid->addButton('Add');
        }
    }

    /**
     * Returns if CRUD is in editing mode or not. It's preferable over
     * checking if($grid->form).
     *
     * @param string $mode Specify which editing mode you expect
     *
     * @return bool true if editing.
     */
    public function isEditing($mode = null)
    {
        $page_mode = $this->virtual_page->isActive();

        // Requested edit, but not allowed
        if ($page_mode == 'edit' && !$this->allow_edit) {
            throw $this->exception('Editing is not allowed');
        }

        // Requested add but not allowed
        if ($page_mode == 'add' && !$this->allow_add) {
            throw $this->exception('Adding is not allowed');
        }

        // Request matched argument exactly
        if (!is_null($mode)) {
            return $mode === $page_mode;
        }

        // Argument was blank, then edit/add is OK
        return (boolean) $page_mode;
    }

    /**
     * Assign model to your CRUD and specify list of fields to use from model.
     *
     * {@inheritdoc}
     *
     * @param string|Model $model       Same as parent
     * @param array        $fields      Specify list of fields for form and grid
     * @param array        $grid_fields Overide list of fields for the grid
     *
     * @return AbstractModel $model
     */
    public function setModel($model, $fields = null, $grid_fields = null)
    {
        $model = parent::setModel($model);

        if ($this->entity_name === null) {
            if ($model->caption === null) {

                // Calculates entity name
                $class = get_class($this->model);
                $class = substr(strrchr($class, '\\') ?: ' '.$class, 1); // strip namespace
                $this->entity_name = str_replace(
                    array('Model_', '_'),
                    array('', ' '),
                    $class
                );
            } else {
                $this->entity_name = $model->caption;
            }
        }

        if (!$this->isEditing()) {
            $this->configureGrid(is_null($grid_fields) ? $fields : $grid_fields);
        }

        if ($this->allow_add) {
            if ($this->configureAdd($fields)) {
                return $model;
            }
        } elseif (isset($this->add_button)) {
            $this->add_button->destroy();
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
     * Assuming that your model has a $relation defined, this method will add a button
     * into a separate column. When clicking, it will expand the grid and will present
     * either another CRUD with related model contents (one to many) or a form preloaded
     * with related model data (many to one).
     *
     * Adds expander to the crud, which edits references under the specified
     * name. Returns object of nested CRUD when active, or null
     *
     * The format of $options is the following:
     * array (
     *   'view_class' => 'CRUD',  // Which View to use inside expander
     *   'view_options' => ..     // Second arg when adding view.
     *   'view_model' => model or callback // Use custom model for sub-View, by default ref($name) will be used
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
    public function addRef($name, $options = array())
    {
        if (!$this->model) {
            throw $this->exception('Must set CRUD model first');
        }

        if (!is_array($options)) {
            throw $this->exception('Must be array');
        }

        // if(!$this->grid || $this->grid instanceof Dummy)return;

        $s = $this->app->normalizeName($name);

        if ($this->isEditing('ex_'.$s)) {
            $n = $this->virtual_page->name.'_'.$s;

            if ($_GET[$n]) {
                $this->id = $_GET[$n];
                $this->app->stickyGET($n);
            }

            $idfield = $this->model->table.'_'.$this->model->id_field;
            if ($_GET[$idfield]) {
                $this->id = $_GET[$idfield];
                $this->app->stickyGET($idfield);
            }

            $view_class = (is_null($options['view_class'])) ?
                get_class($this) :
                $options['view_class'];

            $subview = $this->virtual_page->getPage()->add(
                $view_class,
                $options['view_options']
            );

            $this->model->load($this->id);
            $subview->setModel(
                $options['view_model']
                    ? (is_callable($options['view_model'])
                        ? call_user_func($options['view_model'], $this->model)
                        : $options['view_model']
                    )
                    : $this->model->ref($name),
                $options['fields'],
                $options['grid_fields'] ?: $options['extra_fields']
            );

            return $subview;
        } elseif ($this->grid instanceof Grid) {
            $this->grid->addColumn('expander', 'ex_'.$s, $options['label'] ?: $s);
            $this->grid->columns['ex_'.$s]['page']
                = $this->virtual_page->getURL('ex_'.$s);
            // unused: $idfield = $this->grid->columns['ex_'.$s]['refid'].'_'.$this->model->id_field;
        }

        if ($this->isEditing()) {
            return;
        }
    }

    /**
     * Adds button to the crud, which opens a new frame and returns page to
     * you. Add anything into the page as you see fit. The ID of the record
     * will be inside $crud->id.
     *
     * The format of $options is the following:
     * array (
     *   'title'=> 'Click Me'     // Header for the column
     *   'label'=> 'Click Me'     // Text to put on the button
     *   'icon' => 'click-me'     // Icon for button
     * )
     *
     * @param string $name    Unique name, also button and title default
     * @param array  $options Options
     *
     * @return Page|bool Returns object if clicked on popup.
     */
    public function addFrame($name, $options = array())
    {
        if (!$this->model) {
            throw $this->exception('Must set CRUD model first');
        }

        if (!is_array($options)) {
            throw $this->exception('Must be array');
        }

        $s = $this->app->normalizeName($name);

        if ($this->isEditing('fr_'.$s)) {
            $n = $this->virtual_page->name.'_'.$s;

            if ($_GET[$n]) {
                $this->id = $_GET[$n];
                $this->app->stickyGET($n);
            }

            return $this->virtual_page->getPage();
        }

        if ($this->isEditing()) {
            return false;
        }

        $this
            ->virtual_page
            ->addColumn(
                'fr_'.$s,
                $options['title'] ?: $name,
                array(
                    'descr' => $options['label'] ?: null,
                    'icon' => $options['icon'] ?: null,
                ),
                $this->grid
            );
    }

    /**
     * Assuming that your model contains a certain method, this allows
     * you to create a frame which will pop you a new frame with
     * a form representing model method arguments. Once the form
     * is submitted, the action will be evaluated.
     *
     * @param string $method_name
     * @param array $options
     */
    public function addAction($method_name, $options = array())
    {
        if (!$this->model) {
            throw $this->exception('Must set CRUD model first');
        }
        if ($options == 'toolbar') {
            $options = array('column' => false);
        }
        if ($options == 'column') {
            $options = array('toolbar' => false);
        }

        $descr = $options['descr'] ?: ucwords(str_replace('_', ' ', $method_name));
        $icon = $options['icon'] ?: 'target';

        $show_toolbar = isset($options['toolbar']) ? $options['toolbar'] : true;
        $show_column = isset($options['column']) ? $options['column'] : true;

        if ($this->isEditing($method_name)) {
            /** @type View_Console $c */
            $c = $this->virtual_page->getPage()->add('View_Console');
            $self = $this;

            // Callback for the function
            $c->set(function ($c) use ($show_toolbar, $show_column, $options, $self, $method_name) {
                if ($show_toolbar && !$self->id) {
                    $self->model->unload();
                } elseif ($show_column && $self->id) {
                    $c->out('Loading record '.$self->id, array('class' => 'atk-effect-info'));
                    $self->model->load($self->id);
                } else {
                    return;
                }

                $ret = $self->model->$method_name();

                $c->out('Returned: '.json_encode($ret, JSON_UNESCAPED_UNICODE), array('class' => 'atk-effect-success'));

                /*
                if (isset($options['args'])) {
                    $params = $options['args'];
                } elseif (!method_exists($self->model, $method_name)) {
                    // probably a dynamic method
                    $params = array();
                } else {
                    $reflection = new ReflectionMethod($self->model, $method_name);

                    $params = $reflection->getParameters();
                }
                */
            });

            return;

            /* unused code below

            $has_parameters = (bool) $params;
            foreach ($params as $i => $param) {
                $this->form->addField($param->name);
                $this->has_parameters = true;
            }

            if (!$has_parameters) {
                $this->form->destroy();
                $ret = $this->model->$method_name();
                if (is_object($ret) && $ret == $this->model) {
                    $this->virtual_page->getPage()->add('P')->set('Executed successfully');
                    $this->virtual_page->getPage()->js(true, $this->js_reload);
                } else {
                    $this->virtual_page->getPage()->js(true, $this->js_reload);
                    if (is_object($ret)) {
                        $ret = (string) $ret;
                    }
                    $this->virtual_page->getPage()
                        ->add('P')->set('Returned: '.json_encode($ret, JSON_UNESCAPED_UNICODE));
                }
                $this->virtual_page->getPage()
                    ->add('Button')->set(array('Close', 'icon' => 'cross', 'swatch' => 'green'))
                    ->js('click')->univ()->closeDialog();

                return true;
            }

            $this->form->addSubmit('Execute');
            if ($this->form->isSubmitted()) {
                $ret = call_user_func_array(array($this->model, $method_name), array_values($this->form->get()));
                if (is_object($ret)) {
                    $ret = (string) $ret;
                }
                $this->js(null, $this->js()->reload())->univ()
                    ->successMessage('Returned: '.json_encode($ret, JSON_UNESCAPED_UNICODE))
                    ->closeDialog()
                    ->execute();
            }

            return true;
            */

        } elseif ($this->isEditing()) {
            return;
        }

        $frame_options = array_merge(array(), $this->frame_options ?: array());

        if ($show_column) {
            $this
                ->virtual_page
                ->addColumn(
                    $method_name,
                    $descr.' '.$this->entity_name,
                    array('descr' => $descr, 'icon' => $icon),
                    $this->grid
                );
        }

        if ($show_toolbar) {
            $button = $this->addButton(array($descr, 'icon' => $icon));

            // Configure Add Button on Grid and JS
            $button->js('click')->univ()
                ->frameURL(
                    $this->app->_($this->entity_name.'::'.$descr),
                    $this->virtual_page->getURL($method_name),
                    $frame_options
                );
        }
    }

    /**
     * Transparent method for adding buttons to a crud.
     *
     * @param string|array $label
     * @param string $class
     *
     * @return Button
     */
    public function addButton($label, $class = 'Button')
    {
        if (!$this->grid) {
            return new Dummy();
        }

        return $this->grid->addButton($label, $class);
    }

    /**
     * Configures necessary components when CRUD is in the adding mode.
     *
     * @param array $fields List of fields for add form
     *
     * @return void|Model If model, then bail out, no greed needed
     */
    protected function configureAdd($fields = null)
    {
        // We are actually in the frame!
        if ($this->isEditing('add')) {
            $this->model->unload();
            $m = $this->form->setModel($this->model, $fields);
            $this->form->addSubmit('Add');
            $this->form->onSubmit(array($this, 'formSubmit'));

            return $m;
        } elseif ($this->isEditing()) {
            return;
        }

        // Configure Add Button on Grid and JS
        $this->add_button->js('click')->univ()
            ->frameURL(
                $this->app->_(
                    $this->entity_name === false
                    ? 'New Record'
                    : 'Adding new '.$this->entity_name
                ),
                $this->virtual_page->getURL('add'),
                $this->frame_options
            );

        if ($this->entity_name !== false) {
            $this->add_button->setHTML('<i class="icon-plus"></i> Add '.htmlspecialchars($this->entity_name));
        }
    }

    /**
     * Configures necessary components when CRUD is in the editing mode.
     *
     * @param array $fields List of fields for add form
     *
     * @return void|Model If model, then bail out, no greed needed
     */
    protected function configureEdit($fields = null)
    {
        // We are actually in the frame!
        if ($this->isEditing('edit')) {
            $m = $this->form->setModel($this->model->load($this->id), $fields);
            $this->form->addSubmit();
            $this->form->onSubmit(array($this, 'formSubmit'));

            return $m;
        } elseif ($this->isEditing()) {
            return;
        }

        $this
            ->virtual_page
            ->addColumn(
                'edit',
                'Editing '.$this->entity_name,
                array('descr' => 'Edit', 'icon' => 'pencil'),
                $this->grid
            );
    }

    /**
     * Configures grid's model itself.
     *
     * @param array $fields List of fields for grid
     */
    protected function configureGrid($fields)
    {
        $this->grid->setModel($this->model, $fields);
    }

    /**
     * Configures deleting functionality for grid.
     */
    protected function configureDel()
    {
        $this->grid->addColumn('delete', 'delete', array('icon' => 'trash', 'descr' => 'Delete'));
    }

    /**
     * Called after on post-init hook when form is submitted.
     *
     * @param Form $form Form which was submitted
     */
    protected function formSubmit($form)
    {
        try {
            $form->update();
            $self = $this;
            $this->app->addHook('pre-render', function () use ($self) {
                $self->formSubmitSuccess()->execute();
            });
        } catch (Exception_ValidityCheck $e) {
            $form->displayError($e->getField(), $e->getMessage());
        }
    }

    /**
     * Returns JavaScript action which should be executed on form successfull
     * submission.
     *
     * @return jQuery_Chain to be executed on successful submit
     */
    public function formSubmitSuccess()
    {
        return $this->form->js(null, $this->js()->trigger('reload'))
            ->univ()->closeDialog();
    }
}
