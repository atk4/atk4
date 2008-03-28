<?php
//include 'loader.php';
define('PATH','charts/');

///////////

class chartXmlData extends ChatPage{

function setParam($param, $value){
	$this->template->set($param, $value);
	return $this;
}

function draw(){
	return $this->template->render();
}

function getTitle() {
	return "XMLData";
}

function defaultTemplate(){
	return array ('chart_data','_top');
}
}

//////////

class chartXmlConfig extends ChatPage{
function setParam($param, $value){
	$this->template->set($param, $value);
	return $this;
}

function getConfig(){
	return $this->template->render();
}

function getTitle() {
	return "XMLConfig";
}

function defaultTemplate(){
	return array ('chart_config','_top');
}
}

//////////

class Form_Field_FlashChart extends Form_Field{

private $chart='';
private $day='';
private $week='';
private $month='';
private $year='';
private $type='';
private $xmlDataFile='';
private $xmlConfigFile='';
private $product_id=array('127;');
private $charts_config='';
private $legend='';
private $show_legend=0;
private $period='';
private $charData='';
private $data='';

function init(){

	$this->chartData=new chartData;
	$dateinfo=getdate();
	$week=floor(($dateinfo['yday']+1)/7);
	
	$charts=array('daily', 'weekly', 'monthly', 'yearly');
	$type=array('traffic', 'users');

/*
	if (isset($_REQUEST['day']) && in_array($_REQUEST['day'], range(1,366))) $this->day=$_REQUEST['day'];
	else $this->day=$dateinfo['mday'];

	if (isset($_REQUEST['week']) && in_array($_REQUEST['week'], range(0,53))) $this->week=$_REQUEST['week'];
	else $this->week=$week;

	if (isset($_REQUEST['month']) && in_array($_REQUEST['month'], range(1,12))) $this->month=$_REQUEST['month'];
	else $this->month=$dateinfo['mon'];

	if (isset($_REQUEST['year']) && in_array($_REQUEST['year'], range(1000,9999))) $this->year=$_REQUEST['year'];
	else $this->year=$dateinfo['year'];*/

	parent::init();
}

function getDataSeries($array){

	$date=$this->year.'-'.$this->month.'-'.$this->day;
	$dateinfo=getdate(strtotime($date));
	switch ($this->chart){
		case 'daily':
			$this->period=$date.', '.$dateinfo['weekday'].', '.$array[0]['day'];
			break;
		case 'weekly':
			$this->period=$this->year.', week '.$this->week.', '.$array[0]['day'];
			break;
		case 'monthly':
			$this->period=$dateinfo['month'].', '.$array[0]['day'];
			break;
		case 'yearly':		
			$this->period=$array[0]['day'];
			break;
		}

	////

	$request="\r\n\t<series>";
	$i=1;
	foreach($array as $id => $value){
		$request.="\r\n\t\t<value xid='".$i."'>".$value['day']."</value>"	;
		$i++;
	}
	$ap=array_pop($array);
	$request.="\r\n\t</series>";

	/////

	$this->period.=' - '.$ap['day'];
	return $request;
}

function getDataGraph($array, $gid, $field){
	$request="\r\n\t\t<graph gid='".$gid."'>";
	$i=1;
	foreach($array as $id => $value){
		if ($this->type=='traffic') $v=round($value[$field]/1024/1024,3);
		else  $v=$value[$field];
		$request.="\r\n\t\t\t<value xid='".$i."'>".$v."</value>"	;
		$i++;
	}
	$request.="\r\n\t\t</graph>";
	return $request;
}

function getData($product_id){
	switch ($this->chart){
		case 'daily':
			$data=$this->chartData->getDaysData($product_id, $this->year, $this->month, $this->day);
			break;
		case 'weekly':
			$data=$this->chartData->getWeekData($product_id, $this->year, $this->week);
			break;
		case 'monthly':
			$data=$this->chartData->getMonthData($product_id, $this->year, $this->month);	
			break;
		case 'yearly':		
			$data=$this->chartData->getYearData($product_id, $this->year);	
			break;
		}
	return $data;
}

function getXmlData(){
	$in_index=1;
	$out_index=2;
	$user_index=1;
	$this->legend='';
	$xmlData="<?xml version='1.0' encoding='UTF-8'?>";
	$xmlData.="\r\n<chart>";
	$data=$this->getData($this->product_id[0]);	
	$xmlData.=$this->getDataSeries($data);	
	$xmlData.="\r\n\t<graphs>";
	foreach ($this->product_id as $key => $value)
	{
		$this->data=$this->getData($value);	
		//generate xml
		if ($this->type=='traffic'){
			$xmlData.=$this->getDataGraph($this->data, $in_index, 'bw_in');	
			$this->charts_config.=$this->getConfigGraphTraffic($in_index, $this->chartData->getProductName($value));
			$xmlData.=$this->getDataGraph($this->data, $out_index, 'bw_out');	
			$this->charts_config.=$this->getConfigGraphTraffic($out_index, $this->chartData->getProductName($value));
		}
		if ($this->type=='users'){
			$xmlData.=$this->getDataGraph($this->data, $user_index, 'cnt_custs');	
			$this->charts_config.=$this->getConfigGraphUsers($user_index, $this->chartData->getProductName($value));
		}
		$in_index+=2;
		$out_index+=2;
		$user_index++;

		//generate legend
		if ($this->show_legend==1){
			$this->legend.=	"<tr>\r\n".'<td>'.$this->chartData->getProductName($value).
							'</td><td><font color="'.$this->getTraficColor($in_index, 0).'"><strong>bw_in</strong></font>&nbsp;'.
							 '<font color="'.$this->getTraficColor($out_index, 1).'"><strong>bw_out</strong></font>&nbsp;'.
	 						'<font color="'.$this->getUserColor($user_index).'"><strong>users</strong></font></td>'."\r\n</tr>";
		}
	}
	//close xml tags
	$xmlData.="\r\n\t<graphs>";
	$xmlData.="\r\n</chart>";
	return $xmlData;
}

function getConfigGraphTraffic($gid, $title){
	if (fmod($gid,2)<>0){
	$result='
	    <graph gid="'.$gid.'">
	      <axis>left</axis>
	      <title>'.$title.'</title>
    	  <color>'.$this->getTraficColor($gid, 0).'</color>
	      <line_width>1</line_width>
	      <fill_alpha>50</fill_alpha>
	      <fill_color>'.$this->getTraficColor($gid, 0).'</fill_color>
	    </graph>';
	}
	else{
	$result='
	    <graph gid="'.$gid.'">
	      <axis>left</axis>                   
	      <title>'.$title.'</title>
	      <line_width>1</line_width>              
	      <color>'.$this->getTraficColor($gid, 1).'</color>
	    </graph>';
	}
	return $result;
}

function getConfigGraphUsers($gid, $title){
$result='
	    <graph gid="'.$gid.'">
	      <axis>left</axis>                   
	      <title>'.$title.'</title>
		  <color>'.$this->getUserColor($gid).'</color>
	      <line_width>1</line_width>              
	    </graph>';
return $result;
}

function getTraficColor($index, $bw){ //bw  0 -'in', 1-'out'
	$green=array('#00FF99','#33CC66','#00CC66','#009933','#99FF99','#66FF66','#00FF66','#339933','#006600','#CCFFCC','#99CC99','#66CC66','#669966','#336633','#003300','#33FF33','#00CC00','#66FF00','#33FF00','#33CC00','#339900','#009900','#CCFF99','#99FF66','#66CC00','#669933','#336600','#99FF00','#99CC66','#99CC00','#669900','#CCFF66','#CCFF00');

	$blue=array('#9999CC','#6666FF','#6666CC','#666699','#333399','#333366','#3300CC','#3333CC','#000099','#000066','#6699FF','#3366FF','#0000FF','#0000CC','#0033CC','#000033','#0066FF','#0066CC','#3366CC','#003399','#003366','#99CCFF','#3399FF','#0099FF','#6699CC','#336699','#006699','#66CCFF','#00CCFF','#3399CC','#0099CC','#003333');

	if ($index<count($green)){
		if ($bw==0) return $green[$index];
		if ($bw==1) return $blue[$index];
	}
	
}

function  getUserColor($index){
	$colors=array('#00FF99', '#33CC66', '#00CC66', '#009933', '#99FF99', '#66FF66', '#00FF66', '#339933', '#006600', '#CCFFCC', '#99CC99', '#66CC66', '#669966', '#336633',  '#003300', '#33FF33', '#00CC00', '#66FF00', '#33FF00', '#33CC00', '#339900', '#009900', '#CCFF99', '#99FF66', '#66CC00', '#669933', '#336600', '#99FF00', '#99CC66','#99CC00',  '#669900', '#CCFF66', '#CCFF00'
	);

	if ($index<count($colors)) return $colors[$index];

}

function writeFile($filename, $data){
	$file=fopen($filename, "w+");
	if (!$file) die ("Couldn't create file ".$filename);
	if (fwrite($file, $data)===false) die("Couldn't write to file ".$filename) ;
	fclose($file);
}

function setXmlData(){
	$this->writeFile($this->xmlDataFile, $this->getXmlData());
	$this->writeFile($this->xmlConfigFile, $this->chartData->getChartXmlConfig($this->period, $this->type, $this->charts_config));
}

function getInput($attr=array()){
	
	$output='TEST';
	return 	$output;

}

function draw($chart, $type, $legend, $product_id, $day, $week, $month, $year){

	$this->product_id=explode(';',$product_id);
	$this->product_id=array_slice($this->product_id,0,count($this->product_id)-1);

	$this->day=$day;
	$this->week=$week;
	$this->month=$month;
	$this->year=$year;			
	
	$this->chart=$chart;
	$this->type=$type;
	$this->show_legend=$legend;
	$this->xmlDataFile=PATH.'xml/'.$this->type."_".$this->chart.'_data.xml';
	$this->xmlConfigFile=PATH.'xml/'.$this->type."_".$this->chart.'_config.xml';

	$this->setXmlData();
	
	if (count($this->data)>0){
		$result=$this->chartData->getChart($this->type, $this->chart, $this->xmlDataFile, $this->xmlConfigFile, $this->legend);
	}
	else{
		if ($type=='traffic') $result='<font color="#ff3333" size="1">There are no data for the chosen parametres</font>';
		else $result='';
	}
	return $result;
}
}

