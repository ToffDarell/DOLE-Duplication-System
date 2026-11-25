<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Active DOLE Programs
    |--------------------------------------------------------------------------
    | Controls which DOLE programs are exposed in the UI, dropdowns, navigation,
    | and project directory. Set to ['TUPAD'] for TUPAD-Only Mode; add 'SPES',
    | 'DILP', or 'GIP' anytime to re-enable multi-program mode without any
    | schema or migration changes.
    */
    'enabled_programs' => ['TUPAD'],
];
