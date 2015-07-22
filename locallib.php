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
 * Library of functions used by the subpage module.
 *
 * This contains functions that are called from within the quiz module only
 * Functions that are also called by core Moodle are in {@link lib.php}
 * This script also loads the code in {@link questionlib.php} which holds
 * the module-indpendent code for handling questions and which in turn
 * initialises all the questiontype classes.
 *
 * @package mod_subpage
 * @copyright 2015 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

// Library functions that are also used by core Moodle or other modules.
require_once($CFG->dirroot . '/mod/subpage/lib.php');


class mod_subpage  {
    protected $subpage; // Content of the subpage table row.
    protected $cm;      // Content of the relevant course_modules table.
    protected $course;  // Content of the relevant course table row.

    /**
     * Gets start of range of section numbers used by subpages. Designed to be
     * above any likely week or topic number, but not too high that it causes
     * heavy database bulk in terms of unused sections.
     *
     * Default 110 is slightly more than two years (104).
     *
     * It is possible to override this (per-course) in the config table.
     *
     * This function may make a database call so should not be used if
     * performance is important.
     *
     * @param int $courseid Course id
     * @return int Start of range
     */
    public static function get_min_section_number($courseid) {
        // Check config option, ignore if empty / missing / invalid.
        $config = get_config('mod_subpage', 'courseminsection');
        if (preg_match('~^((?:[0-9]+|\*)=[0-9]+)(,\s*(?:[0-9]+|\*)=[0-9]+)*$~', $config)) {
            foreach (explode(',', $config) as $courseconfig) {
                list ($thiscourseid, $setting) = explode('=', $courseconfig);
                if ($courseid == $thiscourseid || $thiscourseid == '*') {
                    return $setting;
                }
            }
        } else if ($config !== '') {
            throw new moodle_exception('invalidcourseminsections', 'subpage');
        }

        // Default, over 2 years.
        return 110;
    }

    /**
     * Constructor
     *
     * @param stdClass $subpage Subpage table row
     * @param cm_info $cm Course module info object
     * @param stdClass $course Course object
     */
    public function __construct($subpage, cm_info $cm, $course) {
        $this->subpage = $subpage;
        $this->cm = $cm;
        $this->course = $course;
    }

