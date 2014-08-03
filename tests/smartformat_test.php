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
 * Unit tests for the SMART Notebook format.
 * 
 * @package qformat_smart
 * @copyright 2014 Lukas Baumann
 * @author Lukas Baumann
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later (5)
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once ($CFG->libdir . '/questionlib.php');
require_once ($CFG->dirroot . '/question/format/xml/format.php');
require_once ($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once ($CFG->dirroot . '/question/format/smart/importer/import.php');

class qformat_smart_test extends question_testcase {

    public function assert_same_xml($expectedxml, $xml) {
        $expectedxml = str_replace("\n", "", $expectedxml);
        $expectedxml = str_replace("\r", "", $expectedxml);
        $xml = str_replace("\n", "", $xml);
        $xml = str_replace("\r", "", $xml);
        $expectedxml = preg_replace('/>\s+</', '><', $expectedxml);
        $xml = preg_replace('/>\s+</', '><', $xml);
        $this->assertEquals($expectedxml, $xml);
    }

    public function zip_to_import_data($zipcontent) {
        global $CFG;
        
        $moodletmpdir = $CFG->dataroot . "/temp/";
        $tmpfile = tempnam($moodletmpdir, 'smart_');
        
        $handle = fopen($tmpfile, "w");
        fwrite($handle, $zipcontent);
        fclose($handle);
        
        $import_data = new import_data($tmpfile);
        unlink($tmpfile);
        
        return $import_data;
    }

    public function make_test_question() {
        global $USER;
        $q = new stdClass();
        $q->id = 0;
        $q->contextid = 0;
        $q->category = 0;
        $q->parent = 0;
        $q->questiontextformat = FORMAT_HTML;
        $q->generalfeedbackformat = FORMAT_HTML;
        $q->defaultmark = 1;
        $q->penalty = 0.3333333;
        $q->length = 1;
        $q->stamp = make_unique_id_code();
        $q->version = make_unique_id_code();
        $q->hidden = 0;
        $q->timecreated = time();
        $q->timemodified = time();
        $q->createdby = $USER->id;
        $q->modifiedby = $USER->id;
        return $q;
    }

    /*
     * Test export of a truefalse-question with 'True' as the right answer.
     */
    public function test_export_truefalse_true() {
        // Create moodle question object.
        $qdata = new stdClass();
        $qdata->id = 12;
        $qdata->contextid = 0;
        $qdata->qtype = 'truefalse';
        $qdata->name = 'True false question';
        $qdata->questiontext = 'The answer is true.';
        $qdata->questiontextformat = FORMAT_HTML;
        $qdata->generalfeedback = 'General feedback';
        $qdata->generalfeedbackformat = FORMAT_HTML;
        $qdata->defaultmark = 1;
        $qdata->length = 1;
        $qdata->penalty = 1;
        $qdata->hidden = 0;
        
        $qdata->options = new stdClass();
        $qdata->options->answers = array(
                1 => new question_answer(1, 'True', 1, 'Good', FORMAT_HTML),
                2 => new question_answer(2, 'False', 0, 'Not so good', FORMAT_HTML)
        );
        $qdata->options->trueanswer = 1;
        $qdata->options->falseanswer = 2;
        
        // Export the question.
        $exporter = new qformat_smart();
        $questions = array($qdata);
        $zipcontent = $exporter->export_questions($questions);
        
        // Open exported question as import_data.
        $import_data = $this->zip_to_import_data($zipcontent);
        
        /*
         * Test content of imsmanifest.xml
         */
        
        $imsmanifest = $import_data->imsmanifest;
        
        $expected = '<resource identifier="group0_pages" href="page0.svg" type="webcontent" adlcp:scormType="asset"><file href="page0.svg"/></resource>';
        $actual = $imsmanifest->resources->resource[0]->asXML();
        $this->assert_same_xml($expected, $actual);
        
        $expected = '<resource identifier="pages" href="page0.svg" type="webcontent" adlcp:scormType="asset"><file href="page0.svg"/></resource>';
        $actual = $imsmanifest->resources->resource[1]->asXML();
        $this->assert_same_xml($expected, $actual);
        
        /*
         * Test content of page.
         */
        
        // Test number of pages.
        $this->assertCount(1, $import_data->pages);

        $page = $import_data->pages[0];

        // Test the number of question elements.
        $questions = $page->xpath('//g[@class="question"]');
        $this->assertCount(1, $questions);
        
        // Test the question element.
        $question = $questions[0];
        $expectedvotemetadata = '<votemetadata><questiontext format="trueorfalse" labelstyle="true/false" correct="1" points="1" tags="" explanation="General feedback" mathgradingoption="" likert=""/></votemetadata>';
        $votemetadata = $question->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
        
        // Test the question number.
        $expectedquestionnumber = '1';
        $questionnumber = strip_tags($question->text[0]->asXML());
        $this->assertEquals($expectedquestionnumber, $questionnumber);
        
        // Test the question text without formattings.
        $expectedquestiontext = 'The answer is true.';
        $questiontext = strip_tags($question->text[1]->asXML());
        $this->assertEquals($expectedquestiontext, $questiontext);
        
        // Test the number of choices.
        $choices = $page->xpath('//g[@class="questionchoice"]');
        $this->assertCount(2, $choices);
        
        // Test the true choice element.
        $choice = $choices[0];
        $expectedvotemetadata = '<votemetadata><choicetext label="1"/></votemetadata>';
        $votemetadata = $choice->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
        
        // Test the choice text without formattings.
        $expectedchoicetext = get_string('true', 'qtype_truefalse');
        $choicetext = strip_tags($choice->text[0]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test the false choice element.
        $choice = $choices[1];
        $expectedvotemetadata = '<votemetadata><choicetext label="2"/></votemetadata>';
        $votemetadata = $choice->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
        
        // Test the choice text without formattings.
        $expectedchoicetext = get_string('false', 'qtype_truefalse');
        $choicetext = strip_tags($choice->text[0]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
    }
    
    /*
     * Test export of a truefalse-question with 'False' as the right answer.
    */
    public function test_export_truefalse_false() {
        // Create moodle question object.
        $qdata = new stdClass();
        $qdata->id = 12;
        $qdata->contextid = 0;
        $qdata->qtype = 'truefalse';
        $qdata->name = 'True false question';
        $qdata->questiontext = 'The answer is true.';
        $qdata->questiontextformat = FORMAT_HTML;
        $qdata->generalfeedback = 'General feedback';
        $qdata->generalfeedbackformat = FORMAT_HTML;
        $qdata->defaultmark = 1;
        $qdata->length = 1;
        $qdata->penalty = 1;
        $qdata->hidden = 0;
    
        $qdata->options = new stdClass();
        $qdata->options->answers = array(
                1 => new question_answer(1, 'True', 0, 'Good', FORMAT_HTML),
                2 => new question_answer(2, 'False', 1, 'Not so good', FORMAT_HTML)
        );
        $qdata->options->trueanswer = 1;
        $qdata->options->falseanswer = 2;
    
        // Export the question.
        $exporter = new qformat_smart();
        $questions = array($qdata);
        $zipcontent = $exporter->export_questions($questions);
    
        // Open exported question as import_data.
        $import_data = $this->zip_to_import_data($zipcontent);
    
        /*
         * Test content of imsmanifest.xml
        */
    
        $imsmanifest = $import_data->imsmanifest;
    
        $expected = '<resource identifier="group0_pages" href="page0.svg" type="webcontent" adlcp:scormType="asset"><file href="page0.svg"/></resource>';
        $actual = $imsmanifest->resources->resource[0]->asXML();
        $this->assert_same_xml($expected, $actual);
    
        $expected = '<resource identifier="pages" href="page0.svg" type="webcontent" adlcp:scormType="asset"><file href="page0.svg"/></resource>';
        $actual = $imsmanifest->resources->resource[1]->asXML();
        $this->assert_same_xml($expected, $actual);
    
        /*
         * Test content of page.
        */
    
        // Test number of pages.
        $this->assertCount(1, $import_data->pages);
    
        $page = $import_data->pages[0];
    
        // Test the number of question elements.
        $questions = $page->xpath('//g[@class="question"]');
        $this->assertCount(1, $questions);
    
        // Test the question element.
        $question = $questions[0];
        $expectedvotemetadata = '<votemetadata><questiontext format="trueorfalse" labelstyle="true/false" correct="2" points="1" tags="" explanation="General feedback" mathgradingoption="" likert=""/></votemetadata>';
        $votemetadata = $question->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
    
        // Test the question number.
        $expectedquestionnumber = '1';
        $questionnumber = strip_tags($question->text[0]->asXML());
        $this->assertEquals($expectedquestionnumber, $questionnumber);
    
        // Test the question text without formattings.
        $expectedquestiontext = 'The answer is true.';
        $questiontext = strip_tags($question->text[1]->asXML());
        $this->assertEquals($expectedquestiontext, $questiontext);
    
        // Test the number of choices.
        $choices = $page->xpath('//g[@class="questionchoice"]');
        $this->assertCount(2, $choices);
    
        // Test the true choice element.
        $choice = $choices[0];
        $expectedvotemetadata = '<votemetadata><choicetext label="1"/></votemetadata>';
        $votemetadata = $choice->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
    
        // Test the choice text without formattings.
        $expectedchoicetext = get_string('true', 'qtype_truefalse');
        $choicetext = strip_tags($choice->text[0]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
    
        // Test the false choice element.
        $choice = $choices[1];
        $expectedvotemetadata = '<votemetadata><choicetext label="2"/></votemetadata>';
        $votemetadata = $choice->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
    
        // Test the choice text without formattings.
        $expectedchoicetext = get_string('false', 'qtype_truefalse');
        $choicetext = strip_tags($choice->text[0]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
    
    
    }
    
    /*
     * Test export of a multiple-choice-question with one right answer.
    */
    public function test_export_multichoice_1() {
        $qdata = new stdClass();
        $qdata->id = 123;
        $qdata->contextid = 0;
        $qdata->qtype = 'multichoice';
        $qdata->name = 'Multiple choice question';
        $qdata->questiontext = 'Questiontext of muliple choice question.';
        $qdata->questiontextformat = FORMAT_HTML;
        $qdata->generalfeedback = 'General feedback';
        $qdata->generalfeedbackformat = FORMAT_HTML;
        $qdata->defaultmark = 23;
        $qdata->length = 1;
        $qdata->penalty = 0.3333333;
        $qdata->hidden = 0;
    
        $qdata->options = new stdClass();
        $qdata->options->single = 0;
        $qdata->options->shuffleanswers = 0;
        $qdata->options->answernumbering = 'abc';
        $qdata->options->correctfeedback = '<p>Your answer is correct.</p>';
        $qdata->options->correctfeedbackformat = FORMAT_HTML;
        $qdata->options->partiallycorrectfeedback = '<p>Your answer is partially correct.</p>';
        $qdata->options->partiallycorrectfeedbackformat = FORMAT_HTML;
        $qdata->options->shownumcorrect = 1;
        $qdata->options->incorrectfeedback = '<p>Your answer is incorrect.</p>';
        $qdata->options->incorrectfeedbackformat = FORMAT_HTML;
    
        $qdata->options->answers = array(
                13 => new question_answer(13, 'answer 1', 0, '', FORMAT_HTML),
                14 => new question_answer(14, 'answer 2', 1, '', FORMAT_HTML),
                15 => new question_answer(15, 'answer 3', 0, '', FORMAT_HTML),
                16 => new question_answer(16, 'answer 4', 0, '', FORMAT_HTML),
        );
    
        $qdata->hints = array(
                new question_hint_with_parts(0, 'Hint 1.', FORMAT_HTML, false, false),
                new question_hint_with_parts(0, 'Hint 2.', FORMAT_HTML, false, false),
        );
    
        
        // Export the question.
        $exporter = new qformat_smart();
        $questions = array($qdata);
        $zipcontent = $exporter->export_questions($questions);
        
        // Open exported question as import_data.
        $import_data = $this->zip_to_import_data($zipcontent);
        
        /*
         * Test content of imsmanifest.xml
        */
        
        $imsmanifest = $import_data->imsmanifest;
        
        $expected = '<resource identifier="group0_pages" href="page0.svg" type="webcontent" adlcp:scormType="asset"><file href="page0.svg"/></resource>';
        $actual = $imsmanifest->resources->resource[0]->asXML();
        $this->assert_same_xml($expected, $actual);
        
        $expected = '<resource identifier="pages" href="page0.svg" type="webcontent" adlcp:scormType="asset"><file href="page0.svg"/></resource>';
        $actual = $imsmanifest->resources->resource[1]->asXML();
        $this->assert_same_xml($expected, $actual);
        
        /*
         * Test content of page.
        */
        
        // Test number of pages.
        $this->assertCount(1, $import_data->pages);
        
        $page = $import_data->pages[0];
        
        // Test the number of question elements.
        $questions = $page->xpath('//g[@class="question"]');
        $this->assertCount(1, $questions);
        
        // Test the question element.
        $question = $questions[0];
        $expectedvotemetadata = '<votemetadata><questiontext format="choice" labelstyle="upper-alpha" correct="2" points="23" tags="" explanation="General feedback" mathgradingoption="" likert="false"/></votemetadata>';
        $votemetadata = $question->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
        
        // Test the question number.
        $expectedquestionnumber = '1';
        $questionnumber = strip_tags($question->text[0]->asXML());
        $this->assertEquals($expectedquestionnumber, $questionnumber);
        
        // Test the question text without formattings.
        $expectedquestiontext = 'Questiontext of muliple choice question.';
        $questiontext = strip_tags($question->text[1]->asXML());
        $this->assertEquals($expectedquestiontext, $questiontext);
        
        // Test the number of choices.
        $choices = $page->xpath('//g[@class="questionchoice"]');
        $this->assertCount(4, $choices);
        
        // Test the 1. choice element.
        $choice = $choices[0];
        $expectedvotemetadata = '<votemetadata><choicetext label="1" format="choice"/></votemetadata>';
        $votemetadata = $choice->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
        
        // Test the numbering of the choice text without formattings.
        $expectedchoicetext = 'A';
        $choicetext = strip_tags($choice->text[0]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test the choice text without formattings.
        $expectedchoicetext = 'answer 1';
        $choicetext = strip_tags($choice->text[1]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test the 2. choice element.
        $choice = $choices[1];
        $expectedvotemetadata = '<votemetadata><choicetext label="2" format="choice"/></votemetadata>';
        $votemetadata = $choice->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
        
        // Test the numbering of the choice text without formattings.
        $expectedchoicetext = 'B';
        $choicetext = strip_tags($choice->text[0]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test the choice text without formattings.
        $expectedchoicetext = 'answer 2';
        $choicetext = strip_tags($choice->text[1]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test the 3. choice element.
        $choice = $choices[2];
        $expectedvotemetadata = '<votemetadata><choicetext label="3" format="choice"/></votemetadata>';
        $votemetadata = $choice->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
        
        // Test the numbering of the choice text without formattings.
        $expectedchoicetext = 'C';
        $choicetext = strip_tags($choice->text[0]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test the choice text without formattings.
        $expectedchoicetext = 'answer 3';
        $choicetext = strip_tags($choice->text[1]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test the 4. choice element.
        $choice = $choices[3];
        $expectedvotemetadata = '<votemetadata><choicetext label="4" format="choice"/></votemetadata>';
        $votemetadata = $choice->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
        
        // Test the numbering of the choice text without formattings.
        $expectedchoicetext = 'D';
        $choicetext = strip_tags($choice->text[0]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test the choice text without formattings.
        $expectedchoicetext = 'answer 4';
        $choicetext = strip_tags($choice->text[1]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
    }
    
    /*
     * Test export of a multiple-choice-question with two right answers.
    */
    public function test_export_multichoice_2() {
        $qdata = new stdClass();
        $qdata->id = 123;
        $qdata->contextid = 0;
        $qdata->qtype = 'multichoice';
        $qdata->name = 'Multiple choice question';
        $qdata->questiontext = 'Questiontext of muliple choice question.';
        $qdata->questiontextformat = FORMAT_HTML;
        $qdata->generalfeedback = 'General feedback';
        $qdata->generalfeedbackformat = FORMAT_HTML;
        $qdata->defaultmark = 23;
        $qdata->length = 1;
        $qdata->penalty = 0.3333333;
        $qdata->hidden = 0;
    
        $qdata->options = new stdClass();
        $qdata->options->single = 0;
        $qdata->options->shuffleanswers = 0;
        $qdata->options->answernumbering = 'abc';
        $qdata->options->correctfeedback = '<p>Your answer is correct.</p>';
        $qdata->options->correctfeedbackformat = FORMAT_HTML;
        $qdata->options->partiallycorrectfeedback = '<p>Your answer is partially correct.</p>';
        $qdata->options->partiallycorrectfeedbackformat = FORMAT_HTML;
        $qdata->options->shownumcorrect = 1;
        $qdata->options->incorrectfeedback = '<p>Your answer is incorrect.</p>';
        $qdata->options->incorrectfeedbackformat = FORMAT_HTML;
    
        $qdata->options->answers = array(
                13 => new question_answer(13, 'answer 1', 0, '', FORMAT_HTML),
                14 => new question_answer(14, 'answer 2', 1, '', FORMAT_HTML),
                15 => new question_answer(15, 'answer 3', 0, '', FORMAT_HTML),
                16 => new question_answer(16, 'answer 4', 1, '', FORMAT_HTML),
        );
    
        $qdata->hints = array(
                new question_hint_with_parts(0, 'Hint 1.', FORMAT_HTML, false, false),
                new question_hint_with_parts(0, 'Hint 2.', FORMAT_HTML, false, false),
        );
    
    
        // Export the question.
        $exporter = new qformat_smart();
        $questions = array($qdata);
        $zipcontent = $exporter->export_questions($questions);
    
        // Open exported question as import_data.
        $import_data = $this->zip_to_import_data($zipcontent);
    
        /*
         * Test content of imsmanifest.xml
        */
    
        $imsmanifest = $import_data->imsmanifest;
    
        $expected = '<resource identifier="group0_pages" href="page0.svg" type="webcontent" adlcp:scormType="asset"><file href="page0.svg"/></resource>';
        $actual = $imsmanifest->resources->resource[0]->asXML();
        $this->assert_same_xml($expected, $actual);
    
        $expected = '<resource identifier="pages" href="page0.svg" type="webcontent" adlcp:scormType="asset"><file href="page0.svg"/></resource>';
        $actual = $imsmanifest->resources->resource[1]->asXML();
        $this->assert_same_xml($expected, $actual);
    
        /*
         * Test content of page.
        */
    
        // Test number of pages.
        $this->assertCount(1, $import_data->pages);
    
        $page = $import_data->pages[0];
    
        // Test the number of question elements.
        $questions = $page->xpath('//g[@class="question"]');
        $this->assertCount(1, $questions);
    
        // Test the question element.
        $question = $questions[0];
        $expectedvotemetadata = '<votemetadata><questiontext format="selection" labelstyle="upper-alpha" correct="2 4" points="23" tags="" explanation="General feedback" mathgradingoption="" likert=""/></votemetadata>';
        $votemetadata = $question->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
    
        // Test the question number.
        $expectedquestionnumber = '1';
        $questionnumber = strip_tags($question->text[0]->asXML());
        $this->assertEquals($expectedquestionnumber, $questionnumber);
    
        // Test the question text without formattings.
        $expectedquestiontext = 'Questiontext of muliple choice question.';
        $questiontext = strip_tags($question->text[1]->asXML());
        $this->assertEquals($expectedquestiontext, $questiontext);
    
        // Test the number of choices.
        $choices = $page->xpath('//g[@class="questionchoice"]');
        $this->assertCount(4, $choices);
    
        // Test the 1. choice element.
        $choice = $choices[0];
        $expectedvotemetadata = '<votemetadata><choicetext label="1" format="selection"/></votemetadata>';
        $votemetadata = $choice->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
    
        // Test the numbering of the choice text without formattings.
        $expectedchoicetext = 'A';
        $choicetext = strip_tags($choice->text[0]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
    
        // Test the choice text without formattings.
        $expectedchoicetext = 'answer 1';
        $choicetext = strip_tags($choice->text[1]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
    
        // Test the 2. choice element.
        $choice = $choices[1];
        $expectedvotemetadata = '<votemetadata><choicetext label="2" format="selection"/></votemetadata>';
        $votemetadata = $choice->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
    
        // Test the numbering of the choice text without formattings.
        $expectedchoicetext = 'B';
        $choicetext = strip_tags($choice->text[0]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
    
        // Test the choice text without formattings.
        $expectedchoicetext = 'answer 2';
        $choicetext = strip_tags($choice->text[1]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
    
        // Test the 3. choice element.
        $choice = $choices[2];
        $expectedvotemetadata = '<votemetadata><choicetext label="3" format="selection"/></votemetadata>';
        $votemetadata = $choice->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
    
        // Test the numbering of the choice text without formattings.
        $expectedchoicetext = 'C';
        $choicetext = strip_tags($choice->text[0]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
    
        // Test the choice text without formattings.
        $expectedchoicetext = 'answer 3';
        $choicetext = strip_tags($choice->text[1]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
    
        // Test the 4. choice element.
        $choice = $choices[3];
        $expectedvotemetadata = '<votemetadata><choicetext label="4" format="selection"/></votemetadata>';
        $votemetadata = $choice->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
    
        // Test the numbering of the choice text without formattings.
        $expectedchoicetext = 'D';
        $choicetext = strip_tags($choice->text[0]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
    
        // Test the choice text without formattings.
        $expectedchoicetext = 'answer 4';
        $choicetext = strip_tags($choice->text[1]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
    }
    
    /*
     * Test export of a shortanswer-question.
    */
    public function test_export_shortanswer() {
        $qdata = new stdClass();
        $qdata->id = 123;
        $qdata->contextid = 0;
        $qdata->qtype = 'shortanswer';
        $qdata->name = 'Short answer question';
        $qdata->questiontext = 'Fill in the gap in this sequence: Alpha, ________, Gamma.';
        $qdata->questiontextformat = FORMAT_HTML;
        $qdata->generalfeedback = 'The answer is Beta.';
        $qdata->generalfeedbackformat = FORMAT_HTML;
        $qdata->defaultmark = 1;
        $qdata->length = 1;
        $qdata->penalty = 0.3333333;
        $qdata->hidden = 0;
    
        $qdata->options = new stdClass();
        $qdata->options->usecase = 0;
    
        $qdata->options->answers = array(
                13 => new question_answer(13, 'Beta', 1, 'Well done!', FORMAT_HTML),
                14 => new question_answer(14, '*', 0, 'Doh!', FORMAT_HTML),
        );
    
        $qdata->hints = array(
                new question_hint(0, 'Hint 1', FORMAT_HTML),
                new question_hint(0, 'Hint 2', FORMAT_HTML),
        );
    
       // Export the question.
        $exporter = new qformat_smart();
        $questions = array($qdata);
        $zipcontent = $exporter->export_questions($questions);
    
        // Open exported question as import_data.
        $import_data = $this->zip_to_import_data($zipcontent);
    
        /*
         * Test content of imsmanifest.xml
        */
    
        $imsmanifest = $import_data->imsmanifest;
    
        $expected = '<resource identifier="group0_pages" href="page0.svg" type="webcontent" adlcp:scormType="asset"><file href="page0.svg"/></resource>';
        $actual = $imsmanifest->resources->resource[0]->asXML();
        $this->assert_same_xml($expected, $actual);
    
        $expected = '<resource identifier="pages" href="page0.svg" type="webcontent" adlcp:scormType="asset"><file href="page0.svg"/></resource>';
        $actual = $imsmanifest->resources->resource[1]->asXML();
        $this->assert_same_xml($expected, $actual);
    
        /*
         * Test content of page.
        */
    
        // Test number of pages.
        $this->assertCount(1, $import_data->pages);
    
        $page = $import_data->pages[0];
    
        // Test the number of question elements.
        $questions = $page->xpath('//g[@class="question"]');
        $this->assertCount(1, $questions);
    
        // Test the question element.
        $question = $questions[0];
        $expectedvotemetadata = '<votemetadata><questiontext format="short-answer" labelstyle="" correct="Beta" points="1" tags="" explanation="The answer is Beta." mathgradingoption="" likert=""/></votemetadata>';
        $votemetadata = $question->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
    
        // Test the question number.
        $expectedquestionnumber = '1';
        $questionnumber = strip_tags($question->text[0]->asXML());
        $this->assertEquals($expectedquestionnumber, $questionnumber);
    
        // Test the question text without formattings.
        $expectedquestiontext = 'Fill in the gap in this sequence: Alpha, ________, Gamma.';
        $questiontext = strip_tags($question->text[1]->asXML());
        $this->assertEquals($expectedquestiontext, $questiontext);
    
        // Test the number of choices.
        $choices = $page->xpath('//g[@class="questionchoice"]');
        $this->assertCount(0, $choices);
    }
    
    /*
     * Test export of a numerical-question.
    */
    public function test_export_numerical() {
        question_bank::load_question_definition_classes('numerical');
    
        $qdata = new stdClass();
        $qdata->id = 123;
        $qdata->contextid = 0;
        $qdata->qtype = 'numerical';
        $qdata->name = 'Numerical question';
        $qdata->questiontext = 'What is the answer?';
        $qdata->questiontextformat = FORMAT_HTML;
        $qdata->generalfeedback = 'General feedback: Think Hitch-hikers guide to the Galaxy.';
        $qdata->generalfeedbackformat = FORMAT_HTML;
        $qdata->defaultmark = 1;
        $qdata->length = 1;
        $qdata->penalty = 0.1;
        $qdata->hidden = 0;
    
        $qdata->options = new stdClass();
        $qdata->options->answers = array(
                13 => new qtype_numerical_answer(13, '42', 1, 'Well done!',
                                                FORMAT_HTML, 0.001),
                14 => new qtype_numerical_answer(14, '13', 0, 'What were you thinking?!',
                                                FORMAT_HTML, 1),
                15 => new qtype_numerical_answer(15, '*', 0, 'Completely wrong.',
                                                FORMAT_HTML, ''),
        );
    
        $qdata->options->units = array();
    
        // Export the question.
        $exporter = new qformat_smart();
        $questions = array($qdata);
        $zipcontent = $exporter->export_questions($questions);
    
        // Open exported question as import_data.
        $import_data = $this->zip_to_import_data($zipcontent);
    
        /*
         * Test content of imsmanifest.xml
        */
    
        $imsmanifest = $import_data->imsmanifest;
    
        $expected = '<resource identifier="group0_pages" href="page0.svg" type="webcontent" adlcp:scormType="asset"><file href="page0.svg"/></resource>';
        $actual = $imsmanifest->resources->resource[0]->asXML();
        $this->assert_same_xml($expected, $actual);
    
        $expected = '<resource identifier="pages" href="page0.svg" type="webcontent" adlcp:scormType="asset"><file href="page0.svg"/></resource>';
        $actual = $imsmanifest->resources->resource[1]->asXML();
        $this->assert_same_xml($expected, $actual);
    
        /*
         * Test content of page.
        */
    
        // Test number of pages.
        $this->assertCount(1, $import_data->pages);
    
        $page = $import_data->pages[0];
    
        // Test the number of question elements.
        $questions = $page->xpath('//g[@class="question"]');
        $this->assertCount(1, $questions);
    
        // Test the question element.
        $question = $questions[0];
        $expectedvotemetadata = '<votemetadata><questiontext format="numeric" labelstyle="" correct="42" points="1" tags="" explanation="General feedback: Think Hitch-hikers guide to the Galaxy." mathgradingoption="" likert=""/></votemetadata>';
        $votemetadata = $question->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
    
        // Test the question number.
        $expectedquestionnumber = '1';
        $questionnumber = strip_tags($question->text[0]->asXML());
        $this->assertEquals($expectedquestionnumber, $questionnumber);
    
        // Test the question text without formattings.
        $expectedquestiontext = 'What is the answer?';
        $questiontext = strip_tags($question->text[1]->asXML());
        $this->assertEquals($expectedquestiontext, $questiontext);
    
        // Test the number of choices.
        $choices = $page->xpath('//g[@class="questionchoice"]');
        $this->assertCount(0, $choices);
    }
    
    /*
     * Test export of a matching-question.
    */
    public function test_export_matching() {
        $qdata = new stdClass();
        $qdata->id = 123;
        $qdata->contextid = 0;
        $qdata->qtype = 'match';
        $qdata->name = 'Matching question';
        $qdata->questiontext = 'Match the upper and lower case letters.';
        $qdata->questiontextformat = FORMAT_HTML;
        $qdata->generalfeedback = 'The answer is A -> a, B -> b and C -> c.';
        $qdata->generalfeedbackformat = FORMAT_HTML;
        $qdata->defaultmark = 23;
        $qdata->length = 1;
        $qdata->penalty = 0.3333333;
        $qdata->hidden = 0;
    
        $qdata->options = new stdClass();
        $qdata->options->shuffleanswers = 1;
        $qdata->options->correctfeedback = 'Well done.';
        $qdata->options->correctfeedbackformat = FORMAT_HTML;
        $qdata->options->partiallycorrectfeedback = 'Not entirely.';
        $qdata->options->partiallycorrectfeedbackformat = FORMAT_HTML;
        $qdata->options->shownumcorrect = false;
        $qdata->options->incorrectfeedback = 'Completely wrong!';
        $qdata->options->incorrectfeedbackformat = FORMAT_HTML;
    
        $subq1 = new stdClass();
        $subq1->id = -4;
        $subq1->questiontext = 'A';
        $subq1->questiontextformat = FORMAT_HTML;
        $subq1->answertext = 'a';
    
        $subq2 = new stdClass();
        $subq2->id = -3;
        $subq2->questiontext = 'B';
        $subq2->questiontextformat = FORMAT_HTML;
        $subq2->answertext = 'b';
    
        $subq3 = new stdClass();
        $subq3->id = -2;
        $subq3->questiontext = 'C';
        $subq3->questiontextformat = FORMAT_HTML;
        $subq3->answertext = 'c';
    
        $subq4 = new stdClass();
        $subq4->id = -1;
        $subq4->questiontext = '';
        $subq4->questiontextformat = FORMAT_HTML;
        $subq4->answertext = 'd';
    
        $qdata->options->subquestions = array(
                $subq1, $subq2, $subq3, $subq4);
    
        $qdata->hints = array(
                new question_hint_with_parts(0, 'Hint 1', FORMAT_HTML, true, false),
                new question_hint_with_parts(0, '', FORMAT_HTML, true, true),
        );
    
        // Export the question.
        $exporter = new qformat_smart();
        $questions = array($qdata);
        $zipcontent = $exporter->export_questions($questions);
        
        // Open exported question as import_data.
        $import_data = $this->zip_to_import_data($zipcontent);
        
        /*
         * Test content of imsmanifest.xml
        */
        
        $imsmanifest = $import_data->imsmanifest;
        
        $expected = '<resource identifier="group0_pages" href="page0.svg" type="webcontent" adlcp:scormType="asset"><file href="page0.svg"/><file href="page1.svg"/><file href="page2.svg"/></resource>';
        $actual = $imsmanifest->resources->resource[0]->asXML();
        $this->assert_same_xml($expected, $actual);
        
        $expected = '<resource identifier="pages" href="page0.svg" type="webcontent" adlcp:scormType="asset"><file href="page0.svg"/><file href="page1.svg"/><file href="page2.svg"/></resource>';
        $actual = $imsmanifest->resources->resource[1]->asXML();
        $this->assert_same_xml($expected, $actual);
        
        /*
         * Test content of page.
        */
        
        // Test number of pages.
        $this->assertCount(3, $import_data->pages);
        
        // Test first page
        
        $page = $import_data->pages[0];
        
        // Test the number of question elements.
        $questions = $page->xpath('//g[@class="question"]');
        $this->assertCount(1, $questions);
        
        // Test the question element.
        $question = $questions[0];
        $expectedvotemetadata1 = '<votemetadata><questiontext format="choice" labelstyle="upper-alpha"';
        $expectedvotemetadata2 = 'points="23" tags="" explanation="The answer is A -$gt; a, B -$gt; b and C -$gt; c." mathgradingoption="" likert="false"/></votemetadata>';
        $votemetadata = $question->votemetadata->asXML();
        //$this->assert_same_xml($expectedvotemetadata, $votemetadata);
        //$this->assertStringStartsWith($expectedvotemetadata1, $votemetadata);
        //$this->assertStringEndsWith($expectedvotemetadata2, $votemetadata);
        
        // Test the question number.
        $expectedquestionnumber = '1';
        $questionnumber = strip_tags($question->text[0]->asXML());
        //$this->assertEquals($expectedquestionnumber, $questionnumber);
        
        // Test the question text without formattings.
        $expectedquestiontext = 'Match the upper and lower case letters.';
        $questiontext = strip_tags($question->text[1]->asXML());
        $this->assertStringStartsWith($expectedquestiontext, $questiontext);
        
        // Test the number of choices.
        $choices = $page->xpath('//g[@class="questionchoice"]');
        $this->assertCount(4, $choices);
        
        // Test the 1. choice element.
        $choice = $choices[0];
        $expectedvotemetadata = '<votemetadata><choicetext label="1" format="choice"/></votemetadata>';
        $votemetadata = $choice->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
        
        // Test the numbering of the choice text without formattings.
        $expectedchoicetext = 'A';
        $choicetext = strip_tags($choice->text[0]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test the choice text without formattings.
        $expectedchoicetext = 'a';
        $choicetext = strip_tags($choice->text[1]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test the 2. choice element.
        $choice = $choices[1];
        $expectedvotemetadata = '<votemetadata><choicetext label="2" format="choice"/></votemetadata>';
        $votemetadata = $choice->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
        
        // Test the numbering of the choice text without formattings.
        $expectedchoicetext = 'B';
        $choicetext = strip_tags($choice->text[0]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test the choice text without formattings.
        $expectedchoicetext = 'b';
        $choicetext = strip_tags($choice->text[1]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test the 3. choice element.
        $choice = $choices[2];
        $expectedvotemetadata = '<votemetadata><choicetext label="3" format="choice"/></votemetadata>';
        $votemetadata = $choice->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
        
        // Test the numbering of the choice text without formattings.
        $expectedchoicetext = 'C';
        $choicetext = strip_tags($choice->text[0]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test the choice text without formattings.
        $expectedchoicetext = 'c';
        $choicetext = strip_tags($choice->text[1]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test the 4. choice element.
        $choice = $choices[3];
        $expectedvotemetadata = '<votemetadata><choicetext label="4" format="choice"/></votemetadata>';
        $votemetadata = $choice->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
        
        // Test the numbering of the choice text without formattings.
        $expectedchoicetext = 'D';
        $choicetext = strip_tags($choice->text[0]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test the choice text without formattings.
        $expectedchoicetext = 'd';
        $choicetext = strip_tags($choice->text[1]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test second page
        
        $page = $import_data->pages[1];
        
        // Test the number of question elements.
        $questions = $page->xpath('//g[@class="question"]');
        $this->assertCount(1, $questions);
        
        // Test the question element.
        $question = $questions[0];
        $expectedvotemetadata1 = '<votemetadata><questiontext format="choice" labelstyle="upper-alpha"';
        $expectedvotemetadata2 = 'points="23" tags="" explanation="The answer is A -$gt; a, B -$gt; b and C -$gt; c." mathgradingoption="" likert="false"/></votemetadata>';
        $votemetadata = $question->votemetadata->asXML();
        //$this->assert_same_xml($expectedvotemetadata, $votemetadata);
        //$this->assertStringStartsWith($expectedvotemetadata1, $votemetadata);
        //$this->assertStringEndsWith($expectedvotemetadata2, $votemetadata);
        
        // Test the question number.
        $expectedquestionnumber = '2';
        $questionnumber = strip_tags($question->text[0]->asXML());
        //$this->assertEquals($expectedquestionnumber, $questionnumber);
        
        // Test the question text without formattings.
        $expectedquestiontext = 'Match the upper and lower case letters.';
        $questiontext = strip_tags($question->text[1]->asXML());
        $this->assertStringStartsWith($expectedquestiontext, $questiontext);
        
        // Test the number of choices.
        $choices = $page->xpath('//g[@class="questionchoice"]');
        $this->assertCount(4, $choices);
        
        // Test the 1. choice element.
        $choice = $choices[0];
        $expectedvotemetadata = '<votemetadata><choicetext label="1" format="choice"/></votemetadata>';
        $votemetadata = $choice->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
        
        // Test the numbering of the choice text without formattings.
        $expectedchoicetext = 'A';
        $choicetext = strip_tags($choice->text[0]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test the choice text without formattings.
        $expectedchoicetext = 'a';
        $choicetext = strip_tags($choice->text[1]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test the 2. choice element.
        $choice = $choices[1];
        $expectedvotemetadata = '<votemetadata><choicetext label="2" format="choice"/></votemetadata>';
        $votemetadata = $choice->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
        
        // Test the numbering of the choice text without formattings.
        $expectedchoicetext = 'B';
        $choicetext = strip_tags($choice->text[0]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test the choice text without formattings.
        $expectedchoicetext = 'b';
        $choicetext = strip_tags($choice->text[1]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test the 3. choice element.
        $choice = $choices[2];
        $expectedvotemetadata = '<votemetadata><choicetext label="3" format="choice"/></votemetadata>';
        $votemetadata = $choice->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
        
        // Test the numbering of the choice text without formattings.
        $expectedchoicetext = 'C';
        $choicetext = strip_tags($choice->text[0]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test the choice text without formattings.
        $expectedchoicetext = 'c';
        $choicetext = strip_tags($choice->text[1]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test the 4. choice element.
        $choice = $choices[3];
        $expectedvotemetadata = '<votemetadata><choicetext label="4" format="choice"/></votemetadata>';
        $votemetadata = $choice->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
        
        // Test the numbering of the choice text without formattings.
        $expectedchoicetext = 'D';
        $choicetext = strip_tags($choice->text[0]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test the choice text without formattings.
        $expectedchoicetext = 'd';
        $choicetext = strip_tags($choice->text[1]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test third page
        
        $page = $import_data->pages[2];
        
        // Test the number of question elements.
        $questions = $page->xpath('//g[@class="question"]');
        $this->assertCount(1, $questions);
        
        // Test the question element.
        $question = $questions[0];
        $expectedvotemetadata1 = '<votemetadata><questiontext format="choice" labelstyle="upper-alpha"';
        $expectedvotemetadata2 = 'points="23" tags="" explanation="The answer is A -$gt; a, B -$gt; b and C -$gt; c." mathgradingoption="" likert="false"/></votemetadata>';
        $votemetadata = $question->votemetadata->asXML();
        //$this->assert_same_xml($expectedvotemetadata, $votemetadata);
        //$this->assertStringStartsWith($expectedvotemetadata1, $votemetadata);
        //$this->assertStringEndsWith($expectedvotemetadata2, $votemetadata);
        
        // Test the question number.
        $expectedquestionnumber = '3';
        $questionnumber = strip_tags($question->text[0]->asXML());
        //$this->assertEquals($expectedquestionnumber, $questionnumber);
        
        // Test the question text without formattings.
        $expectedquestiontext = 'Match the upper and lower case letters.';
        $questiontext = strip_tags($question->text[1]->asXML());
        $this->assertStringStartsWith($expectedquestiontext, $questiontext);
        
        // Test the number of choices.
        $choices = $page->xpath('//g[@class="questionchoice"]');
        $this->assertCount(4, $choices);
        
        // Test the 1. choice element.
        $choice = $choices[0];
        $expectedvotemetadata = '<votemetadata><choicetext label="1" format="choice"/></votemetadata>';
        $votemetadata = $choice->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
        
        // Test the numbering of the choice text without formattings.
        $expectedchoicetext = 'A';
        $choicetext = strip_tags($choice->text[0]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test the choice text without formattings.
        $expectedchoicetext = 'a';
        $choicetext = strip_tags($choice->text[1]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test the 2. choice element.
        $choice = $choices[1];
        $expectedvotemetadata = '<votemetadata><choicetext label="2" format="choice"/></votemetadata>';
        $votemetadata = $choice->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
        
        // Test the numbering of the choice text without formattings.
        $expectedchoicetext = 'B';
        $choicetext = strip_tags($choice->text[0]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test the choice text without formattings.
        $expectedchoicetext = 'b';
        $choicetext = strip_tags($choice->text[1]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test the 3. choice element.
        $choice = $choices[2];
        $expectedvotemetadata = '<votemetadata><choicetext label="3" format="choice"/></votemetadata>';
        $votemetadata = $choice->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
        
        // Test the numbering of the choice text without formattings.
        $expectedchoicetext = 'C';
        $choicetext = strip_tags($choice->text[0]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test the choice text without formattings.
        $expectedchoicetext = 'c';
        $choicetext = strip_tags($choice->text[1]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test the 4. choice element.
        $choice = $choices[3];
        $expectedvotemetadata = '<votemetadata><choicetext label="4" format="choice"/></votemetadata>';
        $votemetadata = $choice->votemetadata->asXML();
        $this->assert_same_xml($expectedvotemetadata, $votemetadata);
        
        // Test the numbering of the choice text without formattings.
        $expectedchoicetext = 'D';
        $choicetext = strip_tags($choice->text[0]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        // Test the choice text without formattings.
        $expectedchoicetext = 'd';
        $choicetext = strip_tags($choice->text[1]->asXML());
        $this->assertEquals($expectedchoicetext, $choicetext);
        
        
    }
    
    /*
     * Test export of a shortanswer-question.
    */
    public function test_export_formattings() {
        $qdata = new stdClass();
        $qdata->id = 123;
        $qdata->contextid = 0;
        $qdata->qtype = 'shortanswer';
        $qdata->name = 'Short answer question';
        $qdata->questiontext = "<p>This is some text <a href=\"test\">link</a></p>";
        $qdata->questiontextformat = FORMAT_HTML;
        $qdata->generalfeedback = 'The answer is Beta.';
        $qdata->generalfeedbackformat = FORMAT_HTML;
        $qdata->defaultmark = 1;
        $qdata->length = 1;
        $qdata->penalty = 0.3333333;
        $qdata->hidden = 0;
    
        $qdata->options = new stdClass();
        $qdata->options->usecase = 0;
    
        $qdata->options->answers = array(
                13 => new question_answer(13, 'Beta', 1, 'Well done!', FORMAT_HTML),
                14 => new question_answer(14, '*', 0, 'Doh!', FORMAT_HTML),
        );
    
        // Export the question.
        $exporter = new qformat_smart();
        $questions = array($qdata);
        $zipcontent = $exporter->export_questions($questions);
    
        // Open exported question as import_data.
        $import_data = $this->zip_to_import_data($zipcontent);
    
        /*
         * Test content of pages.
        */
    
        // Test number of pages.
        $this->assertCount(2, $import_data->pages);
    
        $page = $import_data->pages[1];
        $questions = $page->xpath('//g[@class="question"]');
        $this->assertCount(1, $questions);
        $question = $questions[0];
        
        // Test the question text without formattings.
        $expectedquestiontext = 'This is some text link';
        $questiontext = strip_tags($question->text[1]->asXML());
        $this->assertEquals($expectedquestiontext, $questiontext);
        
        $page = $import_data->pages[0];
        $infotexts = $page->xpath('//text');
        // Test that text element is present
        $this->assertCount(1, $infotexts);
        $infotext = $infotexts[0];
        
        // Tests if text is not empty
        $expectedinfotext = '';
        $infotext = strip_tags($infotext->asXML());
        $this->assertNotEquals($expectedinfotext, $questiontext);
    
    }

}
