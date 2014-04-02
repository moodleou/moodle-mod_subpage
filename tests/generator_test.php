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

/**
 * Subpage generator unit test.
 *
 * @package mod_subpage
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_subpage_generator_testcase extends advanced_testcase {
    public function test_generator() {
        global $DB, $SITE;

        $this->resetAfterTest(true);

        $this->assertEquals(0, $DB->count_records('subpage'));

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_subpage');
        $this->assertInstanceOf('mod_subpage_generator', $generator);
        $this->assertEquals('subpage', $generator->get_modulename());

        $course = $this->getDataGenerator()->create_course();
        $subpage = $generator->create_instance(array('course' => $course->id));
        $this->assertEquals(1, $DB->count_records('subpage'));
        $subpageid = $subpage->id;
        $cm = get_coursemodule_from_instance('subpage', $subpageid);
        $this->assertEquals($subpageid, $cm->instance);
        $this->assertEquals('subpage', $cm->modname);
        $this->assertEquals($course->id, $cm->course);

        $context = context_module::instance($cm->id);
        $this->assertEquals($subpage->cmid, $context->instanceid);
    }
}