    /**
     * Obtains subpage modinfo, subpage and course records and constructs a subpage object.
     *
     * @param int $cmid the course module id
     * @return mod_subpage
     */
    public static function get_from_cmid($cmid) {
        global $DB;

        $course = $DB->get_record_sql('
                SELECT c.*
                  FROM {course_modules} cm
                  JOIN {course} c ON c.id = cm.course
                 WHERE cm.id = ?', array($cmid), MUST_EXIST);
        $cm = get_fast_modinfo($course)->get_cm($cmid);
        $subpage = $DB->get_record('subpage', array('id' => $cm->instance), '*', MUST_EXIST);
        return new mod_subpage($subpage, $cm, $course);
    }

    /**
     * Returns subpage modinfo.
     *
     * @return cm_info Course module info object
     */
    public function get_course_module() {
        return $this->cm;
    }

    /**
     * returns $course
     *
     * @return stdClass full course record
     */
    public function get_course() {
        return $this->course;
    }

    /**
     * returns $subpage
     *
     * @return stdClass full subpage record
     */
    public function get_subpage() {
        return $this->subpage;
    }

    /**
     * Returns true if there is an intro.
     *
     * @return boolean
     */
    public function has_intro() {
        return !empty($this->subpage->intro);
    }

    /**
     * returns text of name
     *
     * @return string
     */
    public function get_name() {
        return $this->subpage->name;
    }

    /**
     * returns text of intro
     *
     * @return string
     */
    public function get_intro() {
        return $this->subpage->intro;
    }

    /**
     * returns text format of intro
     *
     * @return int
     */
    public function get_intro_format() {
        return $this->subpage->introformat;
    }

    /**
     * Returns an array of all the section objects (containing data from course_sections table)
     * that are included in this subpage, in the order in which they are displayed.
     *
     * @return array
     */
    public function get_sections() {
        global $DB;
        $sql = "SELECT cs.*, ss.pageorder, ss.stealth
                FROM {course_sections} cs, {subpage_sections} ss
                WHERE cs.id = ss.sectionid AND ss.subpageid = ? ORDER BY ss.pageorder";
        return $DB->get_records_sql($sql, array($this->subpage->id));

    }

    /**
     * Returns the highest pageorder of this subpage.
     *
     * @return array
     */
    public function get_last_section_pageorder() {
        global $DB;
        $sql = "SELECT MAX(pageorder) FROM {subpage_sections} ss WHERE
                subpageid = ?";
        return $DB->get_field_sql($sql, array($this->subpage->id));

    }

    /**
     * Adds a new section object to be used by this subpage
     *
     * @return array Array with keys 'subpagesectionid' and 'sectionid'
     */
    public function add_section($name= '', $summary = '') {
        global $DB, $CFG;

        $transaction = $DB->start_delegated_transaction();

        $sectionnum = self::add_course_section($this->get_course()->id);
        $section = $DB->get_record('course_sections', array(
                'course' => $this->course->id, 'section' => $sectionnum), 'id', MUST_EXIST);
        // Now update summary/name if set above.
        if (!empty($name) or !empty($summary)) {
            $section->name = format_string($name);
            $section->summary = format_text($summary);
            $DB->update_record('course_sections', $section);
        }

        $sql = "SELECT MAX(pageorder) FROM {subpage_sections} WHERE subpageid = ?";
        // Get highest pageorder and add 1.
        $pageorder = $DB->get_field_sql($sql, array($this->subpage->id)) + 1;

        $subpagesection = new stdClass();
        $subpagesection->subpageid = $this->subpage->id;
        $subpagesection->sectionid = $section->id;
        $subpagesection->pageorder = $pageorder;
        $subpagesection->stealth = 0;

        $ss = $DB->insert_record('subpage_sections', $subpagesection);

        $transaction->allow_commit();

        return array('subpagesectionid' => $ss, 'sectionid' => $section->id);
    }

    /**
     * Add a new section for a subpage to a course
     * @param int $courseid Course id to add sections to
     * @param int $minsection Lowest section number (default, null, uses get_min_section_number())
     * @return int new section number
     */
    public static function add_course_section($courseid, $minsection = null) {
        global $DB, $CFG;
        require_once($CFG->dirroot .'/course/lib.php'); // For course_create_sections_if_missing().

        // Extra condition if the oucontent module (which has similar but simpler,
        // behaviour) is installed, so they don't tread on each others' toes.
        $oucontentjoin = '';
        $oucontentwhere = '';
        if (file_exists($CFG->dirroot . '/mod/oucontent')) {
            $oucontentjoin = "LEFT JOIN {oucontent} o ON o.course = cs.course AND o.coursesectionid = cs2.id";
            $oucontentwhere = "AND o.id IS NULL";
        }

        // Pick a section number. This query finds the first section,
        // on the course that is at least the minimum number, and does not have,
        // a used section in the following number, and returns that following,
        // section number. (This means it can fill up gaps if sections are deleted.)
        $sql = "
            SELECT cs.section+1 AS num
              FROM {course_sections} cs
         LEFT JOIN {course_sections} cs2 ON cs2.course = cs.course AND cs2.section = cs.section+1
         LEFT JOIN {subpage_sections} ss2 ON ss2.sectionid = cs2.id
                   $oucontentjoin
             WHERE cs.course = ?
               AND cs.section >= ?
               AND ss2.id IS NULL
                   $oucontentwhere
          ORDER BY cs.section";
        if (is_null($minsection)) {
            $minsection = self::get_min_section_number($courseid);
        }
        $result = $DB->get_records_sql($sql, array($courseid, $minsection), 0, 1);
        if (count($result) == 0) {
            // If no existing sections, use the min number.
            $sectionnum = $minsection;
        } else {
            $sectionnum = reset($result)->num;
        }

        // Create a section entry with this section number then get it.
        course_create_sections_if_missing($courseid, $sectionnum);
        return $sectionnum;
    }

    /**
     * Moves a section object within the subpage so that it has the new $pageorder value given.
     * @param int $sectionid the sectionid to move.
     * @param int $pageorder the place to move the section.
     */
    public function move_section($sectionid, $pageorder) {
        global $DB;

        $updatesection = $DB->get_record('subpage_sections',
                array('sectionid' => $sectionid, 'subpageid' => $this->subpage->id));
        $updatesection->pageorder = $pageorder;
        $DB->update_record('subpage_sections', $updatesection);

        $sections = $DB->get_records('subpage_sections',
                array('subpageid' => $this->subpage->id), 'pageorder');
        $newpageorder = 1;
        foreach ($sections as $section) {
            if ($section->sectionid == $sectionid) {
                continue;
            }
            if ($newpageorder == $pageorder) {
                $newpageorder++;
            }

            $section->pageorder = $newpageorder;
            $DB->update_record('subpage_sections', $section);
            $newpageorder++;
        }
    }

    /**
     * Deletes a section
     * @param int $sectionid the sectionid to delete
     */
    public function delete_section($sectionid) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        $coursemodules = $DB->get_records('course_modules',
                array('course' => $this->course->id, 'section' => $sectionid));
        foreach ($coursemodules as $cm) {
            $this->delete_module($cm);
        }
        $DB->delete_records('subpage_sections',
                array('sectionid' => $sectionid, 'subpageid' => $this->subpage->id));

        // Now delete from course_sections.
        $DB->delete_records('course_sections',
                array('id' => $sectionid, 'course' => $this->get_course()->id));
        // Fix pageorder.
        $subpagesections = $DB->get_records('subpage_sections',
                array('subpageid' => $this->subpage->id), 'pageorder');
        $pageorder = 1;
        foreach ($subpagesections as $subpagesection) {
            $subpagesection->pageorder = $pageorder;
            $DB->update_record('subpage_sections', $subpagesection);
            $pageorder++;
        }
        // Clear course cache.
        rebuild_course_cache($this->get_course()->id, true);
        $transaction->allow_commit();
    }

