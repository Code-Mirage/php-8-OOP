<?php


Class DBdriver {
    
                public object $MysqliDB;
                public string $default_host;
                public string  $default_user;
                public string  $default_pwassword;
                public string  $default_name;
                public string  $default_charset;
                public int  $default_pconnect = 0;
                public int  $query_num = 0;
                public int  $default_lp = 1;
    
                public function __construct($default_host,$default_port,$default_user,$default_pwassword,$default_name,$default_charset,$default_pconnect= 0,$default_lp=1){

                    
                    
                            $this->MysqliDBhost = $default_host;
                            $this->MysqliDBport = $default_port;
                            $this->MysqliDBuser = $default_user;
                            $this->MysqliDBpw   = $default_pwassword;
                            $this->MysqliDBname = $default_name;
                            $this->MysqliDBcharset = $default_charset;
                            $this->MysqliDBpconnect = $default_pconnect;
                            $this->MysqliDBlp = & $default_lp;



                            $this->MysqliDB = @mysqli_init();
                    
                            @mysqli_real_connect($this->MysqliDB,$this->MysqliDBhost, $this->MysqliDBuser, $this->MysqliDBpw, false, $this->MysqliDBport);
                    
                            if(@mysqli_errno($this->MysqliDB) != 0){
                                $this->halt('Connect('.$this->MysqliDBpconnect.') to MySQL failed');
                            }
                    
                            $serverinfo = mysqli_get_server_info($this->MysqliDB);
                    
                            if ($serverinfo > '4.1' && $this->MysqliDBcharset) {
                                mysqli_query($this->MysqliDB, "SET character_set_connection=" . $this->MysqliDBcharset . ",character_set_results=" . $this->MysqliDBcharset . ",character_set_client=binary");
                            }
                    
                    
                            if ($serverinfo > '5.0') {
                                mysqli_query($this->MysqliDB, "SET sql_mode=''");
                            }
                    
                            if ($this->MysqliDBname && !@mysqli_select_db($this->MysqliDB, $this->MysqliDBname)) {
                                $this->halt('Cannot use database');
                            }
                }
    
                public function close():bool{
                            return @mysqli_close($this->MysqliDB);
            
                }
    
                public function lock($table_name):mysqli_result|bool{
                            return $this->query("LOCK TABLES ".$table_name." WRITE");
                }
    
                public function unlock($table_name):mysqli_result|bool{
                            return $this->query("UNLOCK $table_name");
                }
    
                public function select_db($default_name):void{
                            if (!@mysqli_select_db($this->MysqliDB,$this->MysqliDBname)) {
                                $this->halt('Cannot use database');
                            }
                }
    
                public function server_info():string{
                            return mysqli_get_server_info($this->MysqliDB);
                }
    
                public function insert_id(){
                            return $this->getvalue('SELECT LAST_INSERT_ID()');
                }
            
                //getone
                public function getone($SQL,$result_type = 'MYSQLI_ASSOC'):bool|array|null{
                            $query = $this->query($SQL,'Q');
                            $array=$this->fetch_array($query,$result_type);
                            $rt=&$array;
                            return $rt;
                }
            
            
            
                //update
                public function update($SQL,$lp=1):mysqli_result|bool{
                            if ($this->MysqliDBlp == 1 && $this->MysqliDBlp) {
                                $tmpsql6 = substr($SQL,0,6);
                                if (strtoupper($tmpsql6.'E')=='REPLACE') {
                                    $SQL = 'REPLACE LOW_PRIORITY'.substr($SQL,7);
                                } else {
                                    $SQL = $tmpsql6.' LOW_PRIORITY'.substr($SQL,6);
                                }
                            }
                            return $this->query($SQL,'U');
                }
    
    
                public function query($SQL,$method = null,$error = true):mysqli_result|bool{
                    
                    
                            $query = @mysqli_query($this->MysqliDB, $SQL, ($method ? MYSQLI_USE_RESULT : MYSQLI_STORE_RESULT));

                    
                            if (in_array(mysqli_errno($this->MysqliDB),array(2006, 2013)) && empty($query) && !defined('QUERY')) {
                                        define('QUERY',true);
                                        @mysqli_close($this->MysqliDB);
                                        sleep(2);
                                        $this->connect();
                                        $query = $this->query($SQL);
                            }
                    
                    
                            if ($method != 'U') {
                                $this->query_num++;
                            }
                            if(!$query && $error){
                                $this->halt('Query Error: '.$SQL);
                            }
                            return $query;
                }
    
    
    
                public function fetch_array($query, $result_type = MYSQLI_ASSOC):bool|array|null{
            
                        return mysqli_fetch_array($query,MYSQLI_ASSOC);
                }
    
    
                public function checktable($tablename):bool{
            
                        if($tablename){
                
                                if($this->num_rows($this->query("SHOW TABLES LIKE '{$tablename}'"))) {
                    
                                    return true;
                    
                                } else {
                                    return false;
                                }
                
                        } else {
                                return false;
                        }
                }
    
                public function affected_rows():int|string{
                        return mysqli_affected_rows($this->MysqliDB);
                }
                public function num_rows($query):int|string{
                        if (!is_bool($query)) {
                            return mysqli_num_rows($query);
                        }
                        return 0;
                }
                public function num_fields($query):int{
                        return @mysqli_num_fields($query);
                }
                public function escape_string($str):string{
                        return mysqli_real_escape_string($this->MysqliDB, $str);
                }
                public function free_result():void{
                        $void = func_get_args();
                        foreach ($void as $query) {
                            if ($query instanceof mysqli_result) {
                                mysqli_free_result($query);
                            }
                        }
                        unset($void);
                }
    
    
    
    
                public function getfetchall($query):bool|array{
            
                        if (!$query){
                            return false;
                        }
                        
                        $result =  $this->query($query);
                        
                        if ($query instanceof mysqli_fetch_all) {
                                $datadb = mysqli_fetch_all($result,MYSQLI_ASSOC);
                        
                        } else {
                        
                                while ($row = mysqli_fetch_assoc($result)) {
                                        $datadb[] = $row;
                                }
                        
                        }
                        
                        $this->free_result();
                        
                        return $datadb;
                }
    
    
                public function halt($msg=null){

    
                    $sqlerror = mysqli_error($this->MysqliDB);
    
                    $sqlerrno = mysqli_errno($this->MysqliDB);
    
                    $sqlerror = str_replace($this->MysqliDBhost,'default_host',$sqlerror);
    
    
                    echo "<!DOCTYPE HTML>\n<head>\n<meta charset='utf-8'/>\n";
                    echo "<title>Error Messages</title>\n";
                    echo "<meta name=\"robots\" content=\"none\">\n";
                    echo "<style type='text/css'>\n";
                    echo "P,BODY{FONT-FAMILY:tahoma,arial,sans-serif;FONT-SIZE:12px;}\n";
                    echo "a{ TEXT-DECORATION: none;}\n";
                    echo "a:hover{ text-decoration: underline;}\n";
                    echo "table{TABLE-LAYOUT:fixed;WORD-WRAP: break-word;padding: 10px;}\n";
                    echo "td{ BORDER-RIGHT: 1px; BORDER-TOP: 0px; FONT-SIZE: 16pt; COLOR: #000000;}\n";
                    echo ".rounded-corners {margin: 50px auto 0px auto;border: 1px solid #ccc;width:900px;-moz-border-radius: 10px;-webkit-border-radius: 10px;-khtml-border-radius: 10px;border-radius: 10px;}\n";
                    echo "</style>\n";
                    echo "<body>\n";
                    echo "<div class='rounded-corners'>\n<table>\n<tr>\n\t<td>";
                    echo "<b style='text-align:left;margin:0px 0px 0px 5px;'>Error Message</b><br /><br />";
                    echo "<div style='margin-left:0px;'>";
    
    
                    if(in_array($GLOBALS['onlineip'],array('127.0.0.1'))){
        
                            echo "$msg <br />";
            
                            if($sqlerror && $sqlerrno){
                
                                echo"$sqlerror <br /><br />";
                
                                echo"錯誤訊息代碼 : $sqlerrno<br />";
                
                                echo"描述訊息 : <b> $sqlerrno </b><br /><br />";
                
                            }
        
                    } else {
        
                            if($sqlerror && $sqlerrno){
                
                                echo"錯誤訊息代碼 : $sqlerrno<br />";
                
                            }
                    }
    
                    echo"\n\t</div></td>\n</tr>\n</table></div>\n";
                    echo"</body>\n</html>";
    
    
                    exit;

                }
	

}


?>