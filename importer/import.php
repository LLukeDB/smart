<?php

require_once ($CFG->dirroot . '/question/format/smart/logging.php');
require_once ($CFG->dirroot . '/question/format/smart/generator/page_generator.php');
require_once ($CFG->dirroot . '/question/format/smart/filetools.php');
require_once ($CFG->dirroot . '/question/format/smart/generator/question.php');
require_once ($CFG->dirroot . '/question/format/smart/idgenerator.php');
require_once ($CFG->dirroot . '/question/format/smart/exporter/truefalse_exporter.php');
require_once ($CFG->dirroot . '/question/format/smart/exporter/export_data.php');
require_once ($CFG->dirroot . '/question/format/smart/text/parse.php');

class qformat_importer_factory {

	public function get_importer($page) {
		$qtype = $this->get_qtype($page);
		
		switch($qtype) {
			case 'noquestion':
				return new noquestion_importer($page);
			case 'trueorfalse':
				return new truefalse_importer($page);
			default:
				return false;
		}
	}
	
	private function get_qtype($page) {
		$qtype = "";
		
		$questiontexts = $page->xpath('//questiontext');
		if(count($questiontexts) == 0) {
			$qtype = "noquestion";
		}
		else {
			$qtype = (String) $questiontexts[0]['format'];
		}
		
		return $qtype;
	}
}

abstract class qformat_importer {
	
	public function import() {
		
		return $moodle_question_object;
	}
	
	function defaultquestion() {
		// returns an "empty" question
		// Somewhere to specify question parameters that are not handled
		// by import but are required db fields.
		// This should not be overridden.
	
		$question = new stdClass();
		$question->shuffleanswers = get_config('quiz', 'shuffleanswers');
		$question->defaultmark = 1;
		$question->image = "";
		$question->usecase = 0;
		$question->multiplier = array();
		$question->generalfeedback = '';
		$question->correctfeedback = '';
		$question->partiallycorrectfeedback = '';
		$question->incorrectfeedback = '';
		$question->answernumbering = 'abc';
		$question->penalty = 0.0;
		$question->length = 1;
		$question->qoption = 0;
		$question->layout = 1;
	
		// this option in case the questiontypes class wants
		// to know where the data came from
		$question->export_process = true;
		$question->import_process = true;
	
		return $question;
	}
	
	/**
	 * import parts of question common to all types
	 * @param $page svg-page which contains the question.
	 * @return object question object
	 */
	protected function import_headers($page) {
	
		// Get empty default question.
		$qo = $this->defaultquestion();
	
		// Set question name.
		$qo->name = 'Frage'; // TODO
		
		// Set question text.
		$qo->questiontext = $this->get_question_text($page);
		$qo->questiontextformat = FORMAT_HTML;		
	
		// Set general feedback.
		$qo->generalfeedback = $this-> get_general_feedback($page);
		$qo->generalfeedbackformat = FORMAT_HTML;
			
		// Set default mark.
		$qo->defaultmark = $this-> get_default_mark($page);
	
		return $qo;
	}
	
	protected function get_question_text($page) {
		$text_element = $page->xpath("//g[@class='question']/text[2]")[0];
		$parser_factory = new parser_factory();
		$parser = $parser_factory->get_parser('svgtext');
		$text = $parser->parse_text($text_element);
		return $text;
	}
	
	protected function get_general_feedback($page) {
		$questiontext = $page->xpath("//questiontext")[0];
		$text = (String) $questiontext['explanation'];
		$html = "<p>" . $text . "</p>";
		return $html;
	}
	
	protected function get_default_mark($page) {
		$questiontext = $page->xpath("//questiontext")[0];
		$points = (String) $questiontext['points'];
		return $points;
	}
	
		
}

/**
 * Dummy class for pages which contain no question.
 */
class noquestion_importer extends qformat_importer {
	
	public function __construct($page) {
		
	}
	
	public function import() {
		return null;
	}
}


class truefalse_importer extends qformat_importer {
	
	private $page;
	
	public function __construct($page) {
		$this->page = $page;
	}
	
	public function import() {
		global $OUTPUT;
		
		// Get parts which are the same for all questions.
		$qo = $this->import_headers($this->page);
		
		// Get truefalse specific parts.
		$qo->qtype = 'truefalse';
		
		$qo->answer = $this->get_correct_answer($this->page);
		$qo->correctanswer = $qo->answer;
		$qo->feedbacktrue = "";
		$qo->feedbackfalse = "";
		
		return $qo;
	}
	
	private function get_correct_answer($page) {
		$questiontext = $page->xpath("//questiontext")[0];
		$label = (String) $questiontext['correct'];
		$answer = $label == "1";
		return $answer;
	}
	
}


?>