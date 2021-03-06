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

require_once ($CFG->dirroot . '/question/format/smart/text/html_parser.php');

class parser_factory {
	public static function get_parser($type) {
		switch($type) {
			case 'html':
				return new html_parser();
			case 'svgtext':
				return new svgtext_parser();
			case FORMAT_HTML :
				return new html_parser();
			case FORMAT_MOODLE:
				return new html_parser();
			case FORMAT_PLAIN :
			    return new html_parser();
			default:
				$text_format = self::trans_format($type);
				print_error('noparserfound', 'qformat_smart', null, $text_format);
				return false;
		}	
	}
	
	public static function trans_format($name) {
		$name = trim($name);
	
		if ($name == FORMAT_MOODLE) {
			return 'moodle_auto_format';
		} else if ($name == FORMAT_HTML) {
			return 'html';
		} else if ($name == FORMAT_PLAIN) {
			return 'plain_text';
		} else if ($name == FORMAT_WIKI) {
			return 'wiki_like';
		} else if ($name == FORMAT_MARKDOWN) {
			return 'markdown';
		} else {
			return $name;
		}
	}
	
}

abstract class parser {
	
	/**
	 * Parses text, which is in the moodle html-format, into a text-object.
	 */
	abstract public function parse_to_text($text, $question, $questionnum);
	
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
