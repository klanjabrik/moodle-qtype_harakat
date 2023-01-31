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

/*
 * JavaScript to allow dragging options to slots (using mouse down or touch) or tab through slots using keyboard.
 *
 * @module     qtype_harakat/answering
 * @copyright  2023 Meirza <meirza.arson@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'jquery'
], function(
    $
) {
    "use strict";

    // Close all harakat options if user click outside the options container.
    document.onclick = function(e) {
        if(
            e.target.className !== 'qtype_harakat_options' &&
            e.target.className !== 'harakat_yes' &&
            e.target.className !== 'option_letter') {
            var myClasses = document.querySelectorAll('.qtype_harakat_options'),
            i = 0,
            l = myClasses.length;

            for (i; i < l; i++) {
                myClasses[i].style.display = 'none';
            }
        }
    };

    /**
     * Initialise Harakat.
     *
     * @method
     * @param {String} inputname the id of the div.que that contains this question.
     * @param {String} containerQuestionId the id of the div.que that contains this question.
     * @param {String} containerId the id of the div.que that contains this question.
     */
    function HarakatQuestion(inputname, containerQuestionId, containerId) {
        this.containerId = containerId;
        this.inputname = inputname;
        this.containerQuestionId = containerQuestionId;

        const root = document.getElementById(containerQuestionId);

        // Add click event handlers for the divs containing the answer since these cannot be enclosed in a label element.
        const answerLabels = root.querySelectorAll('[class=harakat_yes]');
        answerLabels.forEach((answerLabel) => {
            answerLabel.addEventListener('click', (e) => {
                this.showHarakatOptions(e);
            });
        });
    }

    /**
     * Binding the click event for showing options.
     *
     * @param {jQuery} e Element to bind the event
     */
    HarakatQuestion.prototype.showHarakatOptions = function(e) {
        const coords = this.getCoords(e);
        window.console.log('#'+this.containerId+' .qtype_harakat_options');
        $('#'+this.containerId+' .qtype_harakat_options').css({
            top: coords.top,
            left: coords.left,
            display: 'block'
        });
        this.displayOptions(e);
    };

    /**
     * Binding the displayOptions.
     *
     * @param {jQuery} letterTarget Element to bind the event
     */
    HarakatQuestion.prototype.displayOptions = function(letterTarget) {

        const thisQ = this;

        const letter = letterTarget.target.getAttribute('original');

        const a = answeringManager.harakat.split("|");

        var someDiv = document.createElement('div');
        for (let i = -1; i < a.length; i++) {
            var someSpan = document.createElement('span');

            someSpan.className = "option_letter";
            if (i > -1) {
                var b = a[i].split("::");
                if (b.length > 1) {
                    var txt = document.createTextNode(letter + String.fromCharCode(b[0])+String.fromCharCode(b[1]));
                } else {
                    var txt = document.createTextNode(letter + String.fromCharCode(a[i]));
                }
            } else {
                var txt = document.createTextNode(letter);
            }
            someSpan.append(txt);

            someSpan.addEventListener('click', function(e) {
                letterTarget.target.innerText = e.target.innerText;
                $('#'+thisQ.containerId+' .qtype_harakat_options').css('display', 'none');

                thisQ.applyAnswer();
            });

            someDiv.append(someSpan);
        }
        $('#'+this.containerId+' .qtype_harakat_options').html('').append(someDiv);
    };

    /**
     * Binding the getCoords.
     *
     * @param {jQuery} e Element to bind the event
     */
    HarakatQuestion.prototype.getCoords = function(e) {
        const rect = e.target.getBoundingClientRect();
        const left = e.clientX - rect.left; //x position within the element.
        const top = e.clientY - rect.top;  //y position within the element.
        return {
          left: left,
          top: top
        };
    };

    /**
     * Binding the answer applying to input name.
     *
     * @param {jQuery} e Element to bind the event
     */
    HarakatQuestion.prototype.applyAnswer = function() {
        const question = document.getElementById(this.containerQuestionId);
        const inputname = document.getElementById(this.inputname);
        inputname.value = question.innerText;
    };

    /**
     * Singleton object that handles all the HarakatQuestion
     * on the page, and deals with event dispatching.
     * @type {Object}
     */
    var answeringManager = {
        /**
         * {String} Harakat.
         */
        harakat: '',

        /**
         * {Object} all the questions on this page, indexed by containerId (id on the .que div).
         */
        questions: {}, // An object containing all the information about each question on the page.

        /**
             * Initialise one question.
             *
             * @method
             * @param {String} inputname the id of the div.que that contains this question.
             * @param {String} containerQuestionId the id of the div.que that contains this question.
             * @param {String} containerId the id of the div.que that contains this question.
             * @param {String} harakat the id of the div.que that contains this question.
             */
        init: function(inputname, containerQuestionId, containerId, harakat) {
            answeringManager.questions[containerId] = new HarakatQuestion(
                inputname,
                containerQuestionId,
                containerId,
            );
            answeringManager.harakat = harakat;
        }
    };

    /**
     * @alias module:qtype_ddimageortext/question
     */
     return {
        init: answeringManager.init
    };
});
