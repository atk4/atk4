<?php
/**
 * Undocumented.
 */
class TMail_Transport_DBStore extends TMail_Transport
{
    /** @var Model */
    public $model = null;

    /**
     * @param string $m
     *
     * @return Model
     */
    public function setModel($m)
    {
        if (is_string($m)) {
            $m = 'Model_'.$m;
        }
        $this->model = $this->add($m);

        return $this->model;
    }
    
    public function send($tm, $to, $from, $subject, $body, $headers)
    {
        if (!$this->model) {
            throw $this->exception('Must use setModel() on DBStore Transport');
        }
        $data = array(
                'to' => $to,
                'from' => $from,
                'subject' => $subject,
                'body' => $body,
                'headers' => $headers,
                );

        $this->model->unload()->set($data)->save();

        return $this;
    }
}
