<?php
/**
 * Undocumented
 */
class Frame extends View
{
    public function setTitle($title)
    {
        $this->template->trySet('title', $title);

        return $this;
    }
    public function render()
    {
        if (!$this->template->get('title')) {
            $this->template->tryDel('title_tag');
        }

        return parent::render();
    }
    public function defaultTemplate()
    {
        return array('frames', 'MsgBox');
    }
}
