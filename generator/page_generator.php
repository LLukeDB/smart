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

require_once($CFG->dirroot . '/question/format/smart/format.php');
require_once($CFG->dirroot . '/question/format/smart/helper/filetools.php');
require_once($CFG->dirroot . '/question/format/smart/generator/generator.php');
require_once($CFG->dirroot . '/question/format/smart/helper/simplexml_helper.php');
require_once($CFG->dirroot . '/question/format/smart/text/text.php');
require_once($CFG->dirroot . '/question/format/smart/text/html_parser.php');

class page_generator extends file_generator {

	private $xml;
	private $question;
	private $html_parser;
	
	private $ypos = 20;

	public function __construct($question){
		$this->question = $question;
		$this->html_parser = new html_parser();
	}
	
	public function save($dir) {
		$this->generate_xml();

		// Write pageX.svg to directory.
		$filename = $dir . $this->question->page_name;
		$string = $this->xml->asXML();
		// Replace id through xml:id in case of namespace problems.
		$string = preg_replace('/\s+id="annotation/', ' xml:id="annotation', $string);
		$string = preg_replace('/\s+id="page/', ' xml:id="page', $string);
		
		if($this->question->format == "short-answer") {
			$string = str_replace('&#10;', "\r\n", $string);  // Replace escape sequence for linebreak with real linebreak. Needed for shortanswer questions.
		}
		return file_put_contents($filename, $string);
		//return save_simplexml($this->xml, $filename);
	}

	protected function generate_xml() {
		$this->init();
		
		switch ($this->question->format) {
			case 'noquestion':
				$this->generate_noquestion_page();
				break;
			case 'trueorfalse':
				$this->generate_choicequestion_page();
				break;
			case 'selection':
				$this->generate_choicequestion_page();
				break;
			case 'choice':
				$this->generate_choicequestion_page();
				break;
			case 'numeric':
				$this->generate_numeric_page();
				break;
			case 'short-answer':
				$this->generate_shortanswer_page();
				break;
		}
		
		$this->finish();
	}
	
	private function generate_noquestion_page() {
		$g = $this->xml->g;
		$text = $this->question->questiontext;
		$this->generate_text($g, $text, 30, 740);
	}
	
	private function generate_choicequestion_page() {
		$this->generate_question();
		foreach($this->question->choices as $choice) {
			//$this->ypos += 20; // Add margin.
			$this->generate_choice($choice);
		}
	}
	
	private function generate_shortanswer_page() {
		$this->generate_question();
		$this->generate_answer_input_element('shortanswer');
	}
	
	private function generate_numeric_page() {
		$this->generate_question();
		$this->generate_answer_input_element('shortanswernumeric');
	}

	private function init() {
		$svg = new SimpleXMLElement("<svg></svg>");
		$doc = dom_import_simplexml($svg)->ownerDocument;
		$doc->encoding = 'UTF-8';
		$svg->addChild("title", date("M d-H:s"));
		$g = $svg->addChild("g");
		$g->addAttribute("class", "foreground");
		
		$this->xml = $svg;
		return true;
	}
	
	private function finish() {
		$svg = $this->xml;
		
		// Calculate page height.
		$height = 600; 
		if($this->ypos > 600) {
			$n = ($this->ypos - 600) / 200 + 1;
			$height += $n * 200;
		}
		
		$svg->addAttribute("width", "800");
		$svg->addAttribute("height", $height);
		$svg->addAttribute("xml:id", "page." . $this->question->page_id, "http://www.w3.org/TR/2009/REC-xml-names-20091208/");
		
	}
	
