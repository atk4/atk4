<?php

class Controller_Data_SQLite extends Controller_Data_SQL {

    function dsql($model) {
        if (!$model->table) {
            throw $this->exception('$table property must be defined');
        }
        if ($model->dsql) {
            return clone $model->dsql;
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
        return clone $model->dsql;
    }
}
