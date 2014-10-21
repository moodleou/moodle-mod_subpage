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

defined('MOODLE_INTERNAL') || die();

/**
 * Subpage generator for unit test.
 *
 * @package mod_subpage
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_subpage_generator extends testing_module_generator {

    /**
     * Create a new subpage instance
     * 
     * @param array|stdClass $record The record to insert to subpage table
     * @param mod_subpage $subpage Subpage object
     * @param array $options Optional parameters
     * @return mod_subpage Subpage object created
     */
    public function create_instance($record = null, array $options = null) {
        global $DB, $CFG;

        $record = (object)(array)$record;
        if (!isset($record->enablesharing)) {
            $record->enablesharing = 0;
        }
        $result = parent::create_instance($record, (array)$options);
        if (!empty($record->addsection)) {
            require_once($CFG->dirroot . '/mod/subpage/locallib.php');
            $subobj = mod_subpage::get_from_cmid($result->cmid);
            $subobj->add_section();
        }
        return $result;
    }
}
