<?php
require_once 'func.php';

$tax = 0.009;
$k1 = 1 - $tax;
$MaxVolume = 372000;
$MaxISK = 2020000000;
//        .mln......

$time = 12;

//Bad star systems (low sec between)
$path='inf\\badsystems.txt';
$tmp = file_get_contents($path);
if (strstr($tmp,'﻿')) $tmp = mb_strcut($tmp,3);
$badsystems = explode("\n",$tmp);

//Get distances db
$path='inf\\jumps.txt';
$csv = file_get_contents($path);
$lines = explode("\n",$csv);
foreach($lines as $line){
	$keys = explode(";",$line);
	if(sizeof($keys)==3){
		$JumpsDB[$keys[0]][$keys[1]]=$keys[2];
	}	
}
//USAGE
//$jumps = GetDistance('Jita','Ashab',$JumpsDB);

//Get Volumes
$Volumes = file2array('inf\\vol.txt');

?>