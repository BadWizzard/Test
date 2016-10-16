<?php 
date_default_timezone_set('America/Los_Angeles');
class RentalPrice {
	var $json_array;
	var $days;
	var $rental_price;

	function __construct($json_path) {
		$json_file = file_get_contents($json_path);
		$this->json_array = json_decode($json_file,true);
		$rows = count($this->json_array["rentals"]);
		for ($i = 0; $i < $rows; $i++) 
			{
				$this->days[] = $this->Count_days($i);
				$this->rental_price[] = $this->Count_rental_price($i);
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
		
		$multidays = $price_per_day * $this->days[$i];

		return $multidays;
	}

	function Count_rental_price($i) {
		$perkm = $this->Count_price_per_km($i);
		$perday = $this->Count_price_per_day($i);

		$price = $perkm + $perday;

		return $price;
	}
}

function To_json($obj) {
	$rows = count($obj->json_array["rentals"]);
	for ($i = 0; $i < $rows; $i++) 
	{
		$multicost[] = array('id' => $i+1, 'price' => $obj->rental_price[$i]);
	}
	unset($rows);
	print_r($multicost);
	$file = "output.json";
	$fp = fopen($file, "w");
	fwrite($fp, json_encode(array("rentals" => $multicost)));
	fclose($fp);
	}

	$car_price = new RentalPrice('data.json');
	To_json($car_price);
 ?>
