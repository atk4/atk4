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
	 */

	//////////////// Loading ///////////////
	private function loadObjFromArray($arr){
		/*
		 * This function loads Obj by supplied array of data. All we
		 * really need to know about object when we are creating it is
		 * it's ID OR Type. If ID is defined, additional data will be
		 * loaded from database. If only Type is defined, then new object
		 * is created. However new object is NOT saved into database yet.
		 *
		 * This function always returns array.
		 */
		if(!$arr)return array();
		$result = null;
		foreach ($arr as $row){
			if(!isset($row['type'])){
				// Perhaps id is specified
				if(!isset($row['id']))throw new APortalException('Must specify either type (for new objects) or id (if you want to load type)');

				$row['type']=$this->api->db->dsql()
					->table('obj')
					->field('type')
					->where('id',$row['id'])
					->getOne();
				if(!$row['type'])throw new APortalException('Unable to initialize object with ID='.$row['id'].'. It was not found in database.');
				// TODO: it's inefficient to loop this. Rather should do one select.
			}
			$class = $row['type'];

			if(substr($row['type'],0,3)!='ap_'){
				throw new APortalException("Place A-Portal (ApiPortal) related classes into 'ap' directory: $".$row['type']);
			}
			$class = $row['type'];
			$last = new $class;
			$last->owner=$this;
			$last->api=$this;
			$cnt = $GLOBALS["ocnt"]++;
			$last->name=($row['id']?'Object #'.$row['id']."_".$cnt:"n_a_" . $cnt);
			$last->short_name=($row['id']?'obj_'.$row['id']."_".$cnt:"n_a_" . $cnt);
			$GLOBALS["lh"][$last->short_name]++;
			$GLOBALS["mc"][$last->short_name] = $last;
			$last->init();

			foreach($row as $key=>$val){
				switch($key){
					case'id':$last->id=$val;break;
					case'name':$last->name=$val;break;
					case'type':$last->type=$val;break;
					case'aux':$last->aux=$val;break;
					case'rel_type':$last->rel_type=$val;break;
					default:
							   $last->set($key,$val);
				}
			}
			$result[]=$last;
		}
		return $result;
	}
	public function genericLoadObj($dq){
		/*
		 * This function loads object based on specified dynamic query. Before
		 * using this function, look at wrappers such as loadChildObj.
		 *
		 * This function always returns array of objects, even if only one
		 * was loaded or empty array if no object matched.
		 *
		 * If you want to load a single object, use
		 *
		 *  $this->api->loadObj($id);
		 *
		 * $dq may be initialized with "where", "join" and "limit", however
		 * main table will be "obj". Here is sample:
		 *
		 *  $acc = $api->genericLoadObj(
		 *      $api->db->dsql()
		 *          ->join('account','account.id=obj.id')
		 *          ->where('account.name',$_GET['login']));
		 *
		 *  if(!$acc) login_failed();
		 *  $acc=shift($acc);
		 */
		$limit = 0;
		$dq
			->table('obj')
			->field('obj.id id')
			->field('obj.type type')
			->field('obj.name name');

		$arr = $dq->do_getAllHash();
		$obj_pool = $this->loadObjFromArray($arr);
		foreach ($obj_pool as $obj){
			$obj->load();
		}
		return $obj_pool;
	}
	public function loadObj($id){
		/*
		 * Loads object into memory based on $id only.
		 */
		if (!(int)$id) throw new APortalException("You must specify ID to loadObj. Specified: '$id'");
		$result = $this->genericLoadObj(
				$this->api->db->dsql()
				->where('obj.id', $id)
				);
		if(!$result) throw new APortalException("Object with specified ID was not found: '$id'");
		return $result[0];
	}

	//////////////// Deletion ///////////////
	function genericDeleteObj($dq){
		/*
		 * This function deletes object based on dynamic query. Before using
		 * it, check if there are any wrapper such as deleteChildObj. If you
		 * want to delete single object use deleteObj($id)
		 */
		$obj_pool = $this->genericLoadObj($dq);
		foreach($obj_pool as $obj){
			$obj->destroy();
		}
	}
	public function deleteObj($id){
		/*
		 * Delete object with specified $id
		 */
		if (!(int)$id) throw new APortalException("You must specify ID to loadObj. Specified: '$id'");
		$this->loadObj($id)->destroy();
	}
	function deleteRel($parent = null, $child = null, $types=null, $dq=null){
		if(!$parent && !$child)throw new ap_Exception("Either parent or child must be specified for delete Relation");

		if(!$dq)$dq=$this->db->dsql();
		$dq->table('rel');

		if(is_object($parent))$parent=$parent->id;
		if(is_object($child))$child=$child->id;
		$types=$this->convertTypes($types);

		if (isset($parent)){
			$dq->where('parent', $parent);
		}
		if (isset($child)){
			$dq->where('child', $child);
		}
		if (isset($type)){
			$dq->where('type in ('.$type.')');
		}
		return $dq->do_delete();
	}

	//////////////// Creating things //////////////
	function createObj($type, $name=null,$dq=null){
		/*
		 * This function will create new object with specified type
		 * and save it to database. No relation will be made. If you are
		 * willing to relate object with any other object use
		 * $obj -> addChild();
		 */

		$obj_data = array(0 =>
				array(
					'type'=>$type,
					'name'=>$name
				)
		);
		list($obj)=$this->loadObjFromArray($obj_data);

		// Now let's initialize object's ID by saving object into database
		$obj->create($dq);

		return $obj;
	}
	function createRel($parent, $child, $type, $aux = null){
		/*
		 * Creates new relation between 2 objects. You can either specify
		 * objects or IDs
		 */
		if(is_object($parent))$parent=$parent->id;
		if(is_object($child))$child=$child->id;
		$dq = $this->db->dsql();
		if(!$dq
				->table('rel')
				->field('id')
				->where('parent', $parent)
				->where('child', $child)
				->where('type', $type)->do_getOne()){
			$dq->set(
					array(
						'parent' => $parent,
						'child' => $child,
						'type' => $type,
						'aux' => $aux
						)
					)->do_insert();
			return true;
		}

	}

	//////////////// Supplementary functions ///////////////
	function childDQ($dq,$id,$types=null){
		/*
		 * This function modifies $dq object, so that only children of specified $id
		 * would get selected. New dq object is returned.
		 */

		if(!$dq)$dq=$this->api->db->dsql();
		$types=$this->api->convertTypes($types);
		$dq
			->field('autorel.type rel_type')
			->field('autorel.aux aux')
			->join('rel autorel',"autorel.child=obj.id")
			->where('autorel.parent',$id);

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
			->field('autorel.type rel_type')
			->field('autorel.aux aux')
			->join('rel autorel',"autorel.parent=obj.id")
			->where('autorel.child',$id);

		if($types)$dq->where("autorel.type in (".$types.")");
		return $dq;
	}
	function convertTypes($type=null){
		/*
		 * When function accepts argument $types, you can normally specify
		 * coma-separate types or specify array. This function converts
		 * them into SQL-friendly format
		 */
		if(!$type)return $type;
		if(is_array($type))$type=join(',',$type);
		$type=addslashes($type);
		$type = '"' . str_replace(',','","',$type) . '"';
		return $type;
	}
}
