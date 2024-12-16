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

   public function controls(question_attempt $qa, question_display_options $options) {
       // Only display the Check button and specific feedback if correct answer still not found.
        $state = $qa->get_state();
        if ($state !== question_state::$complete) {
            return $this->submit_button($qa, $options);
        } else {
            $options->feedback = 0;
            $options->numpartscorrect = '';
            $save = clone($options);
        }
    }

    public function feedback(question_attempt $qa, question_display_options $options) {
            // If the latest answer was invalid, no need to display an informative message.
            if ($qa->get_state() == question_state::$invalid) {
                return '';                
            }
     }

}
