<?php
/**
 * Generic class for several UI elements (warnings, errors, info).
 */
class View_Box extends View
{
    public function set($text)
    {
        $this->template->set('Content', $text);

        return $this;
    }
    /**
     * By default box uses information Icon.
     * You can use addIcon() to override or $this->template->del('Icon') to remove.
     *
     * @param [type] $i [description]
     */
    public function addIcon($i)
    {
        return $this->add('Icon', null, 'Icon')->set($i)->addClass('atk-size-mega');
    }

    /**
     * Adds Button on the right side of the box for follow-up action.
     *
     * @param [type] $page  [description]
     * @param string $label [description]
     */
    public function addButton($label = 'Continue')
    {
        if (!is_array($label)) {
            $label = array($label, 'icon-r' => 'right-big');
        }

        return $this->add('Button', null, 'Button')
            ->set($label);
    }

    public function link($page)
    {
        $this->addButton(false)->link($page);
    }
    /**
     * View box can be closed by clicking on the cross.
     */
    public function addClose()
    {
        if ($this->recall('closed', false)) {
            $this->destroy();
        }

        $self = $this;
        $this->add('Icon', null, 'Button')
            ->addComponents(array('size' => 'mega'))
            ->set('cancel-1')
            ->addStyle(array('cursor' => 'pointer'))
            ->on('click', function ($js) use ($self) {
                $self->memorize('closed', true);

                return $self->js()->hide()->execute();
            });

        return $this;
    }

    public function defaultTemplate()
    {
        return array('view/box');
    }
}
