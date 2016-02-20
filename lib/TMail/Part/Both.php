<?php
/**
 * Undocumented.
 */
class TMail_Part_Both extends TMail_Part
{
    public function render()
    {
        $html = parent::render();
        $this->template->set('contenttype', 'text/plain');
        $c = $this->content;
        if ($this->content instanceof SMlite) {
            $this->content = $this->content->render();
        }
        $this->content = strip_tags($this->content);
        $plain = parent::render();
        $this->content = $c;

        return $plain.$html;
    }
}
