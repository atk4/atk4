<?php // vim:ts=4:sw=4:et:fdm=marker
/**
   This file is part of Agile Toolkit 4 http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
*/
/**
 * Implementation of SQL Query Abstraction Layer for Agile Toolkit
 *
 * @link http://agiletoolkit.org/doc/dsql
 */
class DB_dsql extends AbstractModel implements Iterator {
    /**
     * Data accumulated by calling Definition methods, which is then
     * used when rendering
     */
    public $args=array();

    /** List of PDO parametical arguments for a query. Used only during rendering. */
    public $params=array();

    /** Manually-specified params */
    public $extra_params=array();

    /** PDO Statement, if query is prepared. Used by iterator */
    public $stmt=null;

    /** Expression to use when converting to string */
    public $template=null;

    /**
     * You can switch mode with select(), insert(), update() commands.
     * Mode is initialized to "select" by default
     */
    public $mode=null;

    /** Used to determine main table. */
    public $main_table=null;

    /** If no fields are defined, this field is used */
    public $default_field='*';

    public $default_exception='Exception_DB';

    /** call $q->debug() to turn on debugging. */
    public $debug=false;

    /** prefix for all parameteric variables: a, a_2, a_3, etc */
    public $param_base='a';

    /** When you convert this object to string, the following happens: */
    public $output_mode='getOne';

    /** Backtics are added around all fields. Set this to blank string to avoid */
    public $bt='`';

    /**
     * Templates are used to construct most common queries. Templates may be
     * changed in vendor-specific implementation of dsql (extending this class)
     */
    public $sql_templates=array(
        'select'=>"select [options] [field] [from] [table] [join] [where] [group] [having] [order] [limit]",
        'insert'=>"insert [options_insert] into [table_noalias] ([set_fields]) values ([set_values])",
        'replace'=>"replace [options_replace] into [table_noalias] ([set_fields]) values ([set_value])",
        'update'=>"update [table_noalias] set [set] [where]",
        'delete'=>"delete from  [table_noalias] [where]",
        'truncate'=>'truncate table [table_noalias]'
    );
    /** required for non-id based tables */
    public $id_field;

    // {{{ Generic routines
    function _unique(&$array,$desired=null)
    {
        $desired=preg_replace('/[^a-zA-Z0-9:]/', '_', $desired);
        $desired=parent::_unique($array, $desired);
        return $desired;
    }
    function __clone()
    {
        $this->stmt=null;
    }
    function __toString()
    {
        try {
            if ($this->output_mode==='render') {
                return $this->render();
            } else {
                return (string)$this->getOne();
            }
        } catch (Exception $e) {
            $this->api->caughtException($e);
            //return "Exception: ".$e->getMessage();
        }

        return $this->toString();

        if ($this->expr) {
            return $this->parseTemplate($this->expr);
        }
        return $this->select();
    }

    /** 
     * Explicitly sets template to your query. Remember to change 
     * $this->mode if you switch this
     *
     * @param string $template New template to use by render
     *
     * @return DB_dsql $this
     */
    function template($template)
    {
        $this->template=$template;
        return $this;
    }

    /** 
     * Change prefix for parametric values. Not really useful.
     *
     * @param string $param_base prefix to use for param names
     *
     * @return DB_dsql $this
     */
    function paramBase($param_base)
    {
        $this->param_base=$param_base;
        return $this;
    }

    /**
     * Create new dsql object linked with the same database connection and
     * bearing same data-type. You can use this method to create sub-queries.
     *
     * @return DB_dsql Empty query for same database
     */
    function dsql()
    {
        return $this->owner->dsql(get_class($this));
    }

    /**
     * Converts value into parameter and returns reference. Use only during
     * query rendering. Consider using `consume()` instead.
     *
     * @param string $val String literal containing input data
     *
     * @return string Safe and escapeed string
     */
    function escape($val)
    {
        if ($val===UNDEFINED) {
            return '';
        }
        if (is_array($val)) {
            $out=array();
            foreach ($val as $v) {
                $out[]=$this->escape($v);
            }
            return $out;
        }
        $name=':'.$this->param_base;
        $name=$this->_unique($this->params, $name);
        $this->params[$name]=$val;
        return $name;
    }

    /**
     * Recursively renders sub-query or expression, combining parameters.
     * If the argument is more likely to be a field, use tick=true
     *
     * @param object|string $dsql Expression
     * @param boolean       $tick Preferred quoted style
     *
     * @return string Quoted expression
     */
    function consume($dsql, $tick = true)
    {
        if ($dsql===UNDEFINED) {
            return '';
        }
        if ($dsql===null) {
            return '';
        }
        if (is_object($dsql) && $dsql instanceof Field) {
            $dsql=$dsql->getExpr();
        }
        if (!is_object($dsql) || !$dsql instanceof DB_dsql) {
            return $tick?$this->bt($dsql):$dsql;
        }
        $dsql->params = &$this->params;
        $ret = $dsql->_render();
        if ($dsql->mode==='select') {
            $ret='('.$ret.')';
        }
        unset($dsql->params);
        $dsql->params=array();
        return $ret;
    }

    /** 
     * Defines a custom tag variable. WARNING: always backtick / escaped
     * argument if it's unsafe
     *
     * @param string        $tag   Corresponds to [tag] inside template
     * @param string|object $value Value for the template tag
     *
     * @return DB_dsql $this
     */
    function setCustom($tag, $value = null)
    {
        if (is_array($tag)) {
            foreach ($tag as $key => $val) {
                $this->setCustom($key, $val);
            }
            return $this;
        }
        $this->args['custom'][$tag]=$value;
        return $this;
    }

