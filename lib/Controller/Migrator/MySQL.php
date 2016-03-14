<?php
/**
 * Implements a generic migration controller. This controller will find
 * folder containing "dbupdates", read all the .sql files from there and
 * sequentially apply them on top of your database::.
 *
 * $app->add('Controller_Migrator_MySQL');
 *
 * or with a custom database::
 *
 * $app->add('Controller_Migrator_MySQL', ['db'=> $dbcon]);
 *
 * Database migration statistics is stored in _db_update table which is
 * created if necessary.
 */
class Controller_Migrator_MySQL extends AbstractController
{
    public $db = null;

    const DOC = 'controller/migrator';

    public function init()
    {
        parent::init();

        if (is_null($this->db)) {
            $this->db = $this->app->db;
        }
        if (!$this->db) {
            $this->db = $this->app->dbConnect();
        }

        if ($this->db->type != 'mysql') {
            throw $this->exception('Migrator can only be used with MySQL database')
            ->addMoreInfo('type', $this->db->type);
        }
    }

    /**
     * Create necessary table in SQL for tracking progress.
     */
    public function create()
    {
        $this->db->dsql()->expr(
            'create table if not exists `[table]` ('.
                'id int not null primary key auto_increment, '.
                'name varchar(255), '.
                'unique key(name), '.
                'status enum("ok","fail")'.
            ')',
            ['table' => '_db_update']
        )->execute();
    }

    /**
     * Find migrations and execute them in order.
     *
     * .. warning:: Use ";" semicolon between full statements. If you leave empty statement
     * between two semilocons MySQL ->exec() seems to fail.
     */
    public function migrate()
    {
        // find migrations
        $folders = $this->app->pathfinder->search('dbupdates', '', 'path');
        // todo - sort files in folders
        foreach ($folders as $dir) {
            $files = scandir($dir);
            sort($files);
            foreach ($files as $name) {
                if (strtolower(substr($name, -4)) != '.sql') {
                    continue;
                }
                $q = $this->db->dsql()
                    ->table('_db_update')
                    ->where('name', strtolower($name))
                    ->field('status');
                if ($q->getOne() === 'ok') {
                    continue;
                }
                $migration = file_get_contents($dir.'/'.$name);
                $q->set('name', strtolower($name));
                try {
                    $this->db->dbh->exec($migration);
                    $q->set('status', 'ok')->replace();
                } catch (Exception $e) {
                    $q->set('status', 'fail')->replace();
                    if (!$e instanceof BaseException) {
                        $e = $this->exception()
                            ->addMoreInfo('Original error', $e->getMessage());
                    }
                    throw $e->addMoreInfo('file', $name);
                }
            }
        }

        return $this;
    }

    /**
     * Produces and returns a generic model you can supply to your Grid
     * if you want show migration status.
     */
    public function getStatusModel()
    {
        /** @type SQL_Model $m */
        $m = $this->add('SQL_Model', ['table' => '_db_update']);
        $m->addField('name');
        $m->addField('status')->enum(['ok', 'fail']);
        $m->setSource('SQL');

        return $m;
    }
}
