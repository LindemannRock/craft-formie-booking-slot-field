<?php
/**
 * Formie Booking Slot Field config.php
 *
 * This file exists only as a template for the Formie Booking Slot Field settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'formie-booking-slot-field.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

return [
    // Global settings
    '*' => [
        // ========================================
        // GENERAL SETTINGS
        // ========================================

        /**
         * Plugin name shown in Control Panel
         */
        // 'pluginName' => 'Bookings',

        // ========================================
        // DEFAULT FIELD SETTINGS
        // ========================================

        /**
         * Default date display type for new booking fields
         * Options: 'radio', 'select'
         */
        'defaultDateDisplayType' => 'radio',

        /**
         * Default slot display type for new booking fields
         * Options: 'radio', 'select'
         */
        'defaultSlotDisplayType' => 'radio',

        /**
         * Show remaining capacity by default
         */
        'defaultShowRemainingCapacity' => true,

        // ========================================
        // DEFAULT LABELS
        // ========================================

        /**
         * Default label for date selection
         */
        'defaultDateSelectionLabel' => 'Select Date',

        /**
         * Default label for slot selection
         */
        'defaultSlotSelectionLabel' => 'Select Time Slot',

        /**
         * Default placeholder for date dropdown
         */
        'defaultDatePlaceholder' => 'Select a date...',

        /**
         * Default placeholder for slot dropdown
         */
        'defaultSlotPlaceholder' => 'Select a time slot...',

        /**
         * Default capacity text template
         * Use {count} placeholder for the number
         */
        'defaultCapacityTemplate' => '{count} spot(s) left',

        /**
         * Default text for fully booked slots
         */
        'defaultFullyBookedText' => 'Fully Booked',

        // ========================================
        // DEFAULT BOOKING CONFIGURATION
        // ========================================

        /**
         * Default operating hours start time (HH:MM format)
         */
        'defaultOperatingHoursStart' => '09:00',

        /**
         * Default operating hours end time (HH:MM format)
         */
        'defaultOperatingHoursEnd' => '17:00',

        /**
         * Default slot duration in minutes
         */
        'defaultSlotDuration' => 60,

        /**
         * Default maximum capacity per slot
         */
        'defaultMaxCapacityPerSlot' => 10,

        // ========================================
        // DEFAULT FORMATS
        // ========================================

        /**
         * Default date display format (PHP date format)
         * Examples: 'F jS, Y', 'Y-m-d', 'd/m/Y'
         */
        'defaultDateDisplayFormat' => 'F jS, Y',

        /**
         * Default time display format (PHP date format)
         * Examples: 'g:i A', 'H:i', 'h:i A'
         */
        'defaultTimeDisplayFormat' => 'g:i A',
    ],

    // Dev environment
    'dev' => [
        // Development-specific overrides
    ],

    // Staging environment
    'staging' => [
        // Staging-specific overrides
    ],

    // Production environment
    'production' => [
        // Production-specific overrides
    ],
];
