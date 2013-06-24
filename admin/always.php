<?php
    require_once('../config.php');
    if (isset($config['libDir'])) set_include_path(get_include_path().PATH_SEPARATOR.$config['libDir']);

    require_once('CDN.php');
    $cdn = new CDN($config['CDN']);
?>
