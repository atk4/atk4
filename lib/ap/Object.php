<?
/*
* ApiPortal Abstract Object
* Every ApiPortal Object class should be parent (direct or indirect) of this class to have full featured
* functionality
*
* jancha@adevel.com
* romans@adevel.com
* */
class ap_Object extends AbstractModel {
    var $data=null; //additional object data as associative array
    var $type=null; //field type, set by ApiPortal
    var $last_field=null;
    var $table_name=null;
    var $fields; //object fields
    var $id; //the id of the current object
    function init(){
        parent::init();

        // Nothing to initialize for generic object
        
        /* No! It must have ID field as well as storage Name */

        $this->table_name = get_class($this);
    }
    function _create($dq=null){
        /*
         * create object in database, without saving actual data. Called by Api->createObj
         */
        if($this->id)throw new ap_Exception("Object already have ID, cannot create",$this);
        
        if(!$dq)$dq = $this->api->db->dsql();
        $dq->set('type',$this->type);
        $dq->set('name',$this->name);
        /*
         * won't work if $dq with table been set is passed
         * so, let's set it manualy
         * */
        $dq->table("obj");
        /* */
        $this->id = $dq->do_insert();
        
        /* Do insert in the suplementary table */
        $dq = $this->api->db->dsql()->table($this->table_name);
        $dq->set("id", $this->id); //agreed on hard-coded solution
        foreach ($this->fields as $field => $properties){
            if ($tmp = $properties["default"]){
                $dq->set($field, $tmp);
            }
        }
        try {
            $dq->do_insert();
        } catch (SQLException $e){
            $this->create_table();
            $dq->do_insert();
        }
        $this->name='Object #'.$this->name;
        $this->short_name='obj_'.$this->name;
        return $this;
    }
    function save($dq=null){
        /*
         * Saves this object. Do this after you modify fields
         */
        if(!$this->id)throw new ap_Exception("Cannot save object without ID. Call ->create() first",$this);
        if(isset($this->data['id']))throw new ap_Exception("You may not use 'id' as a field. It's for system only",$this);
        if(!$dq) $dq = $this->api->db->dsql();
        if(!$this->data){
            return $this;
        }
        try {
            $id=$dq
                ->table($this->table_name)
                ->set($this->data)
                ->do_update(); //faster and better than replace
        } catch (SQLException $e){
            // let's try to create table
            $this->create_table();
            try {
                $dq->do_insert();
            } catch (SQLException $e){
                echo "could not insert? " . $dq->insert();
            }
        }
        return $this;
    }
    function load($dq=null){
        if(!$this->id)throw new ap_Exception("Cannot load object without ID. Use ".'$'."api->loadObj()",$this);
        if(!$dq)$dq = $this->api->db->dsql();
        // TODO - should we use hash_filter with $this->fields here?????
        $this->data = $dq
            ->table($this->table_name)
            ->field($this->table_name.'.*')
            ->where('id',$id)
            ->do_getHash();
        unset($this->data['id']);
        return $this;
    }
    function destroy(){
        /*
         * This is a complicated function, which will destroy object from the database,
         * from parent in memory and also will drop all relations, which would be invalid
         * anyway.
         */
        if(!$this->id)throw new ap_Exception("Can't destroy non-existang object",$this);
            
        $this->hook('destroy'); // can be used for access control or cache deletion

        // STEP1 - destroy relations
        $this->deleteParentRelation();
        $this->deleteChildRelation();

        // STEP2 - destroy supplimentary table entry
        $this->api->db->dsql()
            ->table($this->table_name)
            ->where('id',$this->id)
            ->do_delete();

        // STEP3 - delete obj table entry
        $this->api->db->dsql()
            ->table('obj')
            ->where('id',$this->id)
            ->do_delete();
    }
    function deleteChildRelation($types=null,$dq=null){
        $type = null;
        if (is_array($types)){
            $type = implode(",", $types);
        } else if (isset($types)){
            $type = $types;
        }
        $this->api->deleteRelation($this,null,$type,$dq);
    }
    function deleteParentRelation($types=null,$dq=null){
        $type = null;
        if (is_array($types)){
            $type = implode(",", $types);
        } else if (isset($types)){
            $type = $types;
        }
        $this->api->deleteRelation(null,$this,$type,$dq);
    }
    function deleteChildObj($type=null,$dq=null){
        /*
         * This function deletes child objects of this object. Type names
         * can be specified as array or separated by comma. If $types is not specified
         * all parent objects will be deleted
         */
        return $this->api->deleteObjects($api->childDQ($dq,$this->id,$types));
    }
    function deleteParentObj($types=null,$dq=null){
        /*
         * This function is similar to deleteChildObjects, but i can't imagine a
         * situation where you would need to delete parents. This function is created
         * so you never use it. It's a bad style! You should think about changing your
         * database structure.
         */
        return $this->api->deleteObjects($api->parentDQ($dq,$this->id,$types));
    }
    function loadChildObj($types=null,$dq=null){
        $obj_pool=$this->api->genericLoadObj($api->childDQ($dq,$this->id,$types));
        foreach($obj_pool as $obj){
            $this->add($obj);
        }
        return $obj_pool;
    }
    function loadParentObj($types=null,$dq=null){
        return $this->api->genericLoadObj($api->parentDQ($dq,$this->id,$types));
    }
    function loadObjTree($types=null,$dq=null){
        // Similar to addChildObj, but will recursively load all object hierarchy.
        $obj_pool = $this->loadChildObj($types,$dq);
        foreach($obj_pool as $obj){
            $this->add($obj);
            $obj->loadObjTree($types,$dq);
        }
    }
    function addObj($type,$name,$dq=null){
        /* what is this? */
    }
    function addField($name, $type){
        if (!$this->fields[$name]){
            $this->fields[$name]["type"] = $type;
        }
        $this->last_field=$name;
        return $this;
    }
    function setFieldProperty($name, $property, $value){
        if (!$this->fields[$name]){
            return null;
        }
        $this->fields[$name][$property] = $value;
        return $this;
    }
    function setFieldDefault($name, $value){
        $this->setFieldProperty($name, "default", $value);
        return $this;
    }
    function create_table(){
        $sql_type = array(
            "int" => "int(11)",
            "line" => "varchar(255)",
            "text" => "blob"
        );
        if (!$this->api->db->getOne("show tables like '" . $this->table_name . "'")){
            /*
            * create container first
            * */
            $query = "create table " . $this->table_name . " (";
            $query .= "id int not null";
            foreach ($this->fields as $field => $properties){
                $query .= ", $field " . $sql_type[$properties["type"]];
                if ($tmp = $properties["key"]){
                    $query .= " $tmp key not null";
                }
                if ($tmp = $properties["default"]){
                    $query .= " default '" . $tmp . "'";
                }
            }
            $query .= ")";
            $this->api->db->query($query);
        } else {
            /* check if all fields are there */
            $fields = $this->api->db->getAll("show fields from " . $this->table_name);
            foreach ($fields as $field){
                if ($field[0] != "id"){
                    $existing_fields[$field["0"]] = $field;
                }
            }
            foreach ($this->fields as $field => $properties){
                $default = $properties["default"];
                if (!$existing_fields[$field]){
                    /* new field, add it */
                    echo "Adding new field\n";
                    $this->api->db->getOne("alter table " . $this->table_name . " add " . $field . " " . $sql_type[$properties["type"]] . ($default?" default " . $default:""));
                } else if ($existing_fields[$field][1] != $sql_type[$properties["type"]]){
                    /* change type */
                    echo "Changing type for $field\n";
                    echo $existing_fields[$field][1] . " vs " . $properties["type"] . "\n";
                    $this->api->db->getOne("alter table " . $this->table_name . " change " . $field . " " . $field . " " . $sql_type[$properties["type"]] . ($default?" default " . $default:""));
                    unset($existing_fields[$field]);
                } else {
                    unset($existing_fields[$field]);
                }
            }
            if ($existing_fields){
                foreach ($existing_fields as $field => $field_data){
                    echo "Dropping non-existing field\n";
                    $this->api->db->getOne("alter table " . $this->table_name . " drop $field");
                }
            }
        }
    }
    function set($field_or_array,$value=undefined){
        // We use undefined, because 2nd argument of "null" is meaningfull
        if($value===undefined){
            if(is_array($field_or_array)){
                foreach($field_or_array as $key=>$val){
                    $this->set($key,$val);
                }
                return $this;
            }else{
                $value=$field_or_array;
                $field_or_array=$this->last_field;
            }
        }

        // TODO: handle external field checks here.
        // TODO: check if field exists
        $this->data[$field_or_array]=$value;

        return $this;
    }
    function get($field){
        return $this->data[$field];
    }
}