    /**
     * Removes definition for argument. $q->del('where'), $q->del('fields') etc.
     *
     * @param string $args Could be 'field', 'where', 'order', 'limit', etc
     *
     * @return DB_dsql $this
     */
    function del($args)
    {
        $this->args[$args]=array();
        return $this;
    }

    /**
     * Removes all definitions. Start from scratch
     *
     * @return DB_dsql $this
     */
    function reset()
    {
        $this->args=array();
        return $this;
    }
    // }}}

    // {{{ Dynamic Query Definition methods

    // {{{ Generic methods
    /** 
     * Returns new dynamic query and initializes it to use specific template.
     *
     * @param string $expr SQL Expression. Don't pass unverified input
     * @param array  $tags Array of tags and values. @see setCustom()
     *
     * @return DB_dsql New dynamic query, won't affect $this
     */
    function expr($expr, $tags = array())
    {
        return $this->dsql()->useExpr($expr, $tags);
    }

    /**
     * Change template of existing query instead of creating new one. If unsure
     * use expr()
     *
     * @param string $expr SQL Expression. Don't pass unverified input
     * @param array  $tags Obsolete, use templates / setCustom()
     *
     * @return DB_dsql $this
     */
    function useExpr($expr, $tags = array())
    {
        foreach ($tags as $key => $value) {
            if ($key[0] == ':') {
                $this->extra_params[$key] = $value;
                continue;
            }

            $this->args['custom'][$key]=$value;
        }

        $this->template=$expr;
        if ($tags) {
            $this->setCustom($tags);
        }
        $this->output_mode='render';
        return $this;
    }

    /** 
     * Shortcut to produce expression which concatinates "where" clauses with 
     * "OR" operator
     *
     * @return DB_dsql New dynamic query, won't affect $this
     */
    function orExpr()
    {
        return $this->expr('([orwhere])');
    }

    /**
     * Shortcut to produce expression for series of conditions concatinated
     * with "and". Useful to be passed inside where() or join()
     *
     * @return DB_dsql New dynamic query, won't affect $this
     */
    function andExpr()
    {
        return $this->expr('([andwhere])');
    }

    /**
     * Return expression containing a properly escaped field. Use make 
     * subquery condition reference parent query 
     *
     * @param string $fld Field in SQL table
     *
     * @return DB_dsql Expression pointing to specified field
     */
    function getField($fld)
    {
        if ($this->main_table===false ){
            throw $this->exception(
                'Cannot use getField() when multiple tables are queried'
            );
        }
        return $this->expr(
            $this->bt($this->main_table).
            '.'.
            $this->bt($fld)
        );
    }
    // }}}
    // {{{ table()
    /** 
     * Specifies which table to use in this dynamic query. You may specify
     * array to perform operation on multiple tables.
     *
     * Examples:
     *  $q->table('user');
     *  $q->table('user','u');
     *  $q->table('user')->table('salary')
     *  $q->table(array('user','salary'));
     *  $q->table(array('user','salary'),'user');
     *  $q->table(array('u'=>'user','s'=>'salary'));
     *
     * If you specify multiple tables, you still need to make sure to add 
     * proper "where" conditions. All the above examples return $q (for chaining)
     *
     * You can also call table without arguments, which will return current table:
     *
     *  echo $q->table();
     *
     * If multiple tables are used, "false" is returned. Return is not quoted.
     * Please avoid using table() without arguments as more tables may be
     * dynamically added later.
     *
     * @param string $table Specify table to use
     * @param string $alias Specify alias for the table
     *
     * @return DB_dsql $this
     **/
    function table($table = UNDEFINED, $alias = UNDEFINED)
    {
        if ($table===UNDEFINED) {
            return $this->main_table;
        }

        if (is_array($table)) {
            foreach ($table as $alias => $t) {
                if (is_numeric($alias)) {
                    $alias=UNDEFINED;
                }
                $this->table($t, $alias);
            }
            return $this;
        }

        // main_table tracking allows us to
        if ($this->main_table===null) {
            $this->main_table=$alias===UNDEFINED||!$alias?$table:$alias;
        } elseif ($this->main_table) {
            $this->main_table=false;   // query from multiple tables
        }

        $this->args['table'][]=array($table,$alias);
        return $this;
    }

    /**
     * Renders part of the template: [table]
     * Do not call directly
     *
     * @return string Parsed template chunk
     */
    function render_table()
    {
        $ret=array();
        if (!is_array($this->args['table'])) {
            return;
        }
        foreach ($this->args['table'] as $row) {
            list($table, $alias)=$row;

            $table=$this->bt($table);

            if ($alias!==UNDEFINED && $alias) {
                $table.=' '.$this->bt($alias);
            }

            $ret[]=$table;
        }
        return join(',', $ret);
    }

    /** 
     * Conditionally returns "from", only if table is Specified
     * Do not call directly
     *
     * @return string Parsed template chunk
     */
    function render_from()
    {
        if ($this->args['table']) {
            return 'from';
        }
        return '';
    }

