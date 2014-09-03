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
 * Unit tests for the SMART Notebook format.
 * 
 * @package qformat_smart
 * @copyright 2014 Lukas Baumann
 * @author Lukas Baumann
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later (5)
 */

require_once ($CFG->dirroot . '/question/format/smart/text/text.php');
require_once ($CFG->dirroot . '/question/format/smart/text/parse.php');
require_once ($CFG->dirroot . '/question/format/smart/helper/logging.php');

class html_parser extends parser {

private $formattings;
private $xml_parser;
private $text;
private $question;
private $questionnum;

private function init_parser() {
	$this->formattings = array();
	
	// Init parser.
	$this->xml_parser = xml_parser_create ();
	xml_parser_set_option ( $this->xml_parser, XML_OPTION_CASE_FOLDING, 0 );
	xml_parser_set_option ( $this->xml_parser, XML_OPTION_SKIP_WHITE, 1 );
	xml_set_default_handler ( $this->xml_parser, "parseDEFAULT" );
	xml_set_element_handler ( $this->xml_parser, "html_parser::startElement", "html_parser::endElement" );
	xml_set_character_data_handler ( $this->xml_parser, "html_parser::contents" );
	
	// Set default formattings.
	$default_formattings = $this->get_defalt_formattings();
	array_push($this->formattings, $default_formattings);
	
	// Create text-object.
	$this->text = new text();
}

public static function get_defalt_formattings() {
	$default_formattings = array("fill" => "#000000",
			"font-size" => "20.000",		// TODO Adjust.
			"font-family" => "Arial",
			"char-transform" => "0.00 1.00 0.00 0.00 0.00 1.00");
	return $default_formattings;
}

/*
 * @param $text text in html-format, which should be parsed
 * 		  $question moodle question-object - needed for error logging
 */
public function parse_to_text($text, $question=null, $questionnum=null) {
	$this->init_parser();
	$this->question = $question;
	$this->questionnum = $questionnum;
	
	// Prepare text.
	$text = str_replace("\n", "", $text);
	$text = str_replace("\r", "", $text);
	
	// Create surrounding text-elements to get valid xml.
	$text = "<text>" . $text . "</text";
	
	if (! xml_parse ( $this->xml_parser, $text )) {
		$a = new stdClass();
		$a->questionname = $this->question->name;
		$a->text = $text;
		print_error('parsingfailed', 'qformat_smart', null, $a);
	}
	xml_parser_free ( $this->xml_parser );
	
	return $this->text;
}

public function parse_to_string($text) {
	// Remove linebreaks from text.
	$text = str_replace("\n", "", $text);
	return strip_tags($text);
}

/*
 * Callback function for the parser, which handles start-nodes.
 */
private function startElement($parser, $name, $attrs) {
	switch ($name) {
		case "br" :
			$this->text->add_paragraph();
			break;
		case "strong" :
			$new_formattings = array("font-weight" => "bold");
			array_push($this->formattings, $new_formattings);
			break;
		case "em" :
			$new_formattings = array("font-style" => "italic");
			array_push($this->formattings, $new_formattings);
			break;
		case "span" :
			$this->parse_attributes($attrs);
			break;
		case "text" :
		    // Do nothing.
		    break;
		case "p" :
		    $this->text->add_paragraph();
		    $this->parse_attributes($attrs);
		    break;
		case "li" :
			$this->text->add_paragraph();
			break;
		case "tbody" :
			// Do nothing.
			break;
		case "td" :
			// Do nothing.
			break;
		case "tr" :
			$this->text->add_paragraph();
			break;
		default :
			$this->log_error($name);
			break;
	}
}

/*
 * Callback function for the parser, which handles end-nodes.
 */
private function endElement($parser, $name) {
	switch ($name) {
		case "text" :
			// Do nothing.
			break;
		case "p" :
			// Do nothing.
			break;
		case "br" :
			// Do nothing.
			break;
		case "strong" :
			array_pop($this->formattings);
			break;
		case "em" :
			array_pop($this->formattings);
			break;
		case "span" :
			array_pop($this->formattings);
			break;
		default :
			// Do nothing.
			break;
	}
}

private function parse_attributes($attrs) {
	foreach($attrs as $key => $value) {
		if($key == "style") {
			$this->parse_style_attribue($value);
		}
		else {
			array_push($this->formattings, array());
			$this->log_error($key);
		}
	}
}

private function parse_style_attribue($attrval) {
	$styles = preg_split("/;\s*/", $attrval, -1, PREG_SPLIT_NO_EMPTY);
	$splitted_styles = array();
	foreach ($styles as $num => $style) {
		$splitted_style = preg_split("/:\s*/", $style);
		if(count($splitted_style) != 2) {
			$this->log_error($attrval);
		}
		else {
			$splitted_styles[$splitted_style[0]] = $splitted_style[1];
		}
	}
	
	$translated_styles = array();
	foreach($splitted_styles as $stylename => $stylevalue) {
		$translated_style = $this->translate_style($stylename, $stylevalue);
		$translated_styles = array_merge($translated_styles, $translated_style);
	}
	array_push($this->formattings, $translated_styles);
}

/*
 * Translates one style, which was specified by the style-attribute of a moodle-text into a smart style. 
 */
private function translate_style($stylename, $stylevalue) {
	$returnvalue = array();
	
	switch($stylename) {
		case "font-family": 
			$fonts = preg_split("/,\s*/", $stylevalue);
			if(count($fonts) >= 2) {
				$font = $fonts[0]; // Take only the first specified font.
			}
			else {
				$font = $fonts[0];
			}
			$font = str_replace("'", "", $font);
			//$font = preg_replace('/[^A-Za-z0-9 -_]/', '', $font);
			$returnvalue = array("font-family" => $font); 
			break;
		case "font-size":
			$font_size = "";
			switch($stylevalue) {
				case "xx-small":
					$font_size = "10";
					break;
				case "x-small":
					$font_size = "16";
					break;
				case "small":
					$font_size = "22";
					break;
				case "medium":
					$font_size = "28";
					break;
				case "large":
					$font_size = "34";
					break;
				case "x-large":
					$font_size = "40";
					break;
				case "xx-large":
					$font_size = "46";
					break;
				default:
					$font_size = "28";
					break;
			}
			$returnvalue = array("font-size" => $font_size);
			break;
		case "text-decoration":
			switch($stylevalue) {
				case "underline":
					$returnvalue = array("text-decoration" => "underline");
					break;
				case "line-through":
					$returnvalue = array("text-strikeout" => "strikeout");
					break;
				default:
					$this->log_error($stylename . '="' . $stylevalue . '"');
					break;
			}
			break;
		case "text-align":
			if($stylevalue != "left") {
				$this->log_error($stylename . '="' . $stylevalue . '"');
			}
			break;
		case "color":
			$returnvalue = array("fill" => $stylevalue);
			break;
		default:
			$this->log_error($stylename . '="' . $stylevalue . '"');
			break;
	}
	
	return $returnvalue;
}


/*
 * Callback function for the parser, which handles text-nodes.
 */
private function contents($parser, $data) {
	$formattings = $this->get_formattings();
	$this->text->add_textfragment($data, $formattings);
}

/*
 * Returns the formattings for the current text-node as a single associative array.
 */
private function get_formattings() {
	$merged_formattings = array();
	foreach ($this->formattings as $formatting) {
		$merged_formattings = array_merge($merged_formattings, $formatting);
	}
	
	return $merged_formattings;
}

private function log_error($formatting) {
	// Create log entry only if we know in which question the error is.
	if($this->question != null) {
		// Create object for string params.
		$a = new stdClass();
		$a->formatting = get_string($formatting, 'qformat_smart');
		$a->questiontitle = $this->question->name;
		$a->questionnum = $this->questionnum;
		
		$error_msg = '<p><span font-size="small">- ' . get_string('formatting_error', 'qformat_smart', $a) . '</span></p>';
		
		error_logger::get_instance()->log_error($error_msg);
	}
}

}
