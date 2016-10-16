<?php 
class RentalPrice {
	var $json_array;
	var $days;
	var $driver;
	var $owner;
	var $commission;
	var $rental_price;
	var $deductible_price;
	
	function __construct($json) {
		$this->json_array = $json;
		$c = count($this->json_array["rentals"]);
		for ($i = 0; $i < 3; $i++) 
		{
			$this->days[] = $this->Count_days($i);
			$this->rental_price[] = $this->Count_rental_price($i);
			$this->deductible_price[] = $this->Count_deductible_price($i);
			$this->commission[] = $this->Count_commission($this->rental_price[$i], $i);
			$this->driver[] = $this->rental_price[$i] + $this->deductible_price[$i];
			$this->owner[] = $this->rental_price[$i] - ($this->rental_price[$i] * 0.3);
		}
	}

	function Count_days($i) {
			$d = date_diff(new DateTime($this->json_array["rentals"][$i]["end_date"]), new DateTime($this->json_array["rentals"][$i]["start_date"]));
			$days=$d->format('%d%');
				
			return ++$days;
		}

	function Count_price_per_km($i) {
		if($this->json_array["rentals"][$i]["car_id"] == 1)
			{
				$price_per_km = $this->json_array["cars"][0]["price_per_km"];
			}
			else
			{
				$price_per_km = $this->json_array["cars"][1]["price_per_km"];	
			}
			
		$distance = $this->json_array["rentals"][$i]["distance"];

		//цена за пройденные км
		$multikm = $price_per_km * $distance;

		return $multikm;
	}

	function Count_price_per_day($i) {
		if($this->json_array["rentals"][$i]["car_id"] == 1)
			{
				$price_per_day = $this->json_array["cars"][0]["price_per_day"];
			}
			else
			{
				$price_per_day = $this->json_array["cars"][1]["price_per_day"];	
			}
		
		$discontdays30 = 6;
		$discontdays10 = 3;
			
		if($this->days[$i] > 10)
		{
			$discontdays50 = $this->days[$i] - 10;
			$discont = ($price_per_day * 0.5) * $discontdays50 + ($price_per_day * 0.3) * $discontdays30 + ($price_per_day * 0.1) * $discontdays10;
		}
		elseif ($this->days[$i] > 4) 
		{
			$discontdays30 = $this->days[$i]-4;
			$discont = ($price_per_day * 0.3) * $discontdays30 + ($price_per_day * 0.1) * $discontdays10;

		}
		elseif ($this->days[$i] > 1) 
		{
			$discontdays10 = $this->days[$i]-1;
			$discont = ($price_per_day * 0.1) * $discontdays10;
		}
		$multidays = $price_per_day * $this->days[$i] - $discont;

		return $multidays;
	}

	function Count_commission($rental_price,$i) {
		$com = $rental_price * 0.3;
		$insurance_fee = $com / 2;
		$assistance_fee = $this->days[$i] * 100;
		
		$drivy_fee = $com - $insurance_fee - $assistance_fee + $this->deductible_price[$i];
		$commission = array('insurance_fee' => $insurance_fee, 'assistance_fee' => $assistance_fee, 'drivy_fee' => $drivy_fee);

		return $commission;
	}

	function Count_deductible_price($i) {
		if($this->json_array["rentals"][$i]["deductible_reduction"] == true)
		{
			$deductible = $this->days[$i] * 400;
		}
		else
		{
			$deductible = 0;
		}
		return $deductible;
	}

	function Count_rental_price($i) {
			$km = $this->Count_price_per_km($i);
			$day = $this->Count_price_per_day($i);
			$price =  $km + $day;
		return $price;
	}
}

function Actors($i,$obj1,$obj2) {
			for ($a=0; $a < 5; $a++) 
			{ 
				$type = 'credit';
			 	switch ($a) 
			 	{
				case 0:
					$who = 'driver';
					$type = 'debit';
					$amount = abs($obj2->driver[$i] - $obj1->driver[$i]);
					break;
				case 1:
					$who = 'owner';
					$amount = abs($obj2->owner[$i] - $obj1->owner[$i]);
					break;
				case 2:
					$who = 'insurance';
					$amount = abs($obj2->commission[$i]["insurance_fee"] - $obj1->commission[$i]["insurance_fee"]);
					break;
				case 3:
					$who = 'assistance';
					$amount = abs($obj2->commission[$i]["assistance_fee"] - $obj1->commission[$i]["assistance_fee"]);
					break;
				case 4:
					$who = 'drivy';
					$amount = abs($obj2->commission[$i]["drivy_fee"] - $obj1->commission[$i]["drivy_fee"]);
					break;
				}
				$actors[] = array('who' => $who, 'type' => $type, 'amount' => $amount);
			}
			return $actors; 
	}
	
//создание массива и запись в файл
	function To_json($json,$obj1,$obj2) {
		$y=0;
		for ($i=0; $i < 3; $i++) 
		{ 
			if($json["rentals"][$i]["id"] == $json["rental_modifications"][$y]["rental_id"])
			{
			$multicost[] = array('id' => $i+1, 'rental_id' => $i+1, 'actions' => Actors($i,$obj1,$obj2));
			$y++;
			}
		}
		unset($y);
		print_r($multicost);
		$file = "output.json";
		$fp = fopen($file, "w");
		fwrite($fp, json_encode(array("rentals" => $multicost)));
		fclose($fp);
	}

$json_file = file_get_contents('data.json');
$json = json_decode($json_file,true);

//объект до изменений
$car_price1 = new RentalPrice($json);

//внедрение изменений в rentals
$y = 0;
for($i = 0; $i < 3; $i++)
{
	if($json["rentals"][$i]["id"] == $json["rental_modifications"][$y]["rental_id"])
	{
		if(!is_null($json["rental_modifications"][$y]["start_date"]))
		{
			$json["rentals"][$i]["start_date"] = $json["rental_modifications"][$y]["start_date"];
		}

		if(!is_null($json["rental_modifications"][$y]["end_date"]))
		{
			$json["rentals"][$i]["end_date"] = $json["rental_modifications"][$y]["end_date"];
		}

		if(!is_null($json["rental_modifications"][$y]["distance"]))
		{
			$json["rentals"][$i]["distance"] = $json["rental_modifications"][$y]["distance"];
		}
		$y++;
	}
}
unset($y);

//объект после изменений
$car_price2 = new RentalPrice($json);
//запись в файл
To_json($json,$car_price1,$car_price2);
		




 ?>
