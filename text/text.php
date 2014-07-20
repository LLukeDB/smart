<?php

require_once ($CFG->dirroot . '/question/format/smart/helper/simplexml_helper.php');

class text {
	
	private $paragraphs = array();
	
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
}

class paragraph {
		
	private $lines = array();
	private $textfragments = array();
	
	public function add_textfragment($textfragment) {
		array_push($this->textfragments, $textfragment);
	}
	
	public function toString() {
		$s = "\n<tspan>\n\t<tspan>";
		foreach ($this->textfragments as $tf) {
			$s .= $tf->toString();
		}
		$s .= "\n\t</tspan>\n</tspan>";
		return $s;
	}
	
	public function get_lines($line_width = 0) {
		if($line_width <= 0) {
			$lines = array();
			$line = new line($this->textfragments);
			array_push($lines, $line);
			$this->lines = $lines;
		}
		else {
			$this->calculate_lines($line_width);
		}
		return $this->lines;
	}
	
	/*
	 * Breaks paragraph into several lines with the given width.
	 */
	private function calculate_lines($line_width) {
		$splits = $this->split_textfragments();
		$lines = array();
		$line_fragments = array();
		$current_width = 0;
		foreach ($splits as $split) {
			if($current_width + $split->get_metrics()->width <= $line_width) {
				$current_width += $split->get_metrics()->width;
				array_push($line_fragments, $split);
			}
			else {
				$line = new line($line_fragments);
				array_push($lines, $line);
				$line_fragments = array();
				array_push($line_fragments, $split);
				$current_width = $split->get_metrics()->width;
			}
		}
		if(count($line_fragments) > 0) {
			$line = new line($line_fragments);
			array_push($lines, $line);
		}
		$this->lines = $lines;
	}
	
