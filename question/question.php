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

require_once ($CFG->dirroot . '/question/format/smart/helper/idgenerator.php');
require_once ($CFG->dirroot . '/question/format/smart/text/text.php');

class question {
	
	// Page infos.
	public $page_id;
	public $page_num;
	public $page_name;
	
	// Question infos.		
	public $format;					// page: votemetadata -> format
	public $labelstyle;				// page: votemetadata -> labelstyle
	public $correct;				// page: votemetadata -> correct: labels of correct questionchoice blocks, e.g. "1 2"
	public $points = "";			// page: votemetadata -> points
	public $explanation = "";		// page: votemetadata -> explanation

	public $questionformat;			// rdf: questionformat, eg. choice
	public $choicelabelstyle;		// rdf: choicelabelstyle, eg. true-false
	public $answer_block_id;		// rdf:
	
	public $question_num;
	public $questiontext;
	public $question_id;
	
	public $choices;
	
	// Attributes for numerical questions.
	public $maximumvalue;
	public $minimumvalue;
	
	// Attributes for short-answer questions.
	public $exactmatches;
	
	public function __construct($page_num) {
		$this->choices = array();
		$this->question_id = id_generator::get_instance()->generate_id();
		$this->page_id = id_generator::get_instance()->generate_id();
		$this->answer_block_id = id_generator::get_instance()->generate_id();
		$this->page_num = $page_num;
		$this->page_name = "page" . $page_num . ".svg";
	}
	
	public function add_choice($choice) {
		array_push($this->choices, $choice);
	}
	
	public function get_true_choice_values() {
		$result = "";
		foreach($this->choices as $choice) {
			if($choice->true == true) {
				$result .= " " . $choice->choice_value;
			}
		}
		return trim($result);
	}
}

class choice {
	
	public $choice_id;
	public $choice_value;
	
	public $label;			// page: votemetadata -> label
	public $format = "";	// page: votemetadata -> format
	
	public $choicetext;
	public $choicelabel;
	public $true;
	
	public function __construct() {
		$this->choice_id = id_generator::get_instance()->generate_id();
		$this->choice_value = id_generator::get_instance()->generate_id();
		$this->choicelabel = text::get_empty_text();
		$this->choicetext = text::get_empty_text();
	}
	
}
