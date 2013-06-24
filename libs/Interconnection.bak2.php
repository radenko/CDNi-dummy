<?

if (!defined("MODE_DEBUG")) define("MODE_DEBUG",true);

require_once("DB.php");

class InterconPeer {
    protected $_client=null;
    protected $_localStatus=null;
    protected $_peerStatus=null;
    protected $_interconID=null;
    
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
        $this->_client = new SoapClient(
                null,
                array('location' => $url,
                      'uri'      => $url,
                      'trace' => 1
                )
            );
        $this->setParams($params);
    }
    
    function setParams($params) {
        $params = array_change_key_case($params);
     
        if (isset($params['localStatus'])) $this -> _localStatus = $params['localstatus'];
        if (isset($params['peerStatus'])) $this -> _peerStatus = $params['peerstatus'];
        if (isset($params['interconID'])) $this -> _interconID = $params['interconid'];        
    }
    
    function __call($name, $arguments) {
        if (MODE_DEBUG) {
            echo "Calling '$name' with";
            print_r($arguments);
            echo "<br/>";
        }
        
        $res = $this->_client->__call($name,$arguments);
        
        if (MODE_DEBUG) {
            echo "Response:";
            var_dump($res);
            echo "<br/>";
        }
        
        return $res;
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

    function addPeer($params,$replace=false) {
        $lparams = array_change_key_case($params);
        $id = null;
        $url = null;
        
        if (isset($lparams['cdnid']))
            $id = $lparams['cdnid'];
        if (isset($lparams['id']))
            $id = $lparams['id'];

        if (isset($lparams['peerurl']))
            $url = $lparams['peerurl']; 
        if (isset($lparams['url']))
            $url = $lparams['url'];
        if (isset($lparams['apiurl']))
            $url = $lparams['apiurl'];
        
        $this -> addPeer2($id,$url,$params,$replace);
    }
    
    function addPeer2 ($id,$url,$params,$replace=false) {
        if (isset($params['interconID']))
            $interconID = $params['interconID'];
                
        if (isset($url) && !is_null($url) && $url && isset($this -> clients[$url]))
            $client = $this -> clients[$url];
        if (isset($id)  && !is_null($id)  && $id  && isset($this -> clients[$id]))
            $client = $this -> clients[$id];
        if (isset($interconID)  && !is_null($interconID)  && $interconID  && isset($this -> clients[$interconID]))
            $client = $this -> clients[$interconID];
        
        if (!isset($client) || !is_object($client) || $replace) {
            $params['peerURL'] = $url;
            if (isset($id))
                $params['CDNid'] = $id;
            
            $client = new InterconPeer($params);
        }
                
        if (isset($url) && !is_null($url) && $url)
            $this -> clients[$url] = $client;
        if (isset($id)  && !is_null($id)  && $id)
            $this -> clients[$id] = $client;
        if (isset($params['interconID']))
            $this -> clients[$params['interconID']] = $client;
    }
    
    function addAllPeers() {
        $this -> db -> select('interconnections');
            
        while ($item = $this -> db -> fetch_assoc()) {
                $this -> addPeer($item);
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
        $this->clients[$peerURL]->setCapabilities(
                $this -> config['id'],
                $this -> config['capabilities']
        );
    }
    
    function peerSetFootprint($peerURL) {
        echo "Sending footprint to $peerURL".PHP_EOL;
        $this->clients[$peerURL]->setFootprint(
                $this -> config['id'],
                implode(",",$this -> config['footprint'])
        );
    }
    
    function peerSetOfferLocalStatus($interconID,$status) {     
        $this->db->query("SELECT CDNid, peerURL FROM interconnections WHERE interconID='". $this->db->escape_string($interconID) ."';");       
        if ($this->db->errno()) echo $this->db->error ();
        else {
            $peer = $this->db->fetch_assoc();
            $this->addPeer($peer);
            
            $peerURL = $peer['peerURL'];
            if (defined('MOD_DEBUG')) echo "Sending local status ($status) to $peerURL".PHP_EOL;

            $this->clients[$peerURL]->setOfferLocalStatus($this -> config['id'],  $status);
        }        
    }

    function peerSetOffer($peerURL) {
        if (defined('MOD_DEBUG')) echo "Sending offer to $peerURL".PHP_EOL;
        
        $result = $this -> clients[$peerURL] -> setOffer(
            $this -> config['id'],
            $this -> config['APIurl']
        );
        
        return $result;
    }
    
    function peerSetContentBasicMetadata($peerURL,$contentID,$metadata) {
        if (defined('MOD_DEBUG')) echo "Sending SetContentBasicMetadata to $peerURL".PHP_EOL;
        
        $fields=array(
            'CDNid' => $this -> config['id'],
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
                
                $this -> db -> select('interconnections','COUNT(*)',array('WHERE' => "peerURL='". $this->db->escape_string($peer['APIurl']). "'"));

                if ($this -> db -> errno()) echo $this -> db -> error ();
                elseif ($this -> db -> result() <= 0) {
                    $this -> addPeer($peer);
                    $peerObj = $this -> peerSetOffer ($peer['APIurl']);
                        
                    if (isset($peerObj) && !is_null($peerObj) && $peerObj !== false) {
                        $peer['id'] = $peerObj['CDNid'];
                        $this->addPeer($peer);

                        $this->db->insertIgnore("interconnections",
                                array (
                                    'CDNid' => $peer['id'],
                                    'peerURL' => $peer['APIurl'],
                                    'localStatus' => 'offer'     
                                )
                        );
                                
                        if ($this->db->errno()) echo $this->db->error () . PHP_EOL;
                    }
                }
            }
        }
        

        $this -> db -> select('interconnections', array('interconID', 'CDNid', 'peerURL', 'localStatus', 'peerStatus'), array('WHERE' => "localStatus='offer'"));

        if ($this -> db -> errno()) echo $this->db->error ();
        else {
            while ( $peer = $this -> db -> fetch_assoc() ) {
                $this -> addPeer($peer);                
                $this -> peerSetCapabilities($peer['peerURL']);
                $this -> peerSetFootprint   ($peer['peerURL']);
            }
        }
    }

    function getAllInterconnections() {
        $intercons=array(); 
        $this -> db -> select(' interconnections','*',array('WHERE' => "peerStatus='complete'"));
        while ($intercon = $this->db->fetch_assoc()) {
            array_push($intercons,$intercon);
        }
        return $intercons;
    }
    
    function processContentForTransfer() {
        $this -> addAllPeers();
        $intercons=$this->getAllInterconnections();

        
        $this -> db -> select('content');
        while($content = $this->db->fetch_assoc()) {
            foreach ($intercon as $intercons)
                $this -> distributeContent($content,$intercon);  
        }
    }

    function cron () {
        header('Content-type: text/plain');
        $this -> processLocalOffers();
        $this -> processContentForTransfer();
    }
    
    function processAPI($methods,$data) {
        $result=call_user_func_array(array($this, $methods[0]), $data);
        
        if (is_array($result) && count($result)) echo http_build_query($result);
    }
    
    function setCapabilities ($CDNid,$capabilities) {
        $sql="SELECT interconID FROM interconnections WHERE CDNid='". $this->db->escape_string($CDNid) ."';";
        $this-> db ->query ($sql);

        if ($this-> db ->errno()) echo $this -> db->error () . PHP_EOL;
        else {
            $interconID = $this -> db -> result($qr,0);
            if (!is_numeric($interconID)) echo "interconID is not numeric".PHP_EOL;
            else {       
                $qr = $this->db->query("DELETE FROM peerCapabilities WHERE interconID=$interconID;");       
                if ($this->db->errno()) echo $this->db->error ().PHP_EOL;        
        
                $qr = $this->db->query("INSERT INTO peerCapabilities SET interconID=$interconID, name='', value='".  $this->db->escape_string($capabilities)  ."';");       
                if ($this->db->errno()) echo $this->db->error ().PHP_EOL;
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
        $sql="SELECT interconID FROM interconnections WHERE CDNid='". mysql_escape_string($CDNid) ."';";
        $this -> db -> query ($sql);

        if ($this -> db -> errno()) echo $this -> db -> error () . PHP_EOL;
        else {
            $interconID = $this -> db -> result();
//            echo "found interconnection: $interconID \n";
            if (!is_numeric($interconID)) echo "interconID is not numeric".PHP_EOL;
            else {
                $qr = $this -> db -> query("DELETE FROM peerFootprints WHERE interconID=$interconID;");
                if ($this -> db -> errno()) echo $this -> db -> error () . PHP_EOL;
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
        file_put_contents("debug",var_export($CDNid,true).var_export($peerURL,true));
        
        
        $this->db->insertUpdate("interconnections",
                array(
                    'CDNid' => $CDNid,
                    'peerURL' => $peerURL,
                    'peerStatus' => 'offer'
                )
        );
        if ($this->db->errno()) echo $this->db->error() . PHP_EOL;
        else {
            return array("CDNid" => $this -> config['id']);
        }
        
        return false;
    }
    
    function setOfferLocalStatus($CDNid,$status) {        
        $this->db->query("UPDATE interconnections SET localStatus='".  $this->db->escape_string($status). "' WHERE CDNid = '". mysql_escape_string($CDNid) . "';", $this -> dbConn);       
        if ($status == 'complete')
            $this->doInterconnectionComplete ($CDNid);

        if ($this->db->errno()) echo $this->db->error ().PHP_EOL;
        else return true;
        
        return false;        
    }

    function distributeContent($intercon, $content) {
        if (!is_array($content)) {
            $this->db->select('content','*',array('WHERE' => "contentID=$contentID"));
            if ($this->db->errno()) echo $this->db->error ().PHP_EOL;
            $content = $this->db->fetch_assoc();
        }
        
        if (!is_array($intercon)) {
            $this->db->select("interconnections","*",array('WHERE'=>"interconID=$interconID"));
            if ($this->db->errno()) echo $this->db->error ().PHP_EOL;
            $intercon = $this->db->fetch_assoc();
        }
        
        $internalContentID = $content['internalContentID'];
        $contentID = $content['contentID'];
        $interconID = $intercon['interconID'];

        $metadata=array_intersect_key($content, array('title'=>1,'description'=>1));
        $this->db->select("contentMetadata","*",array("WHERE"=>"internalContentID=$internalContentID"));
        while ($row = $this->db->fetch_assoc()) {
            $metadata[$row['name']]=$row['value'];
        }
        
        $this->clients[$interconID]->setContentBasicMetadata($intercon['CDNid'],$contentID,$metadata);
    }

    
    function setContentBasicMetadata($CDNid,$contentID,$metadata) {
        $this->db->select("interconnections","*",array('WHERE'=>"CDNid='".$this->db->escape_string($CDNid)."'"));
        if ($this->db->errno()) echo $this->db->error ().PHP_EOL;
        else {
            $intercon = $this->db->fetch_assoc();
            $interconID = $intercon['interconID'];

            $params = array(
                'interconID' => $interconID,
                'contentID' => $contentID,
                'title' => $metadata['title'],
            );
            
            if (isset($metadata['description']) && $metadata['description']) {
                $params['description'] = $metadata['description'];
            }
            
            unset($metadata['title']);
            unset($metadata['description']);
            
            $internalContentID = $this->db->insertUpdate("content",$params,true);
            
            foreach($metadata as $name=>$value) {
                $this->db->insertUpdate('contentMetadata', array(
                    'internalContentID' => $internalContentID,
                    'name' => $name,
                    'value' => $value
                ));
            }
        }
        
        return false;                
    }

    function updateCompleteStatus($interconID) { 
        $this->db->query("SELECT COUNT(*) FROM peerFootprints WHERE interconID=$interconID;");
        if ($this->db->errno()) echo $this->db->error () . PHP_EOL;
        elseif ($this->db->num_rows($qr) && ($cnt=$this->db->result()) >0 ) {
            $qr = $this->db->query("SELECT COUNT(*) FROM peerCapabilities WHERE interconID=$interconID;");       
            if ($this->db->errno()) echo $this->db->error ().PHP_EOL;
            elseif (  $this->db->num_rows($qr) && ($cnt=$this->db->result()) > 0  ) {
                $qr = $this->db->query("UPDATE interconnections SET peerStatus='complete' WHERE interconID=$interconID;");       
                if ($this->db->errno()) echo $this->db->error ().PHP_EOL;
                else {
                    $this->peerSetOfferLocalStatus($interconID, 'complete');
                    return true;
                }
            }
        }
        
        return false;
    }
    
    function doInterconnectionComplete ($cdnID){
        $this->db->select("interconnections",'*',array("WHERE"=>"CDNid='$cdnID'"));        
        $this->onInterconnectionComplete ($this->db->fetch_assoc());
    }
    
    function onInterconnectionComplete ($interconnection) {
    }
    
    function setStaticData($interconID,$name,$value) {                    
        return $this->db->insertUpdate('staticData',array (
                'interconID' => $interconID,
                'Name' => $name,
                'Value' => $value
            ));
    }
    
    function getStaticData($interconID,$name) {
        $this->db->select('staticData',"Value",array('WHERE'=>"Name = '".$this->db->escape_string($name)."' AND interconID=$interconID"));
        return $this->db->fetch_result();
    }
}

?>