	private function generate_question() {
		// Create g element for question and set attributes.
		$g = $this->xml->g->addChild("g");
		$g->addAttribute("class", "question");
		
		// Write votemetadata.
		$votemetadata = $g->addChild("votemetadata");
		$questiontext = $votemetadata->addChild("questiontext");
		$questiontext->addAttribute("format", $this->question->format);
		$questiontext->addAttribute("labelstyle", $this->question->labelstyle);
		$questiontext->addAttribute("correct", $this->question->correct);
		$questiontext->addAttribute("points", $this->question->points);
		$questiontext->addAttribute("tags", "");
		$questiontext->addAttribute("explanation", $this->question->explanation);
		$questiontext->addAttribute("mathgradingoption", "");
		$questiontext->addAttribute("likert", $this->question->likert);
		
		// Write 1st text-element.
		$text = $this->html_parser->parse_to_text($this->question->question_num);
		$label_geometry = $this->generate_text($g, $text, 25, 0, false);
		
		// Write 2nd text-element.
		$this->generate_text($g, $this->question->questiontext, 60, 715, true);
		
		// Set remaining attributes.
		//$g->addAttribute("labelwidth", ceil($label_geometry['width']));
		$g->addAttribute("language_direction", 1);
		//$g->addAttribute("RotationPoint", "(350.000000,270.000000)");
		//$g->addAttribute("transform", "rotate(0.00,160.46,45.64)");
		$g->addAttribute("xml:id", "annotation." . id_generator::get_instance()->generate_id(), "http://www.w3.org/TR/2009/REC-xml-names-20091208/");
		$g->addAttribute("visible", 1);
		
		return true;
	}
	
	private function generate_choice($choice) {
		// Create g element for questinchoice and set attributes.
		$g = $this->xml->g->addChild("g");
		$g->addAttribute("class", "questionchoice");
		
		// Write votemetadata.
		$votemetadata = $g->addChild("votemetadata");
		$choicetext = $votemetadata->addChild("choicetext");
		$choicetext->addAttribute("label", $choice->label);
		if($choice->format != "") {
			$choicetext->addAttribute("format", $choice->format);
		}
		
		// Save current ypos.
		$ypos = $this->ypos;
		
		// Write 1st text-element (label).
		$label_geometry = $this->generate_text($g, $choice->choicelabel, 83, 0, $this->question->format == 'trueorfalse' ? true : false);
	
		// Write 2nd text-element (text).
		$this->generate_text($g, $choice->choicetext, 117, 658, true);
		
		// Write bullets.
		switch ($this->question->format) {
			case 'trueorfalse':
				$this->generate_radiobutton($g, $ypos + 25);
				break;
			case 'selection':
				$this->generate_checkbox($g, $ypos + 25);	
				break;
			case 'choice':
				$this->generate_radiobutton($g, $ypos + 25);
				break;
		}
		
		
		// Set remaining attributes.
		//$g->addAttribute("xbk_transform", "rotate(0.00,113.57,128.92)");
		//$g->addAttribute("labelwidth", ceil($label_geometry['width']));
		$g->addAttribute("language_direction", 1);
		//$g->addAttribute("RotationPoint", "(376.000000,353.281250)"); 	
		//$g->addAttribute("transform", "rotate(0.00,113.57,128.92)");
		$g->addAttribute("xml:id", "annotation." . $choice->choice_id, "http://www.w3.org/TR/2009/REC-xml-names-20091208/");
		$g->addAttribute("visible", 1);
		
		return true;
	}
	
	private function generate_text($parent, $text, $xpos, $width=0, $new_line=true) {
		$geometry = new stdClass();
		$geometry->rel_ypos = 0;
		
		$text->set_width($width);
		
		// Create text element.
		$text_elem = $parent->addChild("text");
		
		// Generate all paragraphs.
		$paragraphs = $text->get_paragraphs();
		foreach ($paragraphs as $paragraph) {
			$geometry = $this->generate_paragraph($text_elem, $paragraph, $geometry);
		}
		
		$tmetrics = $text->get_metrics();
		
		// Set Attributes of text element.
		//$text_elem->addAttribute("transform", "translate($xpos," . $this->ypos . ")");
		$text_elem->addAttribute("transform", "translate($xpos," . $this->ypos . ") rotate(0.000," . ($width > 0 ? $width : $tmetrics->width) / 2.0 . "," . $tmetrics->height / 2.0 . ") scale(1.000,1.000)");
		//$text_elem->addAttribute("RotationPoint", "(346.500000,240.000000)");
		$text_elem->addAttribute("xml:id", "annotation." . id_generator::get_instance()->generate_id(), "http://www.w3.org/TR/2009/REC-xml-names-20091208/");
		$text_elem->addAttribute("visible", 1);
		$text_elem->addAttribute("smart-txt-ver", "2.10");
		$text_elem->addAttribute("editwidth", $width > 0 ? $width : $tmetrics->width);
		$text_elem->addAttribute("editheight", $tmetrics->height);
 		$text_elem->addAttribute("forcewidth", $width > 0 ? "1" : "0");
 		$text_elem->addAttribute("forceheight", "0");
 		$text_elem->addAttribute("language_direction", "1");
 		$text_elem->addAttribute("textdirection", "0");
 		$text_elem->addAttribute("theme_anno_style", "0");
		
		// Increase y-position of page if wanted.
		if($new_line) {
			$this->ypos += $geometry->rel_ypos;
		}
		
		return $geometry;
	}
	