    /** 
     * Returns template component [table_noalias]
     *
     * @return string Parsed template chunk
     */
    function render_table_noalias()
    {
        $ret=array();
        foreach ($this->args['table'] as $row) {
            list($table, $alias)=$row;

            $table=$this->bt($table);


            $ret[]=$table;
        }
        return join(', ', $ret);
    }
    // }}}
    // {{{ field()
    /**
     * Adds new column to resulting select by querying $field. 
     *
     * Examples:
     *  $q->field('name');
     *
     * Second argument specifies table for regular fields
     *  $q->field('name','user');
     *  $q->field('name','user')->field('line1','address');
     *
     * Array as a first argument will specify mulitple fields, same as calling field() multiple times
     *  $q->field(array('name','surname'));
     *
     * Associative array will assume that "key" holds the alias. Value may be object.
     *  $q->field(array('alias'=>'name','alias2'=>surname'));
     *  $q->field(array('alias'=>$q->expr(..), 'alias2'=>$q->dsql()->.. ));
     *
     * You may use array with aliases together with table specifier.
     *  $q->field(array('alias'=>'name','alias2'=>surname'),'user');
     *
     * You can specify $q->expr() for calculated fields. Alias is mandatory.
     *  $q->field( $q->expr('2+2'),'alias');                // must always use alias
     *
     * You can use $q->dsql() for subqueries. Alias is mandatory.
     *  $q->field( $q->dsql()->table('x')... , 'alias');    // must always use alias
     *
     * @param string|array $field Specifies field to select
     * @param string       $table Specify if not using primary table
     * @param string       $alias Specify alias for this field
     *
     * @return DB_dsql $this
     */
    function field($field, $table = null, $alias = null)
    {
        if (is_array($field)) {
            foreach ($field as $alias => $f) {
                if (is_numeric($alias)) {
                    $alias=null;
                }
                $this->field($f, $table, $alias);
            }
            return $this;
        } elseif (is_string($field)) {
            $field=explode(',', $field);
            if (count($field) > 1) {
                foreach ($field as $f) {
                    $this->field($f, $table, $alias);
                }
                return $this;
            }
            $field=$field[0];
        }

        if (is_object($field)) {
            $alias=$table;
            $table=null;
        }
        $this->args['fields'][]=array($field, $table, $alias);
        return $this;
    }

    /**
     * Removes all field definitions and returns only field you specify
     * as parameter to this method. Original query is not affected ($this)
     * Same as for field() syntax
     *
     * @param string|array $field Specifies field to select
     * @param string       $table Specify if not using primary table
     * @param string       $alias Specify alias for this field
     *
     * @return DB_dsql Clone of $this with only one field
     */
    function fieldQuery($field, $table = null, $alias = null)
    {
        $q=clone $this;
        return $q->del('fields')->field($field, $table, $alias);
    }

    /** 
     * Returns template component [field]
     *
     * @return string Parsed template chunk
     */
    function render_field()
    {
        $result=array();
        if (!$this->args['fields']) {
            if ($this->default_field instanceof DB_dsql) {
                return $this->consume($this->default_field);
            }
            return (string)$this->default_field;
        }
        foreach ($this->args['fields'] as $row) {
            list($field,$table,$alias)=$row;
            if ($alias===$field) {
                $alias=UNDEFINED;
            }
            /**/$this->api->pr->start('dsql/render/field/consume');
            $field=$this->consume($field);
            /**/$this->api->pr->stop();
            if (!$field) {
                $field=$table;
                $table=UNDEFINED;
            }
            if ($table && $table!==UNDEFINED) {
                $field=$this->bt($table).'.'.$field;
            }
            if ($alias && $alias!==UNDEFINED) {
                $field.=' '.$this->bt($alias);
            }
            $result[]=$field;
        }
        return join(',', $result);
    }
    // }}}
    // {{{ where() and having()
    /** 
     * Adds condition to your query
     *
     * Examples:
     *  $q->where('id',1);
     *
     * Second argument specifies table for regular fields
     *  $q->where('id>','1');
     *  $q->where('id','>',1);
     *
     * You may use expressions
     *  $q->where($q->expr('a=b'));
     *  $q->where('date>',$q->expr('now()'));
     *  $q->where($q->expr('length(password)'),'>',5);
     *
     * Finally, subqueries can also be used
     *  $q->where('foo',$q->dsql()->table('foo')->field('name'));
     *
     * To specify OR conditions
     *  $q->where($q->orExpr()->where('a',1)->where('b',1));
     *
     * you can also use the shortcut:
     * 
     *  $q->where(array('a is null','b is null'));
     *
     * @param mixed  $field Field, array for OR or Expression
     * @param string $cond  Condition such as '=', '>' or 'is not'
     * @param string $value Value. Will be quoted unless you pass expression
     * @param string $kind  Do not use directly. Use having()
     * 
     * @return DB_dsql $this
     */
    function where($field, $cond = UNDEFINED, $value = UNDEFINED, $kind = 'where')
    {
        if (is_array($field)) {
            // or conditions
            $or=$this->orExpr();
            foreach ($field as $row) {
                if (is_array($row)) {
                    $or->where(
                        $row[0],
                        isset($row[1])?$row[1]:UNDEFINED,
                        isset($row[2])?$row[2]:UNDEFINED
                    );
                } elseif (is_object($row)) {
                    $or->where($row);
                } else {
                    $or->where($or->expr($row));
                }
            }
            $field=$or;
            $this->api->x=1;
        }


        if (is_string($field) && !preg_match('/^[.a-zA-Z0-9_]*$/', $field)) {
            // field contains non-alphanumeric values. Look for condition
            preg_match(
                '/^([^ <>!=]*)([><!=]*|( *(not|is|in|like))*) *$/',
                $field,
                $matches
            );
            $value=$cond;
            $cond=$matches[2];
            if (!$cond) {
                // IF COMPAT
                $matches[1]=$this->expr($field);
                if ($value && $value!==UNDEFINED) {
                    $cond='=';
                } else {
                    $cond=UNDEFINED;
                }
            }
            $field=$matches[1];
        }

        $this->args[$kind][]=array($field,$cond,$value);
        return $this;
    }

    /**
     * Same syntax as where()
     *
     * @param mixed  $field Field, array for OR or Expression
     * @param string $cond  Condition such as '=', '>' or 'is not'
     * @param string $value Value. Will be quoted unless you pass expression
     *
     * @return DB_dsql $this
     */
    function having($field, $cond = UNDEFINED, $value = UNDEFINED)
    {
        return $this->where($field, $cond, $value, 'having');
    }

