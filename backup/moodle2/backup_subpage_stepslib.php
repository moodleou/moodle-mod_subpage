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
 * Define all the backup steps that will be used by the backup_subpage_activity_task
 * Define the complete subpage structure for backup, with file and id annotations
 * @package mod
 * @subpackage subpage
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_subpage_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $subpage = new backup_nested_element('subpage', array('id'),
                array('course', 'name', 'intro', 'introformat', 'enablesharing'));

        $subpagesections = new backup_nested_element('subpage_sections');

        $subpagesection = new backup_nested_element('subpage_section', array('id'),
                array('sectionid', 'pageorder', 'stealth'));

        // Build the tree.
        $subpage->add_child($subpagesections);
        $subpagesections->add_child($subpagesection);

        // Define sources.
        $subpage->set_source_table('subpage', array('id' => backup::VAR_ACTIVITYID));

        $subpagesection->set_source_table('subpage_sections',
                array('subpageid' => backup::VAR_PARENTID));

        // Define id annotations
        // none.

        // Define file annotations
        // none.

        // Return the root element (wiki), wrapped into standard activity structure.
        return $this->prepare_activity_structure($subpage);

    }
}
