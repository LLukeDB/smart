<?php

require_once ($CFG->dirroot . '/question/format/smart/format.php');
require_once ($CFG->dirroot . '/question/format/smart/helper/filetools.php');
require_once ($CFG->dirroot . '/question/format/smart/generator/generator.php');
require_once ($CFG->dirroot . '/question/format/smart/helper/idgenerator.php');

/**
 * Baseclass for all generators.
 * 
 * @author Lukas Baumann
 *
 */
abstract class file_generator {
	protected abstract function generate_xml();
	public abstract function save($dir);
	
}

class imsmanifest_generator extends file_generator {

	private static $template = "generator/templates/imsmanifest.xml";

	private $xml;

	public function __construct(){
		global $CFG;

		// Load settings.xml-template.
		$filename = $CFG->dirroot . qformat_smart::get_plugin_dir() . imsmanifest_generator::$template;
		$this->xml = load_simplexml($filename);
	}

	protected function generate_xml() {
		// Nothing to generate.
	}

	public function save($dir) {
		$this->generate_xml();

		// Write settings.xml to directory.
		$filename = $dir . "imsmanifest.xml";
		return save_simplexml($this->xml, $filename);
	}

	public function add_page($page_name) {
		$page = $this->xml->resources->resource[0]->addChild("file");
		$page->addAttribute("href", $page_name);

		$page = $this->xml->resources->resource[1]->addChild("file");
		$page->addAttribute("href", $page_name);
	}

}

class metadatardf_generator extends file_generator {

	private $xml;
	private $questions;

	public function __construct(){
		$this->questions = array();
	}

	public function add_question($question) {
		array_push($this->questions, $question);
	}

	protected function generate_xml() {
		$this->init();

		foreach ($this->questions as $question) {
			$this->generate_answer_block($question);
			foreach ($question->choices as $choice) {
				$this->generate_choice_block($choice);
			}
			$this->generate_page_block($question);
		}

		return true;
	}

	public function save($dir) {
		$this->generate_xml();

		// Write metadata.rdf to directory.
		$filename = $dir . "metadata.rdf";
		return save_simplexml($this->xml, $filename);
	}

	function init() {
		$xml = simplexml_load_string("<rdf:RDF xmlns:dc=\"http://purl.org/dc/elements/1.1/\" xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\" xmlns:senteo=\"http://www.smarttech.com/2008/senteo/\"></rdf:RDF>");

		$doc = dom_import_simplexml($xml)->ownerDocument;
		$doc->encoding = 'UTF-8';
		$this->xml = $xml;

		return true;
	}

	function generate_answer_block($question) {
		switch ($question->questionformat) {
			case 'choice':
				$this->generate_answer_block_choice($question);
				break;
			case 'selection':
				$this->generate_answer_block_choice($question);
				break;
			case 'decimal':
				$this->generate_answer_block_numerical($question);
				break;
			case 'short-answer':
				$this->generate_answer_block_shortanswer($question);
				break;
		}
		return true;
	}

