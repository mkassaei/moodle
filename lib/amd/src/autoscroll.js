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
 * JavaScript to provide automatic scrolling, e.g. during a drag operation.
 *
 * @module     core/autoscroll
 * @class      autoscroll
 * @package    core
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.4
 */
define(['jquery'], function($) {
    /**
     * @alias module:theme_osep/autoscroll
     */
    var t = {
        /**
         * Size of area near edge of screen that triggers scrolling.
         * @private
         */
        SCROLL_THRESHOLD : 30,

        /**
         * How frequently to scroll window.
         * @private
         */
        SCROLL_FREQUENCY : 1000 / 60,

        /**
         * How many pixels to scroll per unit (1 = max scroll 30).
         * @private
         */
        SCROLL_SPEED : 0.5,

        /**
         * Set if currently scrolling up/down.
         * @private
         */
        scrollingId : null,

        /**
         * Speed we are supposed to scroll (range 1 to SCROLL_THRESHOLD).
         * @private
         */
        scrollAmount : 0,

        /**
         * Optional callback called when it scrolls
         * @private
         */
        callback : null,

        /**
         * Starts automatically scrolling if user moves near edge of window.
         * This should be called in response to mouse down or touch start.
         *
         * @public
         * @param callback Optional callback that is called every time it scrolls
         */
        start : function(callback) {
            $(window).on('mousemove', t.mouseMove);
            $(window).on('touchmove', t.touchMove);
            t.callback = callback;
        },

        /**
         * Stops automatically scrolling. This should be called in response to mouse up or touch end.
         *
         * @public
         */
        stop : function() {
            $(window).off('mousemove', t.mouseMove);
            $(window).off('touchmove', t.touchMove);
            if (t.scrollingId !== null) {
                t.stopScrolling();
            }
        },

        /**
         * Event handler for touch move.
         *
         * @private
         * @param e Event
         */
        touchMove : function(e) {
            for (var i = 0; i < e.changedTouches.length; i++) {
                t.handleMove(e.changedTouches[i].clientX, e.changedTouches[i].clientY);
            }
        },

        /**
         * Event handler for mouse move.
         *
         * @private
         * @param e Event
         */
        mouseMove : function(e) {
            t.handleMove(e.clientX, e.clientY);
        },

        /**
         * Handles user moving.
         *
         * @private
         * @param clientX X
         * @param clientY Y
         */
        handleMove : function(clientX, clientY) {
            // If near the bottom or top, start auto-scrolling.
            if (clientY < t.SCROLL_THRESHOLD) {
                t.scrollAmount = -Math.min(t.SCROLL_THRESHOLD - clientY, t.SCROLL_THRESHOLD);
            } else if (clientY > $(window).height() - t.SCROLL_THRESHOLD) {
                t.scrollAmount = Math.min(clientY - ($(window).height() - t.SCROLL_THRESHOLD), t.SCROLL_THRESHOLD);
            } else {
                t.scrollAmount = 0;
            }
            if (t.scrollAmount && t.scrollingId === null) {
                t.startScrolling();
            } else if (!t.scrollAmount && t.scrollingId !== null) {
                t.stopScrolling();
            }
        },

        /**
         * Starts automatic scrolling.
         *
         * @private
         */
        startScrolling : function() {
            var maxScroll = $(document).height - $(window).height;
            t.scrollingId = window.setInterval(function() {
                // Work out how much to scroll.
                var y = $(window).scrollTop();
                var offset = Math.round(t.scrollAmount * t.SCROLL_SPEED);
                if (y + offset < 0) {
                    offset = -y;
                }
                if (y + offset > maxScroll) {
                    offset = maxScroll - y;
                }
                if (offset === 0) {
                    return;
                }

                // Scroll.
                $(window).scrollTop(y + offset);
                var realOffset = $(window).scrollTop() - y;
                if (realOffset === 0) {
                    return;
                }

                // Inform callback
                if (t.callback) {
                    t.callback(realOffset);
                }

            }, t.SCROLL_FREQUENCY);
        },

        /**
         * Stops the automatic scrolling.
         *
         * @private
         */
        stopScrolling : function() {
            window.clearInterval(t.scrollingId);
            t.scrollingId = null;
        }
    };
    return t;
});
