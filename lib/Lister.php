<?php // vim:ts=4:sw=4:et:fdm=marker
/**
 * Lister implements a very simple and fast way to output series
 * of data by applying template formatting
 * 
 * @link http://agiletoolkit.org/doc/lister
 *
 * Use:
 *  $list=$this->add('Lister');
 *  $list->setModel('User');
 *
 * Template (view/users.html):
 *  <h4><?$name?></h4>
 *  <p><?$desc?></p>
 *
 * @license See http://agiletoolkit.org/about/license
 *
**/
class Lister extends View {

    /** If lister data is retrieed from the SQL database, this will contain dynamic query. */
	public $dq=null;

    /** For other iterators, this variable will be used */
    public $iter=null;

    /** Points to current row before it's being outputted. Used in formatRow() */
	public $current_row=array();

    /** 
     * Sets source data for the lister. If source is a model, use setModel() instead.
     *
     * @link http://agiletoolkit.org/doc/lister
     *
     * Examples:
     *  $l=$this->add('Lister');
     *  $l->setSource( array('a','b','c') );        // associative array
     *
     * or   // array of hashes
     *  $l->setSource( array(
     *      array('id'=>1,'name'=>'John','surname'=>'Smith'),
     *      array('id'=>2,'name'=>'Joe','surname'=>'Blogs')
     *      ));
     *
     * or   // dsql
     *  $l->setSource( $this->api->db->dsql()
     *      ->table('user')
     *      ->where('age>',3)
     *      ->field('*') 
     *  );
     *
     * or   // sql table
     *  $l->setSource( 'user', array('name','surname'));
     **/
	function setSource($source,$fields=null){

        // Set DSQL
        if($source instanceof DB_dsql){
            $this->dq=$source;
            return $this;
        }

        // SimpleXML and other objects
        if(is_object($source)){
            if($source instanceof Model)throw $this->exception('Use setModel() for Models');
            if($source instanceof Controller)throw $this->exception('Use setController() for Controllers');
            if($source instanceof Iterator){
                $this->iter=$source;
                return $this;
            }

            // Cast non-iterable objects into array
            $source=(array)$source;
        }

        // Set Array as a data source
        if(is_array($source)){

            $m=$this->setModel('Array');

            if(is_array(reset($source))){
                $m->setSource($source);
            }else{
                $m->setAssoc($source);
            }
            return $this;
        }

        // Set manually
        $this->dq=$this->api->db->dsql();
        $this->dq
            ->table($source)
            ->field($fields?:'*');

		return $this;
	}
    /** @obsolete set array source */
	function setStaticSource($data){
        return $this->setSource($data);
	}
    /** Redefine and change $this->current_row to format data before it appears */
	function formatRow(){
		$this->hook('formatRow');
	}
    /** Renders single row. If you use for formatting then interact with template->set() directly prior to calling parent */
    function rowRender($template) {
        $template->set($this->current_row);
        return $template->render();
    }
    function getIterator(){
        if(!($i=$this->model?:$this->dq?:$this->iter))
            throw $this->exception('Please specify data source with setSource or setModel');
        return $i;
    }
	function render(){
        foreach($this->getIterator() as $this->current_row){
            $this->formatRow();
            $this->output($this->rowRender($this->template));
        }
	}
    function defaultTemplate(){
        return array('view/lister');
    }
}
