<?php

require_once($CFG->dirroot.'/lib/formslib.php');

class preset_form extends moodleform {

// Define the form
    function definition () {
        global $CFG;

        $mform =& $this->_form;
        
        $radioarray=array();
        $attributes=[];
        $radioarray[] = $mform->createElement('radio', 'preset', '', get_string('presetrecommended','plagiarism_mcopyfind'), 0, $attributes);
        $radioarray[] = $mform->createElement('radio', 'preset', '', get_string('presetminoredit','plagiarism_mcopyfind'), 1, $attributes);
        $radioarray[] = $mform->createElement('radio', 'preset', '', get_string('presetabsolute','plagiarism_mcopyfind'), 2, $attributes);
        $radioarray[] = $mform->createElement('radio', 'preset', '', get_string('presetpdfheadfoot','plagiarism_mcopyfind'), 3, $attributes);
        $mform->setDefault('preset', 0);
        //$radioarray[] = $mform->createElement('radio', 'preset', '', get_string('Remove Header and Footer'), 0, $attributes);
        $mform->addGroup($radioarray, 'radioar', '', array(' '), false);
        $this->add_action_buttons(false);
    }
}
