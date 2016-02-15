<?php
class View_Breadcrumb extends CompleteLister {
    public $max_depth;
    function formatRow() {
        parent::formatRow();

        $page = $this->model['page'];

        if(!$page && $this->max_depth) {
            // by default resort to parent pages
            $tmp=array();
            for($i=0;$i<$this->max_depth;$i++){
                $tmp[]='..';
            }
            $this->max_depth--;

            $page=$this->app->url(join('/',$tmp));
        }



        if($page){
            $this->current_row_html['crumb'] = '<a href="'.$this->app->url($page).'">'.
                htmlspecialchars($this->model['name']).
                '</a>';
        }else{
            $this->current_row_html['crumb'] = htmlspecialchars($this->model['name']);
        }
    }
    function render() {
        $this->max_depth = count(parent::setModel($this->model))-1;
        return parent::render();
    }
    function defaultTemplate() {
        return array('view/breadcrumb');
    }
}
