<?php
/***********************************************************

  Quicksearch represents one-field filter which works perfectly with a grid

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
class QuickSearch extends Filter
{
    
    // icons
    public $submit_icon = 'ui-icon-search';
    public $cancel_icon = 'ui-icon-cancel';
    
    // field
    public $search_field;
    
    // buttonset
    public $bset_class = 'ButtonSet';
    public $bset_position = 'after'; // after|before
    protected $bset;
    
    // cancel button
    public $show_cancel = true; // show cancel button? (true|false)

    /**
     * Initialization
     * 
     * @return void
     */
    function init()
    {
        parent::init();

        // template fixes
        $this->addClass('float-right span4 atk-quicksearch');
        $this->template->trySet('fieldset', 'atk-row');
        $this->template->tryDel('button_row');
        
        // add field
        $this->search_field = $this->addField('Line', 'q', '')->setNoSave();
        
        // add buttonset
        $this->bset = $this->bset_position=='after'
                    ? $this->search_field->afterField()
                    : $this->search_field->beforeField();
        $this->bset = $this->bset->add($this->bset_class);

        // cancel button
        if($this->show_cancel && $this->recall($this->search_field->short_name)) {
            $this->bset->addButton('', array('options'=>array('text'=>false)))
                    ->setHtml('&nbsp;')
                    ->setIcon($this->cancel_icon)
                    ->js('click', array(
                        $this->search_field->js()->val(null),
                        $this->js()->submit()
                    ));
        }

        // search button
        $this->bset->addButton('', array('options'=>array('text'=>false)))
                ->setHtml('&nbsp;')
                ->setIcon($this->submit_icon)
                ->js('click', $this->js()->submit());
        
    }
    
    /**
     * Set fields on which filtering will be done
     * 
     * @param string|array $fields
     * @return QuickSearch $this
     */
    function useFields($fields)
    {
        if(is_string($fields)) {
            $fields = explode(',', $fields);
        }
        $this->fields = $fields;
        return $this;
    }
    
    /**
     * Process received filtering parameters after init phase
     * 
     * @return void
     */
    function postInit()
    {
        parent::postInit();
        if(!($v = trim($this->get('q')))) {
            return;
        }

        if($this->view->model->hasMethod('addConditionLike')){
            return $this->view->model->addConditionLike($v);
        }
        if($this->view->model) {
            $q = $this->view->model->_dsql();
        } else {
            $q = $this->view->dq;
        }
        $or = $q->orExpr();
        foreach($this->fields as $field) {
            $or->where($field, 'like', '%'.$v.'%');
        }
        $q->having($or);
    }
}
