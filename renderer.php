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
 * Short answer question renderer class.
 *
 * @package    qtype
 * @subpackage harakat
 * @copyright  2023 Meirza <meirza.arson@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Generates the output for short answer questions.
 *
 * @copyright  2023 Meirza <meirza.arson@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_harakat_renderer extends qtype_renderer {

    public function formulation_and_controls(question_attempt $qa,
            question_display_options $options) {

        /**  @var \qtype_harakat_question $question */
        $question = $qa->get_question();
        $currentanswer = $qa->get_last_qt_var('answer');
        $inputname = $qa->get_qt_field_name('answer');

        $questiontext = $question->format_questiontext($qa);
        $result = html_writer::tag('div', $questiontext, array('class' => 'qtext'));
        $feedbackimg = '';
        if ($options->correctness) {
            $matchinganswer = $question->matching_answer(array('answer' => $currentanswer));
            $answer  = $matchinganswer['answer'];
            if ($answer) {
                $fraction = $answer->fraction;
            } else {
                $fraction = 0;
            }
            $feedbackimg = $this->feedback_image($fraction);
            $feedbackimg .= "(" . $matchinganswer['right'] ." / " . $matchinganswer['total'] . ")";

            $input = html_writer::tag('div', $matchinganswer['htmlanswer'], array('class' => 'text-right display-4'));
            $result .= html_writer::tag('label', get_string('answer', 'qtype_harakat', ''));
            $result .= html_writer::tag('div', $input, array('class' => 'answer'));
            $result .= html_writer::tag('div', $feedbackimg);
        } else {
            $htmlquestion = $question->html_question($currentanswer);
            $ansinputname = 'ans-'.$inputname;
            $result .= html_writer::tag('div', '', array('class' => 'qtype_harakat_options'));
            $result .= html_writer::tag('div', $htmlquestion, array('id' => $ansinputname, 'class' => 'text-right display-4'));
            $result .= html_writer::start_tag('div', array('class' => 'ablock form-inline'));
            $result .= html_writer::tag(
                'input',
                '',
                array(
                    'id' => $inputname,
                    'name' => $inputname,
                    'type' => 'hidden',
                    'value' => strip_tags($htmlquestion)
                    )
                );
            $result .= html_writer::end_tag('div');

            $this->page->requires->js_call_amd(
                'qtype_harakat/answering',
                'init',
                [
                    $inputname,
                    $ansinputname,
                    'question-'.$qa->get_usage_id().'-'.$qa->get_slot(),
                    $question->jsunicode
                ]);
        }

        if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div',
                    $question->get_validation_error(array('answer' => $currentanswer)),
                    array('class' => 'validationerror'));
        }

        return $result;
    }

    public function correct_response(question_attempt $qa) {
        /**  @var \qtype_harakat_question $question */
        $question = $qa->get_question();

        $answer = $question->get_answer();
        if (!$answer) {
            return '';
        }

        return get_string('correctansweris', 'qtype_harakat',
                s($question->clean_response($answer->answer)));
    }
}
