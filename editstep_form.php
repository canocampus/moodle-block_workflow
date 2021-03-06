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
 * Form for editing steps
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/formslib.php');

class step_edit extends moodleform {

    const MAX_DAYS = 10;

    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('stepsettings', 'block_workflow'));

        // Step data.
        $mform->addElement('text', 'name', get_string('name', 'block_workflow'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', null, 'maxlength', 255);

        $mform->addElement('editor',   'instructions_editor', get_string('instructions', 'block_workflow'),
                block_workflow_editor_options());
        $mform->setType('instructions_editor', PARAM_RAW);
        $mform->addRule('instructions_editor', null, 'required', null, 'client');

        // Scripts.
        $scriptoptions = array('cols' => 80, 'rows' => 8);
        $mform->addElement('textarea', 'onactivescript', get_string('onactivescript', 'block_workflow'), $scriptoptions);
        $mform->setType('onactivescript', PARAM_RAW);

        $mform->addElement('textarea', 'oncompletescript', get_string('oncompletescript', 'block_workflow'), $scriptoptions);
        $mform->setType('oncompletescript', PARAM_RAW);

        // IDs.
        $mform->addElement('hidden', 'stepid');
        $mform->setType('stepid', PARAM_INT);
        $mform->addElement('hidden', 'workflowid');
        $mform->setType('workflowid', PARAM_INT);

        // Before or after.
        $mform->addElement('hidden', 'beforeafter');
        $mform->setType('beforeafter', PARAM_INT);

        // Automatically finish.
        $days = array();
        $secondsinday = 24*60*60;
        for ($count = -self::MAX_DAYS; $count <= self::MAX_DAYS; $count++) {
            if ($count < 0) {
                $days[$count * $secondsinday] = abs($count) . ' days before';
            }
            if ($count == 0) {
                $days[$count * $secondsinday] = 'do not set';
            }
            if ($count > 0) {
                $days[$count * $secondsinday] = $count . ' days after';
            }
        }
        $appliesto = $this->_customdata['appliesto'];
        $options = array('' => get_string('donotautomaticallyfinish', 'block_workflow'));

        if ($appliesto === 'course') {
            // The string is stored in the dtabase in the following format.
            // {database table name}_{field name with value as timestamp}.
            // For instance, course_startdate, quiz_timeopen, quiz_timeclose.
            $options['course_startdate'] = get_string('coursestartdate', 'block_workflow');
        } else {
            $options['quiz_timeopen'] = get_string('quizopendate', 'block_workflow');
            $options['quiz_timeclose'] = get_string('quizclosedate', 'block_workflow');
        }
        $autofinish = array();
        $autofinish[] = $mform->createElement('select', 'autofinishoffset', null, $days);
        $autofinish[] = $mform->createElement('select', 'autofinish', null, $options);
        $mform->addGroup($autofinish, null, get_string('automaticallyfinish', 'block_workflow'), ' ', true);

        $mform->setDefault('autofinishoffset', 0);
        $mform->setDefault('autofinish', '');

        $mform->disabledIf('autofinish', 'autofinishoffset', 'eq', 0);

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $step = new block_workflow_step($data['stepid']);

        // If the workflowid was specified, this step has not yet been created.
        // We need to set the workflow temporarily (it'll be overwritten
        // shortly anyway) for script validation to succeed.
        if ($data['workflowid']) {
            $step->set_workflow($data['workflowid']);
        }

        if (isset($data['onactivescript'])) {
            // Validate the onactivescript.
            $script = $step->validate_script($data['onactivescript']);
            if ($script->errors) {
                // Only display the first error.
                $errors['onactivescript'] = get_string('invalidscript', 'block_workflow', $script->errors[0]);
            }
        }

        if (isset($data['oncompletescript'])) {
            // Validate the oncompletescript.
            $script = $step->validate_script($data['oncompletescript']);
            if ($script->errors) {
                // Only display the first error.
                $errors['oncompletescript'] = get_string('invalidscript', 'block_workflow', $script->errors[0]);
            }
        }

        return $errors;
    }
}
