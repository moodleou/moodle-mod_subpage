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
 * Unit tests for (some of) mod/subpage/locallib.php.
 *
 * @package mod
 * @subpackage subpage
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author David Drummond <david@catalyst.net.nz> Catalyst IT Ltd.
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); /// It must be included from a Moodle page.
}

require_once($CFG->dirroot . '/mod/subpage/locallib.php');

// todo add some modules to the test sections
// todo test general locallib functions

class subpage_locallib_test extends UnitTestCaseUsingDatabase {

    public static $includecoverage = array('mod/subpage/locallib.php');
    public $tables = array('lib' => array(
                                      'course_categories',
                                      'course',
                                      'course_sections',
                                      'files',
                                      'modules',
                                      'context',
                                      'course_modules',
                                      'user',
                                      'groups',
                                      'groups_members',
                                      'capabilities',
                                      'role_assignments',
                                      'role_capabilities',
                                      'role_names',
                                      'filter_active',
                                      'filter_config',
                                      'cache_text',
                                      ),
                                  'mod/label' => array(
                                      'label'),
                                  'mod/subpage' => array(
                                      'subpage',
                                      'subpage_sections')
                            );

     public $usercount = 0;

     /**
      * Create temporary test tables and entries in the database for these tests.
      * These tests have to work on a brand new site.
      */
    public function setUp() {
        global $CFG;

        parent::setup();

        // All operations until end of test method will happen in test DB
        $this->switch_to_test_db();

        foreach ($this->tables as $dir => $tables) {
            $this->create_test_tables($tables, $dir); // Create tables
            foreach ($tables as $table) { // Fill them if load_xxx method is available
                $function = "load_$table";
                if (method_exists($this, $function)) {
                    $this->$function();
                }
            }
        }

    }

    public function load_course_categories() {
        $cat = new stdClass();
        $cat->name = 'misc';
        $cat->depth = 1;
        $cat->path = '/1';
        $this->testdb->insert_record('course_categories', $cat);
    }

    /**
     * Load module entries in modules table
     */
    public function load_modules() {
        $module = new stdClass();
        $module->name = 'subpage';
        $module->id = $this->testdb->insert_record('modules', $module);
        $this->modules[] = $module;
    }

    /*

     Backend functions covered:

        mod_subpage:get_from_cmid()
        mod_subpage:has_intro()
        mod_subpage:get_name()
        mod_subpage:get_intro()
        mod_subpage:get_subpage()
        mod_subpage:add_section()
        mod_subpage:get_sections()
        mod_subpage:get_last_section_pageorder()
        mod_subpage:move_section()
        mod_subpage:delete_section()
        mod_subpage::get_course_subpages()
        mod_subpage:set_section_stealth()

     Functions not covered:
         mod_subpage:delete_module - tested through delete_section()
         mod_subpage::destination_options()
         mod_subpage::moveable_modules()
         format_sections()

    */

    public function test_subpage_mod_subpage_class() {

        // setup a course and module
        $course  = $this->get_new_course();
        $coursesection = $this->get_new_course_section($course->id);
        $subpage = $this->get_new_subpage($course->id);
        $cm      = $this->get_new_course_module($course->id, $subpage->id, $coursesection->id);

        $testname  = $subpage->name;
        $testintro = $subpage->intro;

        // get the subpage object
        $module = mod_subpage::get_from_cmid($cm->id);
        $this->assertIsA($module, "mod_subpage");
        $this->assertTrue($module->has_intro());
        $this->assertEqual($module->get_name(), $testname);
        $this->assertEqual($module->get_intro(), $testintro);

        $subpage = $module->get_subpage();
        $this->assertIsA($subpage, "stdClass");
        $this->assertEqual($subpage->name, $testname);

        // testing adding sections
        $sid1 = 2;
        $sectionname1 = "sectionname1";
        $sectionsummary1 = "sectionsummary1";
        $section1 = $module->add_section($sectionname1, $sectionsummary1);
        $this->assertIsA($section1, "array");

        $sid2 = 3;
        $sectionname2 = "sectionname2";
        $sectionsummary2 = "sectionsummary2";
        $section2 = $module->add_section($sectionname2, $sectionsummary2);
        $this->assertIsA($section2, "array");

        $sid3 = 4;
        $sectionname3 = "sectionname3";
        $sectionsummary3 = "sectionsummary3";
        $section3 = $module->add_section($sectionname3, $sectionsummary3);
        $this->assertIsA($section3, "array");

        // test getting sections
        $sections = $module->get_sections();

        $this->assertEqual($sections[$section1['sectionid']]->name, $sectionname1);
        $this->assertEqual($sections[$section1['sectionid']]->summary,
                "<div class=\"text_to_html\">$sectionsummary1</div>");
        $this->assertEqual($sections[$section2['sectionid']]->name, $sectionname2);
        $this->assertEqual($sections[$section2['sectionid']]->summary,
                "<div class=\"text_to_html\">$sectionsummary2</div>");
        $this->assertEqual($sections[$section3['sectionid']]->name, $sectionname3);
        $this->assertEqual($sections[$section3['sectionid']]->summary,
                "<div class=\"text_to_html\">$sectionsummary3</div>");

        // test last section order
        $this->assertEqual($module->get_last_section_pageorder(), 3);

        // test moving a section - move first section to the end
        $module->move_section($section1['sectionid'], 3);

        // check if all page orders have been updated correctly
        $sections = $module->get_sections();
        $this->assertEqual($sections[$section1['sectionid']]->pageorder, 3);
        $this->assertEqual($sections[$section2['sectionid']]->pageorder, 1);
        $this->assertEqual($sections[$section3['sectionid']]->pageorder, 2);

        // test deleting a section
        $module->delete_section($section3['sectionid']);
        $this->assertEqual($module->get_last_section_pageorder(), 2);

        $sections = $module->get_sections();
        $this->assertEqual($sections[$section1['sectionid']]->pageorder, 2);
        $this->assertEqual($sections[$section2['sectionid']]->pageorder, 1);

        // test section stealthing
        $this->assertFalse($sections[$section2['sectionid']]->stealth);
        $stealth = 1;
        $module->set_section_stealth($sid2, $stealth);
        $sections = $module->get_sections(); //refresh from db
        $this->assertTrue($sections[$section2['sectionid']]->stealth);

    }

