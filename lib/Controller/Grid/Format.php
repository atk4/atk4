<?php
abstract class Controller_Grid_Format extends AbstractController{
    /**
     * Initialize field
     *
     * @param string $name Field name
     * @param string $descr Field title
     *
     * @return void
     */
    public function initField($name, $descr) {
    }
    
    /**
     * Format output of cell in particular row
     *
     * @param string $field Field name
     * @param array $column Array [type=>string, descr=>string]
     *
     * @return void
     */
    public function formatField($field, $column) {
    }
}
