<?php

return [

    /**
     * Email domain used by students.
     *
     * ex: c14230001@john.petra.ac.id
     */
    'student' => env('STUDENT_EMAIL_DOMAIN', '@john.petra.ac.id'),

    /**
     * Email domain used by lecturer.
     *
     * ex: lecturer@peter.petra.ac.id
     */
    'lecturer' => env('LECTURER_EMAIL_DOMAIN', '@peter.petra.ac.id'),

];