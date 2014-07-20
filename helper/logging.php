<?php

class error_logger {
	
	private static $instance;
	
	private $error_log;
	
	public static function get_instance() {
		if(!error_logger::$instance) {
			error_logger::$instance = new error_logger(null);
		}
		return error_logger::$instance;
	}
	
	private function __construct($question) {
		$this->error_log = array();
	}
	
	public function log_error($error_msg) {
		array_push($this->error_log, $error_msg);
	}
	
	public function get_error_log() {
		return $this->error_log;
	}
	
}

?>