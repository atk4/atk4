<?
/**
 * Date selection control. Consists of checkbox and three comboboxes (dropdown lists of  days, 
 * months and years).
 * Unchecking combobox sets value to zero date '0000-00-00' and disables the controls. 
 * Years list starts from previous year and finishes with next one (three options total) by default.
 * It could be altered using setYearRange() method.
 *
 * @author		Kirich <chk@adevel.com>
 * @copyright		See file COPYING
 * @version		$Id$
 * 
 */
 

class Form_Field_DateSelector extends Form_Field {
	
	private $required = false;
	private $enabled = true;
	
	private $year_from;
	private $year_to;
	private $years = array();
	private $months = array('1'=>'Jan', '2'=>'Feb', '3'=>'Mar', '4'=>'Apr', 
			'5'=>'May', '6'=>'Jun', '7'=>'Jul', '8'=>'Aug', 
			'9'=>'Sep', '10'=>'Oct', '11'=>'Nov', '12'=>'Dec');
	private $days = array();
	
	private $c_year;
	private $c_month;
	private $c_day;
	
	
	function init(){
		$this->days = array();
		for($i=1; $i<=31; $i++)
			$this->days[$i] = str_pad($i, 2, '0', STR_PAD_LEFT);
		
		$cur_year = date('Y');
		$this->setYearRange($cur_year-1, $cur_year+1);
		$this->c_year = $cur_year;
		$this->c_month= date('m');
		$this->c_day= date('d');
		
		$this->enable();
		
		parent::init();
	}
	
	function set($value){
		
		if (empty($value) || ($value == '0000-00-00') || (false === $tm = strtotime($value))){
			$this->disable();
		}
		else{
			$yr = date('Y', $tm);
			if($yr > $this->year_to)
				$yr = $this->year_to;
			elseif($yr < $this->year_from)
				$yr = $this->year_from;
			
			$this->c_year = $yr;
			$this->c_month= date('m', $tm);
			$this->c_day= date('d', $tm);
			
			$this->enable();
		}
		
		parent::set($value);
	}
	
	public function isEnabled(){
		return $this->enabled;
	}
	
	function enable(){
		$this->enabled = true;
		$this->value = str_pad($this->c_year, 4, '0', STR_PAD_LEFT).
	        	'-'.str_pad($this->c_month, 2, '0', STR_PAD_LEFT).
	        	'-'.str_pad($this->c_day, 2, '0', STR_PAD_LEFT);
	}
	
	function disable(){
		if(!$this->required){
			$this->enabled = false;
			$this->value = '0000-00-00';
		}
	}
	
	function setRequired($is_required){
		$this->required = $is_required === true;
	}
	
	function setYearRange($from=null, $to=null){
		if(!is_numeric($from)) 
    		$from = null;
    	if(!is_numeric($to)) 
    		$to = null;
    	
    	$cur_year = date('Y');
    	if(($from === null) && ($to === null))
    		return array($cur_year => $cur_year);
    		
    	if(($from === null) && ($to !== null)){
    		$from = ($to < $cur_year)?$to:$cur_year;
    	}
    	elseif(($from !== null) && ($to === null)){
    		$to = ($from > $cur_year)?$from:$cur_year;
    	}
    	elseif($from > $to ){
    		$temp = $to;
    		$to = $from;
    		$from=$temp;
    	}
    	
    	$res = array();
    	for($i=$from; $i<=$to; $i++)
    		$res[$i] = $i;
    		
    	// correct the c_year value upon range change
		if($this->c_year > $to)
    		$this->c_year = $to;
    	if($this->c_year < $from)
    		$this->c_year = $from;
    	
    	$this->year_from = $from;
    	$this->year_to = $to;	
    	$this->years = $res;
    }
    
    function loadPOST(){
    	if(empty($_POST))
    		return;
    	
    	if(isset($_POST[$this->name.'_year']))
        	$this->c_year = $_POST[$this->name.'_year'];
        if(isset($_POST[$this->name.'_month']))
        	$this->c_month = $_POST[$this->name.'_month'];
        if(isset($_POST[$this->name.'_day']))
        	$this->c_day = $_POST[$this->name.'_day'];
    	
    	if(isset($_POST[$this->name.'_enabled']))
	        $this->enable();
    	else 
    		$this->disable();
    }
    
    function validate(){
    	if($this->enabled)
	        if(false === strtotime($this->value))
	        	$this->owner->errors[$this->short_name]="Invalid date specified!";
        	
        return parent::validate();
    }
    
    function getInput($attr=array()){
    	$output=$this->getTag('span', array('style'=>'white-space: nowrap;'));
    	
    	if($this->required)
    		$output.=$this->getTag('input', 
    			array('type'=>'hidden', 'name'=>$this->name.'_enabled', 'id'=>$this->name.'_enabled', 'value'=>'Y'));
    	else{
    		$attrs =array('type'=>'checkbox', 
					'name'=>$this->name.'_enabled', 
					'id'=>$this->name.'_enabled', 
					'onclick'=>'var d=document, day=d.getElementById(\''.$this->name.'_day\'), month=d.getElementById(\''.$this->name.'_month\'), year=d.getElementById(\''.$this->name.'_year\'); if(!this.checked){if(day)day.disabled=true;if(month)month.disabled=true;if(year)year.disabled=true;}else{if(day)day.disabled=false;if(month)month.disabled=false;if(year)year.disabled=false;}');
					
			if($this->enabled)
				$attrs['checked'] = 'checked'; 
    		
    		$output.=$this->getTag('input', $attrs);
    	}
    	
    	$xtraattrs = array();
    	if(!$this->enabled)
			$xtraattrs['disabled'] = 'disabled';
    	
    	// day control
    	$output.=$this->getTag('select',array_merge(array(
                        'id'=>$this->name.'_day',
						'name'=>$this->name.'_day',
						'onchange'=>($this->onchange)?$this->onchange->getString():''
                        ), $attr, $this->attr, $xtraattrs)
                );
        foreach($this->days as $value=>$descr){
            $output.=
                $this->getTag('option',array(
                        'value'=>$value,
                        'selected'=>$value == $this->c_day
                    ))
                .htmlspecialchars($descr)
                .$this->getTag('/option');
        }
        $output.=$this->getTag('/select').'&nbsp;';
                
    	// month control
    	$output.=$this->getTag('select',array_merge(array(
                        'id'=>$this->name.'_month',
						'name'=>$this->name.'_month',
						'onchange'=>($this->onchange)?$this->onchange->getString():''
                        ), $attr, $this->attr, $xtraattrs)
                );
        foreach($this->months as $value=>$descr){
            $output.=
                $this->getTag('option',array(
                        'value'=>$value,
                        'selected'=>$value == $this->c_month
                    ))
                .htmlspecialchars($descr)
                .$this->getTag('/option');
        }
        $output.=$this->getTag('/select').'&nbsp;';
        
    	// year control
    	$output.=$this->getTag('select',array_merge(array(
                        'id'=>$this->name.'_year',
						'name'=>$this->name.'_year',
						'onchange'=>($this->onchange)?$this->onchange->getString():''
                        ), $attr, $this->attr, $xtraattrs)
                );
        foreach($this->years as $value=>$descr){
            $output.=
                $this->getTag('option',array(
                        'value'=>$value,
                        'selected'=>$value == $this->c_year
                    ))
                .htmlspecialchars($descr)
                .$this->getTag('/option');
        }
        $output.=$this->getTag('/select');
        
        $output.=$this->getTag('/span');
        
        return $output;
    }
}

