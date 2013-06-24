<?

class DB {
    protected $link = null;
    protected $config;
    
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
        mysql_query($query,  $this->getLink());
        
        if (  mysql_errno( $this->getLink() )  )
            echo mysql_error( $this->getLink() );
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
        return $this->formatKeysValues($values);
    }
    
    function insert($table,$values) {
        $valueStr = $this->formatKeysValues($values);
        $query = "INSERT INTO $table SET $valueStr";
        
        $this->query($query);
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
