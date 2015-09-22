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
 * Displays the subpage.
 *
 * @package mod_subpage
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('locallib.php');
require_once('lib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/completionlib.php');

$cmid         = optional_param('id', 0, PARAM_INT);
$edit         = optional_param('edit', -1, PARAM_BOOL);
$hide         = optional_param('hide', 0, PARAM_INT);
$show         = optional_param('show', 0, PARAM_INT);
$section      = optional_param('section', 0, PARAM_INT);
$sectiontitle = optional_param('sectiontitle', '', PARAM_TEXT);
$move         = optional_param('move', 0, PARAM_INT);
$delete       = optional_param('delete', 0, PARAM_INT);
$copy         = optional_param('copy', 0, PARAM_INT);
$confirm      = optional_param('confirm', 0, PARAM_INT);
$addsection   = optional_param('addsection', 0, PARAM_INT);
$recache      = optional_param('recache', 0, PARAM_INT);
$cancelcopy   = optional_param('cancelcopy', 0, PARAM_BOOL);

if (empty($cmid)) {
    print_error('unspecifysubpageid', 'subpage');
}

if (!empty($cancelcopy) && confirm_sesskey()) {
    unset($USER->activitycopy);
    unset($USER->activitycopycourse);
    unset($USER->activitycopyname);
    redirect("view.php?id=$cmid");
}

// This must be done first because some horrible combination of junk means that
// page might be initialised before we expect.
$PAGE->set_pagelayout('incourse');
$subpage = mod_subpage::get_from_cmid($cmid);
$course = $subpage->get_course();
// Defined here to avoid notices on errors etc.
$thisurl = new moodle_url('/mod/subpage/view.php', array('id' => $cmid));
$PAGE->set_url($thisurl);
$PAGE->set_cm($subpage->get_course_module());
$modcontext = context_module::instance($cmid);

require_login($course, true, $subpage->get_course_module());
require_capability('mod/subpage:view', $modcontext);

$event = \mod_subpage\event\course_module_viewed::create(array(
    'objectid' => $subpage->get_course_module()->instance,
    'context' => $modcontext,
));
$event->add_record_snapshot('course', $course);
$event->trigger();

if (!empty($recache) && confirm_sesskey()) {
    $context = context_course::instance($subpage->get_course()->id);
    require_capability('moodle/course:manageactivities', $context);
    rebuild_course_cache($course->id, true);
    redirect($thisurl);
}

if (!empty($copy) and confirm_sesskey()) { // value = course module
    if (!$cm = get_coursemodule_from_id('', $copy, 0, true)) {
        print_error('invalidcoursemodule');
    }
    $context = context_course::instance($cm->course);
    require_capability('moodle/course:manageactivities', $context);

    if (!$section = $DB->get_record('course_sections', array('id' => $cm->section))) {
        print_error('sectionnotexist');
    }

    $USER->activitycopy       = $copy;
    $USER->activitycopycourse = $cm->course;
    $USER->activitycopyname   = $cm->name;
} else if (!empty($delete) and confirm_sesskey()) {
    require_capability('moodle/course:update', $modcontext);
    if (empty($confirm)) {
        if (!$section = $DB->get_record('course_sections', array('id' => $delete))) {
            print_error('sectionnotexist');
        }
        $sectionname = $section->id;
        if (!empty($section->name)) {
            $sectionname = format_string($section->name);
        }
        $PAGE->set_heading($course->fullname);
        $pagelink = 'view.php?id='.$subpage->get_course_module()->id;
        echo $OUTPUT->header();
        echo $OUTPUT->confirm(get_string('sectiondeleteconfirm', 'subpage', $sectionname),
                $pagelink.'&delete='.$delete.'&confirm=1', $pagelink);
        echo $OUTPUT->footer();
        exit;
    } else {
        // Check to see whether section exists.
        if (!$section = $DB->get_record('course_sections', array('id' => $delete))) {
            print_error('sectionnotexist');
        }
        // Check to see whether section has any content or is empty, if not empty get out.
        if (! empty($section->sequence)) {
                print_error('error_deletingsection', 'subpage');
        }
        // Delete section.
        $subpage->delete_section($delete);
    }
}

