<?php
    $config=array();
    
    $config['libDir']=$_SERVER['DOCUMENT_ROOT'].'/CDNi/libs';
    $config['CDN']['id']='cdn.ngnlab.eu';
    $config['CDN']['capabilities']='Uplne secky capabilities';
    $config['CDN']['footprint']=array('10.0.1.0/24','10.0.3.0/24','10.0.5.0/24');
    $config['CDN']['APIurl']='http://147.175.15.42/CDNi/SOAP.php';
    $config['CDN']['paths']['content'] = '/var/www/cdn';

    $config['CDN']['databaseHost']='localhost';
    $config['CDN']['databaseName']='CDN';
    $config['CDN']['databaseUser']='CDN';
    
    $config['CDN']['i']=array();
    
    $config['CDN']['i']['local']['CDNid']=$config['CDN']['id'];
    $config['CDN']['i']['local']['capabilities']='Uplne secky capabilities';
    $config['CDN']['i']['local']['footprint']=array('10.0.1.0/24','10.0.3.0/24','10.0.5.0/24');
    $config['CDN']['i']['local']['APIurl']='http://212.89.230.69/CDNi/SOAP.php';
    $config['CDN']['i']['databaseHost']='localhost';
    $config['CDN']['i']['databaseName']='CDNi';
    $config['CDN']['i']['databaseUser']='CDNi';
    //$config['CDN']['i']['databasePass']='ngnlabCDNi';
    
    define ("MODE_DEBUG",true);
?>