    /**
     * Subroutine which renders either [where] or [having]
     *
     * @param string $kind 'where' or 'having'
     * 
     * @return string Parsed chunk of query
     */
    function _render_where($kind)
    {
        $ret=array();
        foreach ($this->args[$kind] as $row) {
            list($field,$cond,$value)=$row;

            if (is_object($field)) {
                // if first argument is object, condition must be explicitly
                // specified
                $field=$this->consume($field);
            } else {
                list($table, $field)=explode('.', $field, 2);
                if ($field) {
                    $field=$this->bt($table).'.'.$this->bt($field);
                } else {
                    $field=$this->bt($table);
                }
            }

            if ($value===UNDEFINED && $cond===UNDEFINED) {
                $r=$field;
                $ret[]=$r;
                continue;
            }

            if ($value===UNDEFINED) {
                $value=$cond;
                $cond='=';
                if (is_array($value)) {
                    $cond='in';
                }
                if (is_object($value) && @$value->mode==='select') {
                    $cond='in';
                }
            } else {
                $cond=trim($cond);
            }

            if ($cond==='=' && $value===null) {
                $cond='is';
            }


            if ($cond==='in' && is_string($value)) {
                $value=explode(',', $value);
            }

            if (is_array($value)) {
                $v=array();
                foreach ($value as $vv) {
                    $v[]=$this->escape($vv);
                }
                $value='('.join(',', $v).')';
                $cond='in';
                $r=$this->consume($field).' '.$cond.' '.$value;
                $ret[]=$r;
                continue;
            }

            if (is_object($value)) {
                $value=$this->consume($value);
            } else {
                $value=$this->escape($value);
            }

            $r=$field.' '.$cond.' '.$value;
            $ret[]=$r;
        }
        return $ret;
    }

    /**
     * Renders [where]
     *
     * @return string rendered SQL chunk
     */
    function render_where()
    {
        if (!$this->args['where']) {
            return;
        }
        return 'where '.join(' and ', $this->_render_where('where'));
    }

    /**
     * Renders [orwhere]
     *
     * @return string rendered SQL chunk
     */
    function render_orwhere()
    {
        if (!$this->args['where']) {
            return;
        }
        return join(' or ', $this->_render_where('where'));
    }

    /**
     * Renders [andwhere]
     *
     * @return string rendered SQL chunk
     */
    function render_andwhere()
    {
        if (!$this->args['where']) {
            return;
        }
        return join(' and ', $this->_render_where('where'));
    }

    /**
     * Renders [having]
     *
     * @return string rendered SQL chunk
     */
    function render_having()
    {
        if (!$this->args['having']) {
            return;
        }
        return 'having '.join(' and ', $this->_render_where('having'));
    }
    // }}}
    // {{{ join()
    /** 
     * Joins your query with another table
     *
     * Examples:
     *  $q->join('address');         // on user.address_id=address.id
     *  $q->join('address.user_id'); // on address.user_id=user.id
     *  $q->join('address a');       // With alias
     *  $q->join(array('a'=>'address')); // Also alias
     *
     * Second argument may specify the field of the master table
     *  $q->join('address', 'billing_id');
     *  $q->join('address.code', 'code');
     *  $q->join('address.code', 'user.code');
     *
     * Third argument may specify which kind of join to use.
     *  $q->join('address', null, 'left');
     *  $q->join('address.code', 'user.code', 'inner');
     *
     * Using array syntax you can join multiple tables too
     *  $q->join(array('a'=>'address', 'p'=>'portfolio'));
     *
     * You can use expression for more complex joins
     *  $q->join('address', 
     *      $q->exprOrExpr()
     *          ->where('user.billing_id=address.id')
     *          ->where('user.technical_id=address.id')
     *  )
     *
     * @param string $foreign_table  Table to join with
     * @param string $master_field   Field in master table
     * @param string $join_kind      'left' or 'inner', etc
     * @param string $_foreign_alias Internal, don't use
     *
     * @return DB_dsql $this
     */
    function join(
        $foreign_table,
        $master_field = null,
        $join_kind = null,
        $_foreign_alias = null
    ) {
        // Compatibility mode
        if (isset($this->api->compat)) {
            if (strpos($foreign_table, ' ')) {
                list($foreign_table, $alias)=explode(' ', $foreign_table);
                $foreign_table=array($alias => $foreign_table);
            }
            if (strpos($master_field, '=')) {
                $master_field=$this->expr($master_field);
            }
        }

        // If array - add recursively
        if (is_array($foreign_table)) {
            foreach ($foreign_table as $alias => $foreign) {
                if (is_numeric($alias)) {
                    $alias=null;
                }

                $this->join($foreign, $master_field, $join_kind, $alias);
            }
            return $this;
        }
        $j=array();

        // Split and deduce fields
        list($f1, $f2)=explode('.', $foreign_table, 2);

        if (is_object($master_field)) {
            $j['expr']=$master_field;
        } else {
            // Split and deduce primary table
            if (is_null($master_field)) {
                list($m1, $m2)=array(null, null);
            } else {
                list($m1, $m2)=explode('.', $master_field, 2);
            }
            if (is_null($m2)) {
                $m2=$m1;
                $m1=null;
            }
            if (is_null($m1)) {
                $m1=$this->main_table;
            }

            // Identify fields we use for joins
            if (is_null($f2) && is_null($m2)) {
                $m2=$f1.'_id';
            }
            if (is_null($m2)) {
                $m2='id';
            }
            $j['m1']=$m1;
            $j['m2']=$m2;
        }
        $j['f1']=$f1;
        if (is_null($f2)) {
            $f2='id';
        }
        $j['f2']=$f2;

        $j['t']=$join_kind?:'left';
        $j['fa']=$_foreign_alias;

        $this->args['join'][]=$j;
        return $this;
    }