    /**
     * function used to delete a module - copied from course/mod.php, it would
     * be nice for this to be a core function.
     * @param stdclass $cm full course modules record
     */
    public function delete_module($cm) {
        global $CFG, $OUTPUT, $USER, $DB;

        $cm->modname = $DB->get_field("modules", "name", array("id" => $cm->module));

        $modlib = "$CFG->dirroot/mod/$cm->modname/lib.php";

        if (file_exists($modlib)) {
            require_once($modlib);
        } else {
            print_error('modulemissingcode', '', '', $modlib);
        }

        try {
            course_delete_module($cm->id);
        } catch (moodle_exception $e) {
            echo $OUTPUT->notification("Could not delete the $cm->modname (coursemodule)");
        }

        rebuild_course_cache($cm->course);
    }

    /**
     * Toggles the subpage section's stealth
     * @param integer $sectionid the section to set
     * @param boolean $stealth value to set
     */
    public function set_section_stealth($sectionid, $stealth) {
        global $DB;
        $DB->set_field('subpage_sections', 'stealth', $stealth ? 1 : 0,
                array('sectionid' => $sectionid));
    }

    /**
     * Return an array of all course subpage objects
     * @param Object $course
     * @return Array mixed
     */
    public static function get_course_subpages($course) {
        global $DB;

        $sql = "SELECT * FROM {course_modules} WHERE course = ? AND module = " .
                "(SELECT DISTINCT(id) FROM {modules} WHERE name = 'subpage')";
        $results = $DB->get_records_sql($sql, array($course->id));

        $subpages = array();
        foreach ($results as $result) {
            $subpage = self::get_from_cmid($result->id);
            $subpages[$result->id] = $subpage;
        }

        return $subpages;
    }

    /**
     * Return an array of modules that can be moved in this situation
     *
     * The Array is keyed first with sections (subpage or main course)
     * and then the modules within each section by cmid.
     *
     * @param mod_subpage $subpage Current subpage
     * @param array $allsubpages Array of other subpage objects
     * @param array $coursesections Array of course sections
     * @param course_modinfo $modinfo Modinfo object
     * @param string $move Whether to move 'to' or 'from' the current subpage
     * @return array An array of items organised by section
     */
    public static function moveable_modules(mod_subpage $subpage, array $allsubpages,
            $coursesections, course_modinfo $modinfo, $move) {

        $modinfo = get_fast_modinfo($subpage->get_course()->id);
        $allmods = $modinfo->get_cms();
        $modnames = get_module_types_names();
        $modnamesplural = get_module_types_names(true);
        $mods = array();

        if ($move === 'to') {
            $parentcmids = array();

            // Get the subpage cm that owns each section.
            $subpagesectioncm = array();
            foreach ($modinfo->get_instances_of('subpage') as $subpageid => $cm) {
                // Get sectionsids array stored in the customdata.
                $cmdata = $cm->customdata;
                if ($cmdata) {
                    foreach ($cmdata->sectionids as $sectionid) {
                        $subpagesectioncm[$sectionid] = $cm;
                    }
                }
            }

            // Loop through ancestor subpages.
            $cm = $modinfo->get_cm($subpage->get_course_module()->id);
            while (true) {
                if (array_key_exists($cm->section, $subpagesectioncm)) {
                    $cm = $subpagesectioncm[$cm->section];
                    // In case of a subpage within itself, prevent endless loop.
                    if (array_key_exists($cm->id, $parentcmids)) {
                        break;
                    }
                    $parentcmids[$cm->id] = true;
                } else {
                    break;
                }
            }
        }

        $subsections = array();
        if (!empty($allsubpages) && $move === 'to') {
            foreach ($allsubpages as $sub) {
                $subsections += $sub->get_sections();
            }
            $sections = $coursesections;
        } else {
            $subsections = $subpage->get_sections();
            $sections = $subsections;
        }

        if ($sections) {
            foreach ($sections as $section) {
                if (!empty($section->sequence)) {
                    if ($move === 'to' && array_key_exists($section->id, $subsections)) {
                        continue;
                    }

                    $sectionalt = (isset($section->pageorder)) ? $section->pageorder : $section->section;
                    if ($move === 'to') {
                        // Include the required course/format library.
                        global $CFG;
                        require_once("$CFG->dirroot/course/format/" .
                                $subpage->get_course()->format . "/lib.php");
                        $callbackfunction = 'callback_' .
                        $subpage->get_course()->format . '_get_section_name';

                        if (function_exists($callbackfunction)) {
                            $name = $callbackfunction($subpage->get_course(), $section);
                        } else {
                            $name = $section->name ? $section->name
                                    : get_string('section') . ' ' . $sectionalt;
                        }

                    } else {
                        $name = $section->name ? $section->name
                            : get_string('section') . ' ' . $sectionalt;
                    }

                    $sectionmods = explode(',', $section->sequence);
                    foreach ($sectionmods as $modnumber) {
                        if (empty($allmods[$modnumber]) ||
                                $modnumber === $subpage->get_course_module()->id) {
                            continue;
                        }

                        if ($move === 'to') {
                            // Prevent moving a parent subpage to its child.
                            if (!empty($parentcmids[$modnumber])) {
                                continue;
                            }
                        }

                        $instancename = format_string($modinfo->cms[$modnumber]->name,
                                true, $subpage->get_course()->id);

                        $icon = $modinfo->get_cm($modnumber)->get_icon_url();
                        $mod = $allmods[$modnumber];
                        $mods[$section->section]['section'] = $name;
                        $mods[$section->section]['pageorder'] = $sectionalt;
                        $mods[$section->section]['mods'][$modnumber] =
                                "<span><img src='$icon' /> " . $instancename . "</span>";
                    }
                }
            }
        }

        return $mods;
    }

