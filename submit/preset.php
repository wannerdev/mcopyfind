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

$return = required_param('return', PARAM_TEXT);

 if ($CFG->version < 2011120100) {
     $context = get_context_instance(CONTEXT_MODULE, $cmid);
 } else {
     $context = context_module::instance($cmid);
 }

$PAGE->set_context($context);

if (!(has_capability('plagiarism/mcopyfind:view', $context) || has_capability('plagiarism/mcopyfind:create', $context))) {
    throw new moodle_exception('Permission denied! You do not have the right capabilities.', 'plagiarism_mcopyfind');
}

if (!get_config('plagiarism_mcopyfind', 'enabled')) {
    // Disabled at the site level
    print_error('disabledsite', 'plagiarism_mcopyfind');
}

$cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);


$notification = \core\output\notification::NOTIFY_SUCCESS;

//to do insert preset in database mcopyfind config , something like: 
// $preset=$DB->get_field('plagiarism_mcopyfind_config', 'preset',array('id' => $cm->instance));
// if($preset ==5){
//     $preset=1;
// }
// $preset = $DB->insert_record('plagiarism_mcopyfind_config', ['preset'=> $preset],array('id' => $cm->instance));



$return = urldecode($return);

redirect($return);