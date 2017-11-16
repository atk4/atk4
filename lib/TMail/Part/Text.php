<?php
/**
 * Undocumented.
 */
class TMail_Part_Text extends TMail_Part
{
    public function init()
    {
        parent::init();
        $this->template->set('contenttype', 'text/plain');
    }
}