	private function generate_paragraph($parent, $paragraph, $geometry) {
		// Create tspan-element for paragraph.
		$p_tspan = $parent->addChild("tspan");
		$p_tspan->addAttribute("justification", "left");
		$p_tspan->addAttribute("bullet", "0");
		
		// Generate all lines.
		$lines = $paragraph->get_lines();
		foreach($lines as $line) {
			$geometry = $this->generate_line($p_tspan, $line, $geometry);
		}
		
		return $geometry;
	}
	
	private function generate_line($parent, $line, $geometry) {
		$line_metrics = $line->get_metrics();	
		$tf_baseline = $geometry->rel_ypos + $line_metrics->baseline;	
		
		// Create tspan-element for line.
		$line_tspan = $parent->addChild("tspan");
		$line_tspan->addAttribute("justification", "left");
		$line_tspan->addAttribute("line-spacing", "1.00");
		$line_tspan->addAttribute("prepara-spacing", "1.00");
		
		// Generate all textfragments.
		$rel_xpos = 0;
		foreach ($line->get_textfragments() as $textfragment) {
			$tf_tspan = $line_tspan->addChild("tspan" , $textfragment->get_text());
			$this->set_formatting_attributes($tf_tspan, $textfragment->get_formattings());
			$tf_tspan->addAttribute("textLength", $textfragment->get_metrics()->width);
			$tf_tspan->addAttribute("y", "$tf_baseline");
			//if($rel_xpos == 0) {
			$tf_tspan->addAttribute("x", $rel_xpos);
			//}
			$rel_xpos += $textfragment->get_metrics()->width;
		}
		
		$geometry->rel_ypos += $line_metrics->height + $line_metrics->leading;
		return $geometry;
	}
	
	private function set_formatting_attributes($tf_tspan, $formattings) {
		foreach($formattings as $name => $value) {
			$tf_tspan->addAttribute($name, $value);
		}
	}
	
	private function generate_radiobutton($parent, $y) {
		$g = $parent->addChild("g");
		$g->addAttribute("class", "group");
		$g->addAttribute("xml:id", "annotation." . id_generator::get_instance()->generate_id(), "http://www.w3.org/TR/2009/REC-xml-names-20091208/");
		//$g->addAttribute("xbk_transform", "rotate(0.00,66.75,189.31)");
	
		$e = $g->addChild("ellipse");
		$e->addAttribute("cx", "68.01");
		$e->addAttribute("cy", $y - 14);
		$e->addAttribute("rx", "9.49");
		$e->addAttribute("ry", "9.49");
		$e->addAttribute("shapename", "3");
		$e->addAttribute("fill", "#808080");
		$e->addAttribute("st_id", "7");  //??
		$e->addAttribute("stroke", "#808080");
		$e->addAttribute("stroke-width", "1.00");
		$e->addAttribute("fade-time", "6");
		$e->addAttribute("fade-enable", "0");
		$e->addAttribute("metadatatoken", "annotationmetadata/metadata.xml");
		//$e->addAttribute("RotationPoint", "(194.000000,329.562500)");
		//$e->addAttribute("transform", "rotate(0.00,68.01,190.57)");
		$e->addAttribute("xml:id", "annotation." . id_generator::get_instance()->generate_id(), "http://www.w3.org/TR/2009/REC-xml-names-20091208/");
		$e->addAttribute("visible", "1");
	
		$e = $g->addChild("ellipse");
		$e->addAttribute("cx", "65.49");
		$e->addAttribute("cy", $y - 17.5);
		$e->addAttribute("rx", "9.49");
		$e->addAttribute("ry", "9.49");
		$e->addAttribute("shapename", "3");
		$e->addAttribute("fill", "#ffffff");
		$e->addAttribute("st_id", "7");  //??
		$e->addAttribute("stroke", "#000000");
		$e->addAttribute("stroke-width", "1.00");
		$e->addAttribute("fade-time", "6");
		$e->addAttribute("fade-enable", "0");
		$e->addAttribute("metadatatoken", "annotationmetadata/metadata.xml");
		//$e->addAttribute("RotationPoint", "(194.000000,329.562500)");
		//$e->addAttribute("transform", "rotate(0.00,68.01,190.57)");
		$e->addAttribute("xml:id", "annotation." . id_generator::get_instance()->generate_id(), "http://www.w3.org/TR/2009/REC-xml-names-20091208/");
		$e->addAttribute("visible", "1");
	}
	
