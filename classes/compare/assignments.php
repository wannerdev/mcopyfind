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

use context_system;
use Exception;
use moodle_url;
use stdClass;
use stored_file;

class assignments {

    private $settings;
    /**
     * Constructor of the plagiarism assignments class
     * 
     * @param bool $notinstance
     */
    function __construct($notinstance = false) {
        $this->config = get_config('plagiarism_mcopyfind');
        $this->settings = settings::getRecommendedSettings();
        if ($notinstance) {
            $this->username = false;
        }
    }


    public function get_file_breadcrumbs(\stored_file $file): ?array {
        $browser = get_file_browser();
        $context = context_system::instance();
    
        $fileinfo = $browser->get_file_info(
            \context::instance_by_id($file->get_contextid()),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        );
    
        if ($fileinfo) {
            // Build a Breadcrumb trail
            $level = $fileinfo->get_parent();
            while ($level) {
                $path[] = [
                    'name' => $level->get_visible_name(),
                ];
                $level = $level->get_parent();
            }
    
            $path = array_reverse($path);
    
            return $path;
        }
    
        return null;
    }

    /**
     * Case where all assignments have to be compared
     */
    public function access_all_files($cm, $context) {
        global $CFG, $DB, $USER;
        
        require_login();
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
            
            //$preset =3; //todo load from config, set via radio buttons in lib file
            $preset=get_config( 'plagiarism_mcopyfind',$cm->id.'preset');
            $this->settings=$this->settings->getPreset($preset);
           

            foreach ($files as $file) {
                $filename = $file->get_filename();
                $userid = $file->get_userid();
                
                $pathnamehash = $file->get_pathnamehash();
                $file = get_file_storage()->get_file_by_hash($pathnamehash);
                
                $resource=$file->get_content_file_handle();
                $document = new document( $filename, $this->settings, $resource);
                $document->contenthash = $file->get_contenthash();
                array_push($corpus, $document);
                // array_push($hashes, $pathnamehash);

                // Momentan erstmal nur die reports speichern.
                // vlt nur metadaten sammeln wie hash und id.  
                // dies wirft die frage auf wie die daten gespeichert werden , soll mcopyfind die daten alle kopieren?
                // Wenn Dateien aus externen Quellen kommen und die Dateien nicht in der db sind, dann müssen sie in die db geschrieben werden.  
            }
          
            $insert = new \stdClass();
            //get logged in user
            $insert->userid = $USER->id;

            //to do check if files already compared, report already in database?
            // compare contenthash for each document with the contenthashes in matches?
            //if there is one check if the matched hash is one of the other documents

            //insert new report to database to get reportId
            $id = $DB->insert_record('plagiarism_mcopyfind_report', $insert);
            //get dummy report to database to get a reportId
            $reportRec = $DB->get_record('plagiarism_mcopyfind_report',array('id'=>$id));
            
            $reportId = $reportRec->id;
            $cmp = new compare_functions($corpus,$reportId, $this->settings);

            //Get results from comparison
            $matches = $cmp->RunComparison();
            $size=count($matches);

            $fs = get_file_storage();
            if($size >0){
                
                //Push each report Match data into DB
                foreach($matches as $match) {
                    $matchR = new stdClass();
                    $matchR->id = $match[0];
                    $matchR->perfectmatch = $match[1];
                    $matchR->reportId = $reportId;
                    $matchR->overalmatch   = $match[2];
                    $matchR->lname   = $match[3];
                    $matchR->rname = $match[4];
                    $matchR->contenthashl   = $match[5];
                    $matchR->contenthashr = $match[6];
                    //Remove file type from filenames
                    $index=strpos($match[3],'.'); 
                    $fileLname = substr($match[3],0,$index);
                    $index=strpos($match[4],'.'); 
                    $fileRname = substr($match[4],0,$index);
                    $file_recordM = array(
                        'contextid' => $context->id,
                        'component' => 'plagiarism_mcopyfind',
                        'filearea' => 'report',
                        'itemid' => $reportId,
                        'filepath' => '/',
                        'filename' => $reportId.'SBS.'. $fileRname .$fileLname.'_1.html',
                    );
                    $file = $fs->create_file_from_pathname($file_recordM ,$CFG->dirroot ."/plagiarism/mcopyfind/reports/".$reportId."SBS.". $fileRname. $fileLname.'_1.html');
                    $file_recordM['filename'] =$reportId.$fileLname.'.'.$fileRname.'.html';

                    $file = $fs->create_file_from_pathname($file_recordM ,$CFG->dirroot ."/plagiarism/mcopyfind/reports/".$reportId. $fileLname.'.'.$fileRname.'.html');
                    $file_recordM['filename'] =$reportId.$fileRname.'.'.$fileLname.'.html';
                    $file = $fs->create_file_from_pathname($file_recordM ,$CFG->dirroot ."/plagiarism/mcopyfind/reports/".$reportId. $fileRname.'.'.$fileLname.'.html');

                    
                    $id = $DB->insert_record('plagiarism_mcopyfind_match', $matchR);
                }
                // Add report file into moodle file storage.
                $file_record = array(
                    'contextid' => $context->id,
                    'component' => 'plagiarism_mcopyfind',
                    'filearea' => 'report',
                    'itemid' => $reportId,
                    'filepath' => '/',
                    'filename' => $reportId.'matches.html',
                );

                 $file = $fs->create_file_from_pathname($file_record ,$CFG->dirroot ."/plagiarism/mcopyfind/reports/". $reportId.'matches.html');

                //todo update report record with fileid
                $reportRec->fileid = $file->get_id();// itemid;
                $reportRec->matches = $size;// itemid;
                $reportRec->settings = strval($this->settings);// itemid;
                $DB->update_record('plagiarism_mcopyfind_report',$reportRec);
                return $file ;
            }else{              
                $DB->delete_records('plagiarism_mcopyfind_report', array('id'=>$reportId));
                // No Match found
                return;
            }
        }else{
            //Wrong context
            return;
        }
    }

}