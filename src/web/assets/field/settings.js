/**
 * Formie Booking Slot Field - Settings Page Enhancements
 *
 * Adds Flatpickr multi-date picker to specific dates and blackout dates
 */

(function() {
    console.log('[BookingSlot Settings] Initializing with Flatpickr');

    function enhanceDateFields() {
        if (typeof flatpickr === 'undefined') {
            console.error('[BookingSlot Settings] Flatpickr not loaded!');
            return;
        }

        console.log('[BookingSlot Settings] Flatpickr available, enhancing fields');

        // Enhance Specific Dates field (multi-select)
        const specificDatesInput = document.querySelector('.fui-specific-dates-picker');
        if (specificDatesInput && !specificDatesInput._flatpickr) {
            console.log('[BookingSlot Settings] Adding multi-date picker to specific dates');

            flatpickr(specificDatesInput, {
                mode: 'multiple',
                dateFormat: 'Y-m-d',
                conjunction: ', ',
                allowInput: true,
                clickOpens: true,
            });

            console.log('[BookingSlot Settings] Specific dates picker initialized');
        }

        // Enhance Blackout Dates field (multi-select like Specific Dates)
        const blackoutDatesInput = document.querySelector('.fui-blackout-dates-picker');
        if (blackoutDatesInput && !blackoutDatesInput._flatpickr) {
            console.log('[BookingSlot Settings] Adding multi-date picker to blackout dates');

            flatpickr(blackoutDatesInput, {
                mode: 'multiple',
                dateFormat: 'Y-m-d',
                conjunction: ', ',
                allowInput: true,
                clickOpens: true,
            });

            console.log('[BookingSlot Settings] Blackout dates picker initialized');
        }

        // Enhance Start Date (single date with year selector)
        const startDateInput = document.querySelector('.fui-start-date-picker');
        if (startDateInput && !startDateInput._flatpickr) {
            console.log('[BookingSlot Settings] Adding date picker to start date');

            flatpickr(startDateInput, {
                dateFormat: 'Y-m-d',
                allowInput: true,
                clickOpens: true,
                // Enable year dropdown for quick navigation
                static: false,
            });

            console.log('[BookingSlot Settings] Start date picker initialized');
        }

        // Enhance End Date (single date with year selector)
        const endDateInput = document.querySelector('.fui-end-date-picker');
        if (endDateInput && !endDateInput._flatpickr) {
            console.log('[BookingSlot Settings] Adding date picker to end date');

            flatpickr(endDateInput, {
                dateFormat: 'Y-m-d',
                allowInput: true,
                clickOpens: true,
                // Enable year dropdown for quick navigation
                static: false,
            });

            console.log('[BookingSlot Settings] End date picker initialized');
        }
    }

    // Wait for everything to be ready
    function init() {
        // Give Vue time to render
        setTimeout(() => {
            enhanceDateFields();

            // Watch for DOM changes (when adding new table rows)
            const observer = new MutationObserver(() => {
                enhanceDateFields();
            });

            const targetNode = document.querySelector('.fui-modal-content') || document.body;
            observer.observe(targetNode, {
                childList: true,
                subtree: true
            });

            console.log('[BookingSlot Settings] Date picker enhancement active');
        }, 1000);
    }

    // Initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
