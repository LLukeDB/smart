<?php

require_once ('html_parser.php');

class parser_factory {
	public static function get_parser($type) {
		switch($type) {
			case 'html':
				return new html_parser();
			case 'svgtext':
				return new svgtext_parser();
			case '1':
				return new html_parser();
			default:
				// TODO printerror
				return false;
		}	
	}
	
}

abstract class parser {
	
	/**
	 * Parses text, which is in the moodle html-format, into a text-object.
	 */
	abstract public function parse_to_text($text);
	
	/**
	 * Parses text, which is in the moodle html-format, into plain text (string).
	 */
	abstract public function parse_to_string($text);
}

class svgtext_parser {
	
	/*
	 * @param $text SimpleXML-Node of the text-elment.
	 */
	public function parse_to_text($text) {
		$plain_text = strip_tags($text->asXML());
		$html = "<p>" . $plain_text . "</p>";
		return $html;
	}
}

?>
