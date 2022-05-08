<?php

require_once($CFG->dirroot.'/lib/formslib.php');

class plagiarism_setup_form extends moodleform {

/// Define the form
    function definition () {
        global $CFG;

        $mform =& $this->_form;
        

        $choices = array('No','Yes');
        $mform->addElement('html', get_string('mcopyfindexplain', 'plagiarism_mcopyfind'));
        $mform->addElement('checkbox', 'mcopyfind_use', get_string('usemcopyfind', 'plagiarism_mcopyfind'));

        $mform->addElement('textarea', 'mcopyfind_student_disclosure', get_string('studentdisclosure','plagiarism_mcopyfind'),'wrap="virtual" rows="6" cols="50"');
        $mform->addHelpButton('mcopyfind_student_disclosure', 'studentdisclosure', 'plagiarism_mcopyfind');
        $mform->setDefault('mcopyfind_student_disclosure', get_string('studentdisclosuredefault','plagiarism_mcopyfind'));
        $this->add_action_buttons(false);
    }
}

