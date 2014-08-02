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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Exports questions in the SMART Notebook format.
 *
 * @package questionbank
 * @subpackage importexport
 * @copyright 2014 Lukas Baumann
 * @author Lukas Baumann
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later (5)
 */
require_once ("$CFG->libdir/xmlize.php");
require_once ($CFG->dirroot . '/lib/uploadlib.php');
require_once ($CFG->dirroot . '/question/format/smart/helper/filetools.php');
require_once ($CFG->dirroot . '/question/format/smart/exporter/export.php');
require_once ("$CFG->dirroot/question/format.php");
require_once ($CFG->dirroot . '/question/format/smart/helper/logging.php');
require_once ($CFG->dirroot . '/question/format/smart/generator/generator.php');
require_once ($CFG->dirroot . '/question/format/smart/generator/page_generator.php');
require_once ($CFG->dirroot . '/question/format/smart/importer/import.php');

class qformat_smart extends qformat_default {
	private static $plugin_dir = "/question/format/smart/"; 				// Folder where the plugin is installed, relative to Moodle $CFG->dirroot.
	
	public static function get_plugin_dir() {
		return qformat_smart::$plugin_dir;
	}
	
	/**
	 * @return bool whether this plugin provides export functionality.
	 */
	public function provide_export() {
		return true;
	}
	
	/**
	 * @return bool whether this plugin provides import functionality.
	 */
	public function provide_import() {
		return false;  // Not fully implemented yet.
	}
	
	public function mime_type() {
		return 'application/x-smarttech-notebook';
		//return 'multipart/x-zip';
	}
	
	// IMPORT FUNCTIONS START HERE
	
	/**
	 * Process the file
	 * This method should not normally be overidden
	 * @param object $category
	 * @return bool success
	 */
	public function importprocess($category) {
		global $USER, $CFG, $DB, $OUTPUT;
	
		// reset the timer in case file upload was slow
		set_time_limit(0);
	
		// STAGE 1: Parse the file
		echo $OUTPUT->notification('asdfjklöasfjklöasjdfjö', 'notifysuccess');
	
		$import_data = new import_data($this->filename);
		
		$importer_factory = new qformat_importer_factory();
		$questions = array();
		
		foreach($import_data->pages as $page) {
			
			$importer = $importer_factory->get_importer($page);
			if(!$importer) {
				print_error('question can not be imported!');  // TODO
			}
			$question = $importer->import();
			array_push($questions, $question);
		}
	
		// STAGE 2: Write data to database
		echo $OUTPUT->notification(get_string('importingquestions', 'question',
				$this->count_questions($questions)), 'notifysuccess');
	
		// check for errors before we continue
		if ($this->stoponerror and ($this->importerrors>0)) {
			echo $OUTPUT->notification(get_string('importparseerror', 'question'));
			return true;
		}
	
		// get list of valid answer grades
		$gradeoptionsfull = question_bank::fraction_options_full();
	
		// check answer grades are valid
		// (now need to do this here because of 'stop on error': MDL-10689)
		$gradeerrors = 0;
		$goodquestions = array();
		foreach ($questions as $question) {
			if (!empty($question->fraction) and (is_array($question->fraction))) {
				$fractions = $question->fraction;
				$invalidfractions = array();
				foreach ($fractions as $key => $fraction) {
					$newfraction = match_grade_options($gradeoptionsfull, $fraction,
							$this->matchgrades);
					if ($newfraction === false) {
						$invalidfractions[] = $fraction;
					} else {
						$fractions[$key] = $newfraction;
					}
				}
				if ($invalidfractions) {
					echo $OUTPUT->notification(get_string('invalidgrade', 'question',
							implode(', ', $invalidfractions)));
					++$gradeerrors;
					continue;
				} else {
					$question->fraction = $fractions;
				}
			}
			$goodquestions[] = $question;
		}
		$questions = $goodquestions;
	
		// check for errors before we continue
		if ($this->stoponerror && $gradeerrors > 0) {
			return false;
		}
	
		// count number of questions processed
		$count = 0;
	
		foreach ($questions as $question) {   // Process and store each question
	
			// reset the php timeout
			set_time_limit(0);
	
			// check for category modifiers
			if ($question->qtype == 'category') {
				if ($this->catfromfile) {
					// find/create category object
					$catpath = $question->category;
					$newcategory = $this->create_category_path($catpath);
					if (!empty($newcategory)) {
						$this->category = $newcategory;
					}
				}
				continue;
			}
			$question->context = $this->importcontext;
	
			$count++;
	
			echo "<hr /><p><b>$count</b>. ".$this->format_question_text($question)."</p>";
	
			$question->category = $this->category->id;
			$question->stamp = make_unique_id_code();  // Set the unique code (not to be changed)
	
			$question->createdby = $USER->id;
			$question->timecreated = time();
			$question->modifiedby = $USER->id;
			$question->timemodified = time();
			$fileoptions = array(
			'subdirs' => false,
			'maxfiles' => -1,
			'maxbytes' => 0,
			);
	
			$question->id = $DB->insert_record('question', $question);
	
			if (isset($question->questiontextitemid)) {
					$question->questiontext = file_save_draft_area_files($question->questiontextitemid,
					$this->importcontext->id, 'question', 'questiontext', $question->id,
					                                $fileoptions, $question->questiontext);
					} else if (isset($question->questiontextfiles)) {
					foreach ($question->questiontextfiles as $file) {
							question_bank::get_qtype($question->qtype)->import_file(
							$this->importcontext, 'question', 'questiontext', $question->id, $file);
			}
		}
			if (isset($question->generalfeedbackitemid)) {
			$question->generalfeedback = file_save_draft_area_files($question->generalfeedbackitemid,
			$this->importcontext->id, 'question', 'generalfeedback', $question->id,
			$fileoptions, $question->generalfeedback);
	} else if (isset($question->generalfeedbackfiles)) {
    	foreach ($question->generalfeedbackfiles as $file) {
    	    question_bank::get_qtype($question->qtype)->import_file(
    		$this->importcontext, 'question', 'generalfeedback', $question->id, $file);
    	}
	}
			$DB->update_record('question', $question);
	
			$this->questionids[] = $question->id;
	
			// Now to save all the answers and type-specific options
	
				$result = question_bank::get_qtype($question->qtype)->save_question_options($question);
	
				if (!empty($CFG->usetags) && isset($question->tags)) {
				require_once($CFG->dirroot . '/tag/lib.php');
						tag_set('question', $question->id, $question->tags);
	}
	
	if (!empty($result->error)) {
	echo $OUTPUT->notification($result->error);
	return false;
	}
	
	if (!empty($result->notice)) {
	echo $OUTPUT->notification($result->notice);
	return true;
	}
	
	// Give the question a unique version stamp determined by question_hash()
	$DB->set_field('question', 'version', question_hash($question),
	array('id' => $question->id));
	}
	return true;
	}
	
