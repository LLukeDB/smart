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
 * @package qformat_smart
 * @copyright 2014 Lukas Baumann
 * @author Lukas Baumann
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later (5)
 */

/**
 * Creates zip-file from the directory.
 *
 * @param string $dir            
 * @param string $file            
 */
function create_zip($dir, $file) {
    $zipArchive = new ZipArchive();
    if ($zipArchive->open($file, ZipArchive::OVERWRITE)) {
        addFolderToZip($dir, $zipArchive);
        $res = $zipArchive->close();
        if ($res === false) {
            print_error('packingfailed', 'qformat_smart');
        }
    } else {
        print_error('packingfailed', 'qformat_smart');
    }
}

/**
 * Function to recursively add a directory,
 * sub-directories and files to a zip archive
 */
function addFolderToZip($dir, $zipArchive, $zipdir = '') {
    if (is_dir($dir)) {
        if ($dh = opendir($dir)) {
            
            // Add the directory.
            if (! empty($zipdir)) {
                $zipArchive->addEmptyDir($zipdir);
            }
            
            // Loop through all the files.
            while (($file = readdir($dh)) !== false) {
                
                // If it's a folder, run the function again!
                if (! is_file($dir . $file)) {
                    // Skip parent and root directories
                    if (($file !== ".") && ($file !== "..")) {
                        addFolderToZip($dir . $file . "/", $zipArchive, $zipdir . $file . "/");
                    }
                } else {
                    // Add the files.
                    $zipArchive->addFile($dir . $file, $zipdir . $file);
                }
            }
        }
    }
}

/**
 * Creates a temporary directory.
 *
 * @param unknown $dir            
 * @param string $prefix            
 * @param number $mode            
 * @return string
 */
function tempdir($dir, $prefix = '', $mode = 0777) {
    $tmpfile = tempnam($dir, 'smart_');
    unlink($tmpfile);
    $tmpfile = $tmpfile . "/";
    mkdir($tmpfile);
    
    return $tmpfile;
}

/**
 * Creates the directory structure for the notebook format.
 *
 * @param string $dir            
 * @return string
 */
function createDirStructure($basedir) {
    mkdir($basedir . '/pictures');
    // mkdir($basedir . '/flash');
    // mkdir($basedir . '/files');
    return $basedir;
}

/**
 * Deletes directory with all files and subdirectories in it.
 *
 * @param unknown $dir            
 * @return boolean
 */
function recurseRmdir($dir) {
    $files = array_diff(scandir($dir), array(
            '.',
            '..'
    ));
    
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? recurseRmdir("$dir/$file") : unlink("$dir/$file");
    }
    
    return rmdir($dir);
}

/**
 * Saves a SimpleXML document to a file.
 *
 * @param SimpleXML-Element $simplexml            
 * @param filename $file            
 * @return boolean
 */
function save_simplexml($simplexml, $file) {
    $dom = new DOMDocument("1.0");
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($simplexml->asXML());
    $result = $dom->save($file);
    
    // $result = $simplexml->asXML($file);
    if (! $result) {
        print_error('savingfailed', 'qformat_smart', null, $file);
        return false;
    }
    return true;
}

function load_simplexml($file) {
    if (file_exists($file)) {
        $xml_doc = simplexml_load_file($file);
        return $xml_doc;
    } else {
        print_error('loadingfailed', 'qformat_smart', null, $file);
    }
}

function save_domdocument($domdocument, $file) {
    $domdocument->formatOutput = true;
    $result = $domdocument->save($filename);
    if (! $result) {
        print_error('savingfailed', 'qformat_smart', null, $file);
    }
}

function load_domdocument($file) {
    $xml_doc = new DOMDocument();
    $result = $xml_doc->load($filename);
    if (! $result) {
        print_error('loadingfailed', 'qformat_smart', null, $file);
    }
    return $xml_doc;
}
  
