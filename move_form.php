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
 * This file contains all necessary code to define and process the move form
 *
 * @package mod
 * @subpackage subpage
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot . "/mod/subpage/form/selecttree.php");

class mod_subpage_move_form extends moodleform {

    protected function definition() {
        global $CFG;

        $mform = $this->_form;

        $moveable = $this->_customdata['moveable'];
        $options = $this->_customdata['options'];
        $move = $this->_customdata['move'];
        $id = $this->_customdata['id'];

        // hiddens
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'move');
        $mform->setType('move', PARAM_RAW);

        if (!empty($moveable)) {
            foreach ($moveable as $item) {
                $mform->addElement('header', 'general', $item['section']);
                foreach ($item['mods'] as $key => $value) {
                    $mform->addElement('advcheckbox', 'mod'.$key, null, $value);
                }
            }
        }

        if (!empty($options)) {
            $mform->addElement('header', 'general', '');
            $mform->addElement('selecttree', 'destination', get_string('moveto', 'mod_subpage'),
                    array('options' => $options));
            if ($move === 'to') {
                $mform->setDefault('destination', $id.',new');
            }
        }

        $this->add_action_buttons(true, get_string('moveselected', 'mod_subpage'));
    }
}
