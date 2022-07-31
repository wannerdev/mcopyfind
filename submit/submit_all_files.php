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
 *
 * @package     plagiarism_mcopyfind
 * @subpackage  plagiarism
 * @author      Jesús Prieto <jprieto@plagscan.com> (Based on the work of Ruben Olmedo  <rolmedo@plagscan.com>)
 * @copyright   2018 PlagScan GmbH {@link https://www.plagscan.com/}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use plagiarism_mcopyfind\compare\assignments;
use plagiarism_mcopyfind\compare\load_documents;



require(__DIR__ . '/../../../config.php');

require_once($CFG->dirroot.'/plagiarism/mcopyfind/classes/compare/assignments.php');
require_once($CFG->dirroot . '/plagiarism/mcopyfind/classes/compare/load_documents.php');
require_once($CFG->dirroot . '/plagiarism/mcopyfind/lib.php');
global $CFG, $DB, $USER;
$PAGE->set_url(new moodle_url('/plagiarism/mcopyfind/submit/submit_all.php'));

require_login();

$cmid = required_param('cmid', PARAM_INT);
//$content = optional_param('content', '', PARAM_RAW);
//$objectid = optional_param('objectid', 0, PARAM_INT);
$return = required_param('return', PARAM_TEXT);

 if ($CFG->version < 2011120100) {
     $context = get_context_instance(CONTEXT_MODULE, $cmid);
 } else {
     $context = context_module::instance($cmid);
 }
//$context=context_module::instance($cmid);
//print_error($cmid);
//print_error($PAGE->cm);
$PAGE->set_context($context);

if (!(has_capability('plagiarism/mcopyfind:view', $context) || has_capability('plagiarism/mcopyfind:create', $context))) {
    throw new moodle_exception('Permission denied! You do not have the right capabilities.', 'plagiarism_mcopyfind');
}

if (!get_config('plagiarism_mcopyfind', 'enabled')) {
    // Disabled at the site level
    print_error('disabledsite', 'plagiarism_mcopyfind');
}

$cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
//$cm=get_fast_modinfo($courseorid)->get_cm($cmid);
//$cm=get_fast_modinfo(4)->get_cm($cmid);

$notification = \core\output\notification::NOTIFY_SUCCESS;

$sub = new assignments();
$fs = get_file_storage();

$file=$sub->access_all_files($cm, $context);

$url=urldecode($return);
if($file != null){
    $url = moodle_url::make_pluginfile_url(
        $file->get_contextid(),
        $file->get_component(),
        $file->get_filearea(),
        $file->get_itemid(),
        $file->get_filepath(),
        $file->get_filename(),
        false                     // Do not force download of the file.
    );
  
}

// $return = $return . "&action=submit_all";
//$return = urldecode($return);

//$report = new moodle_url();
// $url->param('target',$blan = '_blank');
//redirect($return);
 redirect($url);