	private function generate_checkbox($parent, $y) {
		$g = $parent->addChild("g");
		$g->addAttribute("class", "group");
		$g->addAttribute("xml:id", "annotation." . id_generator::get_instance()->generate_id(), "http://www.w3.org/TR/2009/REC-xml-names-20091208/");
		//$g->addAttribute("xbk_transform", "rotate(0.00,66.75,189.31)");
		
		$path = $g->addChild("path");
		$path->addAttribute("fill-rule", "evenodd");
		$path->addAttribute("slscaler", "0.033333");
		$path->addAttribute("d", "M60.6000 ". ($y - 20) ." L78.2700 " . ($y - 20) . " L78.2700 " . ($y - 3) . " L60.6000 " . ($y - 3) . " z");
		$path->addAttribute("shapename", "3");
		$path->addAttribute("fill", "#808080");
		$path->addAttribute("st_id", "46");  //??
		$path->addAttribute("stroke", "#808080");
		$path->addAttribute("stroke-width", "3.00");
		$path->addAttribute("fade-time", "6");
		$path->addAttribute("fade-enable", "0");
		//$path->addAttribute("RotationPoint", "(194.000000,329.562500)");
		//$path->addAttribute("transform", "rotate(0.00,68.01,190.57)");
		$path->addAttribute("xml:id", "annotation." . id_generator::get_instance()->generate_id(), "http://www.w3.org/TR/2009/REC-xml-names-20091208/");
		$path->addAttribute("visible", "1");
		
		$path = $g->addChild("path");
		$path->addAttribute("fill-rule", "evenodd");
		$path->addAttribute("slscaler", "0.033333");
		$path->addAttribute("d", "M56.0000 ". ($y - 25) ." L77.4500 ". ($y - 25) ." L77.4500 ". ($y - 4) ." L56.0000 ". ($y - 4) ." z");	
		$path->addAttribute("shapename", "3");
		$path->addAttribute("fill", "#ffffff");
		$path->addAttribute("st_id", "47");  //??
		$path->addAttribute("stroke", "#000000");
		$path->addAttribute("stroke-width", "1.00");
		$path->addAttribute("fade-time", "6");
		$path->addAttribute("fade-enable", "0");
		//$path->addAttribute("RotationPoint", "(194.000000,329.562500)");
		//$path->addAttribute("transform", "rotate(0.00,68.01,190.57)");
		$path->addAttribute("xml:id", "annotation." . id_generator::get_instance()->generate_id(), "http://www.w3.org/TR/2009/REC-xml-names-20091208/");
		$path->addAttribute("visible", "1");
	}
	
