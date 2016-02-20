<?php
/**
 * Discards email as it's being sent out.
 */
class TMail_Transport_Discard extends TMail_Transport
{
    public function send($tm, $to, $from, $subject, $body, $headers)
    {
        $this->breakHook(true);
    }
}
