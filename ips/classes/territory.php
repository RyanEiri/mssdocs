<?php

class Territories {
	var	$states,
		$provinces,
		$db,
		$state_result,
		$province_result
		;

	function Territories (
		$db
		) {
			$this->states = "SELECT state_or_province_name, state_or_province_abbreviation FROM states_and_provinces WHERE state_or_province='STATE'";
			$this->provinces = "SELECT state_or_province_name, state_or_province_abbreviation FROM states_and_provinces WHERE state_or_province='PROVINCE'";
			$this->db = &$db;
			$this->state_result = $this->getresults($this->states);
			$this->province_result = $this->getresults($this->provinces);
	}

	function getresults (
		$territory
		) {
			$result = &$this->db->query($territory);
			return $result;
	}	

	function forLoop (
		$territory_array,
		$territory_size
		) {
			for ($i = 0; $i < $territory_size; $i++){
				print($territory_array[$i]['state_or_province_abbreviation']);
				if ($i != ($territory_size - 1)){
					print '|';
				}
			}
	}

	function printStates () {
		$state_array = $this->state_result->fetchArray();
		$state_array_size = $this->state_result->size();
		$this->forLoop($state_array,$state_array_size);
	}

	function printProvinces () {
		$province_array = $this->province_result->fetchArray();
		$province_array_size = $this->province_result->size();
		$this->forLoop($province_array,$province_array_size);
	}
}

?>
