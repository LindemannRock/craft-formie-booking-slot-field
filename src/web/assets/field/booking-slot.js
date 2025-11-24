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

        // Refresh capacity from server (for static caching)
        this.refreshCapacityFromServer($wrapper);

        // Setup event listeners
        this.setupDateSelection($wrapper);
        this.setupSlotSelection($wrapper);

        // Setup validation to check capacity before submission
        this.setupValidation($wrapper);
    }

    setupValidation($wrapper) {
        // Listen for form validation event to check capacity in real-time
        this.$form.addEventListener('onFormieValidate', (e) => {
            const dateInput = $wrapper.querySelector('[data-date-input]:checked, select[data-date-input]');
            const slotInput = $wrapper.querySelector('[data-slot-input]:checked, select[data-slot-input]');

            const selectedDate = dateInput?.value;
            const selectedSlot = slotInput?.value;

            if (!selectedDate || !selectedSlot) {
                return; // Let required validation handle this
            }

            // Check if slot is still available
            const availability = this.settings.slotAvailability?.[selectedDate]?.[selectedSlot];

            if (availability && availability.isFull) {
                e.preventDefault();

                // Add error to the field
                const errorMessage = 'Sorry, this time slot is now fully booked. Please select another slot.';
                this.$field.classList.add('fui-error');

                // Find or create error element
                let errorEl = this.$field.querySelector('.fui-error-message');
                if (!errorEl) {
                    errorEl = document.createElement('div');
                    errorEl.className = 'fui-error-message';
                    this.$field.appendChild(errorEl);
                }
                errorEl.textContent = errorMessage;

                // Trigger form error
                if (e.detail.submitHandler) {
                    e.detail.submitHandler.formSubmitError();
                }
            }
        });
    }

    refreshCapacityFromServer($wrapper) {
        // Get form ID from form config
        let formId = null;
        if (this.form && this.form.formId) {
            formId = this.form.formId;
        }

        // Get field handle from parent field element
        let fieldHandle = null;
        if (this.$field) {
            fieldHandle = this.$field.getAttribute('data-field-handle');
        }

        if (!formId || !fieldHandle) {
            console.warn('Booking Slot: Cannot refresh capacity - missing form ID or field handle', {formId, fieldHandle});
            return;
        }

        // Fetch fresh capacity data from server
        fetch(`/actions/formie-booking-slot-field/capacity/get?formId=${formId}&fieldHandle=${fieldHandle}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to fetch capacity data');
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.availability) {
                    // Update settings with fresh availability data
                    this.settings.slotAvailability = data.availability;

                    // Get currently selected date
                    const dateInput = $wrapper.querySelector('[data-date-input]:checked, select[data-date-input]');
                    const selectedDate = dateInput?.value;

                    // If a date is selected, refresh slot availability display
                    if (selectedDate) {
                        this.updateSlotAvailability($wrapper, selectedDate);
                    }
                }
            })
            .catch(error => {
                console.warn('Booking Slot: Failed to refresh capacity', error);
                // Fail silently - use cached HTML capacity as fallback
            });
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

                    // Get the slot label from settings
                    const slotInfo = this.settings.timeSlots.find(s => `${s.startTime}-${s.endTime}` === slotKey);
                    const slotLabel = slotInfo ? slotInfo.label : slotKey;

                    // Update text: TIME - CAPACITY (select has dir="ltr" to handle RTL)
                    if (this.settings.showRemainingCapacity) {
                        if (isFull) {
                            const fullyBookedText = this.settings.fullyBookedText || 'Fully Booked';
                            option.textContent = `${slotLabel} - ${fullyBookedText}`;
                        } else {
                            const template = this.settings.capacityTemplate || '{count} spot(s) left';
                            const capacityText = template.replace('{count}', remaining);
                            option.textContent = `${slotLabel} - ${capacityText}`;
                        }
                    } else {
                        option.textContent = slotLabel;
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
