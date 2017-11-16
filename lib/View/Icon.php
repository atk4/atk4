<?php
/**
 * Add icon from standard icon set of Agile Toolkit
 */
class View_Icon extends View
{
    /** @var string */
    public $shape = null;

    /**
     * Initialization
     */
    public function init()
    {
        parent::init();
        $this->setElement('span');
    }

    /**
     * Sets icon by its shape.
     *
     * @param string $shape
     *
     * @return $this
     */
    public function setText($shape)
    {
        $this->shape = $shape;

        return $this;
    }

    /**
     * Render
     */
    public function render()
    {
        $this->addClass('icon-'.$this->shape);
        parent::render();
    }
}
