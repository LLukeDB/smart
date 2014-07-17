<?php

require_once ($CFG->dirroot . '/question/format/smart/logging.php');
require_once ($CFG->dirroot . '/question/format/smart/generator/page_generator.php');
require_once ($CFG->dirroot . '/question/format/smart/filetools.php');
require_once ($CFG->dirroot . '/question/format/smart/generator/question.php');
require_once ($CFG->dirroot . '/question/format/smart/idgenerator.php');
require_once ($CFG->dirroot . '/question/format/smart/exporter/export_data.php');
require_once ($CFG->dirroot . '/question/format/smart/exporter/export.php');
require_once ($CFG->dirroot . '/question/format/smart/svgtools.php');

/**
 * Class for exporting a true-false-question.
 */
class truefalse_exporter extends qformat_exporter {

	private $mquestion;

	public function __construct($question) {
		$this->mquestion = $question;
	}

	public function export($export_data) {
		$page_num = count($export_data->pages);

		$question = new question($page_num);
		
		// Set question data.
		$this->set_common_question_data($question);
		$question->format = "trueorfalse";
		$question->labelstyle = "true/false";
		$question->correct = $this->mquestion->options->trueanswer == '292' ? 1 : 2;
		$question->question_num = $page_num + 1;
		$question->questionformat = "choice";
		$question->choicelabelstyle = "true-false";

		// Set questionchoice 'Wahr' data.
		$choice_true = new choice();
		$choice_true->choice_id = id_generator::get_instance()->generate_id();
		$choice_true->label = "1";
		$choice_true->choicetext = "Wahr";
		$choice_true->true = $this->mquestion->options->trueanswer == '292' ? true : false;
		$question->add_choice($choice_true);

		// Set questionchoice 'Falsch' data.
		$choice_false = new choice();
		$choice_false->choice_id = id_generator::get_instance()->generate_id();
		$choice_false->label = "1";
		$choice_false->choicetext = "Falsch";
		$choice_false->true = $this->mquestion->options->trueanswer == '292' ? false : true;
		$question->add_choice($choice_false);

		$page_generator = new page_generator($question);
		$export_data->add_page($page_generator);
		$export_data->metadatardf_generator->add_question($question);
		$export_data->imsmanifest_generator->add_page("page" . $page_num . ".svg");
	}
	
	protected function set_common_question_data($question) {
		$question->points = floor($this->mquestion->defaultmark);
		$question->questiontext = $this->mquestion->questiontext;
		$question->explanation = strip_tags($this->mquestion->generalfeedback);
	}
}