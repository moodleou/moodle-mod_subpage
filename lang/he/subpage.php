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
 * Strings for component 'scorm', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @author Dan Marsden <dan@danmarsden.com>
 * @package   subpage
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['modulename'] = 'תת־יחידת־הוראה';
$string['modulenameplural'] = 'תתי־יחידות־הוראה';
$string['pluginname'] = 'תת־יחידת־הוראה';
$string['pluginadministration'] = 'ניהול תת־יחידת־הוראה';

$string['sectionlimitexceeded'] = 'There is a limit on the number of subpage sections permitted per course. You cannot add more subpage sections to any subpage on this course.';
$string['unspecifysubpageid'] = 'Unspecified subpage id';
$string['addsection'] = 'הוספת יחידת־הוראה';
$string['sectiondeleteconfirm'] = 'האם אתם בטוחים שאתם מעוניינים למחוק את יחידת ההוראה: {$a}';
$string['movetopage'] = 'העברת פריטים לעמוד זה';
$string['movefrompage'] = 'העברת פריטים מעמוד זה';
$string['moveitems'] = 'העברת פריטים';
$string['moveselected'] = 'העברת פריטים שנבחרו';
$string['topsection'] = 'יחידת־הוראה ראשית';
$string['newsection'] = 'יחידת־הוראה חדשה';
$string['coursemainpage'] = 'העמוד הראשי של מרחב הלימוד';
$string['anothersection'] = 'תת יחידת־הוראה חדשה בתת עמוד זה';
$string['moveto'] = 'העבירו אל';
$string['section'] = 'יחידת־הוראה';
$string['nomodulesselected'] = 'לא נבחרו רכיבים להעברה.';
$string['sectionnotcreatedorexisting'] = 'Section doesn\'t exist or can\'t be created.';
$string['modulenotfound'] = 'הרכיב לא קיים.';
$string['nomodules'] = 'No modules were found on this subpage to move.';

$string['modulename_help'] = '<p>A subpage is a smaller version of the main course page.  You can create sections and add activities to group them together.</p>';

$string['enablesharing'] = 'אפשרו שיתוף';
$string['enablesharing_help'] = 'When you choose to enable sharing, anyone can add a copy of this page as a shared subpage to any course.

When enabling sharing you must enter an ID number in the field below. This ID number can then be used to create copies of the page.

Sharing only works for items of the following types: Label, Heading, File, and URL. Other items will not be shared.

Items are also not shared if they contain any type of access restriction (date, grouping, etc) or have completion tickboxes.';
$string['error_noidnumber'] = 'When sharing is enabled, you must enter an ID number';
$string['error_duplicateidnumber'] = 'The ID number must be unique across the system; choose something different';
$string['error_sharingused'] = 'Cannot disable sharing because there is already a shared subpage that copies this page';
$string['stealth'] = 'חבוי';
$string['unstealth'] = 'לא חבוי';
$string['error_deletingsection'] = 'לא ניתן למחוק תת־יחידת־הוראה אשר קיימים בה רכיבים';
