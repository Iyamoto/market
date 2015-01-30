<?php

function file2lp($filename){
	if(file_exists($filename)){
		$tmp = file_get_contents($filename);
		if($tmp){
			if (strstr($tmp,'﻿')) $tmp = mb_strcut($tmp,3);
			$keys = explode("\r\n",$tmp);
			if (sizeof($keys)==1) $keys = explode("\n",$tmp);
			foreach($keys as $str){
				if(mb_strlen(trim($str))>0){
					$ex = explode(';',$str);
					$id = $ex[0];
					$data[$id]['name'] = $ex[1];
					$data[$id]['q'] = $ex[2];
					$data[$id]['cost-lp'] = $ex[3];
					$data[$id]['cost-isk'] = $ex[4];
					$data[$id]['src-id'] = $ex[5];
				}	
			}
			return $data;
		} else return false;
	} else return false;
}

function file2array($filename){
	if(file_exists($filename)){
		$tmp = file_get_contents($filename);
		if($tmp){
			if (strstr($tmp,'﻿')) $tmp = mb_strcut($tmp,3);
			$keys = explode("\r\n",$tmp);
			if (sizeof($keys)==1) $keys = explode("\n",$tmp);
			foreach($keys as $str){
				if(mb_strlen(trim($str))>0){
					$ex = explode(';',$str);
					if(sizeof($ex)==1) $data[0]=$ex[0];
					else $data[$ex[0]]=$ex[1];
				}	
			}
			return $data;
		} else return false;
	} else return false;
}

function GetDistance($src,$dst){
	global $JumpsDB;
	if (isset($JumpsDB[$src][$dst])){
		$jumps = $JumpsDB[$src][$dst];
	} else {	
		$jumps = GetDistanceAPI($src,$dst);
		$JumpsDB[$src][$dst]=$jumps;
		sleep(1);
	}	
	return trim($jumps);
}

function GetDistanceAPI($src,$dst,$path='jumps.txt'){
	$url = 'http://api.eve-central.com/api/route/from/'.$src.'/to/'.$dst;
	$rest = file_get_contents($url);
	$decoded = json_decode($rest);
	$jumps = sizeof($decoded);
	file_put_contents($path,$src.';'.$dst.';'.$jumps."\n", FILE_APPEND);
	return $jumps;
}

function GetMaxBuyPriceAll($xml){
	$maxprice = 0;
	foreach($xml->quicklook->buy_orders->order as $order){
		if($order->security>0.4){
			$price = (string)$order->price;
			if($price>$maxprice) {
				$maxprice = $price;
			}
		}
	}
	return $maxprice;
}

function GetXml($path,$id,$time=4){
	$url='http://api.eve-central.com/api/quicklook?typeid='.$id.'&sethours='.$time;
	if(file_exists($path)==false){
		$data = file_get_contents($url);
		file_put_contents($path,$data);
		sleep(2);
	} else {
		$data = file_get_contents($path);
	}	
	return $data;
}

function GetMaxBuyPrice($xml,$stationid){
	$maxprice = 0;
	foreach($xml->quicklook->buy_orders->order as $order){
		if($order->station==$stationid){
			$price = (string)$order->price;
			if($price>$maxprice) {
				$maxprice = $price;
			}
		}
	}
	return $maxprice;
}

function GetProfit($xml,$stationid,$baseprice,$maxq){
	$maxprice = 0;
	$minprice = $baseprice*1.01;
	$rez['realprofit'] = 0;
	$rez['q'] = $maxq;
	$rez['price'] = 0;
	foreach($xml->quicklook->buy_orders->order as $order){
		//if($maxq==1072654) var_dump($order);
		if($order->station==$stationid){
			$price = (string)$order->price;
			if($price>$minprice) {
				$q = (string)$order->vol_remain;
				$data[$price]=$q;
			}
		}
	}
	if (isset($data)) {
		$realprofit = 0;
		krsort($data);	
		//var_dump($data);
		reset($data);
		$s = sizeof($data);
		$i=0;
		while($maxq>0){
			$i++;
			if($i>$s) break;
			//if($maxq==1072654) var_dump($data);
			$price = key($data);
			$q = $data[$price];
			next($data);
			//if($maxq==1072654) echo "$price\t$q\n";
			if($q<$maxq){
				$realprofit += $q*$price;
				$maxq = $maxq - $q;
			} else {
				$realprofit += $maxq*$price;
				$maxq=0;
			}			
		}
		//print_r($realprofit);
		$rez['realprofit'] = $realprofit;
		$rez['q'] -= $maxq;
		$rez['price'] = $price;
		//if($maxq==1072654) var_dump($rez);
	}
	return $rez;
}

?>