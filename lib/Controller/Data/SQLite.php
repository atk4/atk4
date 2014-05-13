<?php

class Controller_Data_SQLite extends Controller_Data_SQL {

    function setSource($model, $data) {
        // do nothing

        if (!$model->table) {
            throw $this->exception('$table property must be defined');
        }

        if ($model->app->db && $model->app->db=='sqlite'){
            $model->dsql = $model->app->db->dsql();
        } elseif ($model->app->db_sqlite) {
            $model->dsql = $model->app->db_sqlite->dsql();
        } else {
            $model->app->db_sqlite=$this->app->dbConnect('sqlite:data/main.db');
            $model->dsql = $model->app->db_sqlite;
        };
        $model->dsql->id_field = $model->id_field;
        $model->dsql->table($model->table);
        return parent::setSource($model,$data);
    }

    function dsql($model)
    {

        return $model->dsql;
    }
}
