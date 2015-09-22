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
 * Copies the subpage to another website.
 *
 * @package mod_subpage
 * @copyright 2015 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('locallib.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/moodle2/backup_plan_builder.class.php');

$cmid = required_param('id', PARAM_INT);

$targetcourseid = optional_param('targetid', null, PARAM_INT);
$confirm = optional_param('confirm', null, PARAM_BOOL);

$url = new moodle_url('/mod/subpage/copy.php', array('id' => $cmid));

$subpage = mod_subpage::get_from_cmid($cmid);

require_login($subpage->get_course(), false, $subpage->get_course_module());

$context = context_module::instance($cmid);

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('copy', 'subpage'));
$PAGE->set_pagelayout('incourse');

require_capability('moodle/backup:backupsection', $context);
require_capability('moodle/backup:backupactivity', $context);

$restrenderer = $PAGE->get_renderer('core', 'backup');

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('copy', 'subpage'));

if (!$targetcourseid) {
    // Start (course selection) screen.
    echo html_writer::div(get_string('copy_help', 'subpage'));
    $coursesearch = new \mod_subpage\copy\restore_course_search(array('url' => $url), $subpage->get_course()->id);
    echo $restrenderer->course_selector($url, false, null, $coursesearch, null);
} else if (!$confirm) {
    // Confirmation screen.
    $shortname = $DB->get_field('course', 'shortname', array('id' => $targetcourseid), MUST_EXIST);
    $starturl = clone $url;
    $starturl->param('targetid', $targetcourseid);
    $starturl->param('confirm', true);
    echo $OUTPUT->confirm(get_string('copy_continue', 'subpage', $shortname), $starturl, $url);
} else {
    // Copy process + feedback.
    $targetcourse = $DB->get_record('course', array('id' => $targetcourseid), '*', MUST_EXIST);
    $copy = new \mod_subpage\copy\lib();
    $copy->process($subpage, $targetcourse);
    echo $OUTPUT->continue_button(new moodle_url('/course/view.php', array('id' => $targetcourseid)));
    $event = \mod_subpage\event\subpage_copied::create(array(
            'context' => $context, 'other' => array('dest' => $targetcourseid)));
    $event->trigger();
}

echo $OUTPUT->footer();
