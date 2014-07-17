<?php
class id_generator {

	private static $instance = null;

	private $ID_LENGTH = 25;
	private $used_values;

	private function __construct() {
		$this->used_values = array();
	}

	public static function get_instance() {
		if(id_generator::$instance == null) {
			id_generator::$instance = new id_generator();
		}
		return id_generator::$instance;
	}

	private function rand_char() {
		$r = rand(0, 45);

		if($r <= 25) {
			$c = ord('A') + $r;
		}
		else {
			$c = ord('0') + ($r - 25) % 10;
		}
		return chr($c);
	}

	private function generate_unchecked_id() {
		$rand_id = '';
		for($i = 0; $i < $this->ID_LENGTH; $i++) {
			$rand_id .= $this->rand_char();
		}

		return $rand_id;
	}

	public function generate_id() {
		$id = '';
		{
			$id = $this->generate_unchecked_id();
		} while(in_array($id, $this->used_values))

			array_push($this->used_values, $id);
		return $id;
	}
}

?>