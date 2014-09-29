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
 * The items_moved event.
 *
 * @package mod_subpage
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_subpage\event;
defined('MOODLE_INTERNAL') || die();

/**
 * The items_moved event class.
 *
 * @property-read array $other {
 *      destsectionid = target section id
 *      destcmid (optional) = cmid of target subpage
 *      cmids = array of cmids
 * }
 *
 * @package mod_subpage
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class items_moved extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    public static function get_name() {
        return get_string('eventitems_moved', 'mod_subpage');
    }

    public function get_description() {
        return 'The user with id ' . $this->userid . ' moved item cmids ' .
                implode(',', $this->other['cmids']) . ' to ' .
                (empty($this->other['destcmid']) ? 'study planner' :
                    'subpage cmid ' . $this->other['destcmid']) .
                ' section id ' . $this->other['destsectionid'];
    }

    public function get_url() {
        return new \moodle_url('/mod/subpage/view.php', array('id' => $this->contextinstanceid));
    }

    public function get_legacy_logdata() {
        return array($this->courseid, 'subpage', 'move',
                '', $this->other['destsectionid'] . ' (' .
                (empty($this->other['destcmid']) ? 'study planner' :
                    'subpage cmid ' . $this->other['destcmid']) .
                '): ' . implode(',', $this->other['cmids']),
                $this->contextinstanceid);
    }
}
