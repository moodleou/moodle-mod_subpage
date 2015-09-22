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
 * Library of functions and constants for module label
 *
 * @package    mod
 * @subpackage subpage
 * @author Dan Marsden <dan@danmarsden.com>
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @global object
 * @param object $label
 * @return bool|int
 */
function subpage_add_instance($subpage) {
    global $DB;

    return $DB->insert_record("subpage", $subpage);
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @global object
 * @param object $label
 * @return bool
 */
function subpage_update_instance($subpage) {
    global $DB;

    $subpage->id = $subpage->instance;

    return $DB->update_record("subpage", $subpage);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @global object
 * @param int $id
 * @return bool
 */
function subpage_delete_instance($id) {
    global $DB, $PAGE, $CFG;

    if (! $subpage = $DB->get_record("subpage", array("id" => $id))) {
        return false;
    }
    require_once($CFG->dirroot . '/mod/subpage/locallib.php');
    $transaction = $DB->start_delegated_transaction();

    // If deleting a subpage activity from course/mod.php page (not delete the whole course).
    if ($PAGE->pagetype == 'course-mod') {
        $subpagecmid = required_param('delete', PARAM_INT);
        // Check if all the sections in this subpage is empty.
        $subpageinstance = mod_subpage::get_from_cmid($subpagecmid);
        $subpagesections = $DB->get_records('subpage_sections',
                array("subpageid" => $subpage->id), '', 'sectionid');
        foreach ($subpagesections as $sections) {
            if (!$subpageinstance->is_section_empty($sections->sectionid)) {
                // Section is not empty.
                $url = new moodle_url('/mod/subpage/view.php', array('id' => $subpagecmid));
                print_error('error_deletingsubpage', 'mod_subpage', $url);
            }
        }
        // All sections are empty, delete the empty sections.
        foreach ($subpagesections as $sections) {
            $subpageinstance->delete_section($sections->sectionid);
        }
    }

    // Delete main table and sections.
    $DB->delete_records("subpage", array("id" => $subpage->id));
    $DB->delete_records("subpage_sections", array("subpageid" => $subpage->id));

    // If there are any shared subpages that reference this, rebuild those
    // courses so that they reflect the deletion.
    if ($DB->get_field('modules', 'id', array('name' => 'sharedsubpage'))) {
        $references = $DB->get_records('sharedsubpage',
                array('subpageid' => $subpage->id), 'id,course');
        foreach ($references as $sharedsubpage) {
            rebuild_course_cache($sharedsubpage->course, true);
        }
    }
    $transaction->allow_commit();
    return true;
}

/**
 * Returns the users with data in one resource
 * (NONE, but must exist on EVERY mod !!)
 *
 * @param int $subpageid
 */
function subpage_get_participants($subpageid) {

    return false;
}

/**
 * @uses FEATURE_IDNUMBER
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return bool|null True if module supports feature, false if not, null if doesn't know
 */
function subpage_supports($feature) {
    switch($feature) {
        case FEATURE_IDNUMBER:                return true;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;

        default: return null;
    }
}

/**
 * Called to obtain information about the coursemodule object. Used only to
 * update sharing options.
 * @param object $cm Raw data from course_modules table
 * @return cached_cm_info Unchanged information about display
 */
function subpage_get_coursemodule_info($cm) {
    global $DB, $CFG;
    $subpage = $DB->get_record('subpage', array('id' => $cm->instance), '*', MUST_EXIST);
    if ($subpage->enablesharing) {
        // Work out current value.
        require_once($CFG->dirroot . '/mod/sharedsubpage/locallib.php');
        $content = serialize(sharedsubpage_gather_data($subpage));
        $hash = sha1($subpage->name . "\n" . $content);

        if ($hash != $subpage->sharedcontenthash) {
            // Update on all shared versions.
            $DB->execute("UPDATE {sharedsubpage} SET content=?, name=? WHERE subpageid=?",
                array($content, sharedsubpage_get_name($subpage->name), $subpage->id));
            // Clear modinfo on all courses that include shared versions.
            $courses = $DB->get_fieldset_sql(
                    "SELECT DISTINCT course FROM {sharedsubpage} WHERE subpageid = ?",
                    array($subpage->id));
            foreach ($courses as $courseid) {
                rebuild_course_cache($courseid, true);
            }
            // Set hash so we don't do that again.
            $DB->set_field('subpage', 'sharedcontenthash', $hash, array('id' => $subpage->id));
        }
    }
    // Add the all the sectionids within this subpage to the customdata.
    $info = new cached_cm_info();
    $sectionids = array();
    $sectionstealth = array();
    $sections = $DB->get_records('subpage_sections',
            array('subpageid' => $subpage->id), 'pageorder');
    foreach ($sections as $section) {
        $sectionids[] = $section->sectionid;
        $sectionstealth[$section->sectionid] = $section->stealth;
    }
    $info->customdata = (object)array('sectionids' => $sectionids,
            'sectionstealth' => $sectionstealth);
    return $info;
}

/**
 * Sets the module uservisible to false if the user has not got the view
 * capability.
 * @param cm_info $cm Module data
 */
function subpage_cm_info_dynamic(cm_info $cm) {
    if (!has_capability('mod/subpage:view', context_module::instance($cm->id))) {
        $cm->set_available(false);
    }
}

/**
 * Used to add options to the Settings menu for the subpage
 * @param unknown_type $settings Don't know what this parameter is
 * @param navigation_node $subpagenode Navigation node object for subpage
 */
function subpage_extend_settings_navigation($settings, navigation_node $subpagenode) {
    global $PAGE;

    if ($PAGE->user_allowed_editing()) {
        $url = new moodle_url('/mod/subpage/view.php', array('id' => $PAGE->cm->id));
        $url->param('sesskey', sesskey());
        if ($PAGE->user_is_editing()) {
            $url->param('edit', 'off');
            $editstring = get_string('turneditingoff');
        } else {
            $url->param('edit', 'on');
            $editstring = get_string('turneditingon');
        }

        $node = navigation_node::create($editstring, $url,
                navigation_node::TYPE_SETTING, null, 'subpageeditingtoggle');
        $subpagenode->add_node($node, 'modedit');
    }

    if (has_all_capabilities(array('moodle/backup:backupsection', 'moodle/backup:backupactivity'),
            $PAGE->context)) {
            $url = new moodle_url('/mod/subpage/copy.php', array('id' => $PAGE->cm->id));
            $node = navigation_node::create(get_string('copy', 'subpage'), $url,
                    navigation_node::TYPE_SETTING, null, 'subpagecopy');
            $subpagenode->add_node($node);
    }
}

/**
 * @return array List of all system capabilitiess used in module
 */
function subpage_get_extra_capabilities() {
    // Note: I made this list by searching for moodle/ within the module. We
    // then added accessallgroups because of grouping restrictions.
    return array('moodle/course:update', 'moodle/course:viewhiddensections',
            'moodle/course:manageactivities', 'moodle/site:accessallgroups');
}
