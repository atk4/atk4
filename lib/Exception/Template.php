<?php
/**
 * Thrown by GiTemplate class.
 */
class Exception_Template extends BaseException
{
    /** @var GiTemplate */
    public $owner;


    public function init()
    {
        parent::init();
        if (isset($this->owner) && isset($this->owner->template_file)) {
            $this->addMoreInfo('file', $this->owner->template_file);
        }

        $keys = array_keys($this->owner->tags);
        if (!empty($keys)) {
            $this->addMoreInfo('keys', implode(', ', $keys));
        }

        if (isset(@$this->owner->source)) {
            $this->addMoreInfo('source', $this->owner->source);
        }
    }
    public function setTag($t)
    {
        $this->addMoreInfo('tag', $t);

        return $this;
    }
}
