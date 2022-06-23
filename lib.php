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
        //add link/information about this file to $output
         
        return $output;
    }

    /**
     * hook to allow a disclosure to be printed notifying users what will happen with their submission
     * @param int $cmid - course module id
     * @return string
     */
    public function print_disclosure($cmid) {
        global $OUTPUT;
        $plagiarismsettings = (array)get_config('plagiarism');
        //TODO: check if this cmid has plagiarism enabled.
        echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        echo format_text($plagiarismsettings['mcopyfind_student_disclosure'], FORMAT_MOODLE, $formatoptions);
        echo $OUTPUT->box_end();
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
        
        
        // if ($config->isEnabled == self::RUN_NO) {
        //     return '';
        // }


        $output = '';
//$DB->set_debug(true);
        //if ($config->upload == self::RUN_AUTO) {
            $modinfo = get_fast_modinfo($course);
            $cminfo = $modinfo->get_cm($cm->id);
            if ($cminfo->modname != 'assignment' && $cminfo->modname != 'assign') {
                // Not an assignment - auto submission to plagscan will not work
                $output .= 'onlyassignmentwarning';//get_string('onlyassignmentwarning', 'plagiarism_plagscan');
            } else {
                if ($cminfo->modname == 'assignment') {
                    $timedue = $DB->get_field('assignment', 'timedue', array('id' => $cm->instance));
                } else {
                    $timedue = $DB->get_field('assign', 'duedate', array('id' => $cm->instance));
                }
                if (!$timedue) {
                    // No deadline set - auto submission will never happen
                    $output .= "nodeadlinewarning";//get_string('nodeadlinewarning', 'plagiarism_plagscan');
                } else {
                    if ($timedue < 0) {
                        $output .= "autodescriptionsubmitted".userdate(0, get_string('strftimedatetimeshort')) ;//get_string('autodescriptionsubmitted', 'plagiarism_plagscan', userdate($run->complete, get_string('strftimedatetimeshort')));
                    } else {
                        $output .= 'autodescription';//get_string('autodescription', 'plagiarism_plagscan');
                    }
                }
            }
            $output .= '<br/>';
        
   
        $pageurl = $PAGE->url;
        $pagination = optional_param('page', -1, PARAM_INT);
        
        if($pagination != -1){
            $pageurl->param('page', $pagination);
        }
        $output .= html_writer::empty_tag('br');
        $params = array('cmid' => s($cm->id), 
                        'return' => urlencode($pageurl));

        $submiturl = new moodle_url('/plagiarism/mcopyfind/reports/submit_all_files.php', $params);
        $output .= html_writer::link($submiturl, get_string('submit_all_files', 'plagiarism_mcopyfind'));
        $output .= html_writer::empty_tag('br');

        $compareurl = new moodle_url('/plagiarism/mcopyfind/classes/load_documents.php');
        $output .= html_writer::link($compareurl, "test compare function");
        $output .= html_writer::empty_tag('br');

        return $output;
    }

    /**
     * called by admin/cron.php 
     *
     */
    public function cron() {
        //do any scheduled task stuff
    }
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
