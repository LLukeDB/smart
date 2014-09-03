<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package qformat_smart
 * @copyright 2014 Lukas Baumann
 * @author Lukas Baumann
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later (5)
 */

require_once($CFG->dirroot . '/question/format/smart/helper/logging.php');
require_once($CFG->dirroot . '/question/format/smart/generator/page_generator.php');
require_once($CFG->dirroot . '/question/format/smart/helper/filetools.php');
require_once($CFG->dirroot . '/question/format/smart/question/question.php');
require_once($CFG->dirroot . '/question/format/smart/helper/idgenerator.php');


class export_data {

	private $pages;						// array of page_generators
	private $metadataxml_generator;
	private $settingsxml_generator;
	private $imsmanifest_generator;
	private $metadatardf_generator;

	public function __construct() {
		$this->pages = array();
		$this->settingsxml_generator = new settingsxml_generator();
		$this->metadataxml_generator = new metadataxml_generator();
		$this->metadatardf_generator = new metadatardf_generator();
		$this->imsmanifest_generator = new imsmanifest_generator();
	}
	
	public function add_question($question) {
	    $question->set_pagenum($this->get_pagecount());
	    $page_generator = new page_generator($question);
	    array_push($this->pages, $page_generator);
	    $this->metadatardf_generator->add_question($question);
	    $this->imsmanifest_generator->add_page($question);
	}
	
	public function get_pagecount() {
	    return count($this->pages);
	}

	public function toZIP() {
		global $CFG;

		// Create temporary directory for data.
		$moodletmpdir = $CFG->dataroot . "/temp/";
		$tmpdir = tempdir($moodletmpdir, "smart_");
		//createDirStructure($tmpdir);

		// Write data to temporary directory.
		$this->settingsxml_generator->save($tmpdir);
		$this->metadataxml_generator->save($tmpdir);
		$this->imsmanifest_generator->save($tmpdir);
		$this->metadatardf_generator->save($tmpdir);
		foreach ($this->pages as $page) {
			$page->save($tmpdir);
		}

		// Create zip file from temporary directory.
		$tmpfile = tempnam($moodletmpdir, 'smart_');
		create_zip($tmpdir, $tmpfile);
		recurseRmdir($tmpdir);

		return $tmpfile;
	}
}

/**
 * Factory class, which creates exporter objects.
 */
class qtype_exporter_factory {

