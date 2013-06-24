<?

if (!defined("MODE_DEBUG")) define("MODE_DEBUG",true);

require_once("DB.php");

class InterconPeer {
    protected $_client=null;
    protected $_localStatus;
    protected $_peerStatus;
    protected $_interconID;
    
    function peerStatus() {
        return $this->_peerStatus;
    }

    function interconID() {
        return $this->_interconID;
    }

    function localStatus() {
        return $this->_localStatus;
    }
    
    function client() {
        return $this -> _client;
    }
    
    function __construct($params) {
        if (!isset($params['peerURL'])) {
            throw new Exception('peerURL param is missing');
        }

        $url = $params['peerURL'];        
        $this->client = new SoapClient(
                null,
                array('location' => $url,
                      'uri'      => $url,
                      'trace' => 1
                )
            );
        $this->setParams($params);
    }
    
    function setParams($params) {
        if (isset($params['localStatus'])) $this -> _localStatus = $params['localStatus'];
        if (isset($params['peerStatus'])) $this -> _peerStatus = $params['peerStatus'];
        if (isset($params['interconID'])) $this -> _interconID = $params['interconID'];        
    }
    
    function __call($name, $arguments) {
        $this->_client->__call($name,$arguments);
    }
}

class Interconnection {
    protected $config;
    protected $db = null;
    protected $clients = array();
    protected $dbConn=null;

    function __construct($config) {
        $this -> config = $config;       
        $this -> db = new DB($this->config['i']);
    }
    
    function addPeer($CDNid,$params,$replace=false) {
        if ($replace || !isset($this -> clients[$CDNid])) {        
            $this -> clients[$CDNid] = new InterconPeer($params);
        }
    }
    
    function addAllPeers() {
        $this -> db -> select('interconnections');
            
        while ($item = $this -> db -> fetch_assoc()) {
                $this -> addPeer($item['CDNid'],$item);
        }
    }
    
    function getPeer($CDNid) {
        if (isset($this->clients[$CDNid])) {
            $this -> $db -> select('interconnections','*',
                    array('WHERE' => "CDNid=$CDNid")
            );
            
            while ($item = $db -> fetch_assoc($result)) {
                $this -> addPeer($item['peerURL'],$item);
            }
        }
        
        return $this->clients[$CDNid];
    }
    
    function setConfig($config) {
        $this -> config = $config;
    }
        
    function peerCall($peerURL,$method,$params) {
        if (defined("MODE_DEBUG")) {echo "Calling $method: ".PHP_EOL; var_dump($params);}
        
        $response = http_post_fields($peerURL."/".$method, $params);
        $responseObj=http_parse_message($response);
        $body=$responseObj->body;
        if (defined("MODE_DEBUG")) echo "Result: $body".PHP_EOL;
        
        $result=array();
        parse_str($body,$result);
                
        return $result;
    }
    
    function peerSetCapabilities($peerURL) {
        echo "Sending capability to $peerURL".PHP_EOL;
        
        $fields=array(
            'CDNid' => $this -> config['local']['CDNid'],
            'capabilities' => $this -> config['local']['capabilities']
        );
        $this ->peerCall($peerURL, "setCapabilities", $fields);
    }
    
    function peerSetFootprint($peerURL) {
        echo "Sending footprint to $peerURL".PHP_EOL;
        
        $fields=array(        
            'CDNid' => $this -> config['local']['CDNid'],
            'footprint' => implode(",",$this -> config['local']['footprint'])
        );
        $this -> peerCall($peerURL, "setFootprint", $fields);
    }
    
    function peerSetOfferLocalStatus($interconID,$status) {     
        $this->db_connect();
        $qr = mysql_query("SELECT peerURL FROM interconnections WHERE interconID='". mysql_escape_string($interconID) ."';");       
        if (mysql_errno()) echo mysql_error ();
        else {
            $peerURL = mysql_result($qr,0);
            if (defined('MOD_DEBUG')) echo "Sending local status ($status) to $peerURL".PHP_EOL;
        
            $fields=array(        
                'CDNid' => $this -> config['local']['CDNid'],
                'status' => $status
            );
            $this -> peerCall($peerURL, "setOfferLocalStatus", $fields);
        }
    }