    public function test_subpage_get_course_subpages() {

        // setup a course and some modules
        $course  = $this->get_new_course();
        $coursesection = $this->get_new_course_section($course->id);

        $subpage1 = $this->get_new_subpage($course->id, "subpage1");
        $cm1      = $this->get_new_course_module($course->id, $subpage1->id, $coursesection->id);

        $subpage2 = $this->get_new_subpage($course->id, "subpage2");
        $cm2      = $this->get_new_course_module($course->id, $subpage2->id, $coursesection->id);

        $subpage3 = $this->get_new_subpage($course->id, "subpage3");
        $cm3      = $this->get_new_course_module($course->id, $subpage3->id, $coursesection->id);

        $allsubpages = mod_subpage::get_course_subpages($course);

        $this->assertIsA($allsubpages, 'array');
        $this->assertIsA($allsubpages[1], 'mod_subpage');
        $this->assertEqual($allsubpages[1]->get_name(), 'subpage1');
        $this->assertEqual($allsubpages[2]->get_name(), 'subpage2');
        $this->assertEqual($allsubpages[3]->get_name(), 'subpage3');

        $subpage = $allsubpages[1];
        $section = $subpage->add_section('section1', 'section1');
        $sectionid = $section['sectionid'];

    }

    /*
     These functions enable us to create database entries and/or grab objects to
     make it possible to test the many permutations required for subpage module.
    */

    public function get_new_user() {
        $this->usercount++;

        $user = new stdClass();
        $user->username = 'testuser' . $this->usercount;
        $user->firstname = 'Test';
        $user->lastname = 'User';
        $user->id = $this->testdb->insert_record('user', $user);
        return $user;
    }

    private function get_new_course() {
        $course = new stdClass();
        $course->category = 1;
        $course->fullname = 'Anonymous test course';
        $course->shortname = 'ANON';
        $course->summary = '';
        $course->modinfo = null;
        $course->id = $this->testdb->insert_record('course', $course);
        return $course;
    }

    private function get_new_course_section($courseid, $sectionid=1) {
        $section = new stdClass();
        $section->course = $courseid;
        $section->section = $sectionid;
        $section->name = 'Test Section';
        $section->id = $this->testdb->insert_record('course_sections', $section);
        return $section;
    }

    public function get_new_subpage($courseid, $name='test') {
        $subpage = new stdClass();
        $subpage->course = $courseid;
        $subpage->name = $name;
        $subpage->intro = 'Test Subpage Introduction';
        $subpage->introformat = 0;

        $subpage->id = $this->testdb->insert_record('subpage', $subpage);
        return $subpage;
    }

    public function get_new_label($courseid, $name='test') {
        $label = new stdClass();
        $label->course = $courseid;
        $label->name = $name;
        $label->intro = 'Test label intro';
        $label->introformat = 0;
        $label->id = $this->testdb->insert_record('label', $label);
        return $label;
    }

    public function get_new_course_module($courseid, $subpageid, $section, $groupmode=0) {
        $cm = new stdClass();
        $cm->course = $courseid;
        $cm->module = $this->modules[0]->id;
        $cm->instance = $subpageid;
        $cm->section = $section;
        $cm->groupmode = $groupmode;
        $cm->groupingid = 0;
        $cm->id = $this->testdb->insert_record('course_modules', $cm);
        return $cm;
    }

    public function get_new_group($courseid) {
        $group = new stdClass();
        $group->courseid = $courseid;
        $group->name = 'test group';
        $group->id = $this->testdb->insert_record('groups', $group);
        return $group;
    }

    public function get_new_group_member($groupid, $userid) {
        $member = new stdClass();
        $member->groupid = $groupid;
        $member->userid = $userid;
        $member->id = $this->testdb->insert_record('groups_members', $member);
        return $member;
    }
}
