<?php
    require_once 'always.php';
    $methods=  array_values(array_filter(explode('/',$_SERVER['PATH_INFO'])));
    
    $intercon -> processAPI($methods,$_REQUEST);
?>
