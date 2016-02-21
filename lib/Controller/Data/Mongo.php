<?php
/**
 * Undocumented
 */
class Controller_Data_Mongo extends Controller_Data
{
    public $supportConditions = true;
    public $supportLimit = true;
    public $supportOrder = true;
    public $supportRef = true;
    public $supportOperators = array();//'='=>true,'>'=>true,'>='=>true,'<='=>true,'<'=>true,'!='=>true,'like'=>true);

    public function setSource($model, $table = null)
    {
        if (!$table) {
            $table = $model->table;
        }

        if (@!$this->app->mongoclient) {
            $m = new MongoClient($this->app->getConfig('mongo/url', null));
            $db = $this->app->getConfig('mongo/db');
            $this->app->mongoclient = $m->$db;
        }

        parent::setSource($model, array(
            'db' => $this->app->mongoclient->$table,
            'conditions' => array(),
            'collection' => $table,
        ));

        $model->addMethod('incr,decr', $this);

        //$model->data=$model->_table[$this->short_name]['db']->get($id);
    }

    /** Implemetns access to our private storage inside model */
    public function _get($model, $key)
    {
        return $model->_table[$this->short_name][$key];
    }
    public function _set($model, $key, $val)
    {
        $model->_table[$this->short_name][$key] = $val;
    }

    public function incr($m, $field, $amount)
    {
        if (!$m->loaded()) {
            throw $this->exception('Can only increment loaded model');
        }
        $m->db()->update(array($m->id_field => new MongoID($m->id)), array('$inc' => array($field => (float) $amount)));
    }

