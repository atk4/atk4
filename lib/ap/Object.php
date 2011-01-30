<?php
/***********************************************************
   ..

   Reference:
     http://atk4.com/doc/ref

 **ATK4*****************************************************
   This file is part of Agile Toolkit 4 
    http://www.atk4.com/
  
   (c) 2008-2011 Agile Technologies Ireland Limited
   Distributed under Affero General Public License v3
   
   If you are using this file in YOUR web software, you
   must make your make source code for YOUR web software
   public.

   See LICENSE.txt for more information

   You can obtain non-public copy of Agile Toolkit 4 at
    http://www.atk4.com/commercial/ 

 *****************************************************ATK4**/
/*
* ApiPortal Abstract Object
* Every ApiPortal Object class should be parent (direct or indirect) of this class to have full featured
* functionality
*
* jancha@adevel.com
* romans@adevel.com
* */
class ap_Object extends AbstractModel {
	var $id;            //the id of the current object
	var $type=null;     //field type, set by ApiPortal
	var $data=null;     //additional object data as associative array
	var $fields=array();        //object fields

	var $table_name=null;
	var $last_field=null;


	//////////////////////// Core functions /////////////////////
	function init(){
		/*
		 * Redefine this object, call parent then use addField to specify which fields
		 * you are going to use
		 */
		parent::init();
		$this->table_name = get_class($this);
	}
	function create($dq=null){
		/*
		 * create object in database, without saving actual data. Called by Api->createObj
		 *
		 * If you throw some $dq->set on the specified argument, those will be inserted
		 * into ssupplementary table
		 */
		if($this->id)throw new APortalException("Object already have ID, cannot create again",$this);

		// First we are going to create entry in obj table
		$dq_obj = $this->api->db->dsql();
		$dq_obj->set('type',$this->type);
		$dq_obj->set('name',$this->name);
		$dq_obj->table("obj");
		$this->id = $dq_obj->do_insert();

		// Now we are creating entry in supplementary table
		$dq=$this->prepareSave($dq);
		$dq->set('id',$this->id);
		try {
			$id=$dq->do_replace();
		} catch (SQLException $e){
			// Perhaps table does not exist. We will try to create it and repeat query.
			$this->createTable();
			$dq->do_replace();
		}

		$this->name='Object #'.$this->id;
		$this->short_name='obj_'.$this->id;
		return $this;
	}
	function prepareSave($dq=null){
		/*
		 * Prepares dynamic query for saving. You may redeclare this object
		 */
		if(!$this->id)throw new APortalException("Cannot save object without ID. Call ->create() instead",$this);
		if(isset($this->data['id']))throw new APortalException("You may not use 'id' as a field. It's for system use only",$this);
		if(!$dq)$dq=$this->api->db->dsql();

		$dq->where('id',$this->id);

		$dq
			->table($this->table_name)
			->set($this->data);
		return $dq;
	}
	function save($dq=null){
		$dq=$this->prepareSave($dq);
		try {
			$id=$dq->do_update(); //faster and better than replace
		} catch (SQLException $e){
			// let's try to create table
			$this->createTable();

			$dq->set('id',$this->id);
			try {
				$dq->do_replace();
			} catch (SQLException $e){
				throw new Aportalexception("User tried to save non-existing field!", $this);
			}
		}
		return $this;
	}
	function load($dq=null){
		if(!$this->id)throw new APortalException("Cannot load object without ID. Use ".'$'."api->loadObj()",$this);
		if(!$dq)$dq = $this->api->db->dsql();
		$this->data = $dq
			->table($this->table_name)
			->field($this->table_name.'.*')
			->where('id',$this->id)
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
		if(!$this->id)
			throw new APortalException("Can't destroy object does not exist in database",$this);

		$this->hook('destroy'); // can be used for access control or cache deletion

		// STEP1 - destroy relations
		$this->api->deleteRel($this,null);
		$this->api->deleteRel(null,$this);

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

	//////////////////////// Dealing with relations //////////////////
	function addChild($obj_type,$rel_type,$rel_aux=null,$name=null,$dq=null){
		/*
		 * This object creates new object and links it under specified relation type.
		 * Newly created object is returned. Relation type must be specified.
		 * $rel_aux is additional argument to relation, which might be useful in
		 * some cases.
		 */
		$obj = $this->api->createObj($obj_type,$name,$dq);
		$this->api->createRel($this,$obj,$rel_type,$rel_aux);
		$this->add($obj);
		return $obj;
	}
	function deleteChild($type=null,$dq=null){
		/*
		 * This function deletes child objects of this object. Type names
		 * can be specified as array or separated by comma. If $types is not specified
		 * all child objects will be deleted.
		 */
		return $this->api->deleteObj($api->childDQ($dq,$this->id,$types));
	}
	function deleteObjTree($types=null,$dq=null){
		// Similar to deleteChild, but will recursively delete all object hierarchy.
		// You should limit by specifying list of allowed types.
		//
		// For your safety - $types is required by this function. Always list allowed
		// types to avoid disaster just because someone added a new relation type
		// with incorrect linknig
		$obj_pool = $this->loadChild($types,$dq);

		foreach($obj_pool as $obj){
			$obj->deleteObjTree($types,$dq);
		}
		$this->destroy();
	}
	function loadChild($types=null,$dq=null){
		/*
		 * This function loads all childs of a current object which are related
		 * with specified relation type. If type is omitted, all childs are loaded
		 *
		 * Loaded objects are added by $this->add(); so you can use down-calls
		 */
		$obj_pool=$this->api->genericLoadObj($this->api->childDQ($dq,$this->id,$types));
		$new_obj_pool=array();
		foreach($obj_pool as $obj){
			$new_obj_pool[$obj->id]=$this->add($obj);
			$new_obj_pool[$obj->id]->aux = $obj->aux; // add sux, and drops this important value!
			$new_obj_pool[$obj->id]->rel_type = $obj->rel_type; // add sux, and drops this important value!
		}
		return $new_obj_pool;
	}
	function loadOneChild($types=null,$dq=null){
		$obj_pool = $this->loadChild($types,$dq);
		if(count($obj_pool)>1)throw new APortalException("Only one child relation of type $types is allowed",$this);
		if(!$obj_pool)return null;
		return array_shift($obj_pool);
	}
	function loadParent($types=null,$dq=null){
		/*
		 * This function loads all parents of a current object which are related
		 * with specified relation type. If type is omitted, all childs are loaded
		 *
		 * Loaded objects are added by $this->add(); so you can use down-calls
		 */
		$obj_pool=$this->api->genericLoadObj($this->api->parentDQ($dq,$this->id,$types));
		return $obj_pool;
	}
	function loadOneParent($types=null,$dq=null){
		$obj_pool = $this->loadParent($types,$dq);
		if(count($obj_pool)>1){
			throw new APortalException("Only one parent relation of type $types is allowed",$this);
		}
		if(!$obj_pool)return null;
		return $obj_pool[0];
	}
	function loadObjTree($types=null,$dq=null){
		// Similar to addChild, but will recursively load all object hierarchy.
		$obj_pool = $this->loadChild($types,$dq);
		$new_obj_pool=array();
		foreach($obj_pool as $obj){
			$new_obj_pool[$obj->id]=$this->add($obj);
			$sub_obj_pool = $obj->loadObjTree($types,$dq);
			if (is_array($sub_obj_pool)){
				$new_obj_pool = array_merge($new_obj_pool, $sub_obj_pool);
			}
		}
		return $new_obj_pool;
	}
	function dumpObjTree($level = 0){
		for ($i=0,$sep="";$i<$level;$i++,$sep.="&nbsp;&nbsp;") continue;
		echo "$sep $this->type $this->id<br />";
		foreach ($this->elements as $obj){
			$obj->dumpObjTree($level + 1);
		}
	}

	//////////////////////// Working with object data /////////////////
	function addField($type, $name){
		if($this->fields[$name])throw new APortalException("Field $name already exist",$this);
		$this->fields[$name]["type"] = $type;
		$this->last_field=$name;
		return $this;
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

		// Do not set unexistant fields
		if(!isset($this->fields[$field_or_array])){echo "warning: no such field $field_or_array";}
		$this->data[$field_or_array]=$value;

		return $this;
	}
	function get($field=null){
		if(!$field)return $this->data;
		return $this->data[$field];
	}

	//////////////////////// Field properties and SQL table //////////////
	function setProperty($property,$value=null){
		/*
		 * This function is useful to call right after adding the filed:
		 *
		 * $this->addField('line','login')->setProperty('not null');
		 */
		return $this->setFieldProperty($this->last_field,$property,$value);
	}
	function setFieldProperty($name, $property, $value=null){
		if (!$this->fields[$name]){
			return null;
		}
		$this->fields[$name][$property] = $value;
		return $this;
	}
	function postCreateTable(){
		/*
		 * Use this function to customize table after it's been created. For example
		 * you can add some exotic indexes.
		 */
	}
	function createTable(){
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
			$query .= "id int not null primary key";
			foreach ($this->fields as $field => $properties){
				$query .= ", $field " . $sql_type[$properties["type"]];
			}
			$query .= ")";
			$this->api->db->query($query);
			$this->postCreateTable();
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
			if (!empty($existing_fields)){
				foreach ($existing_fields as $field => $field_data){
					echo "Dropping non-existing field\n";
					$this->api->db->getOne("alter table " . $this->table_name . " drop $field");
				}
			}
		}
	}
}
