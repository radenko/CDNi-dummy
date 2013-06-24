<?
    require_once 'header.php';
 
//    var_dump($_REQUEST,$_FILES);
    
    foreach ($_FILES as $file) {
	switch ($file['error']) {
		case UPLOAD_ERR_OK:
                    $cdn -> putContentFromUpload($file,$_REQUEST['title'],array('1' => 'v1'));
		break;
	}
    }
    
    require_once 'footer.php';
?>