class chartData extends ChatAdmin{
private $applications=array(1=>'VC', 2=>'LP', 3=>'IM', 4=>'VR');
function getChart($type, $chart, $data, $config, $legend){
	return $this->add('chartXmlData')
				->setParam('title',$type.' - '.$chart)
				->setParam('data',urlencode($data))
				->setParam('settings',urlencode($config))				
				->setParam('legend',$legend)
				->setParam('random',rand(100000,1000000))				
				->draw();
}


function getChartXmlConfig($period, $type, $config){
	$period='<font size="9px">'.$period.'</font>';
	if ($type=='traffic'){
		$type="<b>".$type.', mb</b><br>'.$period;
	}
	else $type="<b>".$type."</b><br>".$period;
	$config="\r\n<graphs>\r\n".$config."\r\n</graphs>\r\n";
	$xmlConfig=$this->add(chartXmlConfig)
					->setParam('header','<?xml version="1.0" encoding="UTF-8"?>')
					->setParam('label', $type)
					->setParam('graphs', $config)
					->getConfig();
	return $xmlConfig;
}


function getDaysData($product_id, $year, $month, $day){
	return $this->api->db->dsql()->table('stat_d')
									->field('product_id')	
									->field('cnt_custs')		
									->field('bw_in')		
									->field('bw_out')										
									->field('date_format(day, "%H:%i") as day')
								->where('product_id', $product_id)																		
								->where('year(day)', $year)																										
								->where('month(day)', $month)																																		
								->where('dayofmonth(day)', $day)																																		
								->order(day)
								->do_getAllHash();
}

function getWeekData($product_id, $year, $week){
	return $this->api->db->dsql()->table('stat_w')
									->field('product_id')	
									->field('cnt_custs')		
									->field('bw_in')		
									->field('bw_out')										
									->field('date_format(day, "%m-%d %H:%i") as day')
								->where('product_id', $product_id)																		
								->where('year(day)', $year)																										
								->where('week(day)', $week)																																		
								->order(day)
								->do_getAllHash();
}

function getMonthData($product_id, $year, $month){
	return $this->api->db->dsql()->table('stat_m')
									->field('product_id')	
									->field('cnt_custs')		
									->field('bw_in')		
									->field('bw_out')										
									->field('date_format(day, "%Y-%m-%d") as day')
								->where('product_id', $product_id)																		
								->where('year(day)', $year)																										
								->where('month(day)', $month)																																		
								->order(day)
								->do_getAllHash();
}

function getYearData($product_id, $year){
	return $this->api->db->dsql()->table('stat_y')
									->field('product_id')	
									->field('cnt_custs')		
									->field('bw_in')		
									->field('bw_out')										
									->field('date_format(day, "%Y-%m-%d") as day')
								->where('product_id', $product_id)																		
								->where('year(day)', $year)																										
								->order(day)
								->do_getAllHash();
}

function getProductName($product_id){
	$d=$this->api->db->getRow('select site_id, application_id from product where id='.$product_id, DB_FETCHMODE_ASSOC);
	$name=$this->api->db->getOne('select name from site where id='.$d['site_id']);
	$app=$this->applications[$d['application_id']];
	$result=$name."(".$app.")&nbsp;&nbsp;";
	return $result;
}
}
?>