	/*
	 * Splits each textfragment into several textfragments each containing only a single word.
	 */
	private function split_textfragments() {
		if(count($this->textfragments) == 0) {
			return;
		}
		$textfragments = $this->merge_textfragments($this->textfragments);
		$new_textfragments = array();
		foreach($textfragments as $textfragment) {
			$splits = preg_split("/ /", $textfragment->get_text());  // TODO improve regex
			foreach ($splits as $split) {
				$new_textfragment = new textfragment($split . " ", $textfragment->get_formattings());
				array_push($new_textfragments, $new_textfragment);
			}
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
}

class line {
	
	private $textfragments = array();
	private $metrics = null;
	
	public function __construct($textfragments) {
		$this->textfragments = $textfragments;
	}
	
	public function get_textfragments() {
		//$this->merge_textfragments();
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
		
		$this->metrics = $metrics;
	}
	
	/*
	 * Merges adjacent textfragments, which have the same formattings.
	*/
	public function merge_textfragments() {
		if(count($this->textfragments) == 0) {
			return;
		}
	
		$new_textfragments = array();
	
		$last_textfragment = null;
		foreach ($this->textfragments as $textfragment) {
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
	
		$this->textfragments = $new_textfragments;
	}
}

class textfragment {
	
	private $text;
	private $formattings = array();
	private $metrics = null;
	
	public function __construct($text, $formattings) {
		$this->text = $text;
		$this->formattings = $formattings;
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
	
// 	public function get_metrics_old() {
// 		if($this->text_metrics == null) {
// 			$this->calculate_metrics_old();
// 		}
// 		return $this->metrics;
// 	}
	
// 	private function calculate_metrics_old() {
// 		$text_metrics = new text_metrics();
			
// 		$tspan = new textfragment("Afqp", $this->formattings);
// 		$text_metrics->height = text_metrics::getTSpanGeometry($tspan->toSimpleXML())->height;
			
// 		$tspan = new textfragment("Af", $this->formattings);
// 		$text_metrics->upper_height = text_metrics::getTSpanGeometry($tspan->toSimpleXML())->height;
			
// 		$tspan = new textfragment("pq", $this->formattings);
// 		$text_metrics->lower_height = text_metrics::getTSpanGeometry($tspan->toSimpleXML())->height;
			
// 		$text_metrics->width = text_metrics::getTSpanGeometry($this->toSimpleXML())->width;
			
// 		$this->metrics = $text_metrics;
// 	}
	
	public function get_metrics() {
		if($this->metrics == null) {
			$this->calculate_metrics();
		}
		return $this->metrics;
	}
	
	private function calculate_metrics() {
		$im = new Imagick ();
		$im->setResolution(800, 600);
		$draw = new ImagickDraw ();
		$draw->setStrokeColor ("none");
		$draw->setFont ($this->formattings['font-family']);
		//$draw->setfontfamily($this->formattings['font-family']);
		$draw->setFontSize ($this->formattings['font-size']);
		$draw->setTextAlignment (Imagick::ALIGN_LEFT);
		//$draw->setfontstyle($this->formattings['font-style']);  // TODO
		//$draw->setfontweight($this->formattings['font-weight']);
		$imagic_metrics = $im->queryFontMetrics ($draw, $this->text);
		
		$metrics = new metrics();
		$metrics->baseline = $baseline = $imagic_metrics['boundingBox']['y2'];
		$metrics->width = $imagic_metrics['textWidth'] + 2 * $imagic_metrics['boundingBox']['x1'];
		$metrics->height = $imagic_metrics['textHeight'] + $imagic_metrics['descender'];
		
		$this->metrics = $metrics;
	}
	
}

class metrics {
	public $baseline;
	public $width;
	public $height;
}

// class text_metrics_old {
	
// 	public $height;
// 	public $upper_height;
// 	public $lower_height;
// 	public $width;
	
// 	public function __construct() {
// 		// TODO
// 	}
	
// 	public function get_height() {
// 		return $this->height;
// 	}
	
// 	public function get_upper_height() {
// 		return $this->upper_height;
// 	}
	
// 	public function get_lower_height() {
// 		return $this->lower_height;
// 	}
	
// 	public function get_width() {
// 		return $this->width;
// 	}
	
// 	public function get_base_height() {
// 		return -($this->height - $this->upper_height - $this->lower_height);
// 	}
	
// 	public function get_ascent() {
// 		return $this->height - $this->lower_height;
// 	}
	
// 	public function get_descent() {
// 		return $this->height - $this->upper_height;
// 	}
	
// 	public function get_baseline() {
// 		return $this->upper_height;
// 	}
	
	
// 	public static function getTSpanGeometry($tspan) {
// 		$svg = new SimpleXMLElement("<svg></svg>");
// 		$svg->addAttribute("width", 1000);
// 		$svg->addAttribute("height", 1400);
// 		$text_elem = $svg->addChild("text", "");
// 		$text_elem->addAttribute("transform", "translate(0, 500)");
// 		simplexml_append_child($tspan, $text_elem);
// 		//$svg->saveXML("/opt/lampp/apps/moodle/moodledata/temp/asdf.svg"); // DEBUGGING
	
// 		$im = new Imagick();
// 		$im->readimageblob($svg->asXML());
// 		$im->setImageFormat("png"); // png24
// 		// $im->writeimages("/opt/lampp/apps/moodle/moodledata/temp/asdf.png", true);  // DEBUGGING
// 		$im->trimimage(0);
	
// 		$geometry = $im->getImageGeometry();
// 		$im->clear();
// 		$im->destroy();
// 		return $geometry;
// 	}
	
// 	public static function getTextGeometry($text) {
// 		$svg = new SimpleXMLElement("<svg></svg>");
// 		$svg->addAttribute("width", 1000);
// 		$svg->addAttribute("height", 1400);
// 		simplexml_append_child($text, $svg);
	
// 		$im = new Imagick();
// 		$im->readimageblob($svg->asXML());
// 		$im->setImageFormat("png"); // png24
// 		$im->trimimage(0);
	
// 		$geometry = $im->getImageGeometry();
// 		$im->clear();
// 		$im->destroy();
// 		return $geometry;
// 	}
// }
