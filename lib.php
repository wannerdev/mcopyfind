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

/**
 * lib.php - Contains Plagiarism plugin specific functions called by Modules.
 *
 * @since 2.0
 * @package    plagiarism_mcopyfind
 * @subpackage plagiarism
 * @copyright  2010 Dan Marsden http://danmarsden.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

//get global class
global $CFG;
require_once($CFG->dirroot.'/plagiarism/lib.php');


class plagiarism_plugin_mcopyfind extends plagiarism_plugin {

    const RUN_NO = 0;
    const RUN_MANUAL = 1;
    const RUN_AUTO = 2;
    const RUN_ALL = 3;
    const RUN_DUE = 4;
    const RUN_SUBMIT_MANUAL = 5;
    const RUN_SUBMIT_ON_CLOSED_SUBMISSION = 6;
    const SHOWSTUDENTS_NEVER = 0;
    const SHOWSTUDENTS_ALWAYS = 1;
    const SHOWSTUDENTS_ACTCLOSED = 2;
    const SHOWS_ONLY_PLVL = 0;
    const SHOWS_LINKS = 1;
    const ONLINE_TEXT = 1;
    const ONLINE_TEXT_NO = 0;

    public $file=null;

     /**
     * hook to allow plagiarism specific information to be displayed beside a submission 
     * @param array  $linkarraycontains all relevant information for the plugin to generate a link
     * @return string
     * 
     */
    public function get_links($linkarray) {
        //$userid, $file, $cmid, $course, $module
        $cmid = $linkarray['cmid'];
        $userid = $linkarray['userid'];
        $file = $linkarray['file'];
        $output = '';
        //todo generate link report by name
        //.strval($file)
        // $output = html_writer::tag('a', "./../../reports", array('class' => 'plagiarismreport'));
        //add link/information about this file to $output
         
        return $output;
    }

    /**
     * hook to allow a disclosure to be printed notifying users what will happen with their submission
     * @param int $cmid - course module id
     * @return string
     */
    public function print_disclosure($cmid) {
        global $OUTPUT,$DB;
        $plagiarismsettings = (array)get_config('plagiarism');
        //TODO: check if this cmid has plagiarism enabled.
        $outputhtml = '';

        // if ($plagiarismsettings = $this->get_settings()) {
        //      if (!empty($plagiarismsettings['mcopyfind_student_disclosure'])) {

        //          $params = array('cm' => $cmid, 'name' => 'use_mcopyfind');
        //          $showdisclosure = $DB->get_field('plagiarism_mcopyfind_config', 'value', $params);
        //          if ($showdisclosure) {
        //              $outputhtml .= $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
        //              $formatoptions = new stdClass;
        //              $formatoptions->noclean = true;
        //              $outputhtml .= format_text($plagiarismsettings['mcopyfind_student_disclosure'], FORMAT_MOODLE, $formatoptions);
        //              $outputhtml .= $OUTPUT->box_end();
        //          }
        //      }
        //  }
         return $outputhtml;
        // echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
        // $formatoptions = new stdClass;
        // $formatoptions->noclean = true;
        // echo format_text($plagiarismsettings['mcopyfind_student_disclosure'], FORMAT_MOODLE, $formatoptions);
        // echo $OUTPUT->box_end();
    }

    /**
     * This function should be used to initialise settings and check if plagiarism is enabled
     * *
     * @return mixed - false if not enabled, or returns an array of relevant settings.
     */
    public function get_settings() {
        global $DB;
        $plagiarismsettings = (array)get_config('plagiarism');
        //check if mcopyfind is enabled.
        if (isset($plagiarismsettings['mcopyfind_use']) ) {
            return $plagiarismsettings;
        } else {
            return false;
        }
    }
    /**
     * hook to allow status of submitted files to be updated - called on grading/report pages.
     *
     * @param object $course - full Course object
     * @param object $cm - full cm object
     */
    public function update_status($course, $cm) {
        global $PAGE, $DB, $CFG;

        //called at top of submissions/grading pages - allows printing of admin style links or updating status
   
        $output = '';
        // $DB->set_debug(true);
        
   
        $pageurl = $PAGE->url;
        $pagination = optional_param('page', -1, PARAM_INT);
        
        if($pagination != -1){
            $pageurl->param('page', $pagination);
        }
        $output .= html_writer::empty_tag('br');
        $params = array('cmid' => s($cm->id), 
                        'return' => urlencode($pageurl)); // 'preset' => s($preset)
                     
        $submiturl = new moodle_url('/plagiarism/mcopyfind/submit/submit_all_files.php', $params);
        $incPreset = new moodle_url('/plagiarism/mcopyfind/submit/preset.php', $params);
        //todo load user config preset
        $preset=get_config( 'plagiarism_mcopyfind','preset');
         // get mcopyfind config preset from database, something like:        
        // $preset = $DB->get_field('plagiarism_mcopyfind_config', 'preset',array('id' => $cm->instance));
        switch($preset){
            default: //fall through to default preset
            case 1:{
                $preset="Recommended";
                break;
            }
            case 2:{
                $preset="MinorEdit";
                break;
            }
            case 3:{
                $preset="PDFCutHeaderandFooter";
                break;
            }
            case  4:{
                $preset="AbsoluteMatching";
            }
        }
        $output .= "<a class=\"btn btn-outline-secondary \" role=\"button\"  href=\"" .$incPreset. "\" > ". " MCopy preset:".$preset."</a>";
        $output .= "<a class=\"btn btn-secondary\" role=\"button\" target=\"_blank\" href=\"" .$submiturl. "\"> ".get_string('compare_all_files', 'plagiarism_mcopyfind')."</a>";
 
        
        $output .= html_writer::empty_tag('br');
        return $output;
    }

    /**
     * called by admin/cron.php 
     *
     */
    public function cron() {
        //do any scheduled task stuff        
        cleanReports();
    }
}