    function peerSetOffer($peerURL) {
        if (defined('MOD_DEBUG')) echo "Sending offer to $peerURL".PHP_EOL;
        
        $fields=array(        
            'CDNid'  => $this -> config['local']['CDNid'],
            'APIurl' => $this -> config['local']['APIurl']
        );
        
        $result = $this -> peerCall($peerURL, "setOffer", $fields);
        
        return $result['CDNid'];
    }
    
    function peerSetContentBasicMetadata($peerURL,$contentID,$metadata) {
        if (defined('MOD_DEBUG')) echo "Sending SetContentBasicMetadata to $peerURL".PHP_EOL;
        
        $fields=array(
            'CDNid' => $this -> config['local']['CDNid'],
            'contentID'  => $contentID,
            'metadata' => $metadata
        );
        
        $result = $this -> peerCall($peerURL, "setContentBasicMetadata", $fields);
        
        return $result;       
    }
    
    function processLocalOffers() {        
        if (isset($this -> config['i']['peers'])) {
            foreach ($this -> config['i']['peers'] as $peer) {
                echo "Processing: "; var_dump($peer);
                
                $this -> db -> query("SELECT COUNT(*) FROM interconnections WHERE peerURL='". $this->db->escape_string($peer['APIurl']). "';");

                if ($this -> db -> errno()) echo $this->db->error ();
                elseif ($this -> db -> result() <= 0) {
                    $CDNid = $this -> peerSetOffer ($peer['APIurl']);
                        
                    if ($CDNid) {
                        $this->db->insertIgnore("interconnections",
                                array (
                                    'CDNid' => $CDNid,
                                    'peerURL' => $peer['APIurl'],
                                    'localStatus' => 'offer'     
                                )
                        );
                                
                        if ($this->db->errno()) echo $this->db->error () . PHP_EOL;
                    }
                }
            }
        }
        

        $this -> db -> query("SELECT peerURL FROM interconnections WHERE localStatus='offer';");

        if ($this -> db -> errno()) echo $this->db->error ();
        else {
            while ( $peer = $this -> db -> fetch_assoc() ) {
                $this -> peerSetCapabilities($peer['peerURL']);
                $this -> peerSetFootprint   ($peer['peerURL']);
            }
        }
    }

    function processContentForTransfer() {
        $this ->addAllPeers();
        
        $this -> db -> query("SELECT peerURL FROM interconnections WHERE peerStatus='complete';");

        if (mysql_errno()) echo mysql_error ();
        else {
            while ( $peer = $this -> db -> fetch_assoc() ) {
                $contentID="content1";
                $metadata="metadata=dfasdasd";
                
                $this -> peerSetContentBasicMetadata($peer['peerURL'],$contentID,$metadata);
            }
        }

    }

    function cron () {
        header('Content-type: text/plain');
        $this -> processLocalOffers();
        $this -> processContentForTransfer();
    }
    
    function processAPI($methods,$data) {
//        var_dump($this -> config, $_SERVER);
        $result=call_user_func_array(array($this, $methods[0]), $data);
        
        if (is_array($result) && count($result)) echo http_build_query($result);
    }
    
    function setCapabilities ($CDNid,$capabilities) {
        $this -> db_connect();

        $sql="SELECT interconID FROM interconnections WHERE CDNid='". mysql_escape_string($CDNid) ."';";
        $qr = mysql_query ($sql,  $this -> dbConn);

        if (mysql_errno()) echo mysql_error () . PHP_EOL;
        else {
            $interconID = mysql_result($qr,0);
//            echo "found interconnection: $interconID \n";
            if (!is_numeric($interconID)) echo "interconID is not numeric".PHP_EOL;
            else {       
                $qr = mysql_query("DELETE FROM peerCapabilities WHERE interconID=$interconID;",$this -> dbConn);       
                if (mysql_errno()) echo mysql_error ().PHP_EOL;        
        
                $qr = mysql_query("INSERT INTO peerCapabilities SET interconID=$interconID, name='', value='".  mysql_escape_string($capabilities)  ."';",$this -> dbConn);       
                if (mysql_errno()) echo mysql_error ().PHP_EOL;
                else {            
                    $this -> updateCompleteStatus($interconID);
                    return true;
                }
            }
        }

        return false;
    }
    