    /**
     * TODO Should be documented.
     */
    public static function destination_options($subpage, $allsubpages,
            $coursesections, $modinfo, $move) {

        $othersectionstr = get_string('anothersection', 'mod_subpage');
        $newstr = get_string('newsection', 'mod_subpage');
        $sectionstr = get_string('section', 'mod_subpage');
        $mainpagestr = get_string('coursemainpage', 'mod_subpage');

        $options = array();

        // Subpage we're on are the default options.
        if ($sections = $subpage->get_sections()) {
            $options = ($move === 'to')
                    ? $options[] = array()
                    : $options[$othersectionstr] = array();
            foreach ($sections as $section) {
                $name = $section->name
                        ? $section->name
                        : $sectionstr . ' ' . $section->pageorder;
                $options[$othersectionstr][
                        $subpage->get_course_module()->id . ',' . $section->id] = $name;
            }
            $options[$othersectionstr][$subpage->get_course_module()->id . ',new'] = $newstr;
        }

        // Only move from has other options.
        if ($move === 'from') {
            // Other subpage sections.
            if (!empty($allsubpages)) {
                foreach ($allsubpages as $sub) {
                    $subpagestr = get_string('modulename', 'mod_subpage');
                    $subpagestr .= ': '.$sub->get_subpage()->name;
                    // Ignore the current subpage.
                    if ($sub->get_course_module()->id !== $subpage->get_course_module()->id) {
                        $options[$subpagestr] = array();
                        if ($sections = $sub->get_sections()) {
                            foreach ($sections as $section) {
                                $name = $section->name ? $section->name
                                : $sectionstr . ' ' . $section->pageorder;
                                $options[$subpagestr][
                                $sub->get_course_module()->id . ',' .$section->id] = $name;
                            }
                            $options[$subpagestr][$sub->get_course_module()->id.',new'] = $newstr;
                        }
                    }
                }
            }

            // Course sections.
            if (!empty($coursesections)) {

                // Include the required course/format library.
                global $CFG;
                require_once($CFG->dirroot . '/course/format/' .
                        $subpage->get_course()->format . '/lib.php');
                $callbackfunction = 'callback_' . $subpage->get_course()->format .
                        '_get_section_name';

                // Get numsections.
                $courseformatoptions = course_get_format($subpage->get_course())->get_format_options();
                $numsections = $courseformatoptions['numsections'];

                // These need to be formatted based on $course->format.
                $minsection = self::get_min_section_number($subpage->get_course()->id);
                foreach ($coursesections as $coursesection) {
                    if ($coursesection->section < $minsection
                            && ($coursesection->section <= $numsections)) {
                        $name = get_section_name($subpage->get_course(), $coursesection);
                        $options[$mainpagestr]['course,'.$coursesection->id] = $name;
                    }
                }
            }
        }

        return $options;
    }

    /**
     * Check if the section contains any modules
     *
     * @param int $sectionid the course section id (the id in the course_section table) to delete
     * @return bool true if the section doesn't contains any modules or false otherwise
     */
    public function is_section_empty($sectionid) {
        global $DB;
        if ($DB->count_records('course_modules',
                array('course' => $this->course->id, 'section' => $sectionid))) {
            return false;
        }
        return true;
    }
}
