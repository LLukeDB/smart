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

require_once ($CFG->dirroot . '/question/format/smart/helper/simplexml_helper.php');

class text {
	
	private $paragraphs;
	
	public function __construct() {
		$this->paragraphs = array();
	}
	
	public function set_width($width) {
	    foreach ($this->paragraphs as $paragraph) {
	        $paragraph->set_width($width);
	    }
	}
	
	public function add_paragraph() {
		$p = new paragraph();
		array_push($this->paragraphs, $p);
	}
	
	public function add_textfragment($text, $formattings) {
		if(count($this->paragraphs) == 0) {
			$this->add_paragraph();
		}
		$p = $this->paragraphs[count($this->paragraphs) -1];
		$textfragment = new textfragment($text, $formattings);
		$p->add_textfragment($textfragment);
	}
	
	public function get_paragraphs() {
		$this->remove_empty_paragraphs();
		return $this->paragraphs;
	}

	public function toString() {
		$s = "<text>";
		foreach ($this->paragraphs as $p) {
			$s .= $p->toString();
		}
		$s .= "\n</text>";
		return $s;
	}
	
	public static function get_empty_text() {
		$text = new text();
		$text->add_paragraph();
		return $text;
	}
	
	public function append_text($text) {
		$this->paragraphs = array_merge($this->paragraphs, $text->get_paragraphs());
	}
	
	/*
	 * Removes empty paragraphs at the beginning and at the end of the text.
	 */
	private function remove_empty_paragraphs() {
		$this->remove_empty_paragraphs_at_the_beginning();
		$this->remove_empty_paragraphs_at_the_end();
	}
	
	private function remove_empty_paragraphs_at_the_beginning() {
		$paragraphs = $this->paragraphs;
		if(count($paragraphs) > 1) {
			if($paragraphs[0]->is_empty()) {
				$this->paragraphs = array_slice($paragraphs, 1);
				$this->remove_empty_paragraphs_at_the_beginning();
			}
		}
		return;
	}
	
	private function remove_empty_paragraphs_at_the_end() {
		$paragraphs = $this->paragraphs;
		if(count($paragraphs) > 1) {
			if($paragraphs[count($paragraphs) - 1]->is_empty()) {
				$this->paragraphs = array_slice($paragraphs, 0, count($paragraphs) - 1);
				$this->remove_empty_paragraphs_at_the_end();
			}
		}
		return;
	}
	
	public function get_metrics() {
	    $paragraphs = $this->get_paragraphs();
	    $tmetrics = new metrics();
	    $tmetrics->width = 0;
	    $tmetrics->height = 0;
	    foreach ($paragraphs as $paragraph) {
	        $pmetrics = $paragraph->get_metrics();
	        $tmetrics->height += $pmetrics->height;
	        if($pmetrics->width > $tmetrics->width) {
	            $tmetrics->width = $pmetrics->width;
	        }
	    }
	    return $tmetrics;
	}
	
}

class paragraph {
		
	private $lines;
	private $textfragments;
	private $width;
	
	public function __construct() {
	    $this->lines = array();
	    $this->textfragments = array();
	    $this->width = 0;
	}
	
	public function add_textfragment($textfragment) {
		array_push($this->textfragments, $textfragment);
	}
	
	public function set_width($width) {
	    $this->width = $width;
	}
	
	public function toString() {
		$s = "\n<tspan>\n\t<tspan>";
		foreach ($this->textfragments as $tf) {
			$s .= $tf->toString();
		}
		$s .= "\n\t</tspan>\n</tspan>";
		return $s;
	}
	
	public function get_lines() {
		if($this->width <= 0) {
			$lines = array();
			$line = new line($this->textfragments);
			array_push($lines, $line);
			$this->lines = $lines;
		}
		else {
			$this->calculate_lines();
		}
		return $this->lines;
	}
	
