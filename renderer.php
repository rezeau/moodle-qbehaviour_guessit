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
 * Renderer for outputting parts of a question belonging to the guessit behaviour.
 *
 * @package    qbehaviour_guessit
 * @subpackage guessit
 * @copyright  2024 Joseph Rézeau
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../adaptive/renderer.php');

/**
 * Renderer for outputting parts of a question belonging to the guessit behaviour.
 */
class qbehaviour_guessit_renderer extends qbehaviour_adaptive_renderer {

    /**
     * Get graded step.
     * @param question_attempt $qa a question attempt.
     */
    protected function get_graded_step(question_attempt $qa) {
        foreach ($qa->get_reverse_step_iterator() as $step) {
            if ($step->has_behaviour_var('_try')) {
                return $step;
            }
        }
    }

    /**
     * Displays controls for the question attempt.
     *
     * @param question_attempt $qa The question attempt being processed.
     * @param question_display_options $options Options for displaying the controls.
     * @return string The HTML output for the controls.
     */
    public function controls(question_attempt $qa, question_display_options $options) {
        $prevtries = $qa->get_last_behaviour_var('_try', 0);
        $question = $qa->get_question(false);
        $nbtriesbeforehelp = $question->nbtriesbeforehelp;
        $helprequested = false;
        $gradedstep = $this->get_graded_step($qa);
        if ($gradedstep && $gradedstep->has_behaviour_var('helpme') ) {
            $helprequested = true;
        }
        $output = $this->submit_button($qa, $options).'&nbsp;';
        $helptext = get_string('gethelp', 'qbehaviour_guessit');
        $attributes = [
            'type' => 'submit',
            'id' => $qa->get_behaviour_field_name('helpme'),
            'name' => $qa->get_behaviour_field_name('helpme'),
            'value' => $helptext,
            'class' => 'submit btn btn-secondary',
        ];
        if ($nbtriesbeforehelp > 0 && !$helprequested && $prevtries !== 0) {
            $output .= html_writer::empty_tag('input', $attributes);
        }
        return $output;
    }

    /**
     * Provides extra help for the question attempt.
     *
     * @param question_attempt $qa The question attempt being processed.
     * @param question_display_options $options Options for displaying the help.
     * @return string The HTML output for the extra help.
     */
    public function extra_help(question_attempt $qa, question_display_options $options) {
        return html_writer::nonempty_tag('div', $qa->get_behaviour()->get_extra_help_if_requested($options->markdp));
    }

    /**
     * Provides feedback for the question attempt.
     *
     * @param question_attempt $qa The question attempt being processed.
     * @param question_display_options $options Options for displaying the feedback.
     * @return string The HTML output for the feedback.
     */
    public function feedback(question_attempt $qa, question_display_options $options) {
        // If the latest answer was invalid, no need to display an informative message.
        if ($qa->get_state() == question_state::$invalid) {
            return '';
        }
        // Try to find the last graded step.
        $gradedstep = $this->get_graded_step($qa);
        if ($gradedstep) {
            if ($gradedstep->has_behaviour_var('helpme') ) {
                return $this->extra_help($qa, $options);
            }
        }
    }

}
