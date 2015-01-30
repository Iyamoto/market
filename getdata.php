<?php
require_once 'conf.php';
echo "start\n";

$MaxJumps = 14;
//$JumpProfit = 500000;
$JumpProfit = 1000000;
//$MinProfit = 3000000;
$MinProfit = 20000000;

$stations = array('Jita'=>'60003760','Hek'=>'60005686','Amarr'=>'60008494','Rens'=>'60004588','Dodixie'=>'60011866'); 

//Get IDs
$ids = file2array('inf\\ids.txt');
//var_dump($ids);
/*
foreach($ids as $id=>$name){
	if(!isset($Volumes[$id])) echo "$id;\n";
}	
exit;*/

echo "Name;SRC-DST;ISK per jump;Profit;Jumps;Quantity;Volume;Money;BuyPrice;SellPrice;MaxPrice;Station\n";
foreach($ids as $id=>$name){
	if (strpos($id, '#')!==false) {
		continue;
	}	
	$path = 'data\\'.$id.'.xml';
	$XMLData = GetXml($path,$id,$time);
	$xml = simplexml_load_string($XMLData);
	if ($xml===false) continue;
	
	//Get prices
	$prices['Jita']['buy'][$id] = GetMaxBuyPrice($xml,$stations['Jita']);
	$prices['Hek']['buy'][$id] = GetMaxBuyPrice($xml,$stations['Hek']);
	$prices['Amarr']['buy'][$id] = GetMaxBuyPrice($xml,$stations['Amarr']);
	$prices['Rens']['buy'][$id] = GetMaxBuyPrice($xml,$stations['Rens']);
	$prices['Dodixie']['buy'][$id] = GetMaxBuyPrice($xml,$stations['Dodixie']);
	$prices['All']['buy'][$id] = GetMaxBuyPriceAll($xml);
	//var_dump($prices);
	
	if ($xml === false) {
		echo "Failed loading XML: ";
		foreach(libxml_get_errors() as $error) {
			echo "<br>", $error->message;
		}
	} else {
		foreach($xml->quicklook->sell_orders->order as $order){
			if($order->security>0.4){
				$tmp = explode(' ',trim($order->station_name));
				$star = $tmp[0];
				if (in_array($star, $badsystems)) continue;
				
				$maxq = round ($MaxVolume / $Volumes[$id]);
				if ($order->vol_remain > $maxq) $q = $maxq;
				else $q = $order->vol_remain;
				
				$maxq = round ($MaxISK / (float)$order->price);
				if($maxq>$q) $maxq=$q;
				if ($order->vol_remain > $maxq) $q = $maxq;
				else $q = $order->vol_remain;
				
				$sum = $q*(float)$order->price;
				
				//if($id==30248) var_dump($order);
				
				foreach($stations as $system=>$stationid){
					$profit = round($k1*($q*(float)$prices[$system]['buy'][$id])-$sum);
					$jumps = GetDistance($system,$star);
					if(($jumps==0) or ($jumps>$MaxJumps) or ($profit<$MinProfit)) continue;
						
					
					$rez = GetProfit($xml,$stationid,(float)$order->price,(string)$q);
					/*
					if($id==23025) var_dump($order);
					if($id==23025) var_dump($q);
					if($id==23025) var_dump($rez);
					*/
					
					$Sell = $rez['realprofit'];
					$q = $rez['q'];
					$sum = $q*(float)$order->price;
					$realProfit = round($k1*$Sell-$sum);
									
					if($realProfit<$MinProfit) continue;
					
					$proj = round($realProfit/$jumps);
					if($proj>$JumpProfit) {
						$vol = round($q*$Volumes[$id]);
						echo $name.";".$star.' -> '.$system.";".$proj.";".$realProfit.";".$jumps.";".$q.";".$vol.";".$sum.";".$order->price.";".$prices[$system]['buy'][$id].";".$prices['All']['buy'][$id].";".$order->station_name."\n";
						//print_r($order);
					}
				}
			}
		}
	}
}

?>