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
	public $correct = "";			// page: votemetadata -> correct: labels of correct questionchoice blocks, e.g. "1 2"
	public $points = "";			// page: votemetadata -> points
	public $explanation = "";		// page: votemetadata -> explanation
	public $likert = "";            // page: votemetadata -> likert

	public $questionformat;			// rdf: questionformat, eg. choice
	public $choicelabelstyle;		// rdf: choicelabelstyle, eg. true-false
	public $answer_block_id;		// rdf:
	
	public $question_num;
	public $questiontext;
	public $question_id;
	
	public $choices;
	
	public function __construct() {
		$this->choices = array();
		$this->question_id = id_generator::get_instance()->generate_id();
		$this->page_id = id_generator::get_instance()->generate_id();
		$this->answer_block_id = id_generator::get_instance()->generate_id();
	}
	
	public function set_pagenum($pagenum) {
	    $this->page_num = $pagenum;
	    $this->question_num = $pagenum + 1;
	    $this->page_name = "page" . $pagenum . ".svg";
	}
	
	public function add_choice($choice) {
		array_push($this->choices, $choice);
	}
	
	public function get_choicecount() {
	    return count($this->choices);
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
	
	public function set_questiontext($questiontext) {
	    $this->questiontext = $questiontext;
	}
	
	public function set_points($points) {
	    $this->points = $points;
	}
	
	public function set_explanation($explanation) {
	    $this->explanation = $explanation;
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

class no_question extends question {
    public $format = "noquestion";
        
}

class truefalse_question extends question {
    public $format = "trueorfalse";
    public $labelstyle = "true/false";
    public $questionformat = "choice";
    public $choicelabelstyle = "true-false";
    
    public function set_answer($answer) {
        
        $this->correct = $answer ? 1 : 2;
        
        // Get text parser.
        $html_parser = new html_parser();
        
        // Set questionchoice 'true' data.
        $choice_true = new choice();
        $choice_true->choice_id = id_generator::get_instance()->generate_id();
        $choice_true->label = "1";
        $text = get_string('true', 'qtype_truefalse');
        $choicelabel = $html_parser->parse_to_text($text);
        $choice_true->choicelabel = $choicelabel;
        $choice_true->true = $answer;
        $this->add_choice($choice_true);
        
        // Set questionchoice 'false' data.
        $choice_false = new choice();
        $choice_false->choice_id = id_generator::get_instance()->generate_id();
        $choice_false->label = "2";
        $text = get_string('false', 'qtype_truefalse');
        $choicelabel = $html_parser->parse_to_text($text);
        $choice_false->choicelabel = $choicelabel;
        $choice_false->true = !$answer;
        $this->add_choice($choice_false);
    }
    
}

class selection_question extends question {
    public $format = "selection";
    public $labelstyle = "upper-alpha";
    public $questionformat = "selection";
    public $choicelabelstyle = "upper-alpha";
    
    public function create_choice($choicetext, $iscorrect) {
        $choice = new choice();
        $this->add_choice($choice);
        $choice->choice_id = id_generator::get_instance()->generate_id();
        $position = $this->get_choicecount();
        $choice->label = $position;
        $choice->format = "selection";
        $choice->choicetext = $choicetext;
        $parser = new html_parser();
        $choice->choicelabel = $parser->parse_to_text(chr(ord('A') + ($position -1)));
        if($iscorrect) {
            $choice->true = true;
            $this->correct = trim($this->correct . " " . $position);
        }
        return $choice;
    }
    
}

class choice_question extends question {
    public $format = "choice";
    public $labelstyle = "upper-alpha";
    public $questionformat = "choice";
    public $choicelabelstyle = "upper-alpha";
    public $likert = "false";
    
    public function create_choice($choicetext, $iscorrect) {
        $choice = new choice();
        $this->add_choice($choice);
        $choice->choice_id = id_generator::get_instance()->generate_id();
        $position = $this->get_choicecount();
        $choice->label = $position;
        $choice->format = "choice";
        $choice->choicetext = $choicetext;
        $parser = new html_parser();
        $choice->choicelabel = $parser->parse_to_text(chr(ord('A') + ($position -1)));
        if($iscorrect) {
            $choice->true = true;
            $this->correct = trim($this->correct . " " . $position);
        }
        return $choice;
    }
    
}

class numeric_question extends question {
    public $format = "numeric";
    public $labelstyle = "";
    public $questionformat = "decimal";
    public $choicelabelstyle = "";
    
    // Attributes specific for numeric questions.
    public $maximumvalue;
    public $minimumvalue;
    
    public function set_answer($answer) {
        $this->correct = $answer;
        $this->maximumvalue = $answer;
        $this->minimumvalue = $answer;
    }
    
}

class shortanswer_question extends question {
     public $format = "short-answer";
     public $labelstyle = "";
     public $questionformat = "short-answer";
     public $choicelabelstyle = "";
     
     // Attribute specific for shortanswer questions.
     public $exactmatches;
     
     public function set_answers($answers) {
         // Set answers (max 4).
         $correct = "";
         $count = 0;
         foreach($answers as $answer) {
                 //$correct .= $manswer->answer . "\r\n";
                 $correct .= $answer . "\n";
                 if(++$count >= 4) {
                     break;
                 }
         }
         $correct = trim($correct);
         $this->correct = $correct;
         $this->exactmatches = $correct;
     }
}
