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
 * subpage external functions and service definitions.
 *
 * @package    mod-subpage
 * @author     Dan Marsden <dan@danmarsden.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(
    'mod_subpage_add_subpage' => array(
        'classname'    => 'mod_subpage_external',
        'methodname'   => 'add_subpage',
        'classpath'    => 'mod/subpage/externallib.php',
        'description'  => 'Creates new subpage.',
        'type'         => 'write',
        'capabilities' => 'moodle/course:manageactivities'
    ),

    'mod_subpage_add_section' => array(
        'classname'    => 'mod_subpage_external',
        'methodname'   => 'add_section',
        'classpath'    => 'mod/subpage/externallib.php',
        'description'  => 'Creates new section in a given subpage.',
        'type'         => 'write',
        'capabilities' => 'moodle/course:manageactivities'
    ),

    'mod_subpage_add_link' => array(
        'classname'    => 'mod_subpage_external',
        'methodname'   => 'add_link',
        'classpath'    => 'mod/subpage/externallib.php',
        'description'  => 'Adds a link to a section.',
        'type'         => 'write',
        'capabilities' => 'moodle/course:manageactivities'
    ),

    'mod_subpage_add_file' => array(
        'classname'    => 'mod_subpage_external',
        'methodname'   => 'add_file',
        'classpath'    => 'mod/subpage/externallib.php',
        'description'  => 'Adds a file to a section.',
        'type'         => 'write',
        'capabilities' => 'moodle/course:manageactivities'
    ),
);