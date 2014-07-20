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
				break;
			case 'truefalse':
				return new truefalse_exporter($question);
				break;
			case 'log':
				return new log_exporter($question);
				break;
			case 'multichoice':
				return new multichoice_exporter($question);
				break;
			case 'match':
				return new matching_exporter($question);
				break;
			case 'numerical':
				return new numerical_exporter($question);
				break;
			case 'shortanswer':
				return new shortanswer_exporter($question);
				break;
			default:
				return false;
				break;
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
		$questiontext = $parser->parse_to_text($this->mquestion->questiontext, $this->mquestion);
		$question->questiontext = $questiontext;
		
		// Set explanation.
		$parser = parser_factory::get_parser($this->mquestion->generalfeedbackformat);
		$question->explanation = $parser->parse_to_string($this->mquestion->generalfeedback);
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
 * Class for exporting errors, which have been logged during the export process.
 */
class log_exporter extends qformat_exporter {
	
	public function __construct($question) {
		
	}
	
	public function export($export_data) {
		$logger = error_logger::get_instance();
		$log = $logger->get_error_log();
		
		// Create text from log entries.
		$html_text = "";
		foreach ($log as $logentry) {
			$html_text .= '<p><span style="font-size: small;">' . $logentry . '</span></p>';
		}
		$html_parser = new html_parser();
		$text = $html_parser->parse_to_text($html_text);
		
		// Create dummy question with text.
		$page_num = count($export_data->pages);

		$question = new question($page_num);
		$question->question_num = $page_num + 1;
		
		// Set question data.
		$question->questiontext = $text;
		$question->format = "noquestion";
		
		// Add generators.
		$page_generator = new page_generator($question);
		$export_data->add_page($page_generator);
		$export_data->imsmanifest_generator->add_page("page" . $page_num . ".svg");
	}
	
	// Helper function, which exports the log to a file for debugging.
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