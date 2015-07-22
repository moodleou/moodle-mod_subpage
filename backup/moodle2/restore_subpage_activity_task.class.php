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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/subpage/backup/moodle2/restore_subpage_stepslib.php');

/**
 * subpage restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 * @package mod
 * @subpackage subpage
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_subpage_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Choice only has one structure step.
        $this->add_step(new restore_subpage_activity_structure_step(
                'subpage_structure', 'subpage.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('subpage', array('intro'), 'subpage');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('SUBPAGEVIEW',
                '/mod/subpage/view.php?id=$1', 'course_module');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * subpage logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('subpage', 'add',
                'view.php?id={course_module}', '{subpage}');
        $rules[] = new restore_log_rule('subpage', 'update',
                'view.php?id={course_module}', '{subpage}');
        $rules[] = new restore_log_rule('subpage', 'view',
                'view.php?id={course_module}', '{subpage}');
        $rules[] = new restore_log_rule('subpage', 'report',
                'report.php?id={course_module}', '{subpage}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    static public function define_restore_log_rules_for_course() {
        $rules = array();
        return $rules;
    }

    /**
     * Called at the end of the entire restore process, once per subpage.
     * We use it to fix up the subpage section IDs.
     */
    public function after_restore() {
        global $DB;

        $restoreid = $this->get_restoreid();
        $subpageid = $this->get_activityid();
        $needfixes = $DB->get_records_sql("
SELECT
    ss.id, ss.sectionid AS oldsectionid, s.name AS name
FROM
    {subpage} s
    JOIN {subpage_sections} ss ON ss.subpageid = s.id
WHERE
    s.id = ?", array($subpageid));
        $transaction = $DB->start_delegated_transaction();
        foreach ($needfixes as $needfix) {
            $oldsectionid = $needfix->oldsectionid - 10000000;
            $mappingrecord = restore_dbops::get_backup_ids_record(
                    $restoreid, 'course_section', $oldsectionid);
            $newsectionid = $mappingrecord ? $mappingrecord->newitemid : false;
            if ($newsectionid) {
                $DB->set_field('subpage_sections', 'sectionid', $newsectionid,
                    array('id' => $needfix->id));
            } else {
                $this->get_logger()->process("Failed to restore section dependency " .
                        "{$needfix->oldsectionid} in subpage '{$needfix->name}'. " .
                        "Backup and restore will not work correctly unless you include " .
                        "relevant course sections. If you are seeing this message on copy " .
                        "then you must not copy this original subpage again.",
                        backup::LOG_ERROR);
            }
        }
        $transaction->allow_commit();
    }
}
