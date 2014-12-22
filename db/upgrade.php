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
 * Database upgrades.
 *
 * @package mod
 * @subpackage subpage
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_subpage_upgrade($oldversion=0) {

    global $CFG, $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2011041900) {
        // Define field enablesharing to be added to subpage.
        $table = new xmldb_table('subpage');
        $field = new xmldb_field('enablesharing', XMLDB_TYPE_INTEGER, '1', null,
                XMLDB_NOTNULL, null, '0', 'introformat');

        // Conditionally launch add field enablesharing.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field sharedcontenthash to be added to subpage.
        $field = new xmldb_field('sharedcontenthash', XMLDB_TYPE_CHAR, '40', null,
                null, null, null, 'enablesharing');

        // Conditionally launch add field sharedcontenthash.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2011041900, 'subpage');
    }

    if ($oldversion < 2011061300) {
        // Define field stealth to be added to subpage_sections.
        $table = new xmldb_table('subpage_sections');
        $field = new xmldb_field('stealth', XMLDB_TYPE_INTEGER, '1', null,
                null, null, null, 'sectionid');

        // Conditionally launch add field stealth.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Set the field to its new default value, then reset the field's notnull.
        $DB->set_field('subpage_sections', 'stealth', 0);
        $field = new xmldb_field('stealth', XMLDB_TYPE_INTEGER, '1', null,
                XMLDB_NOTNULL, null, null, 'sectionid');
        $dbman->change_field_notnull($table, $field);

        upgrade_mod_savepoint(true, 2011061300, 'subpage');
    }

    if ($oldversion < 2011100500) {
        $transaction = $DB->start_delegated_transaction();
        // Delete duplicate sections. Where there are duplicate sections (more
        // than one entry in subpage_sections for the same sectionid) this
        // query is supposed to delete all those sections except the one with
        // the lowest id (on the basis that this is the 'original').

        // Query to select ids.
        $selectids = "
SELECT
    ss.id
FROM
    {subpage_sections} ss
WHERE
    (ss.sectionid IN (
        SELECT
            cs.id
        FROM
            {course_sections} cs
            JOIN {subpage_sections} ss ON cs.id = ss.sectionid
        GROUP BY
            cs.id
        HAVING COUNT(1) > 1
    )
    AND (
        SELECT
            COUNT(1)
        FROM
            {subpage_sections} ss2
        WHERE
            ss2.id < ss.id
            AND ss2.sectionid=ss.sectionid
    ) > 0)
    OR ss.id IN (
        SELECT
            ss3.id
        FROM
            {subpage_sections} ss3
            LEFT JOIN {course_sections} cs ON cs.id=ss.sectionid
        WHERE
            cs.id IS NULL
    )";

        // List all subpage ids, courses corresponding to those ids.
        $subpageids = $DB->get_records_sql("
SELECT
    DISTINCT ss.subpageid, s.course
FROM
    {subpage_sections} ss
    INNER JOIN {subpage} s ON s.id = ss.subpageid
WHERE
    ss.id IN($selectids)");

        // Delete matching those ids.
        $DB->execute("DELETE FROM {subpage_sections} WHERE id IN ($selectids)");

        // Re-number the sections in affected subpages after delete.
        $lastindex = -1;
        foreach ($subpageids as $subpageidrec) {
            $subpageid = $subpageidrec->subpageid;
            // Get sections for this subpage in reverse order.
            $sections = $DB->get_records('subpage_sections', array('subpageid' => $subpageid),
                    'pageorder DESC');
            foreach ($sections as $section) {
                // Is there a gap?
                $thisindex = $section->pageorder;
                if ($lastindex != -1 && $thisindex < $lastindex - 1) {
                    // There's a gap. Reduce all the numbers above this one.
                    $DB->execute("UPDATE {subpage_sections} " .
                            "SET pageorder = pageorder - ? WHERE subpageid = ? AND pageorder > ?",
                            array($lastindex - 1 - $thisindex, $subpageid, $thisindex));
                }
                $lastindex = $thisindex;
            }
            // Is there a gap at the start?
            if ($lastindex > 1) {
                // There's a gap. Reduce all the numbers above this one.
                $DB->execute("UPDATE {subpage_sections} " .
                        "SET pageorder = pageorder - ? WHERE subpageid = ?",
                        array($lastindex - 1, $subpageid));
            }

            // Clear course cache just in case.
            rebuild_course_cache($subpageidrec->course, true);
        }

        // Define key sectionid (foreign-unique) to be added to subpage_sections.
        $table = new xmldb_table('subpage_sections');
        $key = new xmldb_key('sectionid', XMLDB_KEY_FOREIGN_UNIQUE, array('sectionid'),
                'course_sections', array('id'));

        // Launch add key sectionid.
        $dbman->add_key($table, $key);

        // Define key subpageid (foreign) to be added to subpage_sections.
        $table = new xmldb_table('subpage_sections');
        $key = new xmldb_key('subpageid', XMLDB_KEY_FOREIGN, array('subpageid'),
                'subpage', array('id'));

        // Launch add key subpageid.
        $dbman->add_key($table, $key);

        // Subpage savepoint reached.
        upgrade_mod_savepoint(true, 2011100500, 'subpage');
        $transaction->allow_commit();
    }

    if ($oldversion < 2012021301) {
        // Delete the cached modinfo data for all the courses.
        rebuild_course_cache(0, true);
        upgrade_mod_savepoint(true, 2012021301, 'subpage');
    }

    return true;
}
