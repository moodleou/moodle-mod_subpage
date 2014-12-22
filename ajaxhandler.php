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
 * Code fragment to define the version of subpage
 * This fragment is called by moodle_needs_upgrading() and /admin/index.php
 *
 * @package mod
 * @subpackage subpage
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once('locallib.php');

$subpageid        = required_param('subpage', PARAM_INT);
$action          = required_param('action', PARAM_ALPHA);
$thisid          = required_param('this', PARAM_ALPHANUMEXT);
$nextid          = optional_param('next', '', PARAM_ALPHANUMEXT);
$section         = optional_param('section', '', PARAM_ALPHANUMEXT);

$subpage = mod_subpage::get_from_cmid($subpageid);

require_login($subpage->get_course()->id, false);

$context = context_course::instance($subpage->get_course()->id);
require_capability('moodle/course:manageactivities', $context);

require_sesskey();

if ($action == 'movesection') {

    // Cleanup and check vars.
    if (strpos($thisid, 'section-') === 0) { // Section must be provided - if not then can't move.

        $thisid = str_replace('section-', '', $thisid);
        $thissection = $DB->get_record('course_sections',
                array('course' => $subpage->get_course()->id, 'section' => $thisid), '*', MUST_EXIST);

        if (!empty($nextid) && strpos($nextid, 'section-') === 0) {
            // Section must be provided - if not then can't move.
            $nextid = str_replace('section-', '', $nextid);
            $nextid = $DB->get_record('course_sections',
                    array('course' => $subpage->get_course()->id, 'section' => $nextid),
                    '*', MUST_EXIST);
            $newlocation = $DB->get_field('subpage_sections', 'pageorder',
                    array('subpageid' => $subpage->get_subpage()->id, 'sectionid' => $nextid->id));
            $oldlocation = $DB->get_field('subpage_sections', 'pageorder',
                    array('subpageid' => $subpage->get_subpage()->id,
                        'sectionid' => $thissection->id));
            if ($newlocation > $oldlocation) {
                $newlocation--;
            }
        } else {
            $newlocation = '';
        }
        if (empty($newlocation)) {
            $newlocation = $DB->get_field_sql(
                    "SELECT MAX(pageorder) FROM {subpage_sections} WHERE subpageid = ?",
                    array($subpage->get_subpage()->id));
        }
        $subpage->move_section($thissection->id, $newlocation);
    }

} else if ($action == 'moveactivity') {
    // Now cleanup and check vars.
    if (strpos($thisid, 'module-') === 0) {
        $thisid = str_replace('module-', '', $thisid);
        $thisid = get_coursemodule_from_id(false, $thisid,
                $subpage->get_course()->id, false, MUST_EXIST);
    } else {
        $thisid = '';
    }

    if (strpos($nextid, 'module-') === 0) {
        $nextid = str_replace('module-', '', $nextid);
        $nextid = get_coursemodule_from_id(false, $nextid,
                $subpage->get_course()->id, false, MUST_EXIST);
    } else {
        $nextid = '';
    }

    if (strpos($section, 'section-') === 0) { // Section must be provided - if not then can't move.
        $section = str_replace('section-', '', $section);
        $section = $DB->get_record('course_sections', array(
                'course' => $subpage->get_course()->id, 'section' => $section), '*', MUST_EXIST);

        moveto_module($thisid, $section, $nextid);
    }
}