    public function save($model, $id = null)
    {
        $data = array();

        foreach ($model->elements as $name => $f) {
            if ($f instanceof Field) {
                if (!$f->editable() && !$f->system()) {
                    continue;
                }
                if (!isset($model->dirty[$name]) && $f->defaultValue() === null) {
                    continue;
                }

                $value = $f->get();

                if ($f->type() == 'boolean' && is_bool($value)) {
                    $value = (bool) $value;
                }
                if ($f->type() == 'int') {
                    $value = (int) $value;
                }
                if ($f->type() == 'money' || $f->type() == 'float') {
                    $value = (float) $value;
                }

                $data[$name] = $value;
            }
        }
        unset($data[$model->id_field]);

        foreach ($model->_references as $our_field => $junk) {
            if (isset($data[$our_field]) && $data[$our_field] &&
                $our_field != $model->id_field) {
                $deref = str_replace('_id', '', $our_field);
                if ($deref == $our_field) {
                    continue;
                }

                $m = $model->ref($our_field);
                if (!$m->loaded()) {
                    continue;
                }

                $data[$deref] = $m[$m->title_field];
                if ($m instanceof Mongo_Model) {
                    $data[$our_field] = new MongoID($data[$our_field]);
                }
            }
        }

        if ($model->loaded()) {
            if (!$data) {
                if ($model->debug) {
                    echo '<font style="color: blue">db.'.$model->table.' is not dirty</font>';
                }

                return $model->id;
            }
            if ($model->debug) {
                echo '<font style="color: blue">db.'.$model->table.'.update({_id: '.
                    (new MongoID($model->id)).'},{"$set":'.json_encode($data).'})</font>';
            }
            $db = $this->_get($model, 'db')
                ->update(array($model->id_field => new MongoID($model->id)), array('$set' => $data));

            return $model->id;
        }

        if ($model->debug) {
            echo '<font style="color: blue">db.'.$model->table.'.save('.json_encode($data).')</font>';
        }
        $db = $this->_get($model, 'db')->save($data);
        $model->id = (string) $data[$model->id_field] ?: null;
        $model->data = $data;  // will grab defaults here
        if ($model->debug) {
            echo '<font style="color: blue">='.$model->id.'</font><br/>';
        }
        $model->dirty = array();

        return $model->id;
    }
    public function tryLoad($model, $id)
    {
        $this->tryLoadBy($model, $model->id_field, new MongoID($id)); // TODO thow exception
    }
    public function load($model, $id)
    {
        $this->tryLoadBy($model, $model->id_field, new MongoID($id));
        if (!$model->loaded()) {
            throw $this->exception('Record not found')
            ->addMoreInfo('id', $id);
        }
    }
    public function getBy($model, $field, $cond = undefined, $value = undefined)
    {
        $condition_freeze = $model->_table[$this->short_name]['conditions'];
        $data_freeze = $model->data;
        $id_freeze = $model->id;

        $this->addCondition($model, $field, $cond, $value);

        $this->loadAny($model);

        $result = $model->data;

        $model->_table[$this->short_name]['conditions'] = $condition_freeze;
        $model->data = $data_freeze;
        $model->id = $id_freeze;

        return $result;
    }
    public function tryLoadBy($model, $field, $cond = undefined, $value = undefined)
    {
        $condition_freeze = $model->_table[$this->short_name]['conditions'];

        $this->addCondition($model, $field, $cond, $value);

        $this->tryLoadAny($model);

        $model->_table[$this->short_name]['conditions'] = $condition_freeze;

        return $model->id;
    }
    public function tryLoadAny($model)
    {
        if ($model->debug) {
            echo '<font style="color: blue">db.'.$model->table.'.findOne('.
            json_encode($model->_table[$this->short_name]['conditions']).')</font><br/>';
        }
        $model->data = $this->_get($model, 'db')->findOne(
            $model->_table[$this->short_name]['conditions']
        );
        $model->id = (string) $model->data[$model->id_field] ?: null;

        return $model->id;
    }
    public function loadAny($model)
    {
        $this->tryLoadAny($model);
        if (!$model->loaded()) {
            throw $this->exception('No records for this model');
        }

        return $model->id;
    }
    public function loadBy($model, $field, $cond = undefined, $value = undefined)
    {
        $this->tryLoadBy($model, $field, $cond, $value);
        if (!$model->loaded()) {
            throw $this->exception('No records matching criteria');
        }

        return $model->id;
    }
    public function delete($model, $id)
    {
        $id = new MongoID($id);
        if ($model->debug) {
            echo '<font style="color: blue">db.'.$model->table.'.remove('.
            json_encode(array($model->id_field => $id)).',{justOne:true})</font><br/>';
        }
        $model->data = $this->_get($model, 'db')->remove(
            array($model->id_field => $id),
            array('justOne' => true)
        );
        $model->unload();

        return $this;
    }
    public function deleteAll($model)
    {
        if ($model->debug) {
            echo '<font style="color: blue">db.'.$model->table.'.remove('.
            json_encode($model->_table[$this->short_name]['conditions']).')</font><br/>';
        }

        $model->data = $this->_get($model, 'db')->remove(
            $model->_table[$this->short_name]['conditions']
        );
        $model->unload();

        return $this;
    }
    public function getRows($model)
    {
    }
    public function setOrder($model, $field, $desc = false)
    {
        $this->_set($model, 'order', array($field => $desc ? -1 : 1));
        // TODO: allow setting order multiple times
        // TODO: extend syntax to be compatible with SQL_Model
    }
    public function setLimit($model, $count, $offset = 0)
    {
        $this->_set($model, 'limit', array($count, $offset));
    }
    public function selectQuery($model)
    {
        if ($model->debug) {
            echo '<font style="color: blue">db.'.$model->table.'.find('.
                json_encode($model->_table[$this->short_name]['conditions']).')</font>';
        }
        $c = $this->_get($model, 'db')->find(
            $model->_table[$this->short_name]['conditions']
        );

        // sort
        if ($s = $this->_get($model, 'order')) {
            if ($model->debug) {
                echo '<font style="color: blue">.sort('.json_encode($s).')</font>';
            }
            $c->sort($s);
        }
        $this->_set($model, 'cur', $c);

        // skip
        if ($l = $this->_get($model, 'limit')) {
            list($count, $skip) = $l;
            if ($skip) {
                if ($model->debug) {
                    echo '<font style="color: blue">.skip('.$skip.')</font>';
                }
                $c->skip($skip);
            }
            if ($count) {
                if ($model->debug) {
                    echo '<font style="color: blue">.limit('.$count.')</font>';
                }
                $c->limit($count);
            }
        }

        return $c;
    }
    public function count($model, $alias = null)
    {
        return $this->selectQuery($model)->count();
    }
    public function rewind($model)
    {
        $c = $this->selectQuery($model);

        $model->data = $c->getNext();
        $model->id = (string) $model->data[$model->id_field] ?: null;

        return $model->data;
    }
    public function next($model)
    {
        $c = $this->_get($model, 'cur');
        $model->data = $c->getNext();
        $model->id = (string) $model->data[$model->id_field] ?: null;

        return $model->data;
    }

    public function addCondition($model, $field, $value)
    {
        if ($model->_table[$this->short_name]['conditions'][$field]) {
            throw $this->exception('Multiple conditions on same field not supported yet');
        }
        if ($f = $model->hasElement($field)) {
            if (!is_array($value)) {
                if ($f->type() == 'boolean' && is_bool($value)) {
                    $value = (bool) $value;
                }
                if ($f->type() == 'int') {
                    $value = (int) $value;
                }
                if ($f->type() == 'money' || $f->type() == 'float') {
                    $value = (float) $value;
                }

                if (($f->type() == 'reference_id' && $value && !is_array($value))
                    || $field == $model->id_field
                ) {
                    $value = new MongoID($value);
                }
                $f->defaultValue($value)->system(true);
            }
            // TODO: properly convert to Mongo presentation
        } else {
            if ($field[0] != '$' && strpos($field, '.') === false) {
                throw $this->exception('Condition on undefined field. Does not '.
                    'look like expression either')
                    ->addMoreInfo('model', $model)
                    ->addMoreInfo('field', $field);
            }
        }
        $model->_table[$this->short_name]['conditions'][$field] = $value;
    }
}
