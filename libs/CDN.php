<?php
require_once 'DB.php';

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CDN
 *
 * Class pre handlovanie dummy CDN
 * @author barbarka
 */
class CDN {
    protected $config;
    protected $dbConn=null;
    protected $db;
        
    function __construct($config) {
        $this->config = $config;
        
        $this->db = new DB($config);
//       parent::__construct();
    }

    function putContentFromUpload($file,$title,$metadata=array()) {
        $fileName=$this -> config['paths']['content'].'/'.$file['name'];
                
	move_uploaded_file($file['tmp_name'], $fileName);
        $contentID = $this -> db -> insert(
                "content", 
                array (
                    'file' => $fileName,
                    'title' => $title,
                ),
                true
        );
        $mValues=array();
        $mValues['contentID'] = $contentID;
            
        foreach ($metadata as $key => $value) {
            $mValues['name']  = $key;
            $mValues['value'] = $value;
            
            $this->db->insertUpdate("contentMetadata", $mValues);
        }        
    }
}

?>
