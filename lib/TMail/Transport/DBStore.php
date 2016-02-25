<?php
/**
 * Undocumented.
 */
class TMail_Transport_DBStore extends TMail_Transport
{
    public $model = null;

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

        $this->model->unloadData()->set($data)->update();

        return $this;
    }
}
