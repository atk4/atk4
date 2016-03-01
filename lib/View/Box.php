<?php
/**
 * Generic class for several UI elements (warnings, errors, info).
 */
class View_Box extends View
{
    /**
     * Set text.
     *
     * @param string $text
     *
     * @return $this
     */
    public function set($text)
    {
        $this->template->set('Content', $text);

        return $this;
    }

    /**
     * By default box uses information Icon.
     * You can use addIcon() to override or $this->template->del('Icon') to remove.
     *
     * @param string $i
     *
     * @return Icon
     */
    public function addIcon($i)
    {
        return $this->add('Icon', null, 'Icon')->set($i)->addClass('atk-size-mega');
    }

    /**
     * Adds Button on the right side of the box for follow-up action.
     *
     * @param array|string $label
     *
     * @return Button
     */
    public function addButton($label = 'Continue')
    {
        if (!is_array($label)) {
            $label = array($label, 'icon-r' => 'right-big');
        }

        return $this->add('Button', null, 'Button')
            ->set($label);
    }

    /**
     * Adds link.
     *
     * @param string $page
     *
     * @return Button
     */
    public function link($page)
    {
        $this->addButton('')->link($page);
    }

    /**
     * View box can be closed by clicking on the cross.
     *
     * @return $this
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

    /**
     * Default template.
     *
     * @return array|string
     */
    public function defaultTemplate()
    {
        return array('view/box');
    }
}
