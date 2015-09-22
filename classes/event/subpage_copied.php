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
 * The subpage copied event.
 *
 * @package mod_subpage
 * @copyright 2015 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_subpage\event;
defined('MOODLE_INTERNAL') || die();

/**
 * The event class.
 *
 * @package mod_subpage
 * @copyright 2015 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class subpage_copied extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    public static function get_name() {
        return get_string('copy_event', 'mod_subpage');
    }

    public function get_description() {
        return 'The user with id "' . $this->userid . '" copied subpage "' .
                $this->contextinstanceid . '" to course "' .
                $this->other['dest'] . '"';
    }

    public function get_url() {
        return new \moodle_url('/mod/subpage/view.php', array('id' => $this->contextinstanceid));
    }

    public function get_legacy_logdata() {
        return array($this->courseid, 'subpage', 'copy',
                '', $this->other['dest'], $this->contextinstanceid);
    }
}