	function generate_answer_block_numerical($question) {
		$xml = $this->xml;
		$description = $xml->addChild("rdf:Description", "", "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$description->addAttribute("rdf:nodeID", "blank." . $question->answer_block_id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$description->addChild("senteo:maximumvalue", $question->maximumvalue, "http://www.smarttech.com/2008/senteo/");

		$description = $xml->addChild("rdf:Description", "", "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$description->addAttribute("rdf:nodeID", "blank." . $question->answer_block_id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$description->addChild("senteo:minimumvalue", $question->minimumvalue, "http://www.smarttech.com/2008/senteo/");

		$description = $xml->addChild("rdf:Description", "", "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$description->addAttribute("rdf:nodeID", "blank." . $question->answer_block_id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$description->addChild("senteo:points", $question->points, "http://www.smarttech.com/2008/senteo/");
	}

	function generate_answer_block_choice($question) {
		$xml = $this->xml;
		$description = $xml->addChild("rdf:Description", "", "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$description->addAttribute("rdf:nodeID", "blank." . $question->answer_block_id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$description->addChild("senteo:choicevalue", $question->get_true_choice_values(), "http://www.smarttech.com/2008/senteo/");

		$description = $xml->addChild("rdf:Description", "", "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$description->addAttribute("rdf:nodeID", "blank." . $question->answer_block_id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$description->addChild("senteo:points", $question->points, "http://www.smarttech.com/2008/senteo/");
	}

	function generate_answer_block_shortanswer($question) {
		$xml = $this->xml;
		$description = $xml->addChild("rdf:Description", "", "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$description->addAttribute("rdf:nodeID", "blank." . $question->answer_block_id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$description->addChild("senteo:exactmatches", $question->exactmatches, "http://www.smarttech.com/2008/senteo/");

		$description = $xml->addChild("rdf:Description", "", "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$description->addAttribute("rdf:nodeID", "blank." . $question->answer_block_id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$description->addChild("senteo:points", $question->points, "http://www.smarttech.com/2008/senteo/");
	}

	function generate_choice_block($choice) {
		$xml = $this->xml;
		$description = $xml->addChild("rdf:Description", "", "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$description->addAttribute("rdf:about", "urn:com.smarttech.notebook:annotation." . $choice->choice_id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$description->addChild("senteo:assessmentrole", "http://www.smarttech.com/2008/senteo/assessmentrole#choice", "http://www.smarttech.com/2008/senteo/");

		$description = $xml->addChild("rdf:Description", "", "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$description->addAttribute("rdf:about", "urn:com.smarttech.notebook:annotation." . $choice->choice_id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$description->addChild("senteo:choicevalue", $choice->choice_value, "http://www.smarttech.com/2008/senteo/");

		$description = $xml->addChild("rdf:Description", "", "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$description->addAttribute("rdf:about", "urn:com.smarttech.notebook:annotation." . $choice->choice_id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$type = $description->addChild("senteo:type", "", "http://www.smarttech.com/2008/senteo/");
		$type->addAttribute("rdf:resource", "http://www.smarttech.com/2008/notebook/Annotation", "http://www.w3.org/1999/02/22-rdf-syntax-ns#");

		return true;
	}

	function generate_page_block($question) {
		$xml = $this->xml;
		$description = $xml->addChild("rdf:Description", "", "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$description->addAttribute("rdf:about", "urn:com.smarttech.notebook:page." . $question->page_id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$answer = $description->addChild("senteo:answer", "", "http://www.smarttech.com/2008/senteo/");
		$answer->addAttribute("rdf:nodeID", "blank." . $question->answer_block_id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#");

		$description = $xml->addChild("rdf:Description", "", "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$description->addAttribute("rdf:about", "urn:com.smarttech.notebook:page." . $question->page_id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$description->addChild("senteo:assessmentrole", "http://www.smarttech.com/2008/senteo/assessmentrole#question", "http://www.smarttech.com/2008/senteo/");

		if($question->choicelabelstyle != "") {
			$description = $xml->addChild("rdf:Description", "", "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
			$description->addAttribute("rdf:about", "urn:com.smarttech.notebook:page." . $question->page_id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
			$description->addChild("senteo:choicelabelstyle", "http://www.smarttech.com/2008/senteo/choicelabelstyle#" . $question->choicelabelstyle, "http://www.smarttech.com/2008/senteo/");
		}

		$description = $xml->addChild("rdf:Description", "", "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$description->addAttribute("rdf:about", "urn:com.smarttech.notebook:page." . $question->page_id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$description->addChild("senteo:note", $question->explanation, "http://www.smarttech.com/2008/senteo/");

		$description = $xml->addChild("rdf:Description", "", "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$description->addAttribute("rdf:about", "urn:com.smarttech.notebook:page." . $question->page_id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$description->addChild("senteo:questionformat", "http://www.smarttech.com/2008/senteo/questionformat#" . $question->questionformat, "http://www.smarttech.com/2008/senteo/");

		$description = $xml->addChild("rdf:Description", "", "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$description->addAttribute("rdf:about", "urn:com.smarttech.notebook:page." . $question->page_id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$type = $description->addChild("rdf:type", "", "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$type->addAttribute("rdf:resource", "http://www.smarttech.com/2008/notebook/Page", "http://www.w3.org/1999/02/22-rdf-syntax-ns#");

		return true;
	}

}

class metadataxml_generator extends file_generator {

	private static $metadataxml_template = "generator/templates/metadata.xml";

	private $xml;

	public function __construct(){
		global $CFG;

		// Load metadata.xml-template.
		$filename = $CFG->dirroot . qformat_smart::get_plugin_dir() . metadataxml_generator::$metadataxml_template;
		$this->xml = load_simplexml($filename);
	}

	protected function generate_xml() {
		// write current date to metadata.xml
		$date = date("Y-m-d\TH:i:s");
		$this->xml->children('lom', true)->lifeCycle->children('smartgallery', true)->creationdatetime = $date;
	}

	public function save($dir) {
		$this->generate_xml();

		// Write metadata.xml to directory.
		$filename = $dir . "metadata.xml";
		return save_simplexml($this->xml, $filename);
	}

}

class settingsxml_generator extends file_generator {

	private static $settingsxml_template = "generator/templates/settings.xml";

	private $xml;

	public function __construct(){
		global $CFG;

		// Load settings.xml-template.
		$filename = $CFG->dirroot . qformat_smart::get_plugin_dir() . settingsxml_generator::$settingsxml_template;
		$this->xml = load_simplexml($filename);
	}

	protected function generate_xml() {
		// Nothing to generate.
	}

	public function save($dir) {
		$this->generate_xml();

		// Write settings.xml to directory.
		$filename = $dir . "settings.xml";
		return save_simplexml($this->xml, $filename);
	}

}

