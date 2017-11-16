<?php
/**
 * Why creating your own HelloWorld class?
 * $this->add('HelloWorld'); will do the trick!
 */
class HelloWorld extends AbstractView
{
    // message text
    private $message = 'Hello, World';

    /**
     * Set custom message text.
     *
     * @param string $msg Message text
     */
    public function setMessage($msg)
    {
        $this->message = $msg;
    }

    /**
     * Render message.
     */
    public function render()
    {
        $this->output('<p>'.$this->message.'</p>');
    }
}
