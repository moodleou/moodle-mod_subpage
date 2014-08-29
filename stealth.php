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
 * Deals with stealthing.
 *
 * @package mod_subpage
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('locallib.php');

$cmid        = required_param('id', PARAM_INT);
$stealth     = optional_param('stealth', 0, PARAM_INT);
$unstealth   = optional_param('unstealth', 0, PARAM_INT);

$subpage = mod_subpage::get_from_cmid($cmid);
$course = $subpage->get_course();
$thisurl = new moodle_url('/mod/subpage/view.php', array('id' => $cmid));

require_login($course, true, $subpage->get_course_module());
$context = context_module::instance($cmid);
require_capability('moodle/course:sectionvisibility', $context);

if ($stealth && confirm_sesskey()) {
    $subpage->set_section_stealth($stealth, 1);
    rebuild_course_cache($course->id, true);
}
if ($unstealth && confirm_sesskey()) {
    $subpage->set_section_stealth($unstealth, 0);
    rebuild_course_cache($course->id, true);
}

redirect($thisurl);
