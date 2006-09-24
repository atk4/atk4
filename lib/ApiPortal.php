<?
/*
 * ApiPortal extends ApiWeb and is designed to provide core operations with
 * ApiPortal Objects and ApiPortal Relations (e.g. loading, saving, creating, deleting).
 *
 * ApiPortal works with it's own type of Classes. Those must be based on ap_Obj,
 * those should have ap_ prefix and those can participate in A-Portal object structure
 *
 * ApiPortal is developed an handled by:
 *  jancha@adevel.com
 *  romans@adevel.com
 */

class ApiPortal extends ApiWeb {
    /*
     * This class is a 3rd implementation of a-portal concept.
     *
     */
    private function loadObjFromArray($arr){
        /*
         * This function loads Obj by supplied array of data. All we
         * really need to know about object when we are creating it is
         * it's ID and Type. Based on this data we will be able to pull
         * additional details if necessary. If name was specified, it
         * will also be supplied to the object. If not - name will be
         * just loaded on demand later.
         *
         * $t -> container object
         */
        $last = null;
        $result = array();
        foreach ($arr as $row){
            if(!isset($row['type'])){
                // Perhaps id is specified
                if(!isset($row['id']))throw new ap_Exception('Must specify either type (for new objects) or id (if you want to load type)');

                $row['type']=$this->api->db->dsql()
                    ->table('obj')
                    ->field('type')
                    ->where('id',$row['id'])
                    ->getOne();

                // TODO: it's inefficient to loop this. Rather should do one select.
            }
            $class = 'ap_'.$row['type'];
            $last = new $class;

            $last->owner=$this;
            $last->api=$this;
            $last->name=($row['id']?'Object #'.$row['id']:null);
            $last->short_name=($row['id']?'obj_'.$row['id']:null);

            foreach($row as $key=>$val){
                switch($key){
                    case'id':$last->id=$val;break;
                    case'name':$last->name=$val;break;
                    default:
                               $last->data[$key]=$val;
            }
            $result[] = $last;
        }
        if (count($result) == 1){
            return $last;
        } else {
            return $result; //array of child object pointers
        }
    }
    public function genericLoadObj($dq){
        /*
         * This function creates object as a child of current object.
         * Normally you should call loadObj like this:
         *
         *  $this->api->loadObj($id);
         *
         * However if you want to load multiple objects or if you want
         * to specify different field to use for loading, you can use
         * this funciton.
         *
         * $dq may be initialized with "where", "join" and "limit", however
         * main table will be "obj". Here is sample:
         *
         *  $o=$api->genericLoadObj(
         *      $api->db->dsql()
         *          ->join('account','account.id=obj.id')
         *          ->where('account.name',$_GET['login']);
         *
         * This function will object ID or array of objects, if several matches
         * your criteria
         */
        $dq
            ->table('obj')
            ->field('obj.id id')
            ->field('obj.type type')
            ->field('obj.name name');

        if ($limit){
            $dq->limit($limit);
        }
        $arr = $dq->do_getAllHash();
        return $this->loadObjFromArray($arr);
    }
    function deleteObj($dq){
        $obj_pool = $this->genericLoadObj($dq);
        foreach($obj_pool as $obj){
            $obj->destroy();
        }
    }
    function childDQ($dq,$id,$types=null){
        /*
         * This function modifies $dq object, so that only children of specified $id
         * would get selected. New dq object is returned.
         */

        if(!$dq)$dq=$this->api->db->dsql();
        $types=$this->api->convertTypes($types);
        $dq
            ->join('rel autorel',"autorel.child=obj.id")
            ->where('autorel.parent',$this->id);

        if($types)$dq->where("autorel.type in (".$types.")");
        return $dq;
    }
    function parentDQ($dq,$id,$types=null){
        /*
         * see child DQ, same thing but works for parents
         */

        if(!$dq)$dq=$this->api->db->dsql();
        $types=$this->api->convertTypes($types);
        $dq
            ->join('rel autorel',"autorel.parent=obj.id")
            ->where('autorel.child',$this->id);

        if($types)$dq->where("autorel.type in (".$types.")");
        return $dq;
    }
    function deleteObjTree($root,$types=null){
        // This function deletes all the objects linked as childs for root object.
        // If $types is specified (array of type names or comma separated), then only
        // specified relation types will be followed.
        //
        // $root can be either ID or object itself

        $to_delete = $root;
        if(!is_object($root))$root=$this->loadObj($root);

        while(!empty($to_detele)){

        }

    }
    public function loadObj($id){
        /*
         * Loads object into memory based on $id only
         */
        if (!(int)$id) throw new APortalException("You must specify ID to loadObj. Specified: '$id'");
        return $this->genericLoadObj(
                $this->api->db->dsql()
                ->where('obj.id', $id)
                );
    }
    public function createObj($type, $name = "untitled", $dq=null){
        /*
         * This function will add an object to the main object table, will load
         * it and will call create method on the particular object
         */
        if(!$dq)$dq=$this->db->dsql();

        $obj_data = array(
                'type'=>$type,
                'name'=>$name
                );

        $obj=$this->loadObjFromArray($obj_data);
        $obj->_create();

        return $obj;    
    }
    /*
     * the following functions were moved to ap_Obj by romans
     *
    public function delObj($obj, $deep = null){
        if ($deep){
            $childs = $this->loadChilds($obj);
            if ($childs){
                if (is_array($childs)){  
                    foreach ($childs as $childPtr){
                        $this->delObj($childPtr, $deep);
                    }
                } else {
                    $this->delObj($childs, $deep);
                }
            }
        }
        
        $obj->切腹(); // experimental wrapper for $obj->harakiri();
        $this->db->query(
            $this->db->dsql()
            ->table('obj')
            ->where('obj.id', $obj->id)
            ->delete()
        );
        $this->delRel($obj, null, null);
        $this->delRel(null, $obj, null);
        /*
        * Remove $obj from object tree
        *
        * Proposal - have something like drop() in the AbstractObject
        * reverse for add()
        *
        * Following is sort of a hack
        
        unset($this->elements[$obj->short_name]);
        return true;
    }
    public function addChild($parent, $child, $type, $aux = null){
        /*
        * to add child for a parent, auto check for dublicate entries
        $dq = $this->db->dsql()
            ->table('rel')
            ->field('*')
            ->where('parent', $parent->id)
            ->where('child', $child->id)
            ->where('type', $type);
        if ($dq->do_getOne()){
            /*
            * exists already - what to do? update or what?
            return null;
        } else {
            $dq->set(
                array(
                    'parent' => $parent->id,
                    'child' => $child->id,
                    'type' => $type,
                    'aux' => $aux
                )
            )->do_insert();
            return true;
        }

    }
    */
    function convertTypes($type=null){
        if(!$type)return $type;
        if(is_array($type))$type=join(',',$type);
        $type=addslashes($type);
        $type=str_replace(',','","');
    }

    function deleteRelation($parent = null, $child = null, $type=null, $dq=null){
        if(!$parent && !$child)throw new ap_Exception("Either parent or child must be specified for deleteRelation");

        if(!$dq)$dq=$this->db->dsql();
        $dq->table('rel');

        if(is_object($parent))$parent=$parent->id;
        if(is_object($child))$child=$child->id;
        $type=$this->convertTypes();

        if (isset($parent)){
            $dq->where('parent', $parent);
        }
        if (isset($child)){
            $dq->where('child', $child);
        }
        if (isset($type)){
            $dq->where('type in ('.$type.')');
        }
        return $db->do_delete();
    }
    /*
    public function loadChilds($parent, $dq=null){
        /*
         * This function will load all childs of a 
        * this will load all childs for given parent with necessary rel type
        if(is_object($parent))$parent=$parent->id;
        $dq=$this->db->dsql()
            ->table('obj')
            ->field(array('rel.aux', 'rel.type'))
            ->join('rel','rel.child=obj.id')
            ->where('rel.parent',$parent)
            ;
        if(!empty($rel_type)){
            $dq->where('rel.type',$rel_type);
        }
        return $this->genericLoadObj($dq, 0);
    }
    */
}

/*
 * Conclusion...
 *  - create generic object class AbstractObject. It should be inherited
 *    from AbstractModel.
 *  - libraries for ApiPortal should be located in subdirectory aportal. You
 *    need to add include path. See lib/Namespace.php:init(). It
 *    gives you example how to include new directory into include path.
 *    Only exception is the class ApiPortal itself, which may stay outside.
 *  - Class (or object type) should have information about field type.
 *    do not add many types yet, just a few. Create them as array, NOT
 *    as sub-objects. This array will be used when form is displayed,
 *    or when we create table (in the future).
 *    
 *
 *  Stick to V1.0 of VPBX , as we don't need a lot of functional features
 *  at this point.
 *
 * Deadline for this project is in 2 weeks - friday, September 8. I will have
 * to demonstrate project to client on monday. weekend (9,10 sep) is for
 * reserve.
 *
 */
?>
