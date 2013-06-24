<?

class DB {
    protected $link = null;
    protected $config = null;
    protected $qr = null;
    
    function getLink() {
        if (  is_null($this -> link)  ) {
            if(isset($config['databasePass']))        
                $this -> link = mysql_connect($this->config['databaseHost'],  $this->config['databaseUser'],  $this->config['databasePass']);
            else
                $this -> link = mysql_connect($this->config['databaseHost'],  $this->config['databaseUser']);

            mysql_select_db($this->config['databaseName'],$this -> link);        
        }
        
        return $this -> link;
    }
    
    function __construct($config) {
        $this->config = $config;
    }
    
    function query($query) {
        $this->qr = mysql_query($query, $this->getLink());
        
        if (  mysql_errno( $this->getLink() )  )
            echo mysql_error( $this->getLink() );
        
        return $this->qr;
    }
    
    function formatKeysValues($values,$nokeys=false) {
        $result='';
        
        foreach ($values as $key => $value) {
            if ($result) $result.=',';
            if (!$nokeys) $result .= $key . '=';
            $result .= '\'' . mysql_escape_string($value) . '\'';
        }
        
        return $result;
    }

    function formatValues($values) {
        return $this->formatKeysValues($values,true);
    }
    
    function insert($table,$values,$returnID=false) {
        $valueStr = $this->formatKeysValues($values);
        $query  = "INSERT INTO $table SET $valueStr;";
        if ($returnID) {
            $this->query($query);
            $query = "SELECT LAST_INSERT_ID();";
            $qr = $this->query($query);
            return mysql_result($qr,0);
        } else {
            return $this->query($query);
        }
    }
    function insertIgnore($table,$values,$returnID=false) {
        $valueStr = $this->formatKeysValues($values);
        $query  = "INSERT IGNORE INTO $table SET $valueStr;";
        if ($returnID) {
            $this->query($query);
            $query = "SELECT LAST_INSERT_ID();";
            $qr = $this->query($query);
            return mysql_result($qr,0);
        } else {
            return $this->query($query);
        }
    }

    function insertUpdate($table,$values,$returnID=false) {
        $valueStr = $this->formatKeysValues($values);
        $query  = "INSERT INTO $table SET $valueStr ON DUPLICATE KEY UPDATE $valueStr;";
        if ($returnID) {
            $this->query($query);
            $query = "SELECT LAST_INSERT_ID();";
            $qr = $this->query($query);
            return mysql_result($qr,0);
        } else {
            return $this->query($query);
        }
    }
    
    function fetch_assoc($qr=null) {
        if (is_null($qr)) $qr = $this->qr;        
        return mysql_fetch_assoc($qr); 
    }

    function fetch_array($qr=null) {
        if (is_null($qr)) $qr = $this->qr;        
        return mysql_fetch_array($qr); 
    }

    function fetch_row($qr=null) {
        if (is_null($qr)) $qr = $this->qr;        
        return mysql_fetch_row($qr); 
    }
    
    function fetch_object($qr=null, $class_name = null, array $params = null) {
        if (is_null($qr)) $qr = $this->qr;        
        return mysql_fetch_object($qr, $class_name, $params); 
    }
    
    function fetch_field($qr=null, $field_offset = 0) {
        if (is_null($qr)) $qr = $this->qr;
        return mysql_fetch_field($qr, $field_offset);
    }
    
    function fetch_lengths ($qr=null) {
        if (is_null($qr)) $qr = $this->qr;
        return mysql_fetch_lengths($qr);
    }

    function result($row=0, $field=0) {
        return mysql_result($this->qr, $row, $field);
    }
    
    function select($table,$values="*",$options=array()) {
        if (is_array($values))
            $valueStr = $this->formatValues($values);
        else
            $valueStr = $values;
        
        $query = "SELECT $valueStr FROM $table";
        
        if (isset($options['WHERE'])) $query.=" WHERE".$options['WHERE'];
        
        return $this->query($query);        
    }
    
    function error($dbLink = null) {
        if (is_null($dbLink))
            $dbLink = $this->getLink();            
        return mysql_error($dbLink);       
    }

    function errno($dbLink = null) {
        if (is_null($dbLink))
            $dbLink = $this->getLink();            
        return mysql_errno($dbLink);       
    }
    
    function escape_string($unescaped_string) {    
        return mysql_escape_string($unescaped_string);
    }
}

function db_connect() {
        global $config;
        
	if(isset($config['databasePass']))        
		$dbConn=mysql_connect($config['databaseHost'],$config['databaseUser'],$config['databasePass']);
	else
		$dbConn=mysql_connect($config['databaseHost'],$config['databaseUser']);

        mysql_select_db($config['databaseName'],$dbConn);
        
        return $dbConn;
}
?>
