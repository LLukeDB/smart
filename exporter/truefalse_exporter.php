<?php

require_once ($CFG->dirroot . '/question/format/smart/logging.php');
require_once ($CFG->dirroot . '/question/format/smart/generator/page_generator.php');
require_once ($CFG->dirroot . '/question/format/smart/filetools.php');
require_once ($CFG->dirroot . '/question/format/smart/generator/question.php');
require_once ($CFG->dirroot . '/question/format/smart/idgenerator.php');
require_once ($CFG->dirroot . '/question/format/smart/exporter/export_data.php');
require_once ($CFG->dirroot . '/question/format/smart/exporter/export.php');
require_once ($CFG->dirroot . '/question/format/smart/svgtools.php');
require_once ($CFG->dirroot . '/question/format/smart/text/text.php');
require_once ($CFG->dirroot . '/question/format/smart/text/html_parser.php');

/**
 * Class for exporting a true-false-question.
 */
class truefalse_exporter extends qformat_exporter {

	protected $mquestion;

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

		// Set questionchoice 'True' data.
		$choice_true = new choice();
		$choice_true->choice_id = id_generator::get_instance()->generate_id();
		$choice_true->label = "1";
		$choicetext = $html_parser->parse_text('Wahr');  // TODO localize
		$choice_true->choicetext = $choicetext;
		$choice_true->true = $this->mquestion->options->trueanswer == '292' ? true : false;
		$question->add_choice($choice_true);

		// Set questionchoice 'False' data.
		$choice_false = new choice();
		$choice_false->choice_id = id_generator::get_instance()->generate_id();
		$choice_false->label = "2";
		$choicetext = $html_parser->parse_text('Falsch');  // TODO localize
		$choice_false->choicetext = $choicetext;
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

	//protected  $mquestion;

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
		
		// Exported questiontype depends on number of true answers.
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
			$choicetext = $parser->parse_text($answer->answer);
			$choice->choicetext = $choicetext;
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
			$choicetext = $parser->parse_text($answer->answer);
			$choice->choicetext = $choicetext;
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