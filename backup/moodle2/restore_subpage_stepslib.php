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
 * Structure step to restore one subpage activity
 * @package mod
 * @subpackage subpage
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_subpage_activity_structure_step extends restore_activity_structure_step {

    private $subpageid;

    protected function define_structure() {
        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('subpage', '/activity/subpage');
        $paths[] = new restore_path_element('subpage_sections',
                '/activity/subpage/subpage_sections/subpage_section');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_subpage($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // Insert the subpage record.
        $newitemid = $DB->insert_record('subpage', $data);

        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
        $this->subpageid = $newitemid;
    }

    protected function process_subpage_sections($data) {
        global $DB;

        $data = (object)$data;

        $oldid = $data->id;

        $data->subpageid = $this->get_new_parentid('subpage');
        if (!isset($data->stealth)) {
            $data->stealth = 0;
        }

        // Note that the sectionid still points to the OLD section id. We will
        // fix all of these after restore completes, but to avoid duplicates,
        // I am going to add 10 million to it.
        $data->sectionid += 10000000;

        $newitemid = $DB->insert_record('subpage_sections', $data);
        $this->set_mapping('suppage_sections', $oldid, $newitemid, true);
    }

    protected function after_execute() {
        global $DB;

        // Add subpage related files, no need to match by itemname (just
        // internally handled context).
        $this->add_related_files('mod_subpage', 'intro', null);

        // Check for duplicate idnumbers. This query returns the module's OWN
        // cmid, IF there is another cmid that has the same idnumber.
        $details = $DB->get_record_sql("
SELECT
    DISTINCT cm.id, cm.idnumber, cm.instance
FROM
    {course_modules} cm
    INNER JOIN {modules} m ON m.id=cm.module AND m.name='subpage'
    INNER JOIN {course_modules} othercm ON othercm.idnumber = cm.idnumber
        AND othercm.id <> cm.id
WHERE
    cm.instance = ?
    AND cm.idnumber IS NOT NULL
    AND cm.idnumber <> ''", array($this->subpageid));
        if ($details) {
            // Remove idnumber from restored module.
            $DB->set_field('course_modules', 'idnumber', '', array('id' => $details->id));
            $DB->set_field('subpage', 'enablesharing', 0, array('id' => $details->instance));

            // Put it in backup log (that nobody can see at the moment).
            $this->get_logger()->process('Removing idnumber ' . $details->idnumber .
                    ' from subpage because existing item already has that idnumber',
                    backup::LOG_WARNING);
        }
    }
}
