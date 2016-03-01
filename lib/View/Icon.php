<?php
/**
 * Add icon from standard icon set of Agile Toolkit
 */
class View_Icon extends View
{
    public $shappe = null;



    /**
     * Initialization
     */
    public function init()
    {
        parent::init();
        $this->setElement('span');
    }

    /** Sets icon by its shape. http://agiletoolkit.org/ref/icon */
    public function setText($shape)
    {
        $this->shape = $shape;

        return $this;
    }

    public function render()
    {
        $this->addClass('icon-'.$this->shape);
        parent::render();
    }
}
