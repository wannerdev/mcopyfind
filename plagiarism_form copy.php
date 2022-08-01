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