    /**
     * Renders [join]
     *
     * @return string rendered SQL chunk
     */
    function render_join()
    {
        if (!$this->args['join']) {
            return '';
        }
        $joins=array();
        foreach ($this->args['join'] as $j) {
            $jj='';

            $jj.=$j['t'].' join ';

            $jj.=$this->bt($j['f1']);

            if (!is_null($j['fa'])) {
                $jj.=' as '.$this->bt($j['fa']);
            }

            $jj.=' on ';

            if ($j['expr']) {
                $jj.=$this->consume($j['expr']);
            } else {
                $jj.=
                    $this->bt($j['fa']?:$j['f1']).'.'.
                    $this->bt($j['f2']).' = '.
                    $this->bt($j['m1']).'.'.
                    $this->bt($j['m2']);
            }
            $joins[]=$jj;
        }
        return implode(' ', $joins);
    }
    // }}}
    // {{{ group()
    /**
     * Implemens GROUP BY functionality. Simply pass either string field
     * or expression
     *
     * @param string|object $group Group by this
     *
     * @return DB_dsql $this
     */
    function group($group)
    {
        return $this->_setArray($group, 'group');
    }

    /**
     * Renders [group]
     *
     * @return string rendered SQL chunk
     */
    function render_group()
    {
        if (!$this->args['group']) {
            return'';
        }
        $x=array();
        foreach ($this->args['group'] as $arg) {
            $x[]=$this->consume($arg);
        }
        return 'group by '.implode(', ', $x);
    }
    // }}}
    // {{{ order()
    /** 
     * Orders results by field or Expression. See documentation for full
     * list of possible arguments
     *
     * $q->order('name');
     * $q->order('name desc');
     * $q->order('name desc, id asc')
     * $q->order('name',true);
     *
     * @param string $order Order by
     * @param string $desc  true to sort descending
     *
     * @return DB_dsql $this
     */
    function order($order, $desc = null)
    {
        // Case with comma-separated fields or first argument being an array
        if (is_string($order) && strpos($order, ',')!==false) {
            // Check for multiple
            $order=explode(',', $order);
        }
        if (is_array($order)) {
            if (!is_null($desc)) {
                throw $this->exception(
                    'If first argument is array, second argument must not be used'
                );
            }
            foreach (array_reverse($order) as $o) {
                $this->order($o);
            }
            return $this;
        }

        // First argument may contain space, to divide field and keyword
        if (is_null($desc) && is_string($order) && strpos($order, ' ')!==false) {
            list($order, $desc)=array_map('trim', explode(' ', trim($order), 2));
        }

        if (is_string($order) && strpos($order, '.')!==false) {
            $order=join('.', $this->bt(explode('.', $order)));
        }

        if (is_bool($desc)) {
            $desc=$desc?'desc':'';
        } elseif (strtolower($desc)==='asc') {
            $desc='';
        } elseif ($desc && strtolower($desc)!='desc') {
            throw $this->exception('Incorrect ordering keyword')
                ->addMoreInfo('order by', $desc);
        }

        // TODO:
        /*
        if (isset($this->args['order'][0]) and (
            $this->args['order'][0] === array($order,$desc))) {
        }
         */
        $this->args['order'][]=array($order,$desc);
        return $this;
    }

    /**
     * Renders [order]
     *
     * @return string rendered SQL chunk
     */
    function render_order()
    {
        if (!$this->args['order']) {
            return'';
        }
        $x=array();
        foreach ($this->args['order'] as $tmp) {
            list($arg,$desc)=$tmp;
            $x[]=$this->consume($arg).($desc?(' '.$desc):'');
        }
        return 'order by '.implode(', ', array_reverse($x));
    }
    // }}}
    // {{{ option() and args()
    /** 
     * Defines query option, such as DISTINCT
     *
     * @param string|expresion $option Option to put after SELECT
     *
     * @return DB_dsql $this
     */
    function option($option)
    {
        return $this->_setArray($option, 'options');
    }

    /**
     * Renders [options]
     *
     * @return string rendered SQL chunk
     */
    function render_options()
    {
        return @implode(' ', $this->args['options']);
    }

    /** 
     * Defines insert query option, such as IGNORE
     *
     * @param string|expresion $option Option to put after SELECT
     *
     * @return DB_dsql $this
     */
    function option_insert($option)
    {
        return $this->_setArray($option, 'options_insert');
    }

    /**
     * Renders [options_insert]
     *
     * @return string rendered SQL chunk
     */
    function render_options_insert()
    {
        if (!$this->args['options_insert']) {
            return '';
        }
        return implode(' ', $this->args['options_insert']);
    }
    // }}}
    // {{{  call() and function execution
    /** 
     * Sets a template for a user-defined method call with specified arguments
     *
     * @param string $fx   Name of the user defined method
     * @param array  $args Arguments in mixed form
     *
     * @return DB_dsql $this
     */
    function call($fx, $args = null)
    {
        $this->mode='call';
        $this->args['fx']=$fx;
        if (!is_null($args)) {
            $this->args($args);
        }
        $this->template="call [fx]([args])";
        return $this;
    }

    /**
     * Executes a standard function with arguments, such as IF
     *
     * $q->fx('if', array($condition, $if_true, $if_false));
     *
     * @param string $fx   Name of the built-in method
     * @param array  $args Arguments
     *
     * @return DB_dsql $this
     */
    function fx($fx, $args = null)
    {
        $this->mode='fx';
        $this->args['fx']=$fx;
        if (!is_null($args)) {
            $this->args($args);
        }
        $this->template="[fx]([args])";
        return $this;
    }

