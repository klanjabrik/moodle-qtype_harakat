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
 * Defines the editing form for the harakat question type.
 *
 * @package    qtype
 * @subpackage harakat
 * @copyright  2023 Meirza <meirza.arson@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Short answer question editing form definition.
 *
 * @copyright  2023 Meirza <meirza.arson@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_harakat_edit_form extends question_edit_form {

    /**
     * Get the list of form elements to repeat, one for each answer.
     * @param object $mform the form being built.
     * @param $label the label to use for each option.
     * @param $gradeoptions the possible grades for each answer.
     * @param $repeatedoptions reference to array of repeated options to fill
     * @param $answersoption reference to return the name of $question->options
     *      field holding an array of answers
     * @return array of form fields.
     */
    protected function get_per_answer_fields($mform, $label, $gradeoptions,
            &$repeatedoptions, &$answersoption) {
        $repeated = array();
        $answeroptions = array();
        $answeroptions[] = $mform->createElement('text', 'answer',
                $label, array('size' => 50));
        $answeroptions[] = $mform->createElement('hidden', 'fraction', '1');
        $mform->setType('fraction', PARAM_RAW);
        $repeated[] = $mform->createElement('group', 'answeroptions',
                $label, $answeroptions, null, false);
        $repeated[] = $mform->createElement('editor', 'feedback',
                get_string('feedback', 'question'), array('rows' => 5), $this->editoroptions);
        $repeatedoptions['answer']['type'] = PARAM_RAW;
        $repeatedoptions['fraction']['default'] = 1;
        $answersoption = 'answers';
        return $repeated;
    }

    /**
     * Question-type specific form fields.
     *
     * @param object $mform the form being built.
     */
    protected function definition_inner($mform) {

        $mform->addElement('static', 'answersinstruct', '',
                get_string('filloutoneanswer', 'qtype_harakat'));
        $mform->closeHeaderBefore('answersinstruct');

        $this->add_per_answer_fields($mform, get_string('answerno', 'qtype_harakat', ''),
                question_bank::fraction_options(), 1, 0);

        $this->add_interactive_settings();
    }

    /**
     * Add a set of form fields, obtained from get_per_answer_fields, to the form,
     * one for each existing answer, with some blanks for some new ones.
     * @param object $mform the form being built.
     * @param $label the label to use for each option.
     * @param $gradeoptions the possible grades for each answer.
     * @param $minoptions the minimum number of answer blanks to display.
     *      Default QUESTION_NUMANS_START.
     * @param $addoptions the number of answer blanks to add. Default QUESTION_NUMANS_ADD.
     */
    protected function add_per_answer_fields(&$mform, $label, $gradeoptions,
            $minoptions = QUESTION_NUMANS_START, $addoptions = QUESTION_NUMANS_ADD) {
        $mform->addElement('header', 'answerhdr',
                get_string('answers', 'qtype_harakat'), '');
        $mform->setExpanded('answerhdr', 1);
        $answersoption = '';
        $repeatedoptions = array();
        $repeated = $this->get_per_answer_fields($mform, $label, $gradeoptions,
                $repeatedoptions, $answersoption);

        if (isset($this->question->options)) {
            $repeatsatstart = count($this->question->options->$answersoption);
        } else {
            $repeatsatstart = $minoptions;
        }

        $this->repeat_elements($repeated, $repeatsatstart, $repeatedoptions,
                'noanswers', 'addanswers', $addoptions,
                $this->get_more_choices_string(), true);
    }

    // We only need an answer.
    protected function get_more_choices_string() {
        return '';
    }

    /**
     * Perform an preprocessing needed on the data passed to {@link set_data()}
     * before it is used to initialise the form.
     * @param object $question the data being passed to the form.
     * @return object $question the modified data.
     */
    protected function data_preprocessing($question) {

        // Cleanup the answer from non-arabic script and diacritics.
        $answer = reset($question->options->answers);
        $cleanupanswer = preg_replace('/[^\x{0600}-\x{06FF} !@#$%^&*()]/u', '', $answer->answer);
        $answer->answer = $cleanupanswer;

        $question = parent::data_preprocessing($question);
        $question = $this->data_preprocessing_answers($question);
        $question = $this->data_preprocessing_hints($question);
        return $question;
    }

    /**
     * Validation input
     *
     * @return string|null validation feedback.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $answer = reset($data['answer']);

        $trimmedanswer = trim($answer);

        // Raise an error if the answer is not Arabic script and diacritics.
        $isarabic = preg_match('/\p{Arabic}/u', $trimmedanswer);
        if ($trimmedanswer !== '') {
            if (!$isarabic) {
                $errors['answeroptions[0]'] = get_string('notarabicanswers', 'qtype_harakat', 1);
            }
        } else {
            $errors['answeroptions[0]'] = get_string('notenoughanswers', 'qtype_harakat', 1);
        }

        return $errors;
    }

    /**
     * Question-type
     *
     * @return string question type name.
     */
    public function qtype(): string {
        return 'harakat';
    }
}