	public static function get_exporter($question) {
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

abstract class qtype_exporter {
	
	protected $mquestion;
	
	public abstract function export($export_data);
	
	protected function set_common_question_data($question) {
		// Set points.
		$question->points = floor($this->mquestion->defaultmark);
	
		// Set questiontext.
		$parser = parser_factory::get_parser($this->mquestion->questiontextformat);
		$questiontext = $parser->parse_to_text($this->mquestion->questiontext, $this->mquestion, $question->question_num);
		$question->questiontext = $questiontext;
		
		// Set explanation.
		$parser = parser_factory::get_parser($this->mquestion->generalfeedbackformat);
		$question->explanation = $parser->parse_to_string($this->mquestion->generalfeedback);
	}
}

/**
 * Dummy class for categories, which does nothing.
 */
class category_exporter extends qtype_exporter {
	
	public function export($export_data) {
		return;
	}
}

/**
 * Class for exporting errors, which have been logged during the export process.
 */
class log_exporter extends qtype_exporter {
	
	public function __construct($question) {
		
	}
	
	public function export($export_data) {
		$logger = error_logger::get_instance();
		$log = $logger->get_error_log();
		
		// Create text from log entries.
		$html_text = get_string('formatting_log_heading', "qformat_smart");
		foreach ($log as $logentry) {
			$html_text .= '<p><span style="font-size: small;">' . $logentry . '</span></p>';
		}
		$html_parser = new html_parser();
		$text = $html_parser->parse_to_text($html_text);
		
		// Create dummy question with text.
		$question = new no_question();
		$export_data->add_question($question);
		
		// Set question data.
		$question->questiontext = $text;
		
	}
	
	/**
	 * Helper function, which exports the log to a file for debugging.
	 * 
	 *  @codeCoverageIgnore
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

/**
 * Class for exporting a true-false-question.
 */
class truefalse_exporter extends qtype_exporter {

	public function __construct($question) {
		$this->mquestion = $question;
	}

	public function export($export_data) {
		$question = new truefalse_question();
		$export_data->add_question($question);
		
		// Get true answer.
		$answer = false;
		foreach ($this->mquestion->options->answers as $manswer) {
		    if($manswer->answer == get_string('true', 'qtype_truefalse')) {
		        $answer = $manswer->fraction > 0;
		        break;
		    }
		}

		// Set question data.
		$this->set_common_question_data($question);

		// Set question answer.
		$question->set_answer($answer);

	}

}

/**
 * Class for exporting a multiple-choice question.
 */
class multichoice_exporter extends qtype_exporter {

	public function __construct($question) {
		$this->mquestion = $question;
	}

	public function export($export_data) {

		// Calculate number of true answers.
		$answers = $this->mquestion->options->answers;
		$true_answers_num = 0;
		foreach ($answers as $answer)  {
			if($answer->fraction > 0.0) {
				$true_answers_num += 1;
			}
		}

		// Exported question type depends on the amount of true answers.
		if($true_answers_num > 1) {
			$this->export_selection_question($export_data);
		}
		else {
			$this->export_choice_question($export_data);
		}

	}

	private function export_selection_question($export_data) {
		$question = new selection_question();
		$export_data->add_question($question);
		
		// Set question data.
		$this->set_common_question_data($question);

		// Set question answers.
		$answers = $this->mquestion->options->answers;
		foreach ($answers as $answer) {
			$parser = parser_factory::get_parser($answer->answerformat);
			$choicetext = $parser->parse_to_text($answer->answer, $this->mquestion, $question->question_num, $question->question_num);
			$iscorrect = false;
			if($answer->fraction > 0.0) {
				$iscorrect = true;
			}
			$question->create_choice($choicetext, $iscorrect);
		}

	}

	private function export_choice_question($export_data) {
		$question = new choice_question();
		$export_data->add_question($question);
		
		// Set question data.
		$this->set_common_question_data($question);

		// Set question answers.
		$answers = $this->mquestion->options->answers;
		foreach ($answers as $answer) {
			$parser = parser_factory::get_parser($answer->answerformat);
			$choicetext = $parser->parse_to_text($answer->answer, $this->mquestion, $question->question_num);
			$iscorrect = false;
			if($answer->fraction > 0.0) {
				$iscorrect = true;
			}
			$question->create_choice($choicetext, $iscorrect);
		}

	}

}

/**
 * Class for exporting a matching question.
 */
class matching_exporter extends qtype_exporter {

	public function __construct($question) {
		$this->mquestion = $question;
	}

	public function export($export_data) {
		$subquestions = $this->mquestion->options->subquestions;
		shuffle($subquestions);

		// Generate a multiple choice question for each subquestion.
		foreach($subquestions as $subquestion) {
			if(strlen(trim($subquestion->questiontext)) > 0) {
				$this->generate_multichoice_question($subquestion, $export_data);
			}
		}
	}

	private function generate_multichoice_question($subquestion, $export_data) {
		$question = new choice_question();
		$export_data->add_question($question);

		$this->set_common_question_data($question);

		// Add subquestiontext to questiontext.
		$parser = parser_factory::get_parser($subquestion->questiontextformat);
		$subquestiontext = $parser->parse_to_text($subquestion->questiontext, $this->mquestion, $question->question_num);
		$question->questiontext->append_text($subquestiontext);

		// Set questionanswers.
		$subquestions = $this->mquestion->options->subquestions;
		foreach ($subquestions as $subq) {
			$parser = new html_parser();
			$choicetext = $parser->parse_to_text($subq->answertext, $this->mquestion, $question->question_num);
			$iscorrect = false;
			if($subq->id == $subquestion->id) {
				$iscorrect = true;
			}
			$question->create_choice($choicetext, $iscorrect);
		}

	}
}

/**
 * Class for exporting a numerical-question.
 */
class numerical_exporter extends qtype_exporter {

	public function __construct($question) {
		$this->mquestion = $question;
	}

	public function export($export_data) {

		$question = new numeric_question();
		$export_data->add_question($question);

		// Set question data.
		$this->set_common_question_data($question);

		// Set question answer.
		$answer = "";
		foreach ($this->mquestion->options->answers as $manswer) {
			$answer = $manswer->answer;
			break;
		}
		$question->set_answer($answer);
		
	}

}

/**
 * Class for exporting a shortanswer-question.
 */
class shortanswer_exporter extends qtype_exporter {

	public function __construct($question) {
		$this->mquestion = $question;
	}

	public function export($export_data) {

		$question = new shortanswer_question();
		$export_data->add_question($question);
		
		// Set question data.
		$this->set_common_question_data($question);

		// Set question answers.
		$manswers = $this->mquestion->options->answers;
		$answers = array();
		foreach($manswers as $manswer) {
		    if($manswer->fraction > 0) {
    			array_push($answers, $manswer->answer);
		    }
		}
		$question->set_answers($answers);

	}

}