    /**
     * set arguments for call(). Used by fx() and call() but you can use This
     * with ->expr("in ([args])")->args($values);
     *
     * @param array $args Array with mixed arguments 
     *
     * @return DB_dsql $this
     */
    function args($args)
    {
        return $this->_setArray($args, 'args', false);
    }

    /**
     * Renders [args]
     *
     * @return string rendered SQL chunk
     */
    function render_args()
    {
        $x=array();
        foreach ($this->args['args'] as $arg) {
            $x[]=is_object($arg)?
                $this->consume($arg):
                $this->escape($arg);
        }
        return implode(', ', $x);
    }

    /**
     * Sets IGNORE option 
     *
     * @return DB_dsql $this
     */
    function ignore()
    {
        $this->args['options_insert'][]='ignore';
        return $this;
    }

    /**
     * Check if specified option was previously added
     *
     * @param string $option Which option to check?
     *
     * @return boolean
     */
    function hasOption($option)
    {
        return @in_array($option, $this->args['options']);
    }

    /**
     * Check if specified insert option was previously added
     *
     * @param string $option Which option to check?
     *
     * @return boolean
     */
    function hasInsertOption($option)
    {
        return @in_array($option, $this->args['options_insert']);
    }
    // }}}
    // {{{ limit()
    /** 
     * Limit how many rows will be returned
     *
     * @param int $cnt   Number of rows to return
     * @param int $shift Offset, how many rows to skip
     *
     * @return DB_dsql $this
     */
    function limit($cnt, $shift = 0)
    {
        $this->args['limit']=array(
            'cnt'=>$cnt,
            'shift'=>$shift
        );
        return $this;
    }

    /**
     * Renders [limit]
     *
     * @return string rendered SQL chunk
     */
    function render_limit()
    {
        if ($this->args['limit']) {
            return 'limit '.
                (int)$this->args['limit']['shift'].
                ', '.
                (int)$this->args['limit']['cnt'];
        }
    }
    // }}}
    // {{{ set()
    /** 
     * Sets field value for INSERT or UPDATE statements
     *
     * @param string $field Name of the field
     * @param mixed  $value Value of the field
     *
     * @return DB_dsql $this
     */
    function set($field, $value = UNDEFINED)
    {
        if ($value===false) {
            throw $this->exception('Value "false" is not supported by SQL');
        }
        if (is_array($field)) {
            foreach ($field as $key => $value) {
                $this->set($key, $value);
            }
            return $this;
        }

        if ($value===UNDEFINED) {
            throw $this->exception('Specify value when calling set()');
        }

        $this->args['set'][$field]=$value;
        return $this;
    }
    /**
     * Renders [set] for UPDATE query
     *
     * @return string rendered SQL chunk
     */
    function render_set()
    {
        $x=array();
        if ($this->args['set']) {
            foreach($this->args['set'] as $field=>$value){
                if (is_object($field)) {
                    $field=$this->consume($field);
                } else {
                    $field=$this->bt($field);
                }
                if (is_object($value)) {
                    $value=$this->consume($value);
                } else {
                    $value=$this->escape($value);
                }

                $x[]=$field.'='.$value;
            }
        }
        return join(', ', $x);
    }
    /**
     * Renders [set_fields] for INSERT
     *
     * @return string rendered SQL chunk
     */
    function render_set_fields()
    {
        $x=array();
        if ($this->args['set']) {
            foreach ($this->args['set'] as $field => $value) {

                if (is_object($field)) {
                    $field=$this->consume($field);
                } else {
                    $field=$this->bt($field);
                }

                $x[]=$field;
            }
        }
        return join(',', $x);
    }
    /**
     * Renders [set_values] for INSERT
     *
     * @return string rendered SQL chunk
     */
    function render_set_values()
    {
        $x=array();
        if ($this->args['set']) {
            foreach ($this->args['set'] as $field => $value) {

                if (is_object($value)) {
                    $value=$this->consume($value);
                } else {
                    $value=$this->escape($value);
                }

                $x[]=$value;
            }
        }
        return join(',', $x);
    }
    // }}}
    // {{{ Miscelanious
    /**
     * Adds backtics around argument. This will allow you to use reserved
     * SQL words as table or field names such as "table"
     *
     * @param string $s any string
     *
     * @return string Quoted string
     */
    function bt($s)
    {
        if (is_array($s)) {
            $out=array();
            foreach ($s as $ss) {
                $out[]=$this->bt($ss);
            }
            return $out;
        }

        if (!$this->bt
            || is_object($s)
            || $s==='*'
            || strpos($s, '.')!==false
            || strpos($s, '(')!==false
            || strpos($s, $this->bt)!==false
        ) {
            return $s;
        }

        return $this->bt.$s.$this->bt;
    }
    /**
     * Internal method which can be used by simple param-giving methods such
     * as option(), group(), etc
     *
     * @param string  $values       hm
     * @param string  $name         hm
     * @param boolean $parse_commas hm
     *
     * @private
     * @return DB_dsql $this
     */
    function _setArray($values, $name, $parse_commas = true)
    {
        if (is_string($values) && $parse_commas && strpos($values, ',')) {
            $values=explode(',', $values);
        }
        if (!is_array($values)) {
            $values=array($values);
        }
        if (!isset($this->args[$name])) {
            $this->args[$name]=array();
        }
        $this->args[$name]=array_merge($this->args[$name], $values);
        return $this;
    }
    // }}}

    // }}}

    // {{{ Statement templates and interfaces

