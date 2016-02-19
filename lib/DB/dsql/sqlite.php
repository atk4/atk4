<?php
/**
 * Undocumented.
 */
class DB_dsql_sqlite extends DB_dsql
{
    public function init()
    {
        parent::init();
        $this->sql_templates['describe'] = 'pragma table_info([table_noalias])';
    }
    public function concat()
    {
        $t = clone $this;
        $t->template = '([concat])';
        $t->args['concat'] = func_get_args();

        return $t;
    }
    public function render_concat()
    {
        $x = array();
        foreach ($this->args['concat'] as $arg) {
            $x[] = is_object($arg) ?
                $this->consume($arg) :
                $this->escape($arg);
        }

        return implode(' || ', $x);
    }
    public function random()
    {
        return $this->expr('random()');
    }
}
