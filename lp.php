<?php
require_once 'conf.php';
echo "start\n";

$MaxJumps = 10;
//$MaxLP = 352000;
$MaxLP = 100000;
$MinRatio = 850;

//$stations = array('Jita'=>'60003760','Amarr'=>'60008494'); 
$stations = array('Jita'=>'60003760'); 

//Get LP SRC IDs
$srcids = file2array('inf\\lp-src-ids.txt');

//Get LPs
$LPs = file2lp('inf\\lp-ids.txt');

echo "Price;Jumps;Quantity;Volume;Money;Station\n";
echo "Ratio;Sell-Price;Jumps;Station\n\n";
foreach($LPs as $LPid=>$LP){
	if (strpos($LPid, '#')!==false) {
		continue;
	}	
	//var_dump($LP);
	$id = $LP['src-id'];
	$name = $LP['name'];
	
	$path = 'data\\'.$LPid.'.xml';
	$XMLData = GetXml($path,$LPid,$time);
	$LPxml = simplexml_load_string($XMLData);
	if ($LPxml===false) continue;
	//$MaxPrice = GetMaxBuyPriceAll($LPxml);
	//echo "$MaxPrice\n";
	$MinProfit = $MinRatio*$MaxLP;
	//echo "$MinProfit\n";
	//echo "$name\n";
	$q = $MaxLP*$LP['q']/$LP['cost-lp'];
	//echo "$q\n";
	$MinPrice = $MinProfit/$q;
	//echo "$MinPrice\n";
	
	foreach($stations as $system=>$sid){
		foreach($LPxml->quicklook->sell_orders->order as $order){
			if($order->security>0.4){
				$tmp = explode(' ',trim($order->station_name));
				$star = $tmp[0];
				if (in_array($star, $badsystems)) continue;
				$selljumps = GetDistance($system,$star);
				if($selljumps>$MaxJumps) continue;
				$stationid = (string)$order->station;
				if(isset($hash[$stationid])) continue;
				else $hash[$stationid]=1;
				
				$rez = GetProfit($LPxml,$stationid,$MinPrice,$q);
				if($rez['realprofit']>0){
					if($rez['q']<$q) continue;
					//var_dump($rez);
					//var_dump($order->station_name);
					//exit;
	
					$path = 'data\\'.$id.'.xml';
					$XMLData = GetXml($path,$id,$time);
					$xml = simplexml_load_string($XMLData);
					if ($xml===false) continue;
	
					for($jumps=0;$jumps<$MaxJumps;$jumps++){
						$tmp = GetMinSellPrice($xml,$q,$jumps,'Jita');
						if($tmp!=false){
							$orderid = $tmp['id'];
							$sell[$orderid]=$tmp;
							$sell[$orderid]['q-need'] = $q;
							$sell[$orderid]['vol-need'] = $q*$Volumes[$id];
							$sell[$orderid]['lp-need'] = $LP['cost-lp']*$sell[$orderid]['q-need']/$LP['q'];
							$sell[$orderid]['vol'] = $sell[$orderid]['q']*$Volumes[$id];
							$sell[$orderid]['sum-need'] = $sell[$orderid]['price']*$q;
							$sell[$orderid]['exp'] = $LP['cost-isk']*$q/$LP['q']+$sell[$orderid]['sum-need'];
										
							$sell[$orderid]['profit'] = $k1*$rez['realprofit']-$sell[$orderid]['exp'];
							$sell[$orderid]['ratio'] = round($sell[$orderid]['profit']/$sell[$orderid]['lp-need']);
							$sell[$orderid]['sell-place'] = (string)$order->station_name;
							$sell[$orderid]['sell-price'] = $rez['price'];
							$sell[$orderid]['jumps'] = $selljumps;
							$sell[$orderid]['lp-name'] = $name;
						}	
					}
					PrintSell($sell);
					unset($sell);
	
					//exit;
				}	
			}
		}
		unset($hash);
	}
}

function PrintSell($orders){
	global $MinRatio;
	foreach($orders as $sell){
		if($sell['ratio']<$MinRatio) continue;
		echo $sell['lp-name']."\n";
		$str = 'BUY;'.$sell['price'].';'.$sell['jumps'].';'.$sell['q'].'('.$sell['q-need'].');'.$sell['vol'].';'.$sell['sum'].';'.$sell['station']."\n";
		echo $str;
		$str = 'SELL;'.$sell['ratio'].';'.$sell['sell-price'].';'.$sell['jumps'].';'.$sell['sell-place']."\n";
		echo $str;
		//echo $sell['sum-need']."\n";
		//echo $sell['exp']."\n";
		//echo $sell['profit']."\n";
		//echo $sell['ratio']."\n";
		echo "\n";
	}
	//echo "\n";
}

function GetMinSellPrice($xml,$q,$MaxJumps,$system='Jita'){
	$i = 0;
	$minid = 0;
	$rez[$minid] = false;
	global $badsystems;
	foreach($xml->quicklook->sell_orders->order as $order){
		if(($order->security>0.4) and ($order->vol_remain>=$q)){
			$tmp = explode(' ',trim($order->station_name));
			$star = $tmp[0];
			if (in_array($star, $badsystems)) continue;
			$jumps = GetDistance($system,$star);
			if($jumps>$MaxJumps) continue;
			$price = (string)$order->price;
			if($i==0) {
				$minprice = $price;
				$minid = (string)$order['id'];
				$rez[$minid]['id'] = $minid;
				$rez[$minid]['price'] = $price;
				$rez[$minid]['jumps'] = $jumps;
				$rez[$minid]['q'] = (string)$order->vol_remain;
				$rez[$minid]['vol'] = 0;
				$rez[$minid]['station'] = (string)$order->station_name;
				$rez[$minid]['sum'] = $rez[$minid]['price']*$rez[$minid]['q'];
				$i++;
			}
			if($price<$minprice) {
				$minprice = $price;
				$minid = (string)$order['id'];
				$rez[$minid]['id'] = $minid;
				$rez[$minid]['price'] = $price;
				$rez[$minid]['jumps'] = $jumps;
				$rez[$minid]['q'] = (string)$order->vol_remain;
				$rez[$minid]['vol'] = 0;
				$rez[$minid]['station'] = (string)$order->station_name;
				$rez[$minid]['sum'] = $rez[$minid]['price']*$rez[$minid]['q'];
				$i++;
			}
			//echo "$minprice\n";			
		}
	}
	return $rez[$minid];
}

/*	
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
*/					
					/*
					if($id==23025) var_dump($order);
					if($id==23025) var_dump($q);
					if($id==23025) var_dump($rez);
					*/
/*					
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
*/

?>