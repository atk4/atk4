<?php
/**
 * Undocumented.
 */
class TMail_Part extends AbstractModel
{
    /** @var TMail_Template */
    public $template = null;

    public $content;
    public $auto_track_element = true;

    /** @var TMail_Basic */
    public $owner;

    public function init()
    {
        parent::init();

        // Initialize template of this part
        $t = $this->defaultTemplate();
        $this->template = $this->add($this->owner->template_class);
        /** @type TMail_Template $this->template */
        $this->template->loadTemplate($t[0], '.mail');

        if ($t[1]) {
            $this->template = $this->template->cloneRegion($t[1]);
        }
    }
    public function set($content)
    {
        $this->content = $content;
    }
    public function render()
    {
        $c = $this->content;
        if ($c instanceof SMlite) {
            $c->set($this->owner->args);
            $c = $c->render();
        }

        $this->template->setHTML($this->owner->args);
        $this->template->setHTML('Content', $c);
        $this->template->set('boundary', $this->owner->boundary);

        return $this->template->render();
    }
    public function defaultTemplate()
    {
        return array('shared', 'body_part');
    }
}
