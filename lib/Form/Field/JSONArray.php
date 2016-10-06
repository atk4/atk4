<?php
/**
 * Undocumented.
 */
class Form_Field_JSONArray extends Form_Field_JSON
{
    public function normalize()
    {
        $this->set(json_decode($this->get(), true));
    }
}
