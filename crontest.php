<?php
    require_once 'always.php';
    echo "DummyCDN Cron"."<br \>";
	
	$funct = $_GET["function"];
	
	if($funct == "interconnect"){
    $intercon -> cron(); //Call cron function from interconnection.php of newly created interconnection $intercon
	}
	
	else if($funct == "reset"){
	echo "Starting reset procedure."."<br \>";
	$intercon -> reset();
	}
		
	else {
	echo "Please specify function"."<br \>";
	}
?>