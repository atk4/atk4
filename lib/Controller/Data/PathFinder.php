<?php
/**
 * Undocumented.
*/
class Controller_Data_PathFinder extends Controller_Data
{
    public $path_prefix = '';

    public function save($model, $id, $data)
    {
        throw $this->exception('Unable to save into pathfinder');
    }
    public function delete($model, $id)
    {
        throw $this->exception('Unable to save into pathfinder');
    }
    public function loadById($model, $id)
    {
        try {
            $model->data = $this->app->pathfinder->locate($model->_table[$this->short_name], $id, 'array');
        } catch (Exception_PathFinder $e) {
            throw $this->exception('Requested file not found', 'NotFound')
                ->by($e);
        }

        if (!$model->data) {
            return;
        }

        $model->id = $id;

        // If location field is not defined, get rid unnecessary reference
        if (!$model->hasElement('location')) {
            unset($model->data['location']);
        }

        // only load contents if field is defined
        if ($model->hasElement('data') && $model->data['path']) {
            $model->data['data'] = file_get_contents($model->data['path']);
        }
    }
    // TODO: testing
    public function prefetchAll($model)
    {
        $d = $this->d($model);
        $dirs = $this->app->pathfinder->search($d[0], $d['path_prefix'], 'path');
        $colls = array();

        foreach ($dirs as $dir) {

            // folder will contain collections
            $dd = dir($dir);
            while (false !== ($file = $dd->read())) {

                // skip current folder and hidden files
                if ($file[0] == '.') {
                    continue;
                }

                // skip folders in general
                if (is_dir($dir.'/'.$file)) {
                    continue;
                }

                // do we strip extensios?
                if ($d['strip_extension']) {
                    // remove any extension
                    $basefile = pathinfo($file, PATHINFO_FILENAME);
                    $ext = pathinfo($file, PATHINFO_EXTENSION);

                    if ($d['strip_extension'] !== true) {
                        if ($ext !== $d['strip_extension']) {
                            continue;
                        }
                    }
                    $file = $basefile;
                }
                $colls[] = array(
                    'base_path' => $dir.'/'.$file,
                    'name' => $file,
                    'id' => $file,
                );
            }
        }

        return $colls;
    }
}
