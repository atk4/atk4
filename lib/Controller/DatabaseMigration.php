<?php
/**
 * Undocumented.
 */
class Controller_DatabaseMigration extends AbstractController
{
    public function executeMigrations()
    {
        // TODO: check in pathfinder
        $dbupdates = $this->app->pathfinder->search('dbupdates');

        $results = array();
        foreach ($dbupdates as $dir) {
            $d = dir($dir);
            while (false !== ($file = $d->read())) {
                if ($file[0] == '.') {
                    continue;
                }
                if (!preg_match('/.*\.sql$/', $file)) {
                    continue;
                }
                //if(file_exists($dir.$file.'.ok'))continue;
                $results[] = $this->executeMigration($dir.$file);
            }
        }

        return $results;
    }
    public function executeMigration($file)
    {
        // TODO:
        // 1. test write permissions
        // 2. execute migration
        // 3. write .ok file
        // 3a. roll-back and write .fail if needed
        // 4. store short result in "result"

        return array('name' => $file, 'result' => 'Not Supported Yet');
    }
}
