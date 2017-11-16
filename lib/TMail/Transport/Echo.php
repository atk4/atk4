<?php
/**
 * Undocumented.
 */
class TMail_Transport_Echo extends TMail_Transport
{
    public function send($tm, $to, $from, $subject, $body, $headers)
    {
        echo "to: $to<br/>";
        echo "from: $from<br/>";
        echo "subject: $subject<br/>";
        echo "<textarea cols=100 rows=30>$body</textarea><hr/>";
        echo "<textarea cols=100 rows=10>$headers</textarea><hr/>";
    }
}
