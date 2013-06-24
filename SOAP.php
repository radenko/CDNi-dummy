<?php
    require_once 'always.php';
    
    try {
        $server = new SOAPServer(
            NULL,
            array(
                'uri' => $_SERVER['REQUEST_URI']
            )
        );
 
        $server->setObject($intercon);
        $server->handle();
    }
    catch (SOAPFault $f) {
        print $f->faultstring;
    }
?>
