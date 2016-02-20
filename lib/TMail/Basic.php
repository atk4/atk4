<?php
/**
 * Undocumented.
 */
class TMail_Basic extends AbstractModel
{
    public $mail_template = null;

    public $template_class = 'TMail_Template';
    public $master_template = 'shared';

    public $boundary;

    public $args = array();

    public $version = '2.0';

    public function init()
    {
        parent::init();
        $master_template = $this->add($this->template_class)->loadTemplate('shared', '.mail');
        $this->template = $master_template->cloneRegion('body');
        $this->headers = $master_template->cloneRegion('headers');

        $this->boundary = str_replace('.', '', uniqid('atk4tmail', true));

        if ($t = $this->app->getConfig('tmail/transport', false)) {
            $this->addTransport($t);
        }
    }
    public function extractEmail($fuzzy_email)
    {
        preg_match('/^(?:"?([^@"]+)"?\s)?<?([^>]+@[^>]+)>?$/', $fuzzy_email, $m);

        return $m;
    }
    public function defaultTemplate()
    {
        return array('shared');
    }
    public function addTransport($t)
    {
        return $this->add($this->app->normalizeClassName($t, 'TMail_Transport'));
    }
    public function addPart($p)
    {
        return $this->add('TMail_Part_'.$p);
    }
    /* Setting Content Separatelly */
    public function setText($text)
    {
        $this->addPart('Text')->set($text);
    }
    public function setHTML($html)
    {
        $this->addPart('HTML')->set($html);
    }
    public function loadTemplate($template, $junk = null)
    {
        return $this->setTemplate($template);
    }
    public function setTemplate($template)
    {
        $t = $this->add($this->template_class)->loadTemplate($template, '.mail');

        if ($t->is_set('subject')) {
            $s = trim($t->cloneRegion('subject')->render());
            $this->set('subject', $s);
            $t->del('subject');
        }

        if ($t->is_set('html')) {
            $this->setText($t->cloneRegion('text'));
            $this->setHtml($t->cloneRegion('html'));
        } elseif ($t->is_set('body')) {
            $this->set($t->cloneRegion('body'));
        } else {
            $this->set($t);
        }

        return $this;
    }
    public function setTag($arg, $val = null)
    {
        return $this->set($arg, $val);
    }
    public function set($arg, $val = null)
    {
        if (is_array($arg)) {
            $this->args = array_merge($this->args, $arg);
        } else {
            if ($val === false) {
                unset($this->args[$arg]);
            } elseif (is_null($val)) {
                $this->addPart('Both')->set($arg);
            } else {
                $this->args[$arg] = $val;
            }
        }

        return $this;
    }
    public function get($arg)
    {
        return $this->args[$arg];
    }
    public function render()
    {
        $this->template->set('body_parts', '');
        foreach ($this->elements as $el) {
            if ($el instanceof TMail_Part) {
                $this->template->appendHTML('body_parts', $el->render());
            }
        }
        $this->template->set('boundary', $this->boundary);
        $this->headers
            ->set('boundary', $this->boundary)
            ->setHTML($this->args);
    }
    public function send($to, $from = null)
    {
        if (is_null($from) && isset($this->args['from'])) {
            $from = $this->args['from'];
        }
        if (is_null($from)) {
            $from = $this->app->getConfig('tmail/from');
        }

        if (!isset($this->args['from_formatted'])) {
            $this->args['from_formatted'] = $from;
        }
        if (!isset($this->args['to_formatted'])) {
            $this->args['to_formatted'] = $to;
        }

        $from = $this->extractEmail($from);
        $from = $from[2];
        $to = $this->extractEmail($to);
        $to = $to[2];

        $this->render();
        $body = $this->template->render();
        $headers = trim($this->headers->render());
        $subject = $this->args['subject'];

        // TODO: should we use mb_encode_mimeheader ?
        if (!($res = $this->hook('send', array($to, $from, $subject, $body, $headers)))) {
            return mail($to, $subject, $body, $headers, '-f '.$from);
        }

        return $res;
    }
}