    /**
     * Switch template for this query. Determines what would be done
     * on execute.
     * 
     * By default it is in SELECT mode
     *
     * @param string $mode A key for $this->sql_templates
     * 
     * @return DB_dsql $this
     */
    function SQLTemplate($mode)
    {
        $this->mode=$mode;
        $this->template=$this->sql_templates[$mode];
        return $this;
    }
    /**
     * Return expression for concatinating multiple values
     * Accepts variable number of arguments, all of them would be
     * escaped
     * 
     * @return DB_dsql clone of $this
     */
    function concat()
    {
        $t=clone $this;
        return $t->fx('concat', func_get_args());
    }

    /**
     * Creates a query for listing tables in databse-specific form
     * Agile Toolkit DSQL does not pretend to know anything about model
     * structure, so result parsing is up to you
     *
     * @param string $table Table
     * 
     * @return DB_dsql clone of $this
     */
    function describe($table)
    {
        return $this->expr('desc [desc_table]')
            ->setCustom('desc_table', $this->bt($table));
    }

    /**
     * Renders [fx]
     *
     * @return string rendered SQL chunk
     */
    function render_fx()
    {
        return $this->args['fx'];
    }

    /**
     * Creates expression for SUM()
     *
     * @param string|object $arg Typically an expression of a sub-query
     *
     * @return DB_dsql clone of $this
     */
    function sum($arg = null)
    {
        if (is_null($arg)) {
            $arg='*';
        }
        return $this->expr('sum([sum])')->setCustom('sum', $this->bt($arg));
    }

    /**
     * Creates expression for COUNT()
     *
     * @param string|object $arg Typically an expression of a sub-query
     *
     * @return DB_dsql clone of $this
     */
    function count($arg = null)
    {
        if (is_null($arg)) {
            $arg='*';
        }
        return $this->expr('count([count])')->setCustom('count', $this->bt($arg));
    }
    /**
     * Returns method for generating random numbers. This is used for ordering
     * table in random order
     *
     * @return DB_dsql clone of $this
     */
    function random()
    {
        return $this->expr('rand()');
    }
    // }}}

    // {{{ More complex query generations and specific cases

    /** 
     * Executes current query
     *
     * @return DB_dsql $this
     */
    function execute()
    {
        try {
            /**/$this->api->pr->start('dsql/execute/render');
            $q=$this->render();
            /**/$this->api->pr->next('dsql/execute/query');
            $this->stmt=$this->owner->query($q, $this->params);
            $this->template=$this->mode=null;
            /**/$this->api->pr->stop();
            return $this;
        } catch (PDOException $e) {
            throw $this->exception('Database Query Failed')
                ->addPDOException($e)
                ->addMoreInfo('mode', $this->mode)
                ->addMoreInfo('params', $this->params)
                ->addMoreInfo('query', $q)
                ->addMoreInfo('template', $this->template)
                ;
        }
    }

    /**
     * Executes select query.
     *
     * @return DB_dsql() $this
     */
    function select()
    {
        return $this->SQLTemplate('select')->execute();
    }

    /** 
     * Executes insert query. Returns ID of new record. 
     *
     * @return int new record ID (from last_id)
     */
    function insert()
    {
        $this->SQLTemplate('insert')->execute();
        return
            $this->hasInsertOption('ignore')?null:
            $this->owner->lastID();
    }

    /**
     * Inserts multiple rows of data. Uses ignore option
     * AVOID using this, might not be implemented correctly
     *
     * @param array $array Insert multiple rows into table with one query
     *
     * @return array List of IDs
     */
    function insertAll($array)
    {
        $ids=array();
        foreach ($array as $hash) {
            $ids[]=$this->del('set')->set($hash)->insert();
        }
        return $ids;
    }

    /**
     * Executes update query
     *
     * @return DB_dsql $this
     */
    function update()
    {
        return $this->SQLTemplate('update')->execute();
    }

    /**
     * Executes replace query
     *
     * @return DB_dsql $this
     */
    function replace()
    {
        return $this->SQLTemplate('replace')->execute();
    }

    /**
     * Executes delete query
     *
     * @return DB_dsql $this
     */
    function delete()
    {
        return $this->SQLTemplate('delete')->execute();
    }

    /**
     * Executes truncate query
     *
     * @return DB_dsql $this
     */
    function truncate()
    {
        return $this->SQLTemplate('truncate')->execute();
    }


    /** @obsolete, use select() */
    function do_select(){
        return $this->select();
    }
    /** @obsolete, use insert() */
    function do_insert(){
        return $this->insert();
    }
    /** @obsolete, use update() */
    function do_update(){
        return $this->update();
    }
    /** @obsolete, use replace() */
    function do_replace(){
        return $this->replace();
    }
    // }}}

    // {{{ Data fetching modes
    /** 
     * Will execute DSQL query and return all results inside array of hashes
     *
     * @return array Array of associative arrays
     */
    function get()
    {
        if (!$this->stmt) {
            $this->execute();
        }
        $res=$this->stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->rewind();
        $this->stmt=null;
        return $res;
    }