	/*
	 * Breaks paragraph into several lines with the given width.
	 */
	private function calculate_lines() {
		$splits = $this->split_textfragments($this->width);
		$lines = array();
		$line_fragments = array();
		$current_width = 0;
		if(count($splits) > 0){
			foreach ($splits as $split) {
				if($current_width + $split->get_metrics()->width <= $this->width) {
					$current_width += $split->get_metrics()->width;
					array_push($line_fragments, $split);
				}
				else {
					$merged_line_fragments = $this->merge_textfragments($line_fragments);
					$line = new line($merged_line_fragments);
					array_push($lines, $line);
					$line_fragments = array();
					array_push($line_fragments, $split);
					$current_width = $split->get_metrics()->width;
				}
			}
			if(count($line_fragments) > 0) {
				$merged_line_fragments = $this->merge_textfragments($line_fragments);
				$line = new line($merged_line_fragments);
				array_push($lines, $line);
			}
		}
		$this->lines = $lines;
	}
	
	/*
	 * Splits each textfragment into several textfragments each containing only a single word.
	 */
	private function split_textfragments($line_width) {
		if(count($this->textfragments) == 0) {
			return;
		}
		$textfragments = $this->merge_textfragments($this->textfragments);
		$new_textfragments = array();
		foreach($textfragments as $textfragment) {
			//$splits = preg_split("/ /", $textfragment->get_text());
			$splits = $this->split_string($textfragment->get_text());
			foreach ($splits as $split) {
				$new_textfragment = new textfragment($split, $textfragment->get_formattings());
				array_push($new_textfragments, $new_textfragment);
			}
		}
		
		$new_textfragments = $this->split_too_long_textfragments($new_textfragments, $line_width);
		return $new_textfragments;
	}
	
	private function split_string($text) {
		$splits = array();
		
		$subtext = "";
		for ($i = 0; $i < strlen($text); $i++) {
			$subtext .= $text[$i];
			//if($text[$i] == '#') {  // DEBUGGING space
			if($text[$i] == ' ') {
				array_push($splits, $subtext);
				$subtext = "";
			}
		}
		if(strlen($subtext) > 0) {
			array_push($splits, $subtext);
		}
		
		return $splits;
	}
	
	private function split_too_long_textfragments($textfragments, $line_width) {
		$new_textfragments = array();
		
		foreach ($textfragments as $textfragment) {
			if($textfragment->get_metrics()->width > $line_width) {
				$splits = $this->split_too_long_textfragment($textfragment, $line_width);
				$new_textfragments = array_merge($new_textfragments, $splits);
			}
			else {
				array_push($new_textfragments, $textfragment);
			}
		}
		
		return $new_textfragments;
	}
	
	private function split_too_long_textfragment($textfragment, $line_width) {
		$new_textfragments = array();
		$text = $textfragment->get_text();
		
		$subtext = "";
		$subtf;
		for($i = 0; $i < strlen($text); $i++) {
			$next_subtext = $subtext . $text[$i];
			$tf = new textfragment($next_subtext, $textfragment->get_formattings());
			if($tf->get_metrics()->width > $line_width) {
				array_push($new_textfragments, $subtf);
				$subtext = "" . $text[$i];
				$subtf = new textfragment($subtext, $textfragment->get_formattings());
			}
			else {
				$subtext = $next_subtext;
				$subtf = $tf;
			}
		}
		if(strlen($subtext) > 0) {
			array_push($new_textfragments, $subtf);
		}
		
		return $new_textfragments;
	}
	
	/*
	 * Merges adjacent textfragments, which have the same formattings.
	*/
	public function merge_textfragments($textfragments) {
		if(count($textfragments) == 0) {
			return;
		}
	
		$new_textfragments = array();
	
		$last_textfragment = null;
		foreach ($textfragments as $textfragment) {
			if($last_textfragment == null) {
				$last_textfragment = $textfragment;
			}
			else {
				$diff1 = array_diff_assoc($last_textfragment->get_formattings(), $textfragment->get_formattings());
				$diff2 = array_diff_assoc($textfragment->get_formattings(), $last_textfragment->get_formattings());
	
				if(count($diff1) == 0 && count($diff2) == 0) {
					$last_textfragment = new textfragment($last_textfragment->get_text() . $textfragment->get_text(), $last_textfragment->get_formattings());
				}
				else {
					array_push($new_textfragments, $last_textfragment);
					$last_textfragment = $textfragment;
				}
			}
		}
	
		array_push($new_textfragments, $last_textfragment);
	
		return $new_textfragments;
	}
	
