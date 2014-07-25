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

require_once($CFG->dirroot . '/question/format/smart/helper/logging.php');
require_once($CFG->dirroot . '/question/format/smart/generator/page_generator.php');
require_once($CFG->dirroot . '/question/format/smart/helper/filetools.php');
require_once($CFG->dirroot . '/question/format/smart/question/question.php');
require_once($CFG->dirroot . '/question/format/smart/helper/idgenerator.php');

class export_data {

	public $pages;						// array of page_generators
	public $metadataxml_generator;
	public $settingsxml_generator;
	public $imsmanifest_generator;
	public $metadatardf_generator;

	public function __construct() {
		$this->pages = array();
		$this->settingsxml_generator = new settingsxml_generator();
		$this->metadataxml_generator = new metadataxml_generator();
		$this->metadatardf_generator = new metadatardf_generator();
		$this->imsmanifest_generator = new imsmanifest_generator();
	}

	public function add_page($page) {
		array_push($this->pages, $page);
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
		//recurseRmdir($tmpdir);	// DEBUGGING

		return $tmpfile;
	}
}

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
	
	public abstract function export($export_data);
	
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

/**
 * Class for exporting a true-false-question.
 */
class truefalse_exporter extends qformat_exporter {

	public function __construct($question) {
		$this->mquestion = $question;
	}

	public function export($export_data) {
		$page_num = count($export_data->pages);

		$question = new question($page_num);
		$question->question_num = $page_num + 1;

		// Set question data.
		$this->set_common_question_data($question);
		$question->format = "trueorfalse";
		$question->labelstyle = "true/false";
		$question->correct = $this->mquestion->options->trueanswer == '292' ? 1 : 2;

		$question->questionformat = "choice";
		$question->choicelabelstyle = "true-false";

		// Get text parser.
		$html_parser = new html_parser();

		// Set questionchoice 'true' data.
		$choice_true = new choice();
		$choice_true->choice_id = id_generator::get_instance()->generate_id();
		$choice_true->label = "1";
		$text = get_string('true', 'qtype_truefalse');
		$choicelabel = $html_parser->parse_to_text($text);
		$choice_true->choicelabel = $choicelabel;
		$choice_true->true = $this->mquestion->options->trueanswer == '292' ? true : false;
		$question->add_choice($choice_true);

		// Set questionchoice 'false' data.
		$choice_false = new choice();
		$choice_false->choice_id = id_generator::get_instance()->generate_id();
		$choice_false->label = "2";
		$text = get_string('false', 'qtype_truefalse');
		$choicelabel = $html_parser->parse_to_text($text);
		$choice_false->choicelabel = $choicelabel;
		$choice_false->true = $this->mquestion->options->trueanswer == '292' ? false : true;
		$question->add_choice($choice_false);

		// Add generators.
		$page_generator = new page_generator($question);
		$export_data->add_page($page_generator);
		$export_data->metadatardf_generator->add_question($question);
		$export_data->imsmanifest_generator->add_page("page" . $page_num . ".svg");
	}

}

/**
 * Class for exporting a multiple-choice question.
 */
class multichoice_exporter extends qformat_exporter {

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

		// Exported questiontype depends on the amount of true answers.
		if($true_answers_num > 1) {
			$this->export_selection_question($export_data);
		}
		else {
			$this->export_choice_question($export_data);
		}

	}

	private function export_selection_question($export_data) {
		$page_num = count($export_data->pages);
		$question = new question($page_num);
		$question->question_num = $page_num + 1;

		// Set question data.
		$this->set_common_question_data($question);
		$question->format = "selection";
		$question->labelstyle = "upper-alpha";
		$question->questionformat = "selection";
		$question->choicelabelstyle = "upper-alpha";

		// Set questionanswers.
		$answers = $this->mquestion->options->answers;
		$correct = "";
		$position = 1;
		foreach ($answers as $answer) {
			$choice = new choice();
			$choice->choice_id = id_generator::get_instance()->generate_id();
			$choice->label = $position;
			$choice->format = "selection";
			$parser = parser_factory::get_parser($answer->answerformat);
			$choicetext = $parser->parse_to_text($answer->answer, $this->mquestion);
			$choice->choicetext = $choicetext;
			$choice->choicelabel = $parser->parse_to_text(chr(ord('A') + ($position -1)));
			if($answer->fraction > 0.0) {
				$choice->true = true;
				$correct .= " " . $position;
			}
			$question->add_choice($choice);
			$position += 1;
		}

		$question->correct = trim($correct);

		// Add generators.
		$page_generator = new page_generator($question);
		$export_data->add_page($page_generator);
		$export_data->metadatardf_generator->add_question($question);
		$export_data->imsmanifest_generator->add_page("page" . $page_num . ".svg");
	}

	private function export_choice_question($export_data) {
		$page_num = count($export_data->pages);
		$question = new question($page_num);
		$question->question_num = $page_num + 1;

		// Set question data.
		$this->set_common_question_data($question);
		$question->format = "choice";
		$question->labelstyle = "upper-alpha";
		$question->questionformat = "choice";
		$question->choicelabelstyle = "upper-alpha";

		// Set questionanswers.
		$answers = $this->mquestion->options->answers;
		$correct = "";
		$position = 1;
		foreach ($answers as $answer) {
			$choice = new choice();
			$choice->choice_id = id_generator::get_instance()->generate_id();
			$choice->label = $position;
			$choice->format = "choice";
			$parser = parser_factory::get_parser($answer->answerformat);
			$choicetext = $parser->parse_to_text($answer->answer, $this->mquestion);
			$choice->choicetext = $choicetext;
			$choice->choicelabel = $parser->parse_to_text(chr(ord('A') + ($position -1)));
			if($answer->fraction > 0.0) {
				$choice->true = true;
				$correct .= " " . $position;
			}
			$question->add_choice($choice);
			$position += 1;
		}

		$question->correct = trim($correct);

		// Add generators.
		$page_generator = new page_generator($question);
		$export_data->add_page($page_generator);
		$export_data->metadatardf_generator->add_question($question);
		$export_data->imsmanifest_generator->add_page("page" . $page_num . ".svg");
	}

}

