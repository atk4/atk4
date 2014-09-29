<?php
/**
 * Cache Data Controller implements a transparent cache implementation
 * which can be nicely configured for your needs.
 *
 * This cache will attempt to load records from available caches when
 * you know the ID. The request to list all available records, however,
 * will be sent directly to the primary source.
 *
 * After data is loaded from primary controller, it's saved into
 * all caches. If data is saved, it's only sent into primary controller,
 * this is done because normally Model will execute load() after save()
 * anyway.
 */
class Controller_Data_Cache extends Controller_Data {

    public $primary = null;
    public $caches = array();

    /**
     * Set a primary controller to be used. Without adding addCache
     * all the requests will simply be passed to this controller.
     */
    function setPrimary($c){
        $this->primary=$c;
        return $this;
    }

    /**
     * Add one more cache, with a lower priority than teh caches added
     * previously.
     * @param [type] $c [description]
     */
    function addCache($c){
        $this->caches[] = $c;
        return $this;
    }

    function save($model, $id = null){
        $id = $this->primary->save($model);

        foreach($this->caches as $c){
            // TODO: maybe ignore some exceptions here?
            $c->save($model, $id);
        }

        return $model->id = $id; // just to be sure that we have our original ID
    }
    function delete($model, $id) {
        $this->primary->delete($model, $id);

        foreach($this->caches as $c){
            $c->delete($model, $id);
        }

    }

    function loadById($model, $id){
        foreach($this->caches as $c){
            $c->loadById($model, $id);
            if($model->loaded()) return;
        }

        $this->primary->load($model,$id);

        // save into caches
        foreach($this->caches as $c){
            $c->loadById($model, $id);
            if($model->loaded()) return;
        }

    }

    // Those methods may not be available in a model
    // abstract function loadByConditions($model);
    // abstract function deleteAll($model);

    /** Create a new cursor and load model with the first entry. Returns cursor */
    abstract function prefetchAll($model);

    function save()

    function applyStrategy($label, $fx, $args = array()){
        $strategy = $fx."_strategy";
        $ctls = $this->getList($this->$strategy);

        foreach($ctls as $c){
            $ret = call_user_func_array($c,)


        }


    }

}
