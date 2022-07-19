<?php

// This file is part of the Mcopyfind plugin for Moodle - http://moodle.org/
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
 * Submitted_assignments.php - 
 *
 * @package      plagiarism_mcopyfind
 * @subpackage   plagiarism
 * @author       johannes Wanner @johannes.wanner@web.de
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_mcopyfind\compare;


class assignments {


    /**
     * Constructor of the plagiarism assignments class
     * 
     * @param bool $notinstance
     */
    function __construct($notinstance = false) {
        $this->config = get_config('plagiarism_mcopyfind');
        if ($notinstance) {
            $this->username = false;
        }
    }



    /**
     * Case where all assignments have to be compared
     */
    public function access_all_files($cm, $context) {
        global $CFG, $DB;
        
        // $submitted = $DB->get_records_select('plagiarism_mcopyfind', 'cmid = :cmid',
        //   array('cmid' => $cm->id), '', 'filehash');   

        if ($cm->modname == 'assign') {           
            $files = array();
            require_once($CFG->dirroot.'/mod/assign/locallib.php');

            //$cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
            $course = $DB->get_record('course', array('id' => $cm->course));
            $assign = new \assign($context, $cm, $course);

            // Loop through all the submissions and ask the submission plugins to return a list of files.
            /** @var $plugins assign_submission_plugin[] */
            $plugins = $assign->get_submission_plugins();
            
            $submissions = $DB->get_records('assign_submission', array('assignment' => $cm->instance));
 

            $corpus =array();
            $hashes = array();

            foreach ($submissions as $submission) {
                foreach ($plugins as $plugin) {
                    if (!$plugin->is_enabled() || !$plugin->is_visible() || $plugin->get_type() != "file") {
                        continue;
                    }
                    $user= $DB->get_record('user', array('id' => $submission->userid));     
                    // echo(var_dump($user));          
                    foreach ($plugin->get_files($submission,$user) as $file) {
                        // Files are returned indexed by filename - which causes problems if different students submit
                        // files with the same name.
                        /** @var $file stored_file */
                        if(method_exists($file,'get_id'))
                            $files[$file->get_id()] = $file;
                            
                    }
                }
            }
            
            
            // var_dump($files);
            foreach ($files as $file) {
                $filename = $file->get_filename();
                $userid = $file->get_userid();
                $pathnamehash = $file->get_pathnamehash();
                $file = get_file_storage()->get_file_by_hash($pathnamehash)->get_content_file_handle();
                $document = new document( $filename, $file);

                array_push($corpus, $document);
                // array_push($hashes, $pathnamehash);

                // Momentan erstmal nur die reports speichern.
                // vlt nur metadaten sammeln wie hash und id.  
                // dies wirft die frage auf wie die daten gespeichert werden , soll mcoipyfind die daten alle kopieren?
                // Wenn Dateien aus externen quellen kommen und die dateien nicht in der db sind, dann müssen sie in die db geschrieben werden.  
            }
            // Add content hash in docIn compare functions to add them to the list of compared files.
            // Maybe create hash pairs which were already compared and save these in the DB.
          
            //insert new report to database to get reportId
            // $reportId = $DB->insert_record('plagiarism_mcopyfind_report', array('cmid' => $cm->id));

            
            // $reportId = $reportId->id;
            $reportId = 0;
            $cmp = new compare_functions(new settings(), $corpus,$reportId);
            echo $cmp->RunComparison();
            // throw new \Exception("All files");
        }
    }
    
}