	public function is_empty() {
		$return = true;
		foreach ($this->textfragments as $textfragment) {
			$text = trim($textfragment->get_text(), "\xc2\xa0");
			if(strlen($text) > 0) {
			//if(preg_match("/\S/", $text) == 0) {
				$return = false;
			}
		}
			
		return $return;
	}
	
	public function get_metrics() {
	    $lines = $this->get_lines();
	    $pmetrics = new metrics();
	    $pmetrics->width = 0;
	    $pmetrics->height = 0;
	    $width = 0;
	    $heigth = 0;
	    foreach ($lines as $line) {
	        $lmetrics = $line->get_metrics();
	        $pmetrics->height += $lmetrics->height + $lmetrics->leading;
	        if($lmetrics->width > $pmetrics->width) {
	            $pmetrics->width = $lmetrics->width;
	        }
	    }
	    return $pmetrics;
	}
}

class line {
	
	private $textfragments;
	private $metrics;

	public function __construct($textfragments) {
		$this->textfragments = $textfragments;
		$this->metrics = null;
	}
	
	public function get_textfragments() {
		return $this->textfragments;
	}
	
	public function set_textfragments($textfragments) {
		$this->textfragments = $textfragments;
		$this->metrics = null;
	}
	
	public function add_textfragment($textfragment) {
		array_push($this->textfragments, $textfragment);
		$this->metrics = null;
	}
	
	public function remove_last_textfragment() {
		array_pop($this->textfragments);
		$this->metrics = null;
	}
	
	public function get_metrics() {
		if($this->metrics == null) {
			$this->calculate_metrics();
		}
		return $this->metrics;
	}
	
	private function calculate_metrics() {
		$metrics = new metrics();
		
		// Calculate the baseline.
		$baseline = 0;
		foreach($this->textfragments as $textfragment) {
			$tf_baseline = $textfragment->get_metrics()->baseline;
			if($tf_baseline > $baseline) {
				$baseline = $tf_baseline;
			}
		}
		$metrics->baseline = $baseline;
		
		// Calculate the width.
		$width = 0;
		foreach($this->textfragments as $textfragment) {
			$tf_width = $textfragment->get_metrics()->width;
			$width += $tf_width;
		}
		$metrics->width = $width;
		
		// Calculate the height.
		$height = 0;
		foreach($this->textfragments as $textfragment) {
			$tf_height = $textfragment->get_metrics()->height;
			if($tf_height > $height) {
				$height = $tf_height;
			}
		}
		$metrics->height = $height;
		
		// Calculate the leading.
		$leading = 0;
		foreach($this->textfragments as $textfragment) {
		    $tfleading = $textfragment->get_metrics()->leading;
		    if($tfleading > $leading) {
		        $leading = $tfleading;
		    }
		}
		$metrics->leading = $leading;
		
		$this->metrics = $metrics;
	}
	
}

class textfragment {
	
	private $text;
	private $formattings;
	private $metrics;
	
	public function __construct($text, $formattings) {
		//$text = str_replace(" ", "#", $text);  // DEBUGGING space
		$this->text = $text;
		$this->formattings = $formattings;
		$this->metrics = null;
	}
	
	public function toString() {
		$s = "\n\t\t<tspan";
		foreach ($this->formattings as $key => $value) {
			$s .= " " . $key . "=\"" . $value . "\"";
		}
		$s .= ">" . $this->text . "</tspan>";
		return $s;
	}
	
	public function get_formattings() {
		return $this->formattings;
	}
	
	public function get_text() {
		return $this->text;
	}
	
	public function toSimpleXML() {
		$tspan = new SimpleXMLElement("<tspan>" . $this->text . "</tspan>");
		
		foreach($this->$formattings as $name => $value) {
			$tf_tspan->addAttribute($name, $value);
		}
		
		return $tspan;
	}
	
