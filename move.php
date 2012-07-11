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
 * Handles move requests.
 *
 * @package mod
 * @subpackage subpage
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('locallib.php');
require_once('lib.php');
require_once('move_form.php');
require_once($CFG->dirroot.'/course/lib.php');

// In large courses, this page runs out of memory.
raise_memory_limit(MEMORY_EXTRA);

$cmid        = required_param('id', PARAM_INT);
$move        = optional_param('move', '', PARAM_RAW);

$subpage = mod_subpage::get_from_cmid($cmid);
$cm = $subpage->get_course_module();
$course = $subpage->get_course();
// Defined here to avoid notices on errors etc
$PAGE->set_url('/mod/subpage/move.php', array('id' => $cmid, 'move' => $move));

$coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);

require_login($course);
add_to_log($course->id, 'course', 'move', "move.php?id=$course->id&move=$move", "$course->id");

$PAGE->set_pagelayout('incourse');
$PAGE->set_other_editing_capability('moodle/course:manageactivities');

if ($course->id == SITEID) {
    // This course is not a real course.
    redirect($CFG->wwwroot .'/');
}

$PAGE->set_title(strip_tags($course->fullname.': '.format_string($subpage->get_name())));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(format_string($subpage->get_name()),
        new moodle_url('/mod/subpage/view.php', array('id' => $cmid)));
$PAGE->navbar->add(get_string('moveitems', 'mod_subpage'));

// general information
$modinfo =& get_fast_modinfo($course);
$coursesections = get_all_sections($course->id);
$allsubpages =  mod_subpage::get_course_subpages($course);

// options specifically for moving
$moveableitems = mod_subpage::moveable_modules($subpage, $allsubpages,
        $coursesections, $modinfo, $move);
$options = mod_subpage::destination_options($subpage, $allsubpages,
        $coursesections, $modinfo, $move);

if (empty($moveableitems)) {
    echo $OUTPUT->header();
    // Course wrapper start.
    echo html_writer::start_tag('div', array('class'=>'course-content'));
    echo $OUTPUT->notification(get_string('nomodules', 'mod_subpage'));
    echo $OUTPUT->continue_button("$CFG->wwwroot/mod/subpage/view.php?id=$cmid");
    echo html_writer::end_tag('div');
    echo $OUTPUT->footer();
    exit;
}

$data = array();
$data['id'] = $cmid;
$data['moveable'] = $moveableitems;
$data['options'] = $options;
$data['move'] = $move;
$mform = new mod_subpage_move_form('move.php', $data);

if ($mform->is_cancelled()) {
    redirect("$CFG->wwwroot/mod/subpage/view.php?id=$cmid");
    exit;
}

if ($formdata = $mform->get_data()) {
    $destinationstr = $formdata->destination;
    $info = explode(',', $destinationstr);
    $createnew = ($info[1] === 'new') ? true : false;

    // get the actual items to move; $cmid values
    $cmids = array();
    foreach ($formdata as $key => $data) {
        if (substr($key, 0, 3) === 'mod' && $data == 1) {
            $cmids[] = substr($key, 3);
        }
    }
    if (empty($cmids)) {
        echo $OUTPUT->header();
        // Course wrapper start.
        echo html_writer::start_tag('div', array('class'=>'course-content'));
        echo $OUTPUT->notification(get_string('nomodulesselected', 'mod_subpage'));
        echo $OUTPUT->continue_button("$CFG->wwwroot/mod/subpage/view.php?id=$cmid");
        echo html_writer::end_tag('div');
        echo $OUTPUT->footer();
        exit;
    } else {
        // destination is either null or a subpage
        $dest = ($info[0] !== 'course') ? mod_subpage::get_from_cmid($info[0]) : null;
        if ($createnew && $dest) {
            $newsection = $dest->add_section();
            $id = $newsection['sectionid'];
        } else {
            $id = $info[1];
        }

        // ensure that the destination section does exists
        if (!$section = $DB->get_record('course_sections', array('id' => (int)$id))) {
            print_error('sectionnotcreatedorexisting', 'mod_subpage',
                    "$CFG->wwwroot/mod/subpage/view.php?id=$cmid");
        }

        foreach ($cmids as $id) {
            if (!$cm = get_coursemodule_from_id('', $id)) {
                print_error('modulenotfound', 'mod_subpage',
                        "$CFG->wwwroot/mod/subpage/view.php?id=$cmid");
            }

            // no reason to move if in the same section
            if ($cm->section !== $section->id) {
                moveto_module($cm, $section);
            }
        }
        rebuild_course_cache($course->id, true);
    }

    // return to original subpage view
    if (!$dest) {
        redirect("$CFG->wwwroot/course/view.php?id=" . $subpage->get_course()->id .
                "#section-$section->section");
    } else {
        redirect("$CFG->wwwroot/mod/subpage/view.php?id=" . $dest->get_course_module()->id .
                "#section-$section->section");
    }
    exit;
}

echo $OUTPUT->header();

// Course wrapper start.
echo html_writer::start_tag('div', array('class'=>'course-content'));

// display form
if (!$formdata) {
    $data = new StdClass;
    $data->id = $cmid;
    $data->move = $move ? $move : null;

    $mform->set_data($data);

    $mform->display();
}

echo html_writer::end_tag('div');

echo $OUTPUT->footer();
