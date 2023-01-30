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
 * Short answer question definition class.
 *
 * @package    qtype
 * @subpackage harakat
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/questionbase.php');

/**
 * Represents a short answer question.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_harakat_question extends question_graded_by_strategy
        implements question_response_answer_comparer {

    /** @var array of question_answer. */
    public $answers = array();

    /** @var string Harakat. */
    protected string $unicode =
        "~[\x{064B}|\x{064C}|\x{064D}|\x{064E}|\x{064F}|\x{0650}|\x{0651}|\x{0652}|\x{0656}|\x{0657}|\x{0670}]~u";

    /** @var string Harakat. */
    public string $jsunicode =
        "0x064B|0x064C|0x064D|0x064E|0x064F|0x0650|0x0651::0x064F|0x0651::0x064E|0x0651::0x0650|0x0652|0x0656|0x0657|0x0670";

    public function __construct() {
        parent::__construct(new question_first_matching_answer_grading_strategy($this));
    }

    public function get_expected_data() {
        return array('answer' => PARAM_RAW_TRIMMED);
    }

    public function summarise_response(array $response) {
        if (isset($response['answer'])) {
            return $response['answer'];
        } else {
            return null;
        }
    }

    public function un_summarise_response(string $summary) {
        if (!empty($summary)) {
            return ['answer' => $summary];
        } else {
            return [];
        }
    }

    public function is_complete_response(array $response) {
        return array_key_exists('answer', $response) &&
                ($response['answer'] || $response['answer'] === '0');
    }

    public function get_validation_error(array $response) {
        if ($this->is_gradable_response($response)) {
            return '';
        }
        return get_string('pleaseenterananswer', 'qtype_harakat');
    }

    public function is_same_response(array $prevresponse, array $newresponse) {
        return question_utils::arrays_same_at_key_missing_is_blank(
                $prevresponse, $newresponse, 'answer');
    }

    public function get_answers() {
        return $this->answers;
    }

    public function get_answer() {
        return reset($this->answers);
    }

    public function compare_response_with_answer(array $response, question_answer $answer) {
        question_response_answer_comparer::compare_response_with_answer($response, $answer);
    }

    public function get_correct_response() {
        $response = parent::get_correct_response();
        if ($response) {
            $response['answer'] = $this->clean_response($response['answer']);
        }
        return $response;
    }

    public function clean_response($answer) {
        // Break the string on non-escaped asterisks.
        $bits = preg_split('/(?<!\\\\)\*/', $answer);

        // Unescape *s in the bits.
        $cleanbits = array();
        foreach ($bits as $bit) {
            $cleanbits[] = str_replace('\*', '*', $bit);
        }

        // Put it back together with spaces to look nice.
        return trim(implode(' ', $cleanbits));
    }

    /**
     * Return the question settings that define this question as structured data.
     *
     * @param question_attempt $qa the current attempt for which we are exporting the settings.
     * @param question_display_options $options the question display options which say which aspects of the question
     * should be visible.
     * @return mixed structure representing the question settings. In web services, this will be JSON-encoded.
     */
    public function get_question_definition_for_external_rendering(question_attempt $qa, question_display_options $options) {
        // No need to return anything, external clients do not need additional information for rendering this question type.
        return null;
    }

    public function get_num_parts_right(array $response) {
        $result = $this->matching_answer($response);
        return [
            $result['right'],
            $result['total']
        ];
    }

    public function grade_response(array $response) {
        list($right, $total) = $this->get_num_parts_right($response);
        $fraction = $right / $total;
        return array($fraction, question_state::graded_state_for_fraction($fraction));
    }

    public function get_matching_answer(array $response) {
        return $this->matching_answer($response)['answer'];
    }

    public function mapping_harakat(string $str): array {
        $map = [];
        $plain = '';
        $mapindex = -1;
        $totalharakat = 0;
        foreach (mb_str_split($str) as $s) {
            if ($this->is_harakat($s)) {
                $map[$mapindex]['harakat'][] = $s;
                $totalharakat++;
            } else {
                $mapindex++;
                $map[$mapindex] = ["letter" => $s, "harakat" => []];
                $plain .= $s;
            }
        }
        return [
            "plain" => $plain,
            "original" => $str,
            "totalharakat" => $totalharakat,
            "map" => $map
        ];
    }

    public function matching_answer(array $response) {
        $questionanswer = $this->get_answer();

        $questionanswertext = $this->mapping_harakat($questionanswer->answer);
        $useranswertext = $this->mapping_harakat($response['answer']);

        $totalharakat = $questionanswertext['totalharakat'];

        $righttotal = 0;
        $wrongtotal = 0;
        $wrongletters = [];
        $htmlanswer = '';
        $htmlquestion = '';
        foreach ($questionanswertext['map'] as $key => $val) {
            $totalharakatinletter = count($questionanswertext['map'][$key]['harakat']);
            if ($totalharakatinletter > 0) {
                if ($diff = array_diff(
                    $questionanswertext['map'][$key]['harakat'],
                    $useranswertext['map'][$key]['harakat'])
                ) {
                    $totaldiff = count($diff);
                    $wrongletters[] = [
                        "index" => $key,
                        "questionharakat" => $questionanswertext['map'][$key]['harakat'],
                        "userharakat" => $useranswertext['map'][$key]['harakat'],
                        "total" => $totaldiff
                    ];
                    // If there is more than one harakat in a letter.
                    if ($totaldiff < $totalharakatinletter) {
                        $righttotal += $totalharakatinletter - $totaldiff;
                    }
                    $wrongtotal += $totaldiff;

                    // Displaying wrong letters in HTML.
                    $htmlanswer .= '<span class="harakat_wrong">' . $val['letter'];
                    foreach ($useranswertext['map'][$key]['harakat'] as $key2 => $val2) {
                        $htmlanswer .= $val2;
                    }
                    $htmlanswer .= '</span>';
                } else {
                    $righttotal += $totalharakatinletter;

                    // Displaying correct letters in HTML.
                    $htmlanswer .= '<span class="harakat_correct">' . $useranswertext['map'][$key]['letter'];
                    foreach ($useranswertext['map'][$key]['harakat'] as $key2 => $val2) {
                        $htmlanswer .= $val2;
                    }
                    $htmlanswer .= '</span>';

                    $htmlquestion .= '<span class="harakat_yes">' . $useranswertext['map'][$key]['letter'] . '</span>';
                }
            } else {
                // Displaying empty harakat.
                $htmlanswer .= '<span class="harakat_no">' . $useranswertext['map'][$key]['letter'] . '</span>';
                $htmlquestion .= '<span class="harakat_no">' . $useranswertext['map'][$key]['letter'] . '</span>';
            }
        }
        $questionanswer->fraction = $righttotal / $totalharakat;
        return [
            'answer' => $questionanswer,
            'right' => $righttotal,
            'wrong' => $wrongtotal,
            'total' => $totalharakat,
            'wrongletters' => $wrongletters,
            'htmlanswer' => $htmlanswer,
            'htmlquestion' => $htmlquestion
        ];
    }

    public function html_question($currentanswer) {
        $questionanswer = $this->get_answer();
        $questionanswertext = $this->mapping_harakat($questionanswer->answer);
        if ($currentanswer) {
            $currentanswertext = $this->mapping_harakat($currentanswer);
        }
        $htmlquestion = '';
        foreach ($questionanswertext['map'] as $key => $val) {
            $totalharakatinletter = count($questionanswertext['map'][$key]['harakat']);
            if ($totalharakatinletter > 0) {
                $htmlquestion .= '<span class="harakat_yes" original="'.$val['letter'].'">';
            } else {
                $htmlquestion .= '<span class="harakat_no">';
            }

            $currentharakat = '';
            if ($currentanswer) {
                $currentharakat = implode('', $currentanswertext['map'][$key]['harakat']);
            }
            $htmlquestion .= $val['letter'] . $currentharakat . '</span>';
        }
        return $htmlquestion;
    }

    private function is_harakat(string $str): bool {
        return preg_match($this->unicode, $str);
    }
}
