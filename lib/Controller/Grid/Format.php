<?php
/**
 * Undocumented
 */
abstract class Controller_Grid_Format extends AbstractController
{
    /**
     * Initialize field.
     *
     * Note: $this->owner is Grid object
     *
     * @param string $name  Field name
     * @param string|array $descr Field title
     */
    public function initField($name, $descr)
    {
    }

    /**
     * Format output of cell in particular row.
     *
     * Note: $this->owner is Grid object
     *
     * @param string $field  Field name
     * @param array  $column Array [type=>string, descr=>string]
     */
    public function formatField($field, $column)
    {
    }
}