	// EXPORT FUNCTIONS START HERE
	
	/**
	 * @return string file extension
	 */
	function export_file_extension() {
		return ".notebook";
	}
	
	/**
	 * Do any pre-processing that may be required
	 *
	 * @return     	bool success
	 */
	public function exportpreprocess() {
		return true;
	}
	
	/**
	 * Enable any processing to be done on the content
	 * just prior to the file being saved
	 * default is to do nothing
	 *
	 * @param	string output text
	 *        	
	 * @param	string processed output text
	 *        	
	 */
	protected function presave_process($content) {
		return $content;
	}
	
	/**
	 * Do the export
	 * For most types this should not need to be overrided
	 *
	 * @return stored_file
	 */
	public function exportprocess() {
		global $CFG, $OUTPUT, $DB, $USER;

		// get the questions (from database) in this category
		// only get q's with no parents (no cloze subquestions specifically)
		if ($this->category) {
			$questions = get_questions_category ( $this->category, true );
		} else {
			$questions = $this->questions;
		}
		
		// Check if the neccessary extensions are loaded.
		if(!extension_loaded('imagick')) {
			$error_msg_params = "Imagick";
			print_error('missingextension', 'qformat_smart', $this->get_continue_path(), $error_msg_params);
		}
		
		// Export the questions.
		$filecontent = $this->export_questions($questions);
		
		// Start the download of the export file.
		$this->start_download($filecontent);
	}
	
	public function get_continue_path() {
		$course = $this->course;
		$continuepath = "$CFG->wwwroot/question/export.php?courseid=$course->id";
		return $continuepath;
	}
	
	public function export_questions($questions) {
	    
	    $exporters = array();
	    $unsupported_questions = array();
	    
	    foreach($questions as $question) {
	        $exporter = qtype_exporter_factory::get_exporter($question);
	    
	        if(!$exporter) {
	            array_push($unsupported_questions, $question);
	        }
	        else {
	            array_push($exporters, $exporter);
	        }
	    }
	    
	    // Create error message if there are unsuported questions.
	    if(count($unsupported_questions) > 0) {
	        // Create string list with unsupported questions.
	        $error_msg_params = "";
	        foreach($unsupported_questions as $question) {
	            $error_msg_params .= " - " . $question->name . " [" . get_string($question->qtype, 'quiz') . "]<br/>";
	        }
	        	
	        print_error('unsupportedquestiontype', 'qformat_smart', $this->get_continue_path(), $error_msg_params);
	    }
	    
	    // Export all questions.
	    $export_data = new export_data();
	    foreach ( $exporters as $exporter ) {
	        $exporter->export($export_data);
	    }
	    
	    // Export logged errors.
	    $log = error_logger::get_instance()->get_error_log();
	    if(count($log) > 0) {
	        $dummy_question=new stdClass();
	        $dummy_question->qtype='log';
	        $exporter = qtype_exporter_factory::get_exporter($dummy_question);
	        $exporter->export($export_data);
	    }
	    
	    // Create zip-file from export_data.
	    $zip_file = $export_data->toZIP();
	    
	    // Return the zip file.
	    $filehandle = fopen($zip_file, "r");
	    $filecontent = fread($filehandle, filesize($zip_file));
	    fclose($filehandle);
	    unlink($zip_file);
	    
	    return $filecontent;
	}
	
	/**
	 * Do an post-processing that may be required
	 *
	 * @return bool success
	 */
	protected function exportpostprocess() {
		return true;
	}
	
	private function start_download($filecontent) {
		$name = "quiz-" . $this->course->shortname . "-" . $this->category->name . "-" . $date = date("Ymd-Hi") . ".notebook";
		$name = str_replace(" ", "_", $name);
		$encoding = "BASE64";
		
		header('Content-Description: File Transfer');
		header('Content-Type: '. $this->mime_type());
		header('Content-Disposition: attachment; filename='. $name);
		header('Content-Transfer-Encoding: '.$encoding);
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		ob_clean();
		flush();
		echo $filecontent;
		die();
	}
}


