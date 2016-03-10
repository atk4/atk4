<?php
/**
 * This is a Firebird/Interbase driver for Dynamic SQL.
 * To be able to use it in your projects make sure you have the Firebird PDO driver installed for PHP.
 * For more info see PHP manual: http://php.net/manual/en/ref.pdo-firebird.php
 * Howto for compiling/installing on Linux:
 * http://mapopa.blogspot.com/2009/04/php5-and-firebird-pdo-on-ubuntu-hardy.html.
 */
class DB_dsql_firebird extends DB_dsql
{
    public $bt = '';
    public $id_field;

    public function init()
    {
        parent::init();
        $this->sql_templates['update'] =
            'update [table] set [set] [where]';
        $this->sql_templates['select'] =
            'select [limit] [options] [field] [from] [table] [join] [where] [group] [having] [order]';
    }

    public function SQLTemplate($mode)
    {
        $template = parent::SQLTemplate($mode);
        switch ($mode) {
            case 'insert':
                if (empty($this->$id_field) !== false) {
                    return $template.' returning '.$this->$id_field;
                }
        }

        return $template;
    }

    public function execute()
    {
        if (empty($this->args['fields'])) {
            $this->args['fields'] = array('*');
        }

        return parent::execute();
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

    public function calcFoundRows()
    {
        return $this->foundRows();
    }

    /**
     * @return int
     */
    public function foundRows()
    {
        $c = clone $this;
        $c->del('limit');
        $c->del('order');
        $c->del('group');

        return (int) $c->fieldQuery('count(*)')->getOne();
    }

    public function render_limit()
    {
        if ($this->args['limit']) {
            return 'FIRST '.
                            (int) $this->args['limit']['cnt'].
                            ' SKIP '.
                            (int) $this->args['limit']['shift'];
        }
    }
}