	public function get_metrics() {
		if($this->metrics == null) {
			$this->calculate_metrics();
		}
		return $this->metrics;
	}
	
	function strToHex($string){
		$hex='';
		for ($i=0; $i < strlen($string); $i++){
			$hex .= dechex(ord($string[$i]));
		}
		return $hex;
	}
	
	private function calculate_metrics() {
		// Set font properties.
		$im = new Imagick ();
		//$im->setResolution(800, 600);
		$draw = new ImagickDraw ();
		$draw->setStrokeColor ("none");
		//$font = str_replace(' ', '-', $this->formattings['font-family']);
		$font = $this->get_imagick_fontname();
		$draw->setFont($font);
		//$draw->setfontfamily($this->formattings['font-family']);
		$draw->setFontSize (intval($this->formattings['font-size']));
		$draw->setTextAlignment (Imagick::ALIGN_LEFT);
		$draw->settextencoding("UTF-8");
// 		if(array_key_exists('font-style', $this->formattings)) {
// 			$draw->setfontstyle(imagick::STYLE_ITALIC );
// 		}
// 		if(array_key_exists('font-weight', $this->formattings)) {
// 			$draw->setfontweight(600);
// 		}
// 		if(array_key_exists('text-decoration', $this->formattings)) {
// 			$draw->settextdecoration(imagick::DECORATION_UNDERLINE);
// 		}
// 		if(array_key_exists('text-strikeout', $this->formattings)) {
// 			$draw->settextdecoration(imagick::DECORATION_LINETROUGH);
// 		}
		
		// Query font metrics.
		$imagic_metrics = $im->queryFontMetrics ($draw, $this->text);
		
		// Create new metrics object.
		$metrics = new metrics();
		$metrics->baseline = $baseline = $imagic_metrics['boundingBox']['y2'];
		$metrics->width = $imagic_metrics['textWidth'];
		$metrics->height = $imagic_metrics['textHeight'];
		$metrics->leading = $imagic_metrics['maxHorizontalAdvance'] - $imagic_metrics['textHeight'];
		
		$this->metrics = $metrics;
	}
	
	private function get_imagick_fontname() {
		$fontname = strtolower($this->formattings['font-family']);
		$italic = array_key_exists('font-style', $this->formattings);
		$bold = array_key_exists('font-weight', $this->formattings);
		
		$imagick_fontname = "";
		
		// Translate fontname.
		if($fontname === "courier new") {
			$imagick_fontname = "Courier-New";
		}
		else if($fontname === "trebuchet ms") {
			$imagick_fontname = "Trebuchet-MS";
		}
		else if($fontname === "Trebuchet") {
			$imagick_fontname = "Trebuchet-MS";
		}
		else if($fontname === "arial") {
			$imagick_fontname = "Arial";
		}
		else if($fontname === "georgia") {
			$imagick_fontname = "Georgia";
		}
		else if($fontname === "tahoma") {
			$imagick_fontname = "Tahoma";  // Not available in italic!
			$italic = false;
		}
		else if($fontname === "times new roman") {
			$imagick_fontname = "Times-New-Roman";
		}
		else if($fontname === "verdana") {
			$imagick_fontname = "Verdana";
		}
		else if($fontname === "impact") {
			$imagick_fontname = "Impact";  // Only regular!
			$italic = false;
			$bold = false;
		}
		else if($fontname === "wingdings") {
			$imagick_fontname = "Wingdings";  // Only regular!
			$italic = false;
			$bold = false;
		}
		else {
			$imagick_fontname = $fontname;
		}
		
		// Set font style.
		if(!$italic && !$bold) {
			$imagick_fontname .= "-Regular";
		}
		else if($italic && !$bold) {
			$imagick_fontname .= "-Italic";
		}
		else if(!$italic && $bold) {
			$imagick_fontname .= "-Bold";
		}
		else if($italic && $bold) {
			$imagick_fontname .= "-Bold-Italic";
		}
		
		return $imagick_fontname;
	}
	
}

class metrics {
	public $baseline = null;
	public $width = null;
	public $height = null;
	public $leading = null;
}
