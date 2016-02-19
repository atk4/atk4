<?php
/**
 * Undocumented
 */
class Controller_Data_SQLite extends Controller_Data_SQL
{
    public function setSource($model, $data)
    {
        // do nothing

        if (!$model->table) {
            throw $this->exception('$table property must be defined');
        }

        if ($model->app->db && $model->app->db == 'sqlite') {
            $model->dsql = $model->app->db->dsql();
        } elseif (isset($model->app->db_sqlite)) {
            $model->dsql = $model->app->db_sqlite->dsql();
        } else {
            $model->app->db_sqlite = $this->app->dbConnect(array('sqlite:data/main.db', null, null, array(PDO::ATTR_PERSISTENT => false)));
            $model->dsql = $model->app->db_sqlite->dsql();
        };
        $model->dsql->id_field = $model->id_field;
        $model->dsql->table($model->table);

        return parent::setSource($model, $data);
    }

    public function dsql($model)
    {
        return $model->dsql;
    }
}
