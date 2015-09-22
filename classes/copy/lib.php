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
 * Class to support subpage copying (using backup/restore).
 *
 * @package    mod_subpage
 * @copyright  2015 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_subpage\copy;

require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/moodle2/backup_plan_builder.class.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

class lib {

    /**
     * @var \mod_subpage\copy\logger Current dot logger
     */
    public static $currentlogger;

    private $backupbasepath = null;

    /*
     * The array of initial backup settings.
     */
    public $backupsettings = array (
        'users' => 0,
        'anonymize' => 0,
        'role_assignments' => 0,
        'activities' => 1,
        'blocks' => 0,
        'filters' => 0,
        'comments' => 0,
        'userscompletion' => 0,
        'logs' => 0,
        'grade_histories' => 0,
        'calendarevents' => 0
    );

    /**
     * Takes the original course object, subpage cm id and
     * target course. It backs up a subpage in a course, modifies the
     * backup data, creates and restores to target course.
     *
     * @param mod_subpage $subpage subpage to copy from
     * @param object $newcourse Target course object
     */
    public function process($subpage, $newcourse) {
        global $CFG, $DB;

        // Start transaction.
        $transaction = $DB->start_delegated_transaction();

        // Raise memory limit for backup and restore.
        raise_memory_limit(MEMORY_EXTRA);

        echo \html_writer::tag('div', get_string('copy_wait', 'subpage'));

        $spsectionid = $subpage->get_course_module()->section;

        // Backup the course.
        $backupstart = time();
        $this->display_progress(get_string('copy_backingupcourse', 'subpage'));
        list($activityids, $sectionids) = $this->get_includes($subpage);
        $backupid = $this->backup($subpage->get_course(), $activityids,
                array_merge($sectionids, array($spsectionid)));
        $this->display_progress(null, $backupstart);

        // Update backup file.
        $updatebackupstart = time();
        $this->display_progress(get_string('copy_modifyingbackup', 'subpage'));
        $this->update_backup($this->backupbasepath, $newcourse->id, $sectionids, $spsectionid,
                $subpage->get_course_module()->id);
        $this->display_progress(null, $updatebackupstart);

        // Restore into a new course.
        $restorestart = time();
        $this->display_progress(get_string('copy_restoringcourse', 'subpage'));
        $this->restore($backupid, $newcourse);
        $this->display_progress(null, $restorestart);

        // Completed OK, so commit transaction.
        $transaction->allow_commit();
    }

    /**
     * Returns arrays of all cm ids and all section ids
     * used in the subpage
     * @param mod_subpage $subpage
     * @return array activities,sections
     */
    public function get_includes($subpage) {
        $cm = $subpage->get_course_module();

        $cmids = array($cm->id);
        $sids = array();

        $modinfo = get_fast_modinfo($subpage->get_course(), -1);
        $sections = $modinfo->get_sections();
        $spsections = $subpage->get_sections();

        foreach ($spsections as $sid => $info) {
            $sids[] = $sid;
            if (!empty($sections[$info->section])) {
                foreach ($sections[$info->section] as $cmid) {
                    $cmids[] = $cmid;
                    $cminfo = $modinfo->get_cm($cmid);
                    if (strtolower($cminfo->get_module_type_name()) == 'subpage') {
                        // Support subpage in subpage.
                        $asubpage = \mod_subpage::get_from_cmid($cmid);
                        list($acmids, $asids) = $this->get_includes($asubpage);
                        $cmids = array_merge($cmids, $acmids);
                        $sids = array_merge($sids, $asids);
                    }
                }
            }
        }

        return array($cmids, $sids);
    }

