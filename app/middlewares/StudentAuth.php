<?php

return function (): bool {
    if (!is_student_logged_in()) {
        set_flash('error', 'Please log in first.');
        redirect('login');
    }

    return true;
};
