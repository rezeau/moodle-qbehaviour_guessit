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
     * Get the most recently submitted step.
     * @return question_attempt_step
     */
    public function get_graded_step() {
        foreach ($this->qa->get_reverse_step_iterator() as $step) {
            if ($step->has_behaviour_var('_try')) {
                return $step;
            }
        }
    }

    public function get_expected_data() {
        $expected = parent::get_expected_data();
        if ($this->qa->get_state()->is_active()) {
            $expected['helpme'] = PARAM_BOOL;
        }
        return $expected;
    }

    public function process_action(question_attempt_pending_step $pendingstep) {
        //echo '<br><br><br>function process_action';
        if ($pendingstep->has_behaviour_var('helpme')) {
            return $this->process_helpme($pendingstep);
        } else {
            return parent::process_action($pendingstep);
        }
    }

    public function process_submit(question_attempt_pending_step $pendingstep) {
        // todo do not submit Check if help has been requested!
        //$status = $this->process_save($pendingstep);
        $response = $pendingstep->get_qt_data();
        $question = $this->qa->get_question();
        $nbtriesbeforehelp = $question->nbtriesbeforehelp;
        $helprequested = $pendingstep->has_behaviour_var('helpme');
        if ($helprequested) {
            echo '$response<pre>';
        print_r($response);
        echo '</pre>';
        //die;
        $response = [];
        }
        $prevstep = $this->qa->get_last_step_with_behaviour_var('_try');
        $prevresponse = $prevstep->get_qt_data();
        $prevtries = $this->qa->get_last_behaviour_var('_try', 0);
        if ($helprequested && $prevtries !== $nbtriesbeforehelp) {
            $prevtries = $prevtries - 1;
        }
        // Added 'helpme' condition so question attempt would not be DISCARDED when student asks for help.
        if ($this->question->is_same_response($response, $prevresponse)) {
            return question_attempt::DISCARD;
        }

        if ($prevstep->get_state() == question_state::$complete) {
            $pendingstep->set_state(question_state::$complete);
        } else {
            $pendingstep->set_state(question_state::$todo);
        }
        $pendingstep->set_behaviour_var('_try', $prevtries + 1);

        return question_attempt::KEEP;
    }

    public function summarise_action(question_attempt_step $step) {
        return;
    }

    public function process_helpme(question_attempt_pending_step $pendingstep) {
        $keep = $this->process_submit($pendingstep);
        if ($keep == question_attempt::KEEP && $pendingstep->get_state() != question_state::$invalid) {
            $pendingstep->set_behaviour_var('_help', 1);
        }
        return $keep;
    }

    public function get_extra_help_if_requested($dp) {
        // Try to find the last graded step.
        $question = $this->qa->get_question();
        $nbtriesbeforehelp = $question->nbtriesbeforehelp;
        $prevtries = $this->qa->get_last_behaviour_var('_try', 0);
        $output = '';
        echo '<br>$prevtries = ' . $prevtries . ' $nbtriesbeforehelp = ' . $nbtriesbeforehelp;
        $gradedstep = $this->get_graded_step($this->qa);
        $prevstep = $this->qa->get_last_step_with_behaviour_var('_try');
        $prevresponse = $prevstep->get_qt_data();
        if ($prevtries >= $nbtriesbeforehelp) {
            $isstateimprovable = $this->qa->get_behaviour()->is_state_improvable($this->qa->get_state());
            if (is_null($gradedstep) || !$gradedstep->has_behaviour_var('helpme')) {
                return '';
            }
            $answersArray = $question->answers;
            $answerList = '';
            $counter = 1; // Start counter from 0
            $nbanswers = count($answersArray);
            foreach ($answersArray as $key => $rightansweer) {
                //echo '<br>$counter $rightansweer->answer = ' . $counter .' '. $rightansweer->answer;
                if ($rightansweer->answer !== $prevresponse['p' . $counter] ) {
                    $answerList .= '<b>' . $rightansweer->answer . '</b> ';
                    break;
                } else {
                    $answerList .= $rightansweer->answer . ' ';
                }
                $counter++;
                
            }
            // Trim any extra whitespace at the end
            $answerList = trim($answerList);
            $output .= '<span class="que guessit help">' . $answerList . '</span>';
            return $output;
        }
        $triesleft = $nbtriesbeforehelp - $prevtries;
        if ($triesleft > 1) {
            return get_string('moretries', 'qtype_guessit', $triesleft);
        } else {
            return get_string('moretry', 'qtype_guessit', $triesleft);
        }
    }

}
