<?php
$string['unsupportedquestiontype'] = 'Your choice contains questionstypes which are not supported!<br />{$a}';
$string['missingextension'] = 'The following necessary php extension is not installed: {$a}';
$string['filenotreadable'] = 'The file could not be read!';
$string['filenotvalid'] = 'The file is not a valid .notebook-file!';
$string['shortanswer_input'] = 'Pupils insert their answers here';
$string['formatting_error'] = 'Formatting "{$a->formatting}" in question "{$a->questiontitle}" has been omitted.';

$string['invalidxml'] = 'Invalid XML file - string expected (use CDATA?) en';
$string['pluginname'] = 'SMART Notebook Format';
$string['pluginname_help'] = 'Plugin for exporting questions in the SMART notebook format.';
$string['pluginname_link'] = 'qformat/smart';
$string['truefalseimporterror'] = '<b>Warning</b>: The true/false question \'{$a->questiontext}\' could not be imported properly. It was not clear whether the correct answer is true or false. The question has been imported assuming that the answer is \'{$a->answer}\'. If this is not correct, you will need to edit the question.';
$string['unsupportedexport'] = 'Question type {$a} is not supported by XML export';
$string['xmlimportnoname'] = 'Missing question name in XML file';
$string['xmlimportnoquestion'] = 'Missing question text in XML file';
$string['xmltypeunsupported'] = 'Question type {$a} is not supported by XML import';
?>
