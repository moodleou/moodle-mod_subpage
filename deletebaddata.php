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
 * Deletes bad data from the database.
 *
 * @package mod_subpage
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

require_login();
if (!is_siteadmin()) {
    throw new coding_exception("Site admins only!");
}

// You must call this with iamsure=1 and sesskey=<your sesskey> to avoid any
// potential security problems. To find out the right sesskey to use, look
// at other 'action links' in Moodle.
required_param('iamsure', PARAM_INT);
require_sesskey();

// The temporary ids used during restore are made by adding 10,000,000 to the
// id, so it should be safe to delete everything over 10M. Just to be sure,
// also check that there isn't a course_section with that id.
$count = $DB->count_records_sql("SELECT COUNT(1) FROM {subpage_sections} WHERE sectionid > 10000000 " .
        "AND NOT EXISTS(SELECT 1 FROM {course_sections} WHERE id = sectionid)");
if ($count) {
    $DB->execute("DELETE FROM {subpage_sections} WHERE sectionid > 10000000 " .
            "AND NOT EXISTS(SELECT 1 FROM {course_sections} WHERE id = sectionid)");
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/mod/subpage/deletebaddata.php'));
print $OUTPUT->header();
print html_writer::tag('p', 'Deleted ' . $count . ' row(s) of bad data');
print $OUTPUT->footer();