    /**
     * Takes the original course object as an argument, backs up the
     * course and returns a backupid which can be used for restoring the course.
     *
     * @param object $course Original course object
     * @param array $activityids all cm ids to backup
     * @param array $activityids all section ids to backup
     * @throws coding_exception Moodle coding_exception
     * @retun string backupid Backupid
     */
    private function backup($course, $activityids, $sectionids) {
        global $CFG, $DB, $USER;

        // MODE_IMPORT option is required to prevent it from zipping up the
        // result at the end. Unfortunately this breaks the system in some
        // other ways (see other comments).
        // NOTE: We cannot use MODE_SAMESITE here because that option will
        // zip the result and anyway, MODE_IMPORT already turns off the files.
        $bc = new \backup_controller(
                \backup::TYPE_1COURSE, $course->id, \backup::FORMAT_MOODLE,
                \backup::INTERACTIVE_NO, \backup::MODE_IMPORT, $USER->id);

        // Setup custom logger that prints dots.
        $logger = new logger(\backup::LOG_INFO, false, true);
        self::$currentlogger = $logger;
        $logger->potential_dot();
        $bc->get_logger()->set_next($logger);
        $bc->set_progress(new progress());

        foreach ($bc->get_plan()->get_tasks() as $taskindex => $task) {
            $settings = $task->get_settings();
            foreach ($settings as $settingindex => $setting) {
                $setting->set_status(\backup_setting::NOT_LOCKED);

                // Modify the values of the intial backup settings.
                if ($taskindex == 0) {
                    if (isset($this->backupsettings[$setting->get_name()])) {
                        $setting->set_value($this->backupsettings[$setting->get_name()]);
                    }
                } else {
                    list($name, $id, $type) = $this->parse_backup_course_module($setting->get_name());

                    // Include user data on glossary if the role 'contributingstudent'
                    // does not have the capability mod/glossary:write on that glossary.
                    // This is so that we include course-team glossary data but not
                    // glossaries that are written by students.
                    if (($name === 'glossary') && ($type === 'userinfo')) {
                        // Find the contributing student role id. If there
                        // isn't one, use the student role id, for dev servers etc.
                        $roles = $DB->get_records_select('role',
                                "shortname IN ('contributingstudent', 'student')",
                                null, 'shortname', 'id');
                        if (!$roles) {
                            continue;
                        }
                        $roleid = reset($roles)->id;

                        // Get the list of roles which have the capability in the context
                        // of the glossary.
                        $context = \context_module::instance($id);
                        list ($allowed, $forbidden) = get_roles_with_cap_in_context($context, 'mod/glossary:write');

                        // If student has this capability then the user data is false.
                        if (!empty($allowed[$roleid]) && empty($forbidden[$roleid])) {
                            $setting->set_value(false);
                        } else {
                            $setting->set_value(true);
                        }
                    }

                    // Ignone any sections not in subpage.
                    if ($name === 'section' && $type === 'included' && !in_array($id, $sectionids)) {
                        $setting->set_value(false);
                    }

                    // Ignone any activities not in subpage.
                    if ($name !== 'section' && $type === 'included' && !in_array($id, $activityids)) {
                        $setting->set_value(false);
                    }
                }
            }
        }
        $logger->potential_dot();
        $bc->save_controller();

        $backupid = $bc->get_backupid();
        $this->backupbasepath = $bc->get_plan()->get_basepath();
        $bc->execute_plan();
        $bc->destroy();

        return $backupid;
    }

    /**
     * It parses the input string and return an array
     * @param string $settingname
     * @return array:
     */
    private function parse_backup_course_module($settingname) {
        $parseinfo = explode('_', $settingname);
        return $parseinfo;
    }

    /**
     * Modifies XML files from a backup before doing the restore.
     * @param string $path Path to backup root
     * @param int $courseid Target course id
     * @param array $sectionids ids for each section in subpage (given new number)
     * @param int $spsectionid Section id for this subpage (set to 0)
     * @param int $spid cm id for this subpage (used to hide it)
     */
    private function update_backup($path, $courseid, $sectionids, $spsectionid, $spid = null) {
        // List all files in backup so we can search through it later.
        $allfiles = self::list_files_recursive($path);

        // Create array of original id and new number.
        $sections = array($spsectionid => 0);
        $minnumber = null;
        foreach ($sectionids as $sectionid) {
            $minnumber = \mod_subpage::add_course_section($courseid, $minnumber);
            $sections[$sectionid] = $minnumber;
        }

        // Update section number (title element???) with empty value on target course.
        foreach (self::get_matching_files($path, $allfiles, '(moodle_backup.xml)') as $file) {
            $dom = new \DOMDocument();
            $dom->load($file);
            $xpath = new \DOMXpath($dom);
            foreach ($xpath->query('/contents/sections/section/sectionid') as $node) {
                if (in_array($node->nodeValue, $sectionids)) {
                    $titlenode = $node->parentNode->getElemetsByTagName('title')[0];
                    $titlenode->nodeValue = $sections[$node->nodeValue];
                }
            }
            $dom->save($file);
        }

        // Update section number in each section xml.
        foreach ($sections as $origid => $newnum) {
            foreach (self::get_matching_files($path, $allfiles, "(.*/section_$origid/section\.xml)") as $file) {
                $dom = new \DOMDocument();
                $dom->load($file);
                $xpath = new \DOMXpath($dom);
                foreach ($xpath->query('/section/number') as $node) {
                    $node->nodeValue = $newnum;
                }
                $dom->save($file);
            }
        }

        // Hide subpage if id sent.
        if ($spid) {
            foreach (self::get_matching_files($path, $allfiles, "(.*/subpage_$spid/module\.xml)") as $file) {
                $dom = new \DOMDocument();
                $dom->load($file);
                $xpath = new \DOMXpath($dom);
                foreach ($xpath->query('/module/visible') as $node) {
                    $node->nodeValue = 0;
                }
                $dom->save($file);
            }
        }

        // Ensure we don't copy groups and groupings.
        file_put_contents($path . '/groups.xml', '<groups></groups>');

        // If there are no quizzes included then don't copy the question bank.
        if (!self::get_matching_files($path, $allfiles, "activities/quiz_[0-9]+/module\.xml")) {
            file_put_contents($path . '/questions.xml', '<question_categories></question_categories>');
        }
    }