/**
 * Class for exporting a matching question.
 */
class matching_exporter extends qformat_exporter {

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
		$page_num = count($export_data->pages);
		$question = new question($page_num);
		$question->question_num = $page_num + 1;

		// Set question data.
		$this->set_common_question_data($question);
		$question->format = "choice";
		$question->labelstyle = "upper-alpha";
		$question->questionformat = "choice";
		$question->choicelabelstyle = "upper-alpha";

		// Add subquestiontext to questiontext.
		$parser = parser_factory::get_parser($subquestion->questiontextformat);
		$subquestiontext = $parser->parse_to_text($subquestion->questiontext, $this->mquestion);
		$question->questiontext->append_text($subquestiontext);

		// Set questionanswers.
		$subquestions = $this->mquestion->options->subquestions;
		$correct = "";
		$position = 1;
		foreach ($subquestions as $subq) {
			$choice = new choice();
			$choice->choice_id = id_generator::get_instance()->generate_id();
			$choice->label = $position;
			$choice->format = "choice";
			$parser = new html_parser();
			$choice->choicetext = $parser->parse_to_text($subq->answertext, $this->mquestion);
			$choice->choicelabel = $parser->parse_to_text(chr(ord('A') + ($position -1)));
			if($subq->id == $subquestion->id) {
				$choice->true = true;
				$correct .= " " . $position;
			}
			$question->add_choice($choice);
			$position += 1;
		}

		$question->correct = trim($correct);

		// Add generators.
		$page_generator = new page_generator($question);
		$export_data->add_page($page_generator);
		$export_data->metadatardf_generator->add_question($question);
		$export_data->imsmanifest_generator->add_page("page" . $page_num . ".svg");

	}
}

/**
 * Class for exporting a numerical-question.
 */
class numerical_exporter extends qformat_exporter {

	public function __construct($question) {
		$this->mquestion = $question;
	}

	public function export($export_data) {
		$page_num = count($export_data->pages);

		$question = new question($page_num);
		$question->question_num = $page_num + 1;

		// Get the first answer.
		$answer = "";
		foreach ($this->mquestion->options->answers as $manswer) {
			$answer = $manswer->answer;
			break;
		}

		// Set question data.
		$this->set_common_question_data($question);
		$question->format = "numeric";
		$question->labelstyle = "";
		$question->correct = $answer;

		$question->questionformat = "decimal";
		$question->choicelabelstyle = "";

		$question->maximumvalue = $answer;
		$question->minimumvalue = $answer;

		// Add generators.
		$page_generator = new page_generator($question);
		$export_data->add_page($page_generator);
		$export_data->metadatardf_generator->add_question($question);
		$export_data->imsmanifest_generator->add_page("page" . $page_num . ".svg");
	}

}

/**
 * Class for exporting a numerical-question.
 */
class shortanswer_exporter extends qformat_exporter {

	public function __construct($question) {
		$this->mquestion = $question;
	}

	public function export($export_data) {
		$page_num = count($export_data->pages);

		$question = new question($page_num);
		$question->question_num = $page_num + 1;

		// Set answers (max 4).
		$manswers = $this->mquestion->options->answers;
		$correct = "";
		$count = 0;
		foreach($manswers as $manswer) {
			//$correct .= $manswer->answer . "\r\n";
			$correct .= $manswer->answer . "\n";
			if(++$count >= 4) {
				break;
			}
		}
		$correct = substr($correct, 0, strlen($correct) - 1);  // Delete last linebreak.
		$question->correct = $correct;

		// Set question data.
		$this->set_common_question_data($question);
		$question->format = "short-answer";
		$question->labelstyle = "";

		$question->questionformat = "short-answer";
		$question->choicelabelstyle = "";
		$question->exactmatches = $correct;

		// Add generators.
		$page_generator = new page_generator($question);
		$export_data->add_page($page_generator);
		$export_data->metadatardf_generator->add_question($question);
		$export_data->imsmanifest_generator->add_page("page" . $page_num . ".svg");
	}

}
