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
 * Admin settings for essay question type.
 *
 * @package   qtype_essay
 * @copyright 2020 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    if (isset($CFG->maxbytes)) {
        $maxbytes = get_config('qtype_essay', 'maxbytes');
        $options = get_max_upload_sizes($CFG->maxbytes, 0, 0, $maxbytes);
    } else {
        $maxbytes = get_max_upload_file_size();
        set_config('maxbytes', $maxbytes, 'qtype_essay');
        $options = get_max_upload_sizes(0, 0, 0, $maxbytes);
    }
    $settings->add(new admin_setting_configselect('qtype_essay/maxbytes', get_string('maxbytes', 'qtype_essay'),
        get_string('maxbytes_desc', 'qtype_essay'), key($options), $options));

}
