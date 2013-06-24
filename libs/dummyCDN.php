<?php
    require_once('iCDN.php');

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of dummyCDN
 *
 * @author barbarka
 */
class dummyCDN implements iCDN{
    protected $config;
    protected $db;
    
    public function __construct($config) {
        $this->config = $config;
        $this -> db = new DB($this->config);
    }
    
    //put your code here
    public function getAllContent(){
        $result = array();
        
        $contentRes = $this->db->select('content',array('contentID', 'title', 'description', 'file'));
        while ($content = $this->db->fetch_assoc($contentRes)){
            $contentID = $content['contentID'];
            $content['contentID']='content_'.$contentID;
            $content['url']='http://147.175.15.42/'.str_replace('/var/www/','',$content['file']);
            unset($content['file']);          
         
            $metadataRes = $this->db->select('contentMetadata','*',array("WHERE"=>"contentID=$contentID"));
            while ($metadata = $this->db->fetch_assoc($metadataRes)) {
                $content[$metadata['name']]=$metadata['value'];
            }
            
            array_push($result,$content);
        }
        
        return $result;
    }
}

?>
