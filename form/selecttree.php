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
 * A moodle form field type for a tree like select form.
 *
 * @author Stacey Walker
 * @package mod
 * @subpackage subpage
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;
require_once("$CFG->libdir/form/selectgroups.php");

/**
 * HTML class for a drop down element in a tree like structure.
 * @access public
 */
class MoodleQuickForm_selecttree extends MoodleQuickForm_selectgroups {
    private $_options = array('top' => false, 'currentcat' => 0, 'nochildrenof' => -1);

    /**
     * Constructor
     *
     * @param string $elementname Select name attribute
     * @param mixed $elementlabel Label(s) for the select
     * @param mixed $attributes Either a typical HTML attribute string or an associative array
     * @param array $options additional options. Recognised options are courseid, published and
     *   only_editable, corresponding to the arguments of question_category_options from
     *   moodlelib.php.
     * @access public
     * @return void
     */
    public function MoodleQuickForm_selecttree($elementname = null, $elementlabel = null,
            $options = null, $attributes = null) {
        parent::__construct($elementname, $elementlabel, array(), $attributes);
        $this->_type = 'selecttree';
        if (is_array($options)) {
            $this->_options = $options + $this->_options;
            $this->loadArrayOptGroups($this->_options['options']);
        }
    }

}

// Register wikieditor.
MoodleQuickForm::registerElementType('selecttree',
        $CFG->dirroot . "/mod/subpage/form/selecttree.php", 'MoodleQuickForm_selecttree');
