<?php 
class RentalPrice {
	var $json_array;
	var $days;
	var $rental_price;
	var $commission;

	function __construct($json_path) {
		$json_file = file_get_contents($json_path);
		$this->json_array = json_decode($json_file,true);

		$rows = count($this->json_array["rentals"]);
		for ($i = 0; $i < $rows; $i++) 
		{
			$this->days[] = $this->Count_days($i);
			$this->rental_price[] = $this->Count_rental_price($i);
			$this->commission[] = $this->Count_commission($this->rental_price[$i],$i);
		}
		unset($rows);
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

		//цена за пройденные км
		$multikm = $price_per_km * $this->json_array["rentals"][$i]["distance"];

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
			$drivy_fee = $com - $insurance_fee - $assistance_fee;
			$commission = array('insurance_fee' => $insurance_fee, 'assistance_fee' => $assistance_fee, 'drivy_fee' => $drivy_fee);

		return $commission;
	}

	function Count_rental_price($i) {
		$perkm = $this->Count_price_per_km($i);
		$perday = $this->Count_price_per_day($i);
		$price =  $perkm + $perday;

		return $price;
	}

	

}
function To_json($obj) {
	$rows = count($obj->json_array["rentals"]);
	for ($i = 0; $i < $rows; $i++) 
	{
		$multicost[] = array('id' => $i+1, 'price' => $obj->rental_price[$i],'commission' => $obj->commission[$i]);
	}
	unset($rows);
	print_r($multicost);
	$file = "output.json";
	$fp = fopen($file, "w");
	fwrite($fp, json_encode(array("rentals" => $multicost)));
	fclose($fp);
	}

//создание объекта
$car_price = new RentalPrice('data.json');
//формирование массива и запись в файл
To_json($car_price);

 ?>
