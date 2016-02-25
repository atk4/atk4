<?php
/**
 * This is class for ordering elements.
 */
class Order extends AbstractController
{
    public $rules = array();
    public $array = null;

    public function init()
    {
        parent::init();
        $this->useArray($this->owner->elements);
    }
    public function useArray(&$array)
    {
        $this->array = &$array;

        return $this;
    }
    public function move($name, $where, $relative = null)
    {
        if (is_object($name)) {
            $name = $name->short_name;
        }
        if (is_object($relative)) {
            $relative = $relative->short_name;
        }
        $this->rules[] = array($name, $where, $relative);

        return $this;
    }
    public function now()
    {
        foreach ($this->rules as $rule) {
            list($name, $where, $relative) = $rule;

            // check if element exists
            if (!isset($this->array[$name])) {
                throw $this->exception('Element does not exist when trying to move it')
                    ->addMoreInfo('element', $name)
                    ->addMoreInfo('move', $where)
                    ->addMoreInfo('relative', $relative);
            }

            $v = $this->array[$name];
            unset($this->array[$name]);

            switch ($where) {
                case 'first':
                    // moving element to be a first child
                    $this->array = array($name => $v) + $this->array;
                    break;
                case 'last':
                    $this->array = $this->array + array($name => $v);
                    break;
                case 'after':
                    $this->array = array_reverse($this->array);
                    // no-break
                case 'before':
                    $tmp = array();
                    foreach ($this->array as $key => $value) {
                        if ($key === $relative || (is_array($relative) && in_array($key, $relative))) {
                            $tmp[$name] = $v;
                            $name = null;
                        }
                        $tmp[$key] = $value;
                    }
                    $this->array = $tmp;
                    if ($name) {
                        throw $this->exception('Relative element not found while moving')
                        ->addMoreInfo('element', $name)
                            ->addMoreInfo('move', $where)
                            ->addMoreInfo('relative', $relative);
                    }

                    if ($where == 'after') {
                        $this->array = array_reverse($this->array);
                    }
                    break;
                case 'middle':
                    array_splice($this->array, floor(count($this->array) / 2), 0, array($name => $v));
                    break;

                case 'middleof':  // in the middle of objects of specified class
                    $cnt = $cnt2 = $mid = 0;
                    foreach ($this->array as $el) {
                        if ($el instanceof $relative) {
                            ++$cnt;
                        }
                    }
                    $cnt = ceil($cnt / 2);
                    foreach ($this->array as $el) {
                        if ($el instanceof $relative) {
                            --$cnt;
                        }

                        ++$mid;
                        if (!$cnt) {
                            break;
                        }
                    }
                    array_splice($this->array, $mid, 0, array($name => $v));
                    break;

            }
        }
    }
    public function onHook($object, $hook)
    {
        $object->addHook($hook, array($this, 'now'));

        return $this;
    }
    public function later()
    {
        $this->app->addHook('beforeRender', array($this, 'now'));

        return $this;
    }
}