    /**
     * Will execute DSQL query and return first column of a first row
     *
     * You can also simply cast your DSQL into string to get this value
     *
     * echo $dsql;
     *
     * @return string Value of first column in first row
     */
    function getOne()
    {
        $res=$this->getRow();
        $this->rewind();
        $this->stmt=null;
        return $res[0];
    }
    /**
     * Will execute DSQL query and return first row as array (not hash). If 
     * you call several times will return subsequent rows
     *
     * @return array Next row of your data (not hash)
     */
    function getRow()
    {
        return $this->fetch(PDO::FETCH_NUM);
    }
    /** 
     * Will execute DSQL query and return first row as hash (column=>value) 
     *
     * @return array Hash of next row in data stream
     */
    function getHash()
    {
        return $this->fetch(PDO::FETCH_ASSOC);
    }
    /**
     * Will execute the query (if it's not executed already) and return
     * first row
     *
     * @param int $mode PDO fetch mode
     *
     * @return mixed return result of PDO::fetch
     */
    function fetch($mode = PDO::FETCH_ASSOC)
    {
        if (!$this->stmt) {
            $this->execute();
        }
        return $this->stmt->fetch($mode);
    }
    // {{{ Obsolete functions 
    /** @obsolete. Use get() */
    function fetchAll(){
        return $this->get();
    }
    /** @obsolete. Use getQne() */
    function do_getOne(){
        return $this->getOne(); 
    }
    /** @obsolete. Use get() */
    function do_getAllHash(){ 
        return $this->get(); 
    }
    function do_getAll(){ 
        return $this->get(); 
    }
    /** @obsolete. Use get() */
    function getAll(){
        return $this->get();
    }
    /** @obsolete. Use getRow() */
    function do_getRow(){ 
        return $this->getRow();
    }
    /** @obsolete. Use getHash() */
    function do_getHash(){ 
        return $this->getHash(); 
    }
    // }}}


    /** 
     * Sets flag to hint SQL (if supported) to prepare total number of columns.
     * Use foundRows() to read this afterwards
     *
     * @return DB_dsql $this
     */
    function calcFoundRows(){
        return $this;
    }

    /**
     * Obsolete - naming bug
     */
    function calc_found_rows()
    {
        return $this->calcFoundRows();
    }
    /**
     * After fetching data, call this to find out how many rows there were in
     * total. Call calcFoundRows() for better performance
     *
     * @return string number of results
     */
    function foundRows()
    {
        if ($this->hasOption('SQL_CALC_FOUND_ROWS')) {
            return $this->owner->getOne('select found_rows()');
        }
        /* db-compatibl way: */
        $c=clone $this;
        $c->del('limit');
        $c->fieldQuery('count(*)');
        return $c->getOne();
    }
    // }}}

    // {{{ Iterator support
    public $data=false;
    public $_iterating=false;
    public $preexec=false;
    /**
     * Execute query faster, but don't fetch data until iterating started. This
     * can be done if you need to know foundRows() before fetching data
     *
     * @return DB_dsql $this
     */
    function preexec()
    {
        $this->execute();
        $this->preexec=true;
        return $this;
    }
    function rewind()
    {
        if($this->_iterating){
            $this->stmt=null;
            $this->_iterating=false;
        }
        $this->_iterating=true;
        return $this;
    }
    function next()
    {
        $this->data = $this->fetch();
        return $this;
    }
    function current()
    {
        return $this->data;
    }
    function key()
    {
        return $this->data[$this->id_field];
    }
    function valid()
    {
        if(!$this->stmt || $this->preexec){
            $this->preexec=false;
            $this->data = $this->fetch();
        }
        return (boolean)$this->data;
    }
    // }}}

    // {{{ Rendering
    /**
     * Will set a flag which will output query (echo) as it is being rendered.
     *
     * @return DB_dsql $this
     */
    function debug()
    {
        $this->debug=1;
        return $this;
    }
    /**
     * Return formatted debug output
     *
     * @param string $r Rendered material
     *
     * @return string debug of the query
     */
    function getDebugQuery($r = null)
    {
        if (!$r) {
            $r=$this->_render();
        }
        $d=$r;
        $pp=array();
        $d=preg_replace('/`([^`]*)`/', '`<font color="black">\1</font>`', $d);
        foreach (array_reverse($this->params) as $key => $val) {
            if (is_string($val)) {
                $d=preg_replace('/'.$key.'([^_]|$)/', '"<font color="green">'.
                    htmlspecialchars(addslashes($val)).'</font>"\1', $d);
            } elseif (is_null($val)) {
                $d=preg_replace(
                    '/'.$key.'([^_]|$)/',
                    '<font color="black">NULL</font>\1',
                    $d
                );
            } elseif (is_numeric($val)) {
                $d=preg_replace(
                    '/'.$key.'([^_]|$)/',
                    '<font color="red">'.$val.'</font>\1',
                    $d
                );
            } else {
                $d=preg_replace('/'.$key.'([^_]|$)/', $val.'\1', $d);
            }

            $pp[]=$key;
        }
        return "<font color='blue'>".$d."</font> <font color='gray'>[".
            join(', ', $pp)."]</font><br/>";
    }
    /**
     * Converts query into string format. This will contain parametric
     * references
     *
     * @return string resulting query
     */
    function render()
    {
        $this->params=$this->extra_params;
        $r=$this->_render();
        if ($this->debug) {
            echo $this->getDebugQuery($r);
        }
        return $r;
    }
    /**
     * Helper for render(), which does the actual work
     *
     * @private
     * @return string resulting query
     */
    function _render()
    {
        /**/$this->api->pr->start('dsql/render');
        if (is_null($this->template)) {
            $this->SQLTemplate('select');
        }
        $self=$this;
        $res= preg_replace_callback(
            '/\[([a-z0-9_]*)\]/',
            function ($matches) use ($self) {
                /**/$self->api->pr->next('dsql/render/'.$matches[1], true);
                $fx='render_'.$matches[1];
                if (isset($self->args['custom'][$matches[1]])) {
                    return $self->consume($self->args['custom'][$matches[1]], false);
                } elseif ($self->hasMethod($fx)) {
                    return $self->$fx();
                } else {
                    return $matches[0];
                }
            },
                $this->template
            );
        /**/$this->api->pr->stop(null, true);
        return $res;
    }
    // }}}
}