    /**
     * Takes backupid and original course object as arguments and returns a new courseid.
     *
     * @param string $backupid The backupid
     * @param object $newcourse Target course object
     * @throws coding_exception Moodle coding exception
     */
    private function restore($backupid, $newcourse) {
        global $CFG, $DB, $USER;

        // Call restore.
        $rc = new \restore_controller($backupid, $newcourse->id,
                \backup::INTERACTIVE_NO, \backup::MODE_GENERAL, $USER->id, \backup::TARGET_EXISTING_ADDING);

        // Setup custom logger that prints dots.
        $logger = new logger(\backup::LOG_INFO, false, true);
        self::$currentlogger = $logger;
        $logger->potential_dot();
        $rc->get_logger()->set_next($logger);
        $rc->set_progress(new progress());

        foreach ($rc->get_plan()->get_tasks() as $taskindex => $task) {
            $settings = $task->get_settings();
            foreach ($settings as $settingindex => $setting) {
                // Set userinfo true for activity, since we controlled it
                // more accurately (i.e. true only for glossary) in backup.
                if (preg_match('/^glossary_([0-9])*_userinfo$$/', $setting->get_name())) {
                    $setting->set_value(true);
                }
                if ($taskindex == 0 && isset($this->backupsettings[$setting->get_name()])) {
                    $setting->set_value($this->backupsettings[$setting->get_name()]);
                }
            }
        }

        if (!$rc->execute_precheck()) {
            if (empty($CFG->keeptempdirectoriesonbackup)) {
                fulldelete($this->backupbasepath);
            }
            $results = print_r($rc->get_precheck_results(), true);
            print \html_writer::tag('pre', s($results));
            throw new \coding_exception('Restore precheck error.');
        }
        $logger->potential_dot();

        $rc->execute_plan();
        $rc->destroy();

        // Delete backup file.
        if (empty($CFG->keeptempdirectoriesonbackup)) {
            fulldelete($this->backupbasepath);
        }
    }

    /**
     * Lists all files (not folders) within given path.
     * @param string $path Root path
     * @return array All files (given as full path) within path
     */
    private static function list_files_recursive($path) {
        $result = array();
        if ($dh = opendir($path)) {
            while (($file = readdir($dh)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $actual = $path . '/' . $file;
                if (is_dir($actual)) {
                    $result = array_merge($result, self::list_files_recursive($actual));
                } else {
                    $result[] = $actual;
                }
            }
        }
        closedir($dh);
        return $result;
    }

    /**
     * Given list of all files in a path, returns ones which match a regular
     * expression. Regular expression will automatically be enclosed in ^...$.
     *
     * @param string $path Root path
     * @param array $allfiles All files (array of full paths)
     * @param string $pattern Pattern to match
     */
    private static function get_matching_files($path, $allfiles, $pattern) {
        $result = array();
        foreach ($allfiles as $filepath) {
            $shortpath = preg_replace('~^' . preg_quote($path . '/') . '~', '', $filepath);
            if (preg_match("~^$pattern$~", $shortpath)) {
                $result[] = $filepath;
            }
        }
        return $result;
    }

    /**
     * Displays progress message that given as the first argument and
     * time taken in seconds as given in the second argumnet.
     * @param string $string Progress message
     * @param int $starttime Time taken while preprocessing certain part of copy
     */
    private function display_progress($string = null, $starttime = 0) {
        if ($string) {
            echo \html_writer::tag('li', $string);
        }
        if ($starttime) {
            $timetaken = time() - $starttime;

            // If the difference is 0 use 1, otherwise the function format_time() return the string 'now'.
            if ($timetaken === 0) {
                $timetaken = 1;
            }
            echo \html_writer::tag('div', get_string('copy_timetaken', 'subpage',  format_time($timetaken)));
        }
        flush();
    }
}
