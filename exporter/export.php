<?php

require_once ($CFG->dirroot . '/question/format/smart/logging.php');
require_once ($CFG->dirroot . '/question/format/smart/generator/page_generator.php');
require_once ($CFG->dirroot . '/question/format/smart/filetools.php');
require_once ($CFG->dirroot . '/question/format/smart/generator/question.php');
require_once ($CFG->dirroot . '/question/format/smart/idgenerator.php');
require_once ($CFG->dirroot . '/question/format/smart/exporter/truefalse_exporter.php');
require_once ($CFG->dirroot . '/question/format/smart/exporter/export_data.php');

class qformat_exporter_factory {

	public function get_exporter($question) {
		switch($question->qtype) {
			case 'category':
				return new category_exporter();
			case 'truefalse':
				return new truefalse_exporter($question);
			case 'log':
				return new log_exporter($question);
			case 'multichoice':
				return new multichoice_exporter($question);
			default:
				return false;
		}
	}
}

abstract class qformat_exporter {
	
	protected $mquestion;
	
	public function export($export_data) {

	}
	
	protected function set_common_question_data($question) {
		// Set points.
		$question->points = floor($this->mquestion->defaultmark);
	
		// Set questiontext.
		$parser = parser_factory::get_parser($this->mquestion->questiontextformat);
		$questiontext = $parser->parse_text($this->mquestion->questiontext);
		$question->questiontext = $questiontext;
		
		// Set explanation.
		$question->explanation = strip_tags($this->mquestion->generalfeedback);  // TODO generalfeedbackformat
	}
}

/**
 * Dummy class for categories, which does nothing.
 */
class category_exporter extends qformat_exporter {
	
	public function export($export_data) {
		return;
	}
}

/**
 * Class for exporting errors, which have been logged during the export process
 * and some general tasks.
 */
class log_exporter extends qformat_exporter {
	
	public function __construct($question) {
		error_logger::get_instance()->log_error("log_exporter created");
	}
	
	/*
	 * Helper function, which exports the log to a file for debugging.
	 */
	private function export_to_file() {
		$path = "/opt/lampp/logs/smart_error_log";
		
		$error_logger = error_logger::get_instance();
		$error_log = $error_logger->get_error_log();
		
		$handle = fopen($path, 'w');
		$date = date("Y-m-d\TH:i:s");
		foreach ($error_log as $error) {
			fputs($handle, "[$date] " . $error . "\n");
		}
		fclose($handle);
	}
	
}


?>