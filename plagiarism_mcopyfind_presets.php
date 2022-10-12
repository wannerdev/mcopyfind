<?php

require_once($CFG->dirroot.'/lib/formslib.php');

class plagiarism_mcopyfind_presets extends moodleform {

// Define the form
    function definition () {

        global $USER;
        $mform =$this->_form;
        
        $radioarray=array();
        $attributes=[];
        //Presets
        $radioarray[] = $mform->createElement('radio', 'preset', '', get_string('default','plagiarism_mcopyfind'), 0, $attributes);
        $radioarray[] = $mform->createElement('radio', 'preset', '', get_string('minoredit','plagiarism_mcopyfind'), 1, $attributes);
        $radioarray[] = $mform->createElement('radio', 'preset', '', get_string('absolutematching','plagiarism_mcopyfind'), 2, $attributes);
        $radioarray[] = $mform->createElement('radio', 'preset', '', get_string('removeheaderandfooter','plagiarism_mcopyfind'), 3, $attributes);
        $mform->setDefault(get_string('default'), 0);

        $mform->addGroup($radioarray, 'radioar', '', array(' '), false);
        // $mform->setAdvanced('radioar');
        
        //Multi select Courses
        $courses = enrol_get_users_courses($USER->id, true);
        $names = array();
        foreach ($courses as $course) {
            //todo check if course is the same course and exclude it from display
            // if($course->id != $cmid)
            $names += [$course->id => $course->fullname];
        }

        $mform->addElement('select', 'courses', get_string('courses_compare','plagiarism_mcopyfind'),  $names);
        $mform->getElement('courses')->setMultiple(true);
        
        // $mform->setAdvanced('courses');
        $this->add_action_buttons(false,"Compare all");
    }
}
