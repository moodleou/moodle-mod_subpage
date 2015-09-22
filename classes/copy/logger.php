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
 * Logger for subpage copy functionality.
 *
 * @package    mod_subpage
 * @copyright  2015 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_subpage\copy;

/**
 * Custom logger that can print dots during process.
 */
class logger extends \output_indented_logger {
    /** @var int Unix time of last dot */
    private $time = 0;

    /**
     * This function is OU custom and not part of the logger interface. It
     * gives the logger a chance to display a dot if necessary. Called by
     * module backup/restore functions at suitable points if needed.
     */
    public function potential_dot() {
        // If it's more than 1 second since last dot, do another one.
        $now = time();
        if ($this->time != $now) {
            $this->time = $now;
            set_time_limit(350);
            print '.' . str_repeat(' ', 16);
            flush();
        }
    }
}
