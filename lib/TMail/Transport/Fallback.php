<?php
/**
 * Uses default sending routine.
 */
class TMail_Transport_Fallback extends TMail_Transport
{
    public function send($tm, $to, $from, $subject, $body, $headers)
    {
        $this->breakHook(false);
    }
}
