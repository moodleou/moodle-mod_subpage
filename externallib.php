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
 * External subpage API
 *
 * @package mod
 * @subpackage subpage
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->libdir/externallib.php");

class mod_subpage_external extends external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function add_subpage_parameters() {
        return new external_function_parameters(
            array(
                'courseshortname'   => new external_value(PARAM_ALPHANUM, 'Course shorname'),
                'section'           => new external_value(PARAM_INT, 'section number'),
                'name'              => new external_value(PARAM_RAW, 'Name of subpage'),
            )
        );
    }

    public function add_subpage($courseshortname, $section, $subpagename) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/mod/subpage/locallib.php');
        $params = self::validate_parameters(self::add_subpage_parameters(),
                array('courseshortname' => $courseshortname, 'section' => $section,
                    'name' => $subpagename));

        $course = $DB->get_record('course',
                array('shortname' => $params['courseshortname']), '*', MUST_EXIST);
        $section = $DB->get_record('course_sections', array('id' => $section), '*', MUST_EXIST);

        self::require_access($course->id);

        // Finally create the subpage.
        // first add course_module record because we need the context.
        $newcm = new stdClass();
        $newcm->course           = $course->id;
        $newcm->module           = $DB->get_field('modules', 'id', array('name' => 'subpage'));
        // Not known yet, will be updated later (this is similar to restore code).
        $newcm->instance         = 0;
        $newcm->section          = $params['section'];
        $newcm->visible          = 1;
        $newcm->groupmode        = 0;
        $newcm->groupingid       = 0;
        $newcm->groupmembersonly = 0;

        if (!$coursemodule = add_course_module($newcm)) {
            throw new invalid_parameter_exception('Error creating course module');
        }
        $subpage = new stdClass();
        $subpage->course = $course->id;
        $subpage->name = format_string($params['name']);
        $subpage->intro = '<p></p>';
        $subpage->introformat = 1;
        $instance = subpage_add_instance($subpage);

        $DB->set_field('course_modules', 'instance', $instance, array('id' => $coursemodule));
        course_add_cm_to_section($subpage->course, $coursemodule, $section->section);
        rebuild_course_cache($course->id);

        return array('id' => $coursemodule);
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function add_subpage_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'subpage course module id'),
            )
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function add_section_parameters() {
        return new external_function_parameters(
            array(
                'cmid'    => new external_value(PARAM_INT, 'Course module id'),
                'name'    => new external_value(PARAM_RAW, 'Name of section'),
                'summary' => new external_value(PARAM_RAW, 'Summary of section'),
            )
        );
    }

    public function add_section($cmid, $name, $summary) {
        global $CFG;
        require_once($CFG->dirroot.'/mod/subpage/locallib.php');
        $params = self::validate_parameters(self::add_section_parameters(),
                array('cmid' => $cmid, 'name' => $name, 'summary' => $summary));

        self::require_access(0, $params['cmid']);

        $subpage = mod_subpage::get_from_cmid($params['cmid']);
        $section = $subpage->add_section($params['name'], $params['summary']);

        return array('id' => $section['sectionid']);

    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function add_section_returns() {
        return new external_single_structure(
            array(
               'id' => new external_value(PARAM_INT, 'section id'),
            )
        );
    }
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function add_link_parameters() {
        return new external_function_parameters(
            array(
                'section'   => new external_value(PARAM_RAW, 'Section id'),
                'name'     => new external_value(PARAM_RAW, 'Title of link'),
                'url'       => new external_value(PARAM_RAW, 'URL of link'),
            )
        );
    }
    public function add_link($section, $name, $url) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/mod/url/lib.php');

        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->libdir.'/resourcelib.php');

        $params = self::validate_parameters(self::add_link_parameters(),
                array('section' => (int)$section, 'name' => $name, 'url' => $url));

        $section = $DB->get_record('course_sections', array('id' => $section), '*', MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $section->course), '*', MUST_EXIST);

        self::require_access($course->id);

        // First add course_module record because we need the context.
        $newcm = new stdClass();
        $newcm->course           = $course->id;
        $newcm->module           = $DB->get_field('modules', 'id', array('name' => 'url'));
        // Not known yet, will be updated later (this is similar to restore code).
        $newcm->instance         = 0;
        $newcm->section          = $params['section'];
        $newcm->visible          = 1;
        $newcm->groupmode        = 0;
        $newcm->groupingid       = 0;
        $newcm->groupmembersonly = 0;

        if (!$coursemodule = add_course_module($newcm)) {
            throw new invalid_parameter_exception('Error creating course module');
        }
        $config = get_config('url');
        $module = new stdClass();
        $module->course = $course->id;
        $module->name = format_string($params['name']);
        $module->intro = '<p></p>';
        $module->introformat = 1;
        $module->externalurl = $params['url'];
        $module->display = $config->display;
        $module->popupwidth = $config->popupwidth;
        $module->popupheight = $config->popupheight;
        $module->printheading = $config->printheading;
        $module->printintro = $config->printintro;
        $module->instance = url_add_instance($module, array());

        $DB->set_field('course_modules', 'instance', $module->instance, array('id' => $coursemodule));

        course_add_cm_to_section($module->course, $coursemodule, $section->section);
        rebuild_course_cache($course->id);

        return array('id' => $coursemodule);
    }

    private static function require_access($courseid, $cmid=null) {
        // By defining this constant, you can use the methods in this library
        // locally.
        if (defined('SUBPAGE_EXTERNAL_SKIP_ACCESS_CHECK')) {
            return;
        }
        if ($courseid) {
            $context = context_course::instance($courseid);
        } else {
            $context = context_module::instance($cmid);
        }
        self::validate_context($context);
        require_capability('moodle/course:manageactivities', $context);
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function add_link_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'link id'),
            )
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function add_file_parameters() {
        return new external_function_parameters(
            array(
                'section' => new external_value(PARAM_INT, 'Section id'),
                'name' => new external_value(PARAM_RAW, 'Title of file'),
                'path' => new external_value(PARAM_RAW, 'URL or Path to file'),
                'display' => new external_value(PARAM_INT, 'Display mode'),
            )
        );
    }


    public function add_file($section, $name, $path, $display=0) {
        global $DB, $CFG, $USER;
        $section = (int)$section;
        $display = (int)$display;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/mod/resource/lib.php');
        require_once($CFG->dirroot.'/mod/resource/locallib.php');
        $params = self::validate_parameters(self::add_file_parameters(),
                array('section' => $section, 'name' => $name, 'path' => $path,
                    'display' => $display));

        $section = $DB->get_record('course_sections', array('id' => $section), '*', MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $section->course), '*', MUST_EXIST);

        self::require_access($course->id);

        // Finally create the file item.
        // First add course_module record because we need the context.
        $newcm = new stdClass();
        $newcm->course           = $course->id;
        $newcm->module           = $DB->get_field('modules', 'id', array('name' => 'resource'));
        // Not known yet, will be updated later (this is similar to restore code).
        $newcm->instance         = 0;
        $newcm->section          = $params['section'];
        $newcm->visible          = 1;
        $newcm->groupmode        = 0;
        $newcm->groupingid       = 0;
        $newcm->groupmembersonly = 0;

        if (!$coursemodule = add_course_module($newcm)) {
            throw new invalid_parameter_exception('Error creating course module');
        }
        $config = get_config('resource');
        $module = new stdClass();
        $module->course = $course->id;
        $module->name = format_string($params['name']);
        $module->intro = '<p></p>';
        $module->introformat = 1;
        $module->coursemodule = $coursemodule;
        if (! $display) {
            $module->display = $config->display;
        } else {
            $module->display = $display;
        }
        $module->popupwidth = $config->popupwidth;
        $module->popupheight = $config->popupheight;
        $module->printintro = $config->printintro;
        // 'Show size' support only from Moodle 2.3 / OU moodle April 2012.
        if (isset($config->showsize)) {
            $module->showsize = $config->showsize;
            $module->showtype = $config->showtype;
        }
        $module->filterfiles = $config->filterfiles;
        $module->section = $section->section;
        // Check $params['path'] and create files based on that and attach to $module->files
        // now check $path and obtain $filename and $filepath.
        $contextuser = context_user::instance($USER->id);
        $fs = get_file_storage();
        $module->files = 0;
        file_prepare_draft_area($module->files, null, null, null, null);

        $fileinfo = array(
            'contextid' => $contextuser->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $module->files,
            'filepath' => '/',
            'filename' => basename($params['path']),
            'timecreated' => time(),
            'timemodified' => time(),
            'mimetype' => mimeinfo('type', $path),
            'userid' => $USER->id
        );

        if (strpos($params['path'], '://') === false) {
            // This is a path.
            if (!file_exists($params['path'])) {
                throw new invalid_parameter_exception(
                        'Error accessing filepath - file may not exist.');
            }
            $fs->create_file_from_pathname($fileinfo, $params['path']);
        } else {
            // This is a URL - download the file first.
            $content = download_file_content($params['path']);
            if ($content === false) {
                throw new invalid_parameter_exception('Error accessing file - url may not exist.');
            }
            $fs->create_file_from_string($fileinfo, $content);
        }

        $module->instance = resource_add_instance($module, array());

        $DB->set_field('course_modules', 'instance', $module->instance, array('id' => $coursemodule));

        course_add_cm_to_section($module->course, $coursemodule, $section->section);
        rebuild_course_cache($course->id, true);

        return array('id' => $coursemodule);
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function add_file_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'file id'),
            )
        );
    }

    public function update_file($cmid, $updatefilepath) {
        global $DB, $CFG;

        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/mod/resource/lib.php');
        require_once($CFG->dirroot.'/mod/resource/locallib.php');

        // Course module should contain section (id).
        $coursemodule = $DB->get_record('course_modules', array('id' => $cmid), '*', MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $coursemodule->course), '*', MUST_EXIST);
        $resource = $DB->get_record('resource', array('id' => $coursemodule->instance),
                '*', MUST_EXIST);

        self::require_access($course->id);

        course_delete_module($cmid);
        $DB->delete_records('report_etexts_usage', array('cmid' => $cmid));

        $this->add_file($coursemodule->section, $resource->name, $updatefilepath,
                $resource->display);
    }


    /**
     * Obtains last subpage section id for a given subpage.
     * @param integer $subpagecmid
     * @return last section id for a given subpage
     */
    public function get_last_subpage_section_id($subpagecmid) {
        global $DB, $CFG;

        $sql = "SELECT sectionid
                FROM {subpage_sections}
                WHERE subpageid = (select instance from {course_modules} where id = ?)
                ORDER BY pageorder DESC";

        $params = array($subpagecmid);
        $records = $DB->get_records_sql($sql, $params, 0, 1);
        if (count($records) == 0) {
            // When there are no sections, add one.
            require_once($CFG->dirroot . '/mod/subpage/locallib.php');
            $subpage = mod_subpage::get_from_cmid($subpagecmid);
            $subpage->add_section();

            // Redo the query.
            $records = $DB->get_records_sql($sql, $params, 0, 1);
            if (count($records) == 0) {
                throw new coding_exception("No section defined in subpage $subpagecmid");
            }
        }
        return reset($records)->sectionid;
    }
}