function cleanReports(){
    // Clean report folder
    $folder_path = "reports";
       
    // List of name of files inside
    // specified folder
    $files = glob($folder_path.'\*'); 
    // Deleting all the files in the list
    foreach($files as $file) {
        // Delete the given file
        if(is_file($file))unlink($file); 
    }
}

/**
 * Serve the files from the mcopyfind file areas.
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 */
function plagiarism_mcopyfind_pluginfile(
    $course,
    $cm,
    $context,
    string $filearea,
    array $args,
    bool $forcedownload,
    array $options = []
): bool {
    global $DB;
    //throw new Exception("EXECUTING HANDLER");
    // echo ("HANDLERRR");
    // Check the contextlevel is as expected - if your plugin is a block, this becomes CONTEXT_BLOCK, etc.
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== 'report' ) {
        return false;
    }

    // Make sure the user is logged in and has access to the module (plugins that are not course modules should leave out the 'cm' part).
    require_login($course, true, $cm);

    // Check the relevant capabilities - these may vary depending on the filearea being accessed.
    // if (!has_capability('plagiarism/mcopyfind:create', $context)) {
    //     return false;
    // }

    // The args is an array containing [itemid, path].
    // Fetch the itemid from the path.
    $itemid = array_shift($args);

    // The itemid can be used to check access to a record, and ensure that the
    // record belongs to the specifeid context. For example:
    if ($filearea === 'report') {
        $report = $DB->get_record('plagiarism_mcopyfind_report', ['id' => $itemid]);
        // if ($report->mcopyfind !== $context->instanceid) {
            // This post does not belong to the requested context.
            // return false;
        // }

        // You may want to perform additional checks here, for example:
        // - ensure that if the record relates to a grouped activity, that this
        //   user has access to it
        // - check whether the record is hidden
        // - check whether the user is allowed to see the record for some other
        //   reason.

        // If, for any reason, the user does not hve access, you can return
        // false here.
    }

    // For a plugin which does not specify the itemid, you may want to use:
    // $itemid = null; //to make your code more consistent.

    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (empty($args)) {
        // $args is empty => the path is '/'.
        $filepath = '/';
    } else {
        // $args contains the remaining elements of the filepath.
        $filepath = '/' . implode('/', $args) . '/';
    }

    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'plagiarism_mcopyfind', $filearea, $itemid, $filepath, $filename);
    // $file=$fs->get_area_files($context->id, 'plagiarism_mcopyfind', 'report', $itemid);
    if (!$file) {
        // The file does not exist.
        return send_file_not_found();
    }
    $daySecs = 60*60*24;
    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    send_stored_file($file, $daySecs, 0, $forcedownload, $options);
}

function mcopyfind_event_file_uploaded($eventdata) {
    $result = true;
        //a file has been uploaded - submit this to the plagiarism prevention service.

    return $result;
}

function mcopyfind_event_files_done($eventdata) {
    $result = true;
        //mainly used by assignment finalize - used if you want to handle "submit for marking" events
        //a file has been uploaded/finalised - submit this to the plagiarism prevention service.

    return $result;
}

function mcopyfind_event_mod_created($eventdata) {
    $result = true;
        //a new module has been created - this is a generic event that is called for all module types
        //make sure you check the type of module before handling if needed.

    return $result;
}

function mcopyfind_event_mod_updated($eventdata) {
    $result = true;
        //a module has been updated - this is a generic event that is called for all module types
        //make sure you check the type of module before handling if needed.

    return $result;
}

function mcopyfind_event_mod_deleted($eventdata) {
    $result = true;
        //a module has been deleted - this is a generic event that is called for all module types
        //make sure you check the type of module before handling if needed.

    return $result;
}
