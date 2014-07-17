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
	abstract public function parse_text($text);
}

class svgtext_parser {
	
	/*
	 * @param $text SimpleXML-Node of the text-elment.
	 */
	public function parse_text($text) {
		$plain_text = strip_tags($text->asXML());
		$html = "<p>" . $plain_text . "</p>";
		return $html;
	}
}

?>
