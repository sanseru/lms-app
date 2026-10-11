<?php

return [
    // Attendance statuses that use up a student's package quota.
    // Excused and sick do not; absent (no notice) still counts.
    'quota_statuses' => ['present', 'absent'],

    // How many weeks ahead sessions are generated from the weekly class schedule.
    'generate_weeks_ahead' => 4,
];
