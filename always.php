<?php
    if (!isset($_SERVER['DOCUMENT_ROOT'])) $_SERVER['DOCUMENT_ROOT']='/var/www';
    require_once('config.php');
    
    if (isset($config['libDir'])) set_include_path(get_include_path().PATH_SEPARATOR.$config['libDir']);
    require_once 'Interconnection.php';
    require_once 'dummyCDN.php';

    $intercon = new Interconnection($config['CDN']);
    $iCDN = new dummyCDN($config['CDN']);
    $intercon -> iCDN = $iCDN;
?>
