<?php

require_once ($CFG->dirroot . '/question/format/smart/logging.php');
require_once ($CFG->dirroot . '/question/format/smart/generator/page_generator.php');
require_once ($CFG->dirroot . '/question/format/smart/filetools.php');
require_once ($CFG->dirroot . '/question/format/smart/generator/question.php');
require_once ($CFG->dirroot . '/question/format/smart/idgenerator.php');
require_once ($CFG->dirroot . '/question/format/smart/exporter/export_data.php');
require_once ($CFG->dirroot . '/question/format/smart/exporter/export.php');
require_once ($CFG->dirroot . '/question/format/smart/text/text.php');
require_once ($CFG->dirroot . '/question/format/smart/text/html_parser.php');

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
			$this->generate_multichoice_question($subquestion, $export_data);
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
			$correct .= $manswer->answer . "\n";  // TODO check
			if(++$count >= 4) {
				break;
			}
		}
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

