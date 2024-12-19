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

    public function controls(question_attempt $qa, question_display_options $options) {
       // If student's answer is no longer improvable, then there's no point enabling the hint button.
        $isimprovable = $qa->get_behaviour()->is_state_improvable($qa->get_state());
        $output = $this->submit_button($qa, $options).'&nbsp;';
        $helpmode = $qa->get_question()->usehint;
        $helptext = 'Help';
        $attributes = [
            'type' => 'submit',
            'id' => $qa->get_behaviour_field_name('helpme'),
            'name' => $qa->get_behaviour_field_name('helpme'),
            'value' => $helptext,
            'class' => 'submit btn btn-secondary',
        ];

        $attributes['round'] = true;
        $output .= html_writer::empty_tag('input', $attributes);
        return $output;
       // Only display the Check button and specific feedback if correct answer still not found.
        $state = $qa->get_state();
        return $this->submit_button($qa, $options);
        if ($state !== question_state::$complete) {
            return $this->submit_button($qa, $options);
        } else {
            $options->feedback = 0;
            $options->numpartscorrect = '';
            $save = clone($options);
        }
    }

    public function extra_help(question_attempt $qa, question_display_options $options) {
        return html_writer::nonempty_tag('div', $qa->get_behaviour()->get_extra_help_if_requested($options->markdp));
    }
    
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
            echo $qa->get_state();
     }

}
