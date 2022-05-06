<?

// This file is part of the Plagscan plugin for Moodle - http://moodle.org/
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
 * submitted_assignments.php - 
 *
 * @package      plagiarism_mcopyfind
 * @subpackage   plagiarism
 * @author       johannes Wanner @johannes.wanner@web.de
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

class submitted_assignments {


    public function access_all_files($cm, $context) {
        global $CFG, $DB;
        
        $fs = get_file_storage();
        $submitted = $DB->get_records_select('plagiarism_mcopyfind', 'cmid = :cmid',
                                             array('cmid' => $cm->id), '', 'filehash');

        if ($cm->modname == 'assign') {
            require_once($CFG->dirroot.'/mod/assign/locallib.php');

            //$cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
            $course = $DB->get_record('course', array('id' => $cm->course));
            $assign = new \assign($context, $cm, $course);

            // Loop through all the submissions and ask the submission plugins to return a list of files.
            /** @var $plugins assign_submission_plugin[] */
            $plugins = $assign->get_submission_plugins();
            $files = array();
            
            $submissions = $DB->get_records('assign_submission', array('assignment' => $cm->instance));
 
            foreach ($submissions as $submission) {
                foreach ($plugins as $plugin) {
                    if (!$plugin->is_enabled() || !$plugin->is_visible() || $plugin->get_type() != "file") {
                        continue;
                    }
                    $user= $DB->get_record('user', array('id' => $submission->userid));               
                    foreach ($plugin->get_files($submission,$user) as $file) {
                        // Files are returned indexed by filename - which causes problems if different students submit
                        // files with the same name.
                        /** @var $file stored_file */
                        if(method_exists($file,'get_id'))
                            $files[$file->get_id()] = $file;
                     
                    }
                }
            }
            
            foreach ($files as $file) {
                $filename = $file->get_filename();
                $userid = $file->get_userid();
                $pathnamehash = $file->get_pathnamehash();
                         
                //this wirft tdie frage auf wie die daten gespeichert werden , soll mcoipyfind die daten alle kopieren?
                //oder einfach nur die analysen?
//vlt nur metadaten sammeln wie hash und id.

                // if (array_key_exists($pathnamehash, $submitted)) {
                //     //mtrace("Plagscan: File '$filename' has already been submitted\n");
                //     continue; // This file has already been submitted.
                // }        
                // if (!supported_filetype($filename)) {
                //     //mtrace("Plagscan: '$filename' is a type of file not supported by the PlagScan server\n");
                //     //$this->set_file_status($plagscan, $plagscan_file::STATUS_FAILED_FILETYPE);
                //     continue; // Don't try to submit unsupported file types.
                // }
                
                $hashes = array();
                array_push($hashes, $pathnamehash);
                \plagiarism_plagscan\handlers\file_handler::instance()->file_uploaded_without_event($context,$userid, $hashes);
            }
        }
    }
    
}