	private function generate_answer_input_element($class) {
		$ypos = $this->ypos + 10;
		$foreground = $this->xml->g;
		
		$g = $foreground->addChild("g");
		$g->addAttribute("class", $class);
		$g->addAttribute("xml:id", "annotation." . id_generator::get_instance()->generate_id(), "http://www.w3.org/TR/2009/REC-xml-names-20091208/");
		$g->addAttribute("language_direction", "1");
		
		$g = $g->addChild("g");
		$g->addAttribute("class", "textshape");
		$g->addAttribute("layoutx", "0.00");
		$g->addAttribute("layouty", "0.00");
		$g->addAttribute("layoutwidth", "1.00");
		$g->addAttribute("layoutheight", "1.00");
		
		$path = $g->addChild("path");
		$path->addAttribute("fill-rule", "evenodd");
		$path->addAttribute("slscaler", "0.033333");
		$path->addAttribute("d", "M30.0000 " . $ypos . " L448.3800 " . $ypos. " L448.3800 " . ($ypos + 27) ." L30.0000 " . ($ypos + 27) ." z");
		$path->addAttribute("shapename", "3");
		$path->addAttribute("fill", "none");
		$path->addAttribute("st_id", "3");
		$path->addAttribute("stroke", "#808080");
		$path->addAttribute("stroke-width", "1.00");
		$path->addAttribute("fade-time", "6");
		$path->addAttribute("fade-enable", "0");
		$path->addAttribute("stroke-dasharray", "3,1");
		//$path->addAttribute("RotationPoint", "(293.314392,136.895065)");
		//$path->addAttribute("transform", "rotate(0.00,239.19,114.86)");
		$path->addAttribute("xml:id", "annotation." . id_generator::get_instance()->generate_id(), "http://www.w3.org/TR/2009/REC-xml-names-20091208/");
		$path->addAttribute("visible", "1");
		
		$text = $g->addChild("text");
		$text->addAttribute("transform", "translate(30,$ypos) rotate(0.000,209.190,18.500) scale(1.000,1.000)");
		//$text->addAttribute("RotationPoint", "(320.000000,240.000000)");
		$text->addAttribute("xml:id", "annotation." . id_generator::get_instance()->generate_id(), "http://www.w3.org/TR/2009/REC-xml-names-20091208/");
		$text->addAttribute("visible", "1");
		$text->addAttribute("smart-txt-ver", "2.10");
		$text->addAttribute("autokern", "0");
		$text->addAttribute("editwidth", "418.38");
		$text->addAttribute("editheight", "37.00");
		$text->addAttribute("forcewidth", "1");
		$text->addAttribute("forceheight", "1");
		$text->addAttribute("language_direction", "1");
		$text->addAttribute("textdirection", "0");
		$text->addAttribute("theme_anno_style", "0");
		
		$p_tspan = $text->addChild("tspan");
		$p_tspan->addAttribute("justification", "center");
		$p_tspan->addAttribute("line-spacing", "1.00");
		$p_tspan->addAttribute("prepara-spacing", "1.00");
		$p_tspan->addAttribute("bullet", "0");
		
		$l_tspan = $p_tspan->addChild("tspan");
		
		$tspan = $l_tspan->addChild("tspan");
		$tspan->addAttribute("fill", "#FF0000");
		$tspan->addAttribute("font-size", "20.000");
		$tspan->addAttribute("font-family", "Arial");
		$tspan->addAttribute("char-transform", "0.00 1.00 0.00 0.00 0.00 1.00");
		$tspan->addAttribute("textLength", "5.56");
		$tspan->addAttribute("y", "18.11");
		$tspan->addAttribute("x", "72.69");
		
		$tspan = $l_tspan->addChild("tspan" , get_string('shortanswer_input', 'qformat_smart'));
		$tspan->addAttribute("fill", "#808080");
		$tspan->addAttribute("backgroundColor", "#FFFFFF");
		$tspan->addAttribute("background-opacity", "0");
		$tspan->addAttribute("font-size", "16.000");
		$tspan->addAttribute("font-family", "Arial");
		$tspan->addAttribute("char-transform", "0.00 1.00 0.00 0.00 0.00 1.00");
		$tspan->addAttribute("leading", "0.806");
		$tspan->addAttribute("textLength", "267.72");
		$tspan->addAttribute("y", "18.11");
		$tspan->addAttribute("x", "78.25");
		
		$this->ypos = $ypos + 40;
	}

}
