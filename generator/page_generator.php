<?php

require_once ($CFG->dirroot . '/question/format/smart/format.php');
require_once ($CFG->dirroot . '/question/format/smart/filetools.php');
require_once ($CFG->dirroot . '/question/format/smart/generator/generator.php');
require_once ($CFG->dirroot . '/question/format/smart/simplexml_helper.php');
require_once ($CFG->dirroot . '/question/format/smart/text/text.php');
require_once ($CFG->dirroot . '/question/format/smart/text/html_parser.php');

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
		return save_simplexml($this->xml, $filename);
	}

	protected function generate_xml() {
		$this->init();
		$this->generate_question();
		foreach($this->question->choices as $choice) {
			$this->ypos += 10; // Add margin.
			$this->generate_choice($choice);
		}
		$this->finish();
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
		$svg->addAttribute("xml:id", "page." . $this->question->page_id, "xml");
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
		$questiontext->addAttribute("likert", "");
		
		// Write 1st text-element.
		$text = $this->html_parser->parse_text($this->question->question_num);
		$label_geometry = $this->generate_text($g, $text, 30, 0, false);
		
		// Write 2nd text-element.
		$this->generate_text($g, $this->question->questiontext, 60);
		
		// Set remaining attributes.
		//$g->addAttribute("labelwidth", ceil($label_geometry['width']));
		$g->addAttribute("language_direction", 1);
		//$g->addAttribute("RotationPoint", "(350.000000,270.000000)"); // TODO Calculate coordiantes.
		//$g->addAttribute("transform", "rotate(0.00,160.46,45.64)"); // TODO Calculate coordiantes.
		$g->addAttribute("xml:id", "annotation." . id_generator::get_instance()->generate_id(), "xml");
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
		
		// Write 1st text-element (label).
		$label_geometry = $this->generate_text($g, $choice->choicetext, 83, 0, false);
	
		// Write 2nd text-element (text).
		$this->generate_text($g, text::get_empty_text(), 100);
		
		// Write bullets.
		$this->generate_true_false_label($g,  $this->ypos);
		
		// Set remaining attributes.
		//$g->addAttribute("xbk_transform", "rotate(0.00,113.57,128.92)"); // TODO Calculate coordinates.
		//$g->addAttribute("labelwidth", ceil($label_geometry['width']));
		$g->addAttribute("language_direction", 1);
		//$g->addAttribute("RotationPoint", "(376.000000,353.281250)"); 	// TODO Calculate coordinates.
		//$g->addAttribute("transform", "rotate(0.00,113.57,128.92)"); 	// TODO Calculate coordinates.
		$g->addAttribute("xml:id", "annotation." . $choice->choice_id, "xml");
		$g->addAttribute("visible", 1);
		
		return true;
	}
	
	private function generate_true_false_label($parent, $y) {
		$g = $parent->addChild("g");
		$g->addAttribute("class", "group");
		$g->addAttribute("xml:id", "annotation." . id_generator::get_instance()->generate_id(), "xml");
		$g->addAttribute("xbk_transform", "rotate(0.00,66.75,189.31)");
		
		$e = $g->addChild("ellipse");
		$e->addAttribute("cx", "68.01");
		$e->addAttribute("cy", $y);
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
		$e->addAttribute("xml:id", "annotation." . id_generator::get_instance()->generate_id(), "xml");
		$e->addAttribute("visible", "1");
		
		$e = $g->addChild("ellipse");
		$e->addAttribute("cx", "65.49");
		$e->addAttribute("cy", $y -2.52);
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
		$e->addAttribute("xml:id", "annotation." . id_generator::get_instance()->generate_id(), "xml");
		$e->addAttribute("visible", "1");
	}
	
	private function generate_text($parent, $text, $xpos, $width=0, $new_line=true) {
		$geometry = new stdClass();
		$geometry->rel_ypos = 0;
		
		// Create text element.
		$text_elem = $parent->addChild("text");
		
		// Generate all paragraphs.
		$paragraphs = $text->get_paragraphs();
		foreach ($paragraphs as $paragraph) {
			$this->generate_paragraph($text_elem, $paragraph, $geometry);
		}
		
		// Set Attributes of text element.
		//$geometry = $this->getTextGeometry($text_elem);
		//$ypos = $this->ypos + 10; // Add margin.
		$text_elem->addAttribute("transform", "translate($xpos," . $this->ypos . ")");
		//$text_elem->addAttribute("transform", "translate($xpos," . $this->ypos . ") rotate(0.000," . $geometry['width'] / 2 . "," . $geometry['height'] / 2 . ") scale(1.000,1.000)");
		//$text_elem->addAttribute("RotationPoint", "(346.500000,240.000000)"); // TODO  Calculate coordinates.
		$text_elem->addAttribute("xml:id", "annotation." . id_generator::get_instance()->generate_id(), "xml");
		$text_elem->addAttribute("visible", 1);
		$text_elem->addAttribute("smart-txt-ver", "2.10");
		//$text_elem->addAttribute("editwidth", $geometry['width']);
		//$text_elem->addAttribute("editheight", $geometry['height']);
// 		$text_elem->addAttribute("forcewidth", "0");
// 		$text_elem->addAttribute("forceheight", "0");
// 		$text_elem->addAttribute("language_direction", "1");
// 		$text_elem->addAttribute("textdirection", "0");
// 		$text_elem->addAttribute("theme_anno_style", "0");
		
		
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
			$this->generate_line($p_tspan, $line, $geometry);
		}
		
		return $geometry;
	}
	
	private function generate_line($parent, $line, $geometry) {
		$line_spacing = 1.0;
		
		// Create tspan-element for line.
		$line_tspan = $parent->addChild("tspan");
		
		$rel_xpos = 0;

		// Generate all textfragments.
		$textfragments = $line->get_textfragments();
		//$geometry->rel_ypos += 40;
		$generated_tspans = array();
		foreach ($textfragments as $textfragment) {
			$tf_tspan = $line_tspan->addChild("tspan" , $textfragment->get_text());
			$this->set_formatting_attributes($tf_tspan, $textfragment->get_formattings());
			array_push($generated_tspans, $tf_tspan);

			if($rel_xpos == 0) {
				$tf_tspan->addAttribute("x", $rel_xpos);
			}
			$rel_xpos += 1; // TODO
		}
		
		// Calculate height of line and set attributes.
		$line_height = $this->getTSpanGeometry($line_tspan)['height'];
		$geometry->rel_ypos += ($line_spacing + 1) * $line_height;
		foreach($generated_tspans as $tf_tspan) {
			$tf_tspan->addAttribute("y", "$geometry->rel_ypos");
		}
		
		return $geometry;
	}
	
	private function set_formatting_attributes($tf_tspan, $formattings) {
		foreach($formattings as $name => $value) {
			$tf_tspan->addAttribute($name, $value);
		}
	}
	
	private function getTSpanGeometry($tspan) {
		$svg = new SimpleXMLElement("<svg></svg>");
		$svg->addAttribute("width", 1000);
		$svg->addAttribute("height", 1400);
		$text_elem = $svg->addChild("text", "");
		$text_elem->addAttribute("transform", "translate(0, 500)");
		simplexml_append_child($tspan, $text_elem);
		$svg->saveXML("/opt/lampp/apps/moodle/moodledata/temp/asdf.svg"); // DEBUGGING
	
		$im = new Imagick();
		$im->readimageblob($svg->asXML());
		$im->setImageFormat("png"); // png24
		// $im->writeimages("/opt/lampp/apps/moodle/moodledata/temp/asdf.png", true);  // DEBUGGING
		$im->trimimage(0);
	
		$geometry = $im->getImageGeometry();
		$im->clear();
		$im->destroy();
		return $geometry;
	}
	
	private function getTextGeometry($text) {
		$svg = new SimpleXMLElement("<svg></svg>");
		$svg->addAttribute("width", 1000);
		$svg->addAttribute("height", 1400);
		simplexml_append_child($text, $svg);
	
		$im = new Imagick();
		$im->readimageblob($svg->asXML());
		$im->setImageFormat("png"); // png24
		$im->trimimage(0);
	
		$geometry = $im->getImageGeometry();
		$im->clear();
		$im->destroy();
		return $geometry;
	}

}