$PAGE->set_other_editing_capability('moodle/course:manageactivities');

if (!isset($USER->editing)) {
    $USER->editing = 0;
}
if ($PAGE->user_allowed_editing()) {
    if (($edit == 1) and confirm_sesskey()) {
        $USER->editing = 1;
        redirect($PAGE->url);
    } else if (($edit == 0) and confirm_sesskey()) {
        $USER->editing = 0;
        if (!empty($USER->activitycopy) && $USER->activitycopycourse == $course->id) {
            $USER->activitycopy       = false;
            $USER->activitycopycourse = null;
        }
        redirect($PAGE->url);
    }
    if ($addsection && confirm_sesskey()) {
        $subpage->add_section($sectiontitle);
        redirect($PAGE->url);
    }

    if ($hide && confirm_sesskey()) {
        require_capability('moodle/course:sectionvisibility', $modcontext);
        set_section_visible($course->id, $hide, '0');
    }

    if ($show && confirm_sesskey()) {
        require_capability('moodle/course:sectionvisibility', $modcontext);
        set_section_visible($course->id, $show, '1');
    }

    if (!empty($section)) {
        if (!empty($move) && confirm_sesskey()) {
            require_capability('moodle/course:movesections', $modcontext);
            $pageorder = $DB->get_field('subpage_sections', 'pageorder',
                    array('subpageid' => $subpage->get_subpage()->id, 'sectionid' => $section));
            $subpage->move_section($section, $pageorder + $move);
            rebuild_course_cache($course->id, true);
        }
    }
} else {
    $USER->editing = 0;
}

$SESSION->fromdiscussion = $CFG->wwwroot .'/course/view.php?id='. $course->id;


if ($course->id == SITEID) {
    // This course is not a real course.
    redirect($CFG->wwwroot .'/');
}

// AJAX-capable?
$useajax = false;



// This will add a new class to the header so we can style differently.
$CFG->blocksdrag = $useajax;

$completion = new completion_info($course);
if ($completion->is_enabled()) {
    $PAGE->requires->string_for_js('completion-title-manual-y', 'completion');
    $PAGE->requires->string_for_js('completion-title-manual-n', 'completion');
    $PAGE->requires->string_for_js('completion-alt-manual-y', 'completion');
    $PAGE->requires->string_for_js('completion-alt-manual-n', 'completion');

    $PAGE->requires->js_init_call('M.core_completion.init');
}

$completion = new completion_info($course);
$completion->set_module_viewed($subpage->get_course_module());

$PAGE->set_title(strip_tags($subpage->get_course()->shortname . ': ' .
        format_string($subpage->get_name())));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($subpage->get_name()));

if ($completion->is_enabled()) {
    // This value tracks whether there has been a dynamic change to the page.
    // It is used so that if a user does this - (a) set some tickmarks, (b)
    // go to another page, (c) clicks Back button - the page will
    // automatically reload. Otherwise it would start with the wrong tick
    // values.
    echo html_writer::start_tag('form', array('action' => '.', 'method' => 'get'));
    echo html_writer::start_tag('div');
    echo html_writer::empty_tag('input', array('type' => 'hidden',
            'id' => 'completion_dynamic_change', 'name' => 'completion_dynamic_change', 'value' => '0'));
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('form');
}

// Course wrapper start.
echo html_writer::start_tag('div', array('class' => 'course-content'));

$modinfo = get_fast_modinfo($COURSE);

if (! $sections = $subpage->get_sections()) {   // No sections found
    // Double-check to be extra sure.
    $subpage->add_section();
    if (! $sections = $subpage->get_sections() ) {      // Try again.
        print_error('cannotcreateorfindstructs', 'error');
    }
}
$renderer = $PAGE->get_renderer('mod_subpage');
echo $renderer->render_subpage($subpage, $modinfo, $sections, $PAGE->user_is_editing(),
        $move, has_capability('moodle/course:movesections', $modcontext),
        has_capability('moodle/course:sectionvisibility', $modcontext));

// Content wrapper end.

echo html_writer::end_tag('div');
$modnamesused = $modinfo->get_used_module_names();
include_course_ajax($COURSE, $modnamesused);

echo $OUTPUT->footer();
