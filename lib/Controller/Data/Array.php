<?php
/**
 * Undocumented
 */
class Controller_Data_Array extends Controller_Data
{
    public $supportConditions = true;
    public $supportLimit = true;
    public $supportOrder = false;
    public $supportOperators = array(
        '=' => true,
        '>' => true,
        '>=' => true,
        '<=' => true,
        '<' => true,
        '!=' => true,
        'like' => true
    );

    public function setSource($model, $table)
    {
        if (!is_array($table)) {
            throw $this->exception('Wrong type: expected array')
                ->addMoreInfo('source', $table);
        }

        if (!$model->hasMethod('push')) {
            $model->addMethod('push', $this);
        }

        // convert single dimension arrays
        reset($table);
        list(, $firstrow) = each($table);

        if (!is_array($firstrow)) {
            // assuming that this array needs to be converted
            foreach ($table as $key => &$name) {
                $name = array($model->id_field => $key, $model->title_field => $name);
            }
            return parent::setSource($model, $table);
        }

        $data = array();
        foreach ($table as $key => $row) {
            $id = isset($row[$model->id_field]) ? $row[$model->id_field] : $key;
            $data[$id] = $row;
        }

        return parent::setSource($model, $data);
    }

    public function save($model, $id, $data)
    {
        $oldId = $id;
        if (is_null($id)) { // insert
            $newId = $data[$model->id_field] ?: $this->generateNewId($model);
            if (isset($model->_table[$this->short_name][$newId])) {
                throw $this->exception('This id is already used. Load the model before')
                    ->addMoreInfo('id', $data[$model->id_field]);
            }
        } else { // update
            //unset($model->_table[$this->short_name][$oldId]);
            $newId = $id; //$data[$model->id_field];
            $data = array_merge($model->_table[$this->short_name][$newId], $data);
        }
        $data[$model->id_field] = $newId;
        $model->_table[$this->short_name][$newId] = $data;
        $model->data = $data;

        return $newId;
    }

    public function delete($model, $id)
    {
        unset($model->_table[$this->short_name][$id]);
    }

    public function loadById($model, $id)
    {
        if (isset($model->_table[$this->short_name][$id])) {
            $model->id = $id;
            $model->data = $model->_table[$this->short_name][$id];

            return true;
        }

        return false;
    }

    public function loadByConditions($model)
    {
        $ids = $this->getIdsFromConditions($model->_table[$this->short_name], $model->conditions);
        if (!empty($ids)) {
            $id = array_pop($ids);
            $model->id = $id;
            $model->data = $model->_table[$this->short_name][$id];
        }
    }

    public function deleteAll($model)
    {
        $ids = $this->getIdsFromConditions($model->_table[$this->short_name], $model->conditions);
        foreach ($ids as $id) {
            $this->delete($model, $id);
        }
    }

    public function prefetchAll($model)
    {
        // TODO: miss ordering...
        return $this->getIdsFromConditions($model->_table[$this->short_name], $model->conditions, $model->limit);
    }

    public function loadCurrent($model, &$cursor)
    {
        if (!$this->loadByID($model, array_shift($cursor))) {
            $model->id = null;
            $model->data = array();
        }
    }

    // resolve all conditions
    public function getIdsFromConditions($rows, $conditions, $limit = null)
    {
        $withLimit = !is_null($limit) && (is_array($limit) && !is_null($limit[0]));
        if ($withLimit) {
            $max = is_null($limit[1]) ? $limit[0] : ($limit[0] + $limit[1]);
        }

        $ids = array();
        foreach ($rows as $id => $row) {
            if ($id === '__ids__') {
                continue;
            }

            $valid = true;
            foreach ($conditions as $c) {
                if (!$this->isValid($row, $c)) {
                    $valid = false;
                    break;
                }
            }
            if ($valid === true) {
                $ids[] = $id;
                if ($withLimit && isset($max) && count($ids) > $max) {
                    break;
                }
            }
        }
        if ($withLimit) {
            if ($limit[1] === null) {
                $ids = array_slice($ids, 0, $limit[0]);
            } else {
                $ids = array_slice($ids, $limit[0], $limit[1]);
            }
        }

        return $ids;
    }

    public function isValid($row, $conditions)
    {
        $value = $row[$conditions[0]];
        $op = $conditions[1];
        $expected = $conditions[2];

        switch ($op) {
            case '=':
                return $value === $expected;
            case '>':
                return $value > $expected;
            case '>=':
                return $value >= $expected;
            case '<=':
                return $value <= $expected;
            case '<':
                return $value < $expected;
            case '!=':
                return $value != $expected;
            case 'like':
                $pattern = '/^' . str_replace('%', '.*', preg_quote($expected, '/')) . '$/i';
                return (bool) preg_match($pattern, $value);
            default:
                throw $this->exception('Unknown operator')
                    ->addMoreInfo('operator', $op);
        }
    }

    public function generateNewId($model)
    {
        $ids = array_keys($model->_table[$this->short_name]);

        $type = $model->getElement($model->id_field)->type();
        if (in_array($type, array('int', 'integer'))) {
            return count($ids) === 0 ? 1 : (max($ids) + 1);
        } elseif (in_array($type, array('str', 'string'))) {
            return uniqid();
        } else {
            throw $this->exception('Unknown id type')
                ->addMoreInfo('type', $type)
                ->addMoreInfo('support', array('int', 'str'));
        }
    }
    public function count($model)
    {
        return count($model->_table[$this->short_name]);
    }
    public function push($model, $row)
    {
        $model->_table[$this->short_name][] = $row;
    }
}
