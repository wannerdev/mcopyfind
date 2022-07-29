<?php

require_once($CFG->dirroot.'/lib/formslib.php');

class plagiarism_settings_form extends moodleform {

// Define the form
    function definition () {
        global $CFG;

        $mform =& $this->_form;
        
        $radioarray=array();
        $attributes=[];
        $radioarray[] = $mform->createElement('radio', 'preset', '', get_string('Default recommended'), 0, $attributes);
        $radioarray[] = $mform->createElement('radio', 'preset', '', get_string('Minor edit'), 1, $attributes);
        $radioarray[] = $mform->createElement('radio', 'preset', '', get_string('Absolute Matching'), 2, $attributes);
        $radioarray[] = $mform->createElement('radio', 'preset', '', get_string('Remove Header and Footer'), 3, $attributes);
        $mform->setDefault('preset', 0);
        //$radioarray[] = $mform->createElement('radio', 'preset', '', get_string('Remove Header and Footer'), 0, $attributes);
        $mform->addGroup($radioarray, 'radioar', '', array(' '), false);
        $this->add_action_buttons(false);
    }
}

/**
 *         
 **         global $CFG;
*
  *      $mform = & $this->_form;
*
  *      //initial variables
  *      $languageoptions = array(0 => get_string('english', 'plagiarism_mcopyfind'), 1 => get_string('german', 'plagiarism_mcopyfind'), 2 => get_string('spanish', 'plagiarism_mcopyfind'), 3 => get_string('french', 'plagiarism_mcopyfind'));
  *      $emailoptions = array(0 => get_string('email_policy_never', 'plagiarism_mcopyfind'), 1 => get_string('email_policy_always', 'plagiarism_mcopyfind'), 2 => get_string('email_policy_ifred', 'plagiarism_mcopyfind'));
  *      $dataoptions = array(0 => get_string('noone', 'plagiarism_mcopyfind'), 1 => get_string('noonedocs', 'plagiarism_mcopyfind'), 2 => get_string('myinstitution', 'plagiarism_mcopyfind'), 3 => get_string('generaldatabase', 'plagiarism_mcopyfind'));
  *      $autostartoptions = array(0 => get_string('no'), 1 => get_string('yes'));
  *      $autodel = array(0 => get_string('week', 'plagiarism_mcopyfind'), 1 => get_string('weeks', 'plagiarism_mcopyfind'), 2 => get_string('months', 'plagiarism_mcopyfind'), 3 => get_string('neverdelete', 'plagiarism_mcopyfind'));
  *      $docx = array(0 => get_string('docxemail', 'plagiarism_mcopyfind'),
  *          1 => get_string('docxgenerate', 'plagiarism_mcopyfind'),
  *          2 => get_string('docxnone', 'plagiarism_mcopyfind'));
  *      $accountsopts = array(0 => get_string('singleaccount', 'plagiarism_mcopyfind'),
  *          1 => get_string('individualaccounts', 'plagiarism_mcopyfind'));
*
  *      //build form
  *      $mform->addElement('html', "<div style='margin-left: 10%;margin-right: 30%;'><img style='margin-left:32%;' src='images/logo-new.png'/> <br /><div style='margin-left: 15%;'>" . get_string('mcopyfindexplain', 'plagiarism_mcopyfind') . "</div><br />");
*
  *      $mform->addElement('html', "<div>");
*
  *      $mform->addElement('checkbox', 'mcopyfind_use', get_string('usemcopyfind', 'plagiarism_mcopyfind'));
*
*
  *      $mform->addElement('select', 'mcopyfind_language', get_string("api_language", "plagiarism_mcopyfind"), $languageoptions);
  *      $mform->addHelpButton('mcopyfind_language', 'api_language', 'plagiarism_mcopyfind');
  *      $mform->setDefault('mcopyfind_language', '0');
*
  *      $mform->addElement('select', 'mcopyfind_email_policy', get_string("email_policy", "plagiarism_mcopyfind"), $emailoptions);
  *      $mform->setDefault('mcopyfind_email_policy', '0');
*
*
*
  *      $mform->addElement('selectyesno', 'mcopyfind_studentpermission', get_string('mcopyfind_studentpermission', 'plagiarism_mcopyfind'), 0);
*
  *      $mform->addElement('textarea', 'mcopyfind_student_disclosure', get_string('studentdisclosure', 'plagiarism_mcopyfind'), 'wrap="virtual" rows="6" cols="50"');
  *      $mform->addHelpButton('mcopyfind_student_disclosure', 'studentdisclosure', 'plagiarism_mcopyfind');
  *      $mform->setDefault('mcopyfind_student_disclosure', get_string('studentdisclosuredefault', 'plagiarism_mcopyfind'));
*
  *      $mform->addElement('text', 'mcopyfind_groups', get_string('allowgroups', 'plagiarism_mcopyfind'), array('size' => '40', 'style' => 'height: 33px'));
  *      $mform->addHelpButton('mcopyfind_groups', 'allowgroups', 'plagiarism_mcopyfind');
  *      $mform->setType('mcopyfind_groups', PARAM_TEXT);
*
  *      $mform->addElement('text', 'mcopyfind_nondisclosure_notice_email', get_string('mcopyfind_nondisclosure_notice_email', 'plagiarism_mcopyfind'), array('style' => 'height: 33px', 'placeholder' => get_string('mcopyfind_nondisclosure_notice_email_desc', 'plagiarism_mcopyfind')));
  *      $mform->addHelpButton('mcopyfind_nondisclosure_notice_email', 'mcopyfind_nondisclosure_notice_email', 'plagiarism_mcopyfind');
  *      $mform->setType('mcopyfind_nondisclosure_notice_email', PARAM_TEXT);
*
*
*
  * 
  *      //  Think about how to secure the reports or rather how  to show them, hpw to clear them
  *      // $wipe_user_url = new moodle_url('/plagiarism/mcopyfind/wipe_mcopyfind_user_cache.php');
  *      // $mform->addElement('html', html_writer::link($wipe_user_url, get_string('wipe_mcopyfind_user_cache_link', 'plagiarism_mcopyfind')) 
  *      // . ' <a class="btn btn-link p-a-0" role="button" data-container="body" data-toggle="popover" data-placement="right"'
  *      // . 'data-content="'.get_string('wipe_mcopyfind_user_cache_help', 'plagiarism_mcopyfind').'<div style=\'color:red;\'>'.get_string('wipe_mcopyfind_user_cache_alert', 'plagiarism_mcopyfind').'</div>" data-html="true" tabindex="0" data-trigger="focus">'
  *      // . '<i class="icon fa fa-question-circle text-info fa-fw " aria-hidden="true" title="" aria-label=""></i>'
  *      // . '</a><br/>');
*
*
  *      $mform->addElement('html', "</br></br>");
  *      $mform->addElement('header', 'mcopyfind_assignment_defaults', get_string('mcopyfind_assignment_defaults_header', 'plagiarism_mcopyfind'));
  *      $mform->addElement('html', get_string('mcopyfind_assignment_defaults_explain', 'plagiarism_mcopyfind') 
  *      . "<br /><br />");
*
  *          $showstudentsopt = array(\plagiarism_plugin_mcopyfind::SHOWSTUDENTS_NEVER => get_string('show_to_students_never', 'plagiarism_mcopyfind'),
  *              \plagiarism_plugin_mcopyfind::SHOWSTUDENTS_ALWAYS => get_string('show_to_students_always', 'plagiarism_mcopyfind'),
  *              \plagiarism_plugin_mcopyfind::SHOWSTUDENTS_ACTCLOSED => get_string('show_to_students_actclosed', 'plagiarism_mcopyfind'));
*
  *          $showstudentslinks = array(\plagiarism_plugin_mcopyfind::SHOWS_ONLY_PLVL => get_string('show_to_students_plvl', 'plagiarism_mcopyfind'),
  *              \plagiarism_plugin_mcopyfind::SHOWS_LINKS => get_string('show_to_students_links', 'plagiarism_mcopyfind'));
*
  *              
  *      $mform->addElement('select', 'mcopyfind_defaults_show_to_students', get_string("show_to_students", "plagiarism_mcopyfind"), $showstudentsopt);
  *      $mform->addHelpButton('mcopyfind_defaults_show_to_students', 'show_to_students', 'plagiarism_mcopyfind');
  *      $mform->setDefault('mcopyfind_defaults_show_to_students', \plagiarism_plugin_mcopyfind::SHOWSTUDENTS_NEVER);
*
  *      $mform->addElement('select', 'mcopyfind_defaults_show_students_links', get_string("show_to_students_opt2", "plagiarism_mcopyfind"), $showstudentslinks);
  *      $mform->addHelpButton('mcopyfind_defaults_show_students_links', 'show_to_students_opt2', 'plagiarism_mcopyfind');
  *      $mform->setDefault('mcopyfind_defaults_show_students_links', \plagiarism_plugin_mcopyfind::SHOWS_ONLY_PLVL);
  *      $mform->disabledIf('mcopyfind_defaults_show_students_links', 'mcopyfind_defaults_show_to_students', 'eq', 0);
  *      $mform->setExpanded('mcopyfind_assignment_defaults', false);
 * 
 */