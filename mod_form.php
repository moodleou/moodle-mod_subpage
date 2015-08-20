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
 * Add subpage form
 *
 * @package    mod
 * @subpackage subpage
 * @copyright 2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_subpage_mod_form extends moodleform_mod {

    public function definition() {
        global $CFG, $DB;

        $mform = $this->_form;

        $mform->addElement('text', 'name', get_string('name'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements();

        // Sharing facilities are only available if the 'sharedsubpage' module
        // is installed.
        if ($DB->get_field('modules', 'id', array('name' => 'sharedsubpage'))) {
            $mform->addElement('checkbox', 'enablesharing', '',
                get_string('enablesharing', 'subpage'));
            $mform->addHelpButton('enablesharing', 'enablesharing', 'subpage');
        }

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

    public function definition_after_data() {
        global $DB;
        parent::definition_after_data();
        $mform = $this->_form;

        // If sharing is turned on, check to see if they can turn it off.
        if ($mform->elementExists('enablesharing') &&
                $mform->getElementValue('enablesharing')) {
            // Look for any shared pages that use this id.
            $subpageid = $mform->getElementValue('instance');
            if ($DB->record_exists('sharedsubpage', array('subpageid' => $subpageid))) {
                $mform->getElement('enablesharing')->freeze();
            }
        }
    }

    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        // If you enable sharing, you must enter an idnumber.
        if (!empty($data['enablesharing']) && empty($data['cmidnumber'])) {
            $errors['cmidnumber'] = get_string('error_noidnumber', 'subpage');
        }
        // If you turn off sharing, there must be no shared pages using it.
        if (empty($data['enablesharing']) && !empty($this->_instance)) {
            if ($DB->get_field('modules', 'id', array('name' => 'sharedsubpage'))) {
                // Check if there is a shared subpage...
                if ($DB->record_exists('sharedsubpage',
                        array('subpageid' => $this->_instance))) {
                    $errors['enablesharing'] = get_string('error_sharingused', 'subpage');
                }
            }
        }
        // ID numbers must be unique, systemwide.
        if (!empty($data['cmidnumber'])) {
            // Except obviously on this existing course-module (if it does exist).
            $except = -1;
            if (!empty($data['coursemodule'])) {
                $except = $data['coursemodule'];
            }

            if ($DB->record_exists_sql(
                    'SELECT 1 FROM {course_modules} WHERE idnumber = ? AND id <> ?',
                    array($data['cmidnumber'], $except))) {
                $errors['cmidnumber'] = get_string('error_duplicateidnumber', 'subpage');
            }
        }
        return $errors;
    }
}