    function updateFootprint($interconID, $subnet) {
            list($subnetIP,$mask)=explode("/",$subnet);
            $subnetLong=  ip2long($subnetIP);
            $maskLong=0xFFFFFFFF-(pow(2, 32-$mask)-1);
            
            $sql="INSERT INTO peerFootprints SET interconID=$interconID". 
                                               ",subnet='".  mysql_escape_string($subnetLong) ."'".
                                               ",mask='".  mysql_escape_string($maskLong)  ."'".
                                               ",subnetIP='".  mysql_escape_string($subnetIP) ."'".
                                               ",maskNr='".  mysql_escape_string($mask)  ."'".
                                               ";";
            mysql_query($sql);
            if (mysql_errno()) echo mysql_error () . PHP_EOL;
    }
    
    function setFootprint($CDNid,$footPrint) {
        $this -> db_connect();
        
//        echo "setting footprints".PHP_EOL;

        $sql="SELECT interconID FROM interconnections WHERE CDNid='". mysql_escape_string($CDNid) ."';";
        $qr = mysql_query ($sql,  $this -> dbConn);

        if (mysql_errno()) echo mysql_error () . PHP_EOL;
        else {
            $interconID = mysql_result($qr,0);
//            echo "found interconnection: $interconID \n";
            if (!is_numeric($interconID)) echo "interconID is not numeric".PHP_EOL;
            else {
                $qr = mysql_query("DELETE FROM peerFootprints WHERE interconID=$interconID;");
                if (mysql_errno()) echo mysql_error () . PHP_EOL;
                else {
                    $subnets=explode(",",$footPrint);
                    foreach ($subnets as $subnet) {
                        $this ->updateFootprint($interconID,$subnet);
                    }
            
                    $this -> updateCompleteStatus($interconID);
            
                    return true;
                }
            }
        }

        return false;
    }

    function setOffer($CDNid,$peerURL) {
        $this -> db_connect();
        
        mysql_query("INSERT INTO interconnections SET CDNid='". mysql_escape_string($CDNid). "', peerURL='". mysql_escape_string($peerURL). "', peerStatus='offer' ".
                        "ON DUPLICATE KEY UPDATE peerStatus='offer';");        

        if (mysql_errno()) echo mysql_error () . PHP_EOL;
        else {
            return array("CDNid" => $this -> config['local']['CDNid']);
        }
        
        return "";
    }
    
    function setOfferLocalStatus($CDNid,$status) {
        $this -> db_connect();
        
        $qr = mysql_query("UPDATE interconnections SET localStatus='".  mysql_escape_string($status). "' WHERE CDNid = '". mysql_escape_string($CDNid) . "';", $this -> dbConn);       
        if (mysql_errno()) echo mysql_error ().PHP_EOL;
        else return true;
        
        return false;        
    }

    function setContentBasicMetadata($CDNid,$contentID,$metadata) {
        $this -> db_connect();

        $sql="SELECT interconID FROM interconnections WHERE CDNid='". mysql_escape_string($CDNid) ."';";
        $qr = mysql_query ($sql,  $this -> dbConn);

        if (mysql_errno()) echo mysql_error () . PHP_EOL;
        else {
            $interconID = mysql_result($qr,0);
        
            $qr = mysql_query("INSERT IGNORE INTO content SET contentID='".  mysql_escape_string($contentID). "', interconID=$interconID;", $this -> dbConn);       
            if (mysql_errno()) echo mysql_error ().PHP_EOL;
            else return true;
        }
        
        return false;                
    }

    function updateCompleteStatus($interconID) {
        $this -> db_connect();
        
        $qr = mysql_query("SELECT COUNT(*) FROM peerFootprints WHERE interconID=$interconID;", $this -> dbConn);
        if (mysql_errno()) echo mysql_error () . PHP_EOL;
        elseif (mysql_num_rows($qr) && ($cnt=mysql_result($qr,0)) >0 ) {
            $qr = mysql_query("SELECT COUNT(*) FROM peerCapabilities WHERE interconID=$interconID;", $this -> dbConn);       
            if (mysql_errno()) echo mysql_error ().PHP_EOL;
            elseif (  mysql_num_rows($qr) && ($cnt=mysql_result($qr,0)) > 0  ) {
                $qr = mysql_query("UPDATE interconnections SET peerStatus='complete' WHERE interconID=$interconID;", $this -> dbConn);       
                if (mysql_errno()) echo mysql_error ().PHP_EOL;
                else {
                    $this->peerSetOfferLocalStatus($interconID, 'complete');
                    return true;
                }
            }
        }
        
        return false;
    }
}

?>
