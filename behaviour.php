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
 * Question behaviour for guessit question type (with help).
 *
 * @package    qbehaviour_guessit
 * @copyright  2024 Joseph Rézeau
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/../adaptive/behaviour.php');

/**
 * Question behaviour for guessit question type (with help).
 * @copyright  2024 Joseph Rézeau
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qbehaviour_guessit extends qbehaviour_adaptive {
    /**
     * Question behaviour for guessit question type (with help).
     */
    const IS_ARCHETYPAL = false;

    /**
     * Retrieves the expected data for the question attempt.
     *
     * @return array The expected data, including 'helpme' if the attempt is active.
     */
    public function get_expected_data() {
        $expected = parent::get_expected_data();
        if ($this->qa->get_state()->is_active()) {
            $expected['helpme'] = PARAM_BOOL;
            $expected['maxtriesreached'] = PARAM_BOOL;
        }
        return $expected;
    }

    /**
     * Processes a user action for the question attempt.
     *
     * @param question_attempt_pending_step $pendingstep The pending step to process.
     * @return bool The result of the action processing.
     */
    public function process_action(question_attempt_pending_step $pendingstep) {
        if ($pendingstep->has_behaviour_var('helpme')) {
            return $this->process_helpme($pendingstep);
        } else {
            return parent::process_action($pendingstep);
        }
    }

    /**
     * Processes a submit action for the question attempt.
     *
     * Handles the logic for comparing responses, tracking attempts, and setting the state
     * based on the user's input and the number of tries before help is allowed.
     *
     * @param question_attempt_pending_step $pendingstep The pending step to process.
     * @return int One of the constants indicating how the step should be handled.
     */
    public function process_submit(question_attempt_pending_step $pendingstep) {
        $response = $pendingstep->get_qt_data();
        $question = $this->qa->get_question();
        $nbtriesbeforehelp = $question->nbtriesbeforehelp;
        $wordle = $question->wordle;
        $helprequested = $pendingstep->has_behaviour_var('helpme');
        if ($helprequested) {
            $response = [];
        }
        $prevstep = $this->qa->get_last_step_with_behaviour_var('_try');
        $prevresponse = $prevstep->get_qt_data();
        $prevtries = $this->qa->get_last_behaviour_var('_try', 0);
        // Do not count this try if help has been requested.
        if ($helprequested) {
            $prevtries = $prevtries - 1;
        }

        list($fraction, $state) = $this->question->grade_response($response);
        if ($fraction === 1) {
            $pendingstep->set_state(question_state::$complete);
        } else {
            $pendingstep->set_state(question_state::$todo);
        }

        if ($wordle) {
            $nbmaxtrieswordle = $question->nbmaxtrieswordle;
            if ($prevtries > $nbmaxtrieswordle - 2 && $fraction != 1) {
                $pendingstep->set_behaviour_var('_maxtriesreached', 1);
                $pendingstep->set_state(question_state::$gradedpartial);
            }
        }
        if ($this->question->is_same_response($response, $prevresponse)) {
            return question_attempt::DISCARD;
        }

        $pendingstep->set_behaviour_var('_try', $prevtries + 1);
        $pendingstep->set_behaviour_var('_rawfraction', $fraction);
        $pendingstep->set_new_response_summary($this->question->summarise_response($response));

        return question_attempt::KEEP;
    }

    /**
     * Processes a "help me" action for the question attempt.
     *
     * @param question_attempt_pending_step $pendingstep The pending step to process.
     * @return int Action handling result.
     */
    public function process_helpme(question_attempt_pending_step $pendingstep) {
        $keep = $this->process_submit($pendingstep);
        if ($keep == question_attempt::KEEP && $pendingstep->get_state() != question_state::$invalid) {
            $pendingstep->set_behaviour_var('_help', 1);
        }
        return $keep;
    }

}
