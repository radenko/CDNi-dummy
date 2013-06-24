<?php
    class ciscoCDSClient {
        protected $config;
        
        private function makeRequest($taskAPI,$action,$params) {
            $params['action']=$action;
            
            $urlString = "https://{$this -> config ['host']}:{$this -> config ['port']}/servlet/$taskAPI?"  .  http_build_query($params);// + "&param=" + channelId_;

            $response = http_get(
                    $urlString,
                    array (
                            'httpauth' => $this -> config ['userName'] . ':' . $this -> config ['password'],
                            'httpauthtype' => HTTP_AUTH_BASIC
                    ));
            $responseObj=http_parse_message($response);// var_dump($responseObj);
    
            if ($responseObj -> responseCode != 200 ) return false;
            
            $body=$responseObj->body;
            
            $xml=new SimpleXMLElement($body);
            
            return $xml;
        }

        public function createDeliveryService($name, $contentOriginID, $params = array()) {
        /*    
            [&weakCert=<true | false>][&skipEncrypt= <true | false>][&priority=<high | medium | low>]
            [&failoverIntvl=<20 | 30 | 40 | 50 | 60 | 70 | 80 | 90 | 100 | 110 | 120>][&never=<true | false>]
            [&qos=<system|0-63>][&desc=<description>][&live=<true | false>]) {
         */
        //   $params = array ();
            $params ['deliveryService'] = $name;
            $params ['contentOrigin'] = $contentOriginID;
           
            return $this->makeRequest('com.cisco.unicorn.ui.ChannelApiServlet', 'createDeliveryService', $params);
        }
    
        public function createContentOrigin($name, $origin_server_IP_or_domain, $fqdn, $params = array()) {
/*        
        https://<cdsmIpAddress>:8443/servlet/com.cisco.unicorn.ui.ChannelApiServlet?action=
createContentOrigin&name=<contentorigin_name>&origin=<origin_server_IP_or_domain>
&fqdn=<fqdn>[&contentBasedRouting=<true | false>][&nasFile=<FileInfo_id | n
 */          
            $params ['name'] = $name;
            $params ['origin'] = $origin_server_IP_or_domain;
            $params ['fqdn'] = $fqdn;
           
            return $this->makeRequest('com.cisco.unicorn.ui.ChannelApiServlet', 'createContentOrigin', $params);
        }
        
        public function __construct ($config) {            
            $this -> config = $config;
        }
        
        
    }

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
?>
