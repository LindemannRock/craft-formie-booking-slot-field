/**
 * Formie Booking Slot Field JavaScript
 *
 * @author LindemannRock
 * @since 1.0.0
 */

// Define the FormieBookingSlot class
window.FormieBookingSlot = class FormieBookingSlot {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form ? this.$form.form : null;
        this.$field = settings.$field;

        // Settings are at the root level, not nested
        this.settings = settings;

        // Initialize the field
        this.initializeField();
    }

    initializeField() {
        if (!this.$field) {
            return;
        }

        // Get wrapper element
        const $wrapper = this.$field.querySelector('[data-fui-booking-slot]');

        if (!$wrapper) {
            return;
        }

        // Setup event listeners
        this.setupDateSelection($wrapper);
        this.setupSlotSelection($wrapper);
    }

    setupDateSelection($wrapper) {
        const dateInputs = $wrapper.querySelectorAll('[data-date-input]');
        const $slotsContainer = $wrapper.querySelector('[data-booking-slots]');

        dateInputs.forEach(input => {
            input.addEventListener('change', (e) => {
                const selectedDate = e.target.value;

                // Update visual selection for radio buttons
                if (input.type === 'radio') {
                    dateInputs.forEach(di => {
                        const option = di.closest('.fui-date-option');
                        if (option) {
                            option.classList.remove('is-selected');
                        }
                    });
                    const option = e.target.closest('.fui-date-option');
                    if (option) {
                        option.classList.add('is-selected');
                    }
                }

                // Enable slots container
                if ($slotsContainer) {
                    $slotsContainer.classList.remove('is-disabled');
                }

                // Update slot availability for selected date
                this.updateSlotAvailability($wrapper, selectedDate);

                // Clear any previously selected slot
                const slotInputs = $wrapper.querySelectorAll('[data-slot-input]');
                slotInputs.forEach(si => {
                    if (si.type === 'radio') {
                        si.checked = false;
                        const option = si.closest('.fui-slot-option');
                        if (option) {
                            option.classList.remove('is-selected');
                        }
                    } else {
                        si.selectedIndex = 0; // Reset select to first option
                    }
                });
            });
        });
    }

    setupSlotSelection($wrapper) {
        const slotInputs = $wrapper.querySelectorAll('[data-slot-input]');

        slotInputs.forEach(input => {
            input.addEventListener('change', (e) => {

                // Update visual selection for radio buttons only
                if (input.type === 'radio') {
                    slotInputs.forEach(si => {
                        const option = si.closest('.fui-slot-option');
                        if (option) {
                            option.classList.remove('is-selected');
                        }
                    });

                    if (e.target.checked) {
                        const option = e.target.closest('.fui-slot-option');
                        if (option) {
                            option.classList.add('is-selected');
                        }
                    }
                }
            });
        });
    }

    updateSlotAvailability($wrapper, selectedDate) {
        if (!this.settings.slotAvailability || !this.settings.slotAvailability[selectedDate]) {
            return;
        }

        const dateAvailability = this.settings.slotAvailability[selectedDate];

        // Handle both select dropdown and radio buttons
        const slotSelect = $wrapper.querySelector('select[data-slot-input]');
        const slotRadios = $wrapper.querySelectorAll('input[type="radio"][data-slot-input]');

        if (slotSelect) {
            // Update select dropdown options
            const options = slotSelect.querySelectorAll('option[data-slot-key]');
            options.forEach(option => {
                const slotKey = option.getAttribute('data-slot-key');
                if (dateAvailability[slotKey]) {
                    const { remaining, isFull } = dateAvailability[slotKey];
                    option.disabled = isFull;
                    option.setAttribute('data-remaining', remaining);

                    // Update the option text to show correct capacity
                    const originalText = option.textContent.split(' - ')[0]; // Get time label part
                    if (this.settings.showRemainingCapacity) {
                        if (isFull) {
                            const fullyBookedText = this.settings.fullyBookedText || 'Fully Booked';
                            option.textContent = `${originalText} - ${fullyBookedText}`;
                        } else {
                            const template = this.settings.capacityTemplate || '{count} spot(s) left';
                            const capacityText = template.replace('{count}', remaining);
                            option.textContent = `${originalText} - ${capacityText}`;
                        }
                    }
                }
            });
        } else if (slotRadios.length > 0) {
            // Update radio button options
            slotRadios.forEach(radio => {
                const option = radio.closest('.fui-slot-option');
                if (!option) return;

                const slotKey = option.getAttribute('data-slot-key');
                const capacitySpan = option.querySelector('.fui-slot-capacity');

                if (dateAvailability[slotKey]) {
                    const { remaining, isFull } = dateAvailability[slotKey];

                    option.setAttribute('data-remaining', remaining);

                    if (isFull) {
                        option.classList.add('is-full');
                        radio.disabled = true;
                        radio.checked = false;
                        if (capacitySpan) {
                            capacitySpan.textContent = this.settings.fullyBookedText || 'Fully Booked';
                            capacitySpan.classList.add('is-full');
                        }
                    } else {
                        option.classList.remove('is-full');
                        radio.disabled = false;
                        if (capacitySpan && this.settings.showRemainingCapacity) {
                            const template = this.settings.capacityTemplate || '{count} spot(s) left';
                            capacitySpan.textContent = template.replace('{count}', remaining);
                            capacitySpan.classList.remove('is-full');
                        }
                    }
                }
            });
        }
    }

    onAfterSubmit(e) {
        // Handle post-submit actions if needed
    }
};
