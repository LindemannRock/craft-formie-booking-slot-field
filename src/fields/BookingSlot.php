<?php
/**
 * Formie Booking Slot Field plugin for Craft CMS 5.x
 *
 * @link      https://lindemannrock.com
 * @copyright Copyright (c) 2025 LindemannRock
 */

namespace lindemannrock\formiebookingslotfield\fields;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Json;
use craft\helpers\Template;
use GraphQL\Type\Definition\Type;
use verbb\formie\base\FormField;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\helpers\SchemaHelper;
use yii\db\Schema;
use lindemannrock\formiebookingslotfield\web\assets\field\BookingSlotFieldAsset;
use lindemannrock\formiebookingslotfield\FormieBookingSlotField;

/**
 * Booking Slot field - Craft 4 / Formie 2 version
 *
 * @author LindemannRock
 * @since 1.0.0
 */
class BookingSlot extends FormField implements FormFieldInterface
{
    // Properties
    // =========================================================================

    /**
     * @var string Date configuration mode (range or specific)
     */
    public string $dateMode = 'specific';

    /**
     * @var string Start date option (today, tomorrow, specific)
     */
    public string $startDateOption = 'today';

    /**
     * @var mixed Start date for range mode (can be string or array from date picker)
     */
    public mixed $startDate = null;

    /**
     * @var string Start date as string (for UI)
     */
    public ?string $startDateString = null;

    /**
     * @var string End date option (+7, +14, +30, +60, +90, specific)
     */
    public string $endDateOption = '+30';

    /**
     * @var mixed End date for range mode (can be string or array from date picker)
     */
    public mixed $endDate = null;

    /**
     * @var string End date as string (for UI)
     */
    public ?string $endDateString = null;

    /**
     * @var array Specific dates (for specific mode)
     */
    public array $specificDates = [];

    /**
     * @var string Specific dates as comma-separated string (for UI)
     */
    public ?string $specificDatesString = null;

    /**
     * @var array Days of week enabled (0=Sunday, 6=Saturday)
     */
    public array $daysOfWeek = [1, 2, 3, 4, 5]; // Mon-Fri default

    /**
     * @var string Operating hours start time
     */
    public string $operatingHoursStart = '09:00';

    /**
     * @var string Operating hours end time
     */
    public string $operatingHoursEnd = '17:00';

    /**
     * @var int Slot duration in minutes
     */
    public int $slotDuration = 60;

    /**
     * @var int Maximum capacity per slot
     */
    public int $maxCapacityPerSlot = 10;

    /**
     * @var bool Show remaining capacity
     */
    public bool $showRemainingCapacity = true;

    /**
     * @var array Submission status IDs that count as "booked"
     */
    public array $bookedStatusIds = [];

    /**
     * @var string Display type for date selection (radio or select)
     */
    public string $dateDisplayType = 'radio';

    /**
     * @var string Display type for slot selection (radio or select)
     */
    public string $slotDisplayType = 'radio';

    /**
     * @var array Blackout dates
     */
    public array $blackoutDates = [];

    /**
     * @var string Blackout dates as comma-separated string (for UI)
     */
    public ?string $blackoutDatesString = null;

    /**
     * @var string Date display format
     */
    public string $dateDisplayFormat = 'F jS, Y';

    /**
     * @var string Time display format
     */
    public string $timeDisplayFormat = 'g:i A';

    /**
     * @var string Label for date selection
     */
    public string $dateSelectionLabel = 'Select Date';

    /**
     * @var string Position for date selection label
     */
    public string $dateSelectionLabelPosition = '';

    /**
     * @var string Label for slot selection
     */
    public string $slotSelectionLabel = 'Select Time Slot';

    /**
     * @var string Position for slot selection label
     */
    public string $slotSelectionLabelPosition = '';

    /**
     * @var string Placeholder for date dropdown
     */
    public string $datePlaceholder = 'Select a date...';

    /**
     * @var string Placeholder for slot dropdown
     */
    public string $slotPlaceholder = 'Select a time slot...';

    /**
     * @var string Template for capacity display (use {count} for number)
     */
    public string $capacityTemplate = '{count} spot(s) left';

    /**
     * @var string Text for fully booked slots
     */
    public string $fullyBookedText = 'Fully Booked';

    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Booking Slot');
    }

    /**
     * @inheritdoc
     */
    public static function getSvgIcon(): string
    {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/>
    <path d="M3 10H21" stroke="currentColor" stroke-width="2"/>
    <path d="M8 2V6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    <path d="M16 2V6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    <circle cx="8" cy="14" r="1" fill="currentColor"/>
    <circle cx="12" cy="14" r="1" fill="currentColor"/>
    <circle cx="16" cy="14" r="1" fill="currentColor"/>
    <circle cx="8" cy="18" r="1" fill="currentColor"/>
    <circle cx="12" cy="18" r="1" fill="currentColor"/>
</svg>';
    }

    /**
     * @inheritdoc
     */
    public static function getSvgIconPath(): string
    {
        return '@formie-booking-slot-templates/icon.svg';
    }

    // Public Methods
    // =========================================================================


    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        // Ensure arrays are initialized
        if (!is_array($this->specificDates)) {
            $this->specificDates = [];
        }

        if (!is_array($this->blackoutDates)) {
            $this->blackoutDates = [];
        }

        if (!is_array($this->daysOfWeek)) {
            $this->daysOfWeek = [1, 2, 3, 4, 5]; // Default Mon-Fri
        }

        if (!is_array($this->bookedStatusIds)) {
            $this->bookedStatusIds = [];
        }

        // Convert string to array for specific dates
        if ($this->specificDatesString && is_string($this->specificDatesString)) {
            $dates = array_map('trim', explode(',', $this->specificDatesString));
            $this->specificDates = [];
            foreach ($dates as $date) {
                if (!empty($date)) {
                    $this->specificDates[] = [
                        'date' => $date,
                        'label' => '', // Will auto-generate in getAvailableDates
                    ];
                }
            }
        }

        // Convert array to string for UI
        if (empty($this->specificDatesString) && !empty($this->specificDates)) {
            $dateStrings = [];
            foreach ($this->specificDates as $dateConfig) {
                if (isset($dateConfig['date'])) {
                    $dateStrings[] = $dateConfig['date'];
                }
            }
            $this->specificDatesString = implode(', ', $dateStrings);
        }

        // Convert blackout dates string to array
        if ($this->blackoutDatesString && is_string($this->blackoutDatesString)) {
            $dates = array_map('trim', explode(',', $this->blackoutDatesString));
            $this->blackoutDates = array_filter($dates); // Remove empty values
        }

        // Convert array to string for UI
        if (empty($this->blackoutDatesString) && !empty($this->blackoutDates)) {
            $this->blackoutDatesString = implode(', ', $this->blackoutDates);
        }

        // Sync start/end date strings
        if ($this->startDateString) {
            $this->startDate = $this->startDateString;
        } elseif ($this->startDate && !$this->startDateString) {
            $this->startDateString = is_array($this->startDate) ? ($this->startDate['date'] ?? '') : $this->startDate;
        }

        if ($this->endDateString) {
            $this->endDate = $this->endDateString;
        } elseif ($this->endDate && !$this->endDateString) {
            $this->endDateString = is_array($this->endDate) ? ($this->endDate['date'] ?? '') : $this->endDate;
        }

        // Apply plugin default settings if properties are not set
        $plugin = FormieBookingSlotField::$plugin;
        if ($plugin) {
            $settings = $plugin->getSettings();
            if ($settings) {
                if ($this->dateDisplayType === 'radio') {
                    $this->dateDisplayType = $settings->defaultDateDisplayType ?? 'radio';
                }
                if ($this->slotDisplayType === 'radio') {
                    $this->slotDisplayType = $settings->defaultSlotDisplayType ?? 'radio';
                }
                if ($this->showRemainingCapacity === true) {
                    $this->showRemainingCapacity = $settings->defaultShowRemainingCapacity ?? true;
                }
                if ($this->dateSelectionLabel === 'Select Date') {
                    $this->dateSelectionLabel = $settings->defaultDateSelectionLabel ?? 'Select Date';
                }
                if ($this->slotSelectionLabel === 'Select Time Slot') {
                    $this->slotSelectionLabel = $settings->defaultSlotSelectionLabel ?? 'Select Time Slot';
                }
                if ($this->datePlaceholder === 'Select a date...') {
                    $this->datePlaceholder = $settings->defaultDatePlaceholder ?? 'Select a date...';
                }
                if ($this->slotPlaceholder === 'Select a time slot...') {
                    $this->slotPlaceholder = $settings->defaultSlotPlaceholder ?? 'Select a time slot...';
                }
                if ($this->capacityTemplate === '{count} spot(s) left') {
                    $this->capacityTemplate = $settings->defaultCapacityTemplate ?? '{count} spot(s) left';
                }
                if ($this->fullyBookedText === 'Fully Booked') {
                    $this->fullyBookedText = $settings->defaultFullyBookedText ?? 'Fully Booked';
                }
                if ($this->operatingHoursStart === '09:00') {
                    $this->operatingHoursStart = $settings->defaultOperatingHoursStart ?? '09:00';
                }
                if ($this->operatingHoursEnd === '17:00') {
                    $this->operatingHoursEnd = $settings->defaultOperatingHoursEnd ?? '17:00';
                }
                if ($this->slotDuration === 60) {
                    $this->slotDuration = $settings->defaultSlotDuration ?? 60;
                }
                if ($this->maxCapacityPerSlot === 10) {
                    $this->maxCapacityPerSlot = $settings->defaultMaxCapacityPerSlot ?? 10;
                }
                if ($this->dateDisplayFormat === 'F jS, Y') {
                    $this->dateDisplayFormat = $settings->defaultDateDisplayFormat ?? 'F jS, Y';
                }
                if ($this->timeDisplayFormat === 'g:i A') {
                    $this->timeDisplayFormat = $settings->defaultTimeDisplayFormat ?? 'g:i A';
                }
            }
        }
    }

    /**
     * Get Formie status options for checkbox select
     */
    private function getFormieStatusOptions(): array
    {
        $options = [];
        $statuses = \verbb\formie\Formie::$plugin->getStatuses()->getAllStatuses();

        foreach ($statuses as $status) {
            $options[] = [
                'label' => $status->name,
                'value' => (string)$status->id,
            ];
        }

        return $options;
    }

    /**
     * Get label position options
     */
    private function getLabelPositionOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Form Default'), 'value' => ''],
            ['label' => Craft::t('formie', 'Above Input'), 'value' => 'verbb\\formie\\positions\\AboveInput'],
            ['label' => Craft::t('formie', 'Below Input'), 'value' => 'verbb\\formie\\positions\\BelowInput'],
            ['label' => Craft::t('formie', 'Left of Input'), 'value' => 'verbb\\formie\\positions\\LeftInput'],
            ['label' => Craft::t('formie', 'Right of Input'), 'value' => 'verbb\\formie\\positions\\RightInput'],
            ['label' => Craft::t('formie', 'Hidden'), 'value' => 'verbb\\formie\\positions\\Hidden'],
        ];
    }

    /**
     * Generate available dates based on configuration
     */
    public function getAvailableDates(): array
    {
        $dates = [];

        if ($this->dateMode === 'range') {
            // Calculate start date based on option
            if ($this->startDateOption === 'today') {
                $start = new \DateTime('today');
            } elseif ($this->startDateOption === 'tomorrow') {
                $start = new \DateTime('tomorrow');
            } else {
                // Specific date
                $startDateStr = is_array($this->startDate) ? ($this->startDate['date'] ?? null) : $this->startDate;
                if (!$startDateStr && !$this->startDateString) {
                    return [];
                }
                $start = new \DateTime($this->startDateString ?: $startDateStr);
            }

            // Calculate end date based on option
            if ($this->endDateOption === 'specific') {
                // Specific date
                $endDateStr = is_array($this->endDate) ? ($this->endDate['date'] ?? null) : $this->endDate;
                if (!$endDateStr && !$this->endDateString) {
                    return [];
                }
                $end = new \DateTime($this->endDateString ?: $endDateStr);
            } else {
                // Relative date (+7, +14, +30, etc.)
                $days = (int)str_replace('+', '', $this->endDateOption);
                $end = (clone $start)->modify("+{$days} days");
            }
            $interval = new \DateInterval('P1D');
            $period = new \DatePeriod($start, $interval, $end->modify('+1 day'));

            foreach ($period as $date) {
                $dayOfWeek = (int)$date->format('w');
                $dateStr = $date->format('Y-m-d');

                // Check if day of week is enabled and not blacklisted
                if (in_array($dayOfWeek, $this->daysOfWeek) && !in_array($dateStr, $this->blackoutDates)) {
                    $dates[] = [
                        'date' => $dateStr,
                        'label' => $date->format($this->dateDisplayFormat),
                    ];
                }
            }
        } elseif ($this->dateMode === 'specific') {
            // Use specific dates
            foreach ($this->specificDates as $dateConfig) {
                if (!empty($dateConfig['date']) && !in_array($dateConfig['date'], $this->blackoutDates)) {
                    // Generate label if empty or not provided
                    $label = !empty($dateConfig['label']) ? $dateConfig['label'] : date($this->dateDisplayFormat, strtotime($dateConfig['date']));

                    $dates[] = [
                        'date' => $dateConfig['date'],
                        'label' => $label,
                    ];
                }
            }
        }

        return $dates;
    }

    /**
     * Generate time slots based on operating hours and duration
     */
    public function getTimeSlots(): array
    {
        $slots = [];

        $start = new \DateTime($this->operatingHoursStart);
        $end = new \DateTime($this->operatingHoursEnd);
        $duration = new \DateInterval('PT' . $this->slotDuration . 'M');

        $current = clone $start;

        while ($current < $end) {
            $slotEnd = clone $current;
            $slotEnd->add($duration);

            // Don't add slot if it goes past operating hours
            if ($slotEnd <= $end) {
                $startTime = $current->format('H:i');
                $endTime = $slotEnd->format('H:i');

                $slots[] = [
                    'startTime' => $startTime,
                    'endTime' => $endTime,
                    'label' => $current->format($this->timeDisplayFormat) . ' - ' . $slotEnd->format($this->timeDisplayFormat),
                ];
            }

            $current->add($duration);
        }

        return $slots;
    }

    /**
     * Get time select options (every 15 minutes, 00:00 to 23:45)
     */
    private function getTimeSelectOptions(): array
    {
        $options = [];

        for ($hour = 0; $hour < 24; $hour++) {
            for ($minute = 0; $minute < 60; $minute += 15) {
                $time = sprintf('%02d:%02d', $hour, $minute);
                $display = date('g:i A', strtotime($time));

                $options[] = [
                    'label' => $display,
                    'value' => $time,
                ];
            }
        }

        return $options;
    }


    /**
     * @inheritdoc
     */
    public function getContentColumnType(): string
    {
        return Schema::TYPE_TEXT;
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        // If it's already an array, return it
        if (is_array($value)) {
            return $value;
        }

        // Try to decode JSON
        if (is_string($value)) {
            $decoded = Json::decodeIfJson($value);
            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function serializeValue($value, ElementInterface $element = null): mixed
    {
        if (is_array($value)) {
            return Json::encode($value);
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function getDefaultValue($attributePrefix = '')
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function validateSubmission($value, ElementInterface $element, &$error): bool
    {
        // If field is required, ensure both date and slot are selected
        if ($this->required) {
            if (empty($value) || !is_array($value)) {
                $error = Craft::t('formie', $this->errorMessage ?: 'This field is required.');
                return false;
            }

            if (empty($value['date']) || empty($value['slot'])) {
                $error = Craft::t('formie', $this->errorMessage ?: 'Please select both a date and time slot.');
                return false;
            }
        }

        return true;
    }

    /**
     * Get remaining capacity for a specific slot
     */
    public function getRemainingCapacity(string $date, string $slotKey): int
    {
        // Get the form - in Formie 2, we need to find it differently
        $form = null;

        // Try to get form from all forms and match by field handle
        $allForms = \verbb\formie\elements\Form::find()->all();
        foreach ($allForms as $possibleForm) {
            $layout = $possibleForm->getFormFieldLayout();
            if ($layout) {
                foreach ($layout->getCustomFields() as $field) {
                    if ($field->handle === $this->handle) {
                        $form = $possibleForm;
                        break 2;
                    }
                }
            }
        }

        if (!$form) {
            // Can't determine capacity without knowing the form - return max capacity
            return $this->maxCapacityPerSlot;
        }

        // Get all submissions for this form and field
        $submissions = \verbb\formie\elements\Submission::find()
            ->form($form)
            ->all();

        $bookedCount = 0;
        foreach ($submissions as $submission) {
            // Check submission status if configured
            if (!empty($this->bookedStatusIds)) {
                // Skip if submission status is not in the "booked" list (e.g., cancelled status)
                if (!in_array($submission->statusId, $this->bookedStatusIds)) {
                    continue;
                }
            }

            $fieldValue = $submission->getFieldValue($this->handle);

            if (is_array($fieldValue) &&
                isset($fieldValue['date']) &&
                isset($fieldValue['slot']) &&
                $fieldValue['date'] === $date &&
                $fieldValue['slot'] === $slotKey) {
                $bookedCount++;
            }
        }

        return max(0, $this->maxCapacityPerSlot - $bookedCount);
    }

    /**
     * Get all slot availability
     */
    public function getSlotAvailability(): array
    {
        $availability = [];
        $availableDates = $this->getAvailableDates();
        $timeSlots = $this->getTimeSlots();

        foreach ($availableDates as $dateConfig) {
            $date = $dateConfig['date'] ?? $dateConfig;

            foreach ($timeSlots as $slot) {
                $slotKey = $slot['startTime'] . '-' . $slot['endTime'];
                $remaining = $this->getRemainingCapacity($date, $slotKey);

                $availability[$date][$slotKey] = [
                    'remaining' => $remaining,
                    'isFull' => $remaining <= 0,
                ];
            }
        }

        return $availability;
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        $currentDate = is_array($value) ? ($value['date'] ?? '') : '';
        $currentSlot = is_array($value) ? ($value['slot'] ?? '') : '';

        $availableDates = $this->getAvailableDates();
        $timeSlots = $this->getTimeSlots();
        $availability = $this->getSlotAvailability();

        $html = '<div class="fui-booking-slot-cp-input" style="display: flex; flex-direction: column; gap: 16px;">';

        // Date dropdown
        $html .= '<div>';
        $html .= '<label style="display: block; font-weight: 600; margin-bottom: 8px;">Date</label>';
        $html .= '<div class="select"><select name="' . $this->handle . '[date]" id="' . $this->handle . '-date">';
        $html .= '<option value="">Select a date...</option>';
        foreach ($availableDates as $dateConfig) {
            $dateVal = $dateConfig['date'] ?? $dateConfig;
            $dateLabel = $dateConfig['label'] ?? $dateVal;
            $selected = $dateVal === $currentDate ? ' selected' : '';
            $html .= '<option value="' . htmlspecialchars($dateVal) . '"' . $selected . '>' . htmlspecialchars($dateLabel) . '</option>';
        }
        $html .= '</select></div>';
        $html .= '</div>';

        // Slot dropdown
        $html .= '<div>';
        $html .= '<label style="display: block; font-weight: 600; margin-bottom: 8px;">Time Slot</label>';
        $html .= '<div class="select"><select name="' . $this->handle . '[slot]" id="' . $this->handle . '-slot">';
        $html .= '<option value="">Select a time slot...</option>';
        foreach ($timeSlots as $slotConfig) {
            $slotKey = $slotConfig['startTime'] . '-' . $slotConfig['endTime'];
            $slotLabel = $slotConfig['label'] ?? $slotKey;
            $selected = $slotKey === $currentSlot ? ' selected' : '';

            // Check availability for the current date (or first date if no date selected)
            $checkDate = $currentDate ?: (isset($availableDates[0]) ? ($availableDates[0]['date'] ?? '') : '');
            $remaining = 0;
            $isFull = false;

            if ($checkDate && isset($availability[$checkDate][$slotKey])) {
                $remaining = $availability[$checkDate][$slotKey]['remaining'];
                $isFull = $availability[$checkDate][$slotKey]['isFull'];
            }

            $disabled = $isFull ? ' disabled' : '';
            $capacityText = $isFull ? ' (Fully Booked)' : ($this->showRemainingCapacity ? " ({$remaining} spots left)" : '');
            $label = $slotLabel . $capacityText;

            $html .= '<option value="' . htmlspecialchars($slotKey) . '"' . $selected . $disabled . '>' . htmlspecialchars($label) . '</option>';
        }
        $html .= '</select></div>';
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * @inheritdoc
     */
    public function getValueAsString($value, ElementInterface $element = null): string
    {
        if (!is_array($value)) {
            return '';
        }

        $date = $value['date'] ?? '';
        $slot = $value['slot'] ?? '';

        $availableDates = $this->getAvailableDates();
        $timeSlots = $this->getTimeSlots();

        // Find date label
        $dateLabel = $date;
        foreach ($availableDates as $dateConfig) {
            if (($dateConfig['date'] ?? $dateConfig) === $date) {
                $dateLabel = $dateConfig['label'] ?? $date;
                break;
            }
        }

        // Find slot label
        $slotLabel = $slot;
        foreach ($timeSlots as $slotConfig) {
            $slotKey = $slotConfig['startTime'] . '-' . $slotConfig['endTime'];
            if ($slotKey === $slot) {
                $slotLabel = $slotConfig['label'] ?? $slot;
                break;
            }
        }

        return $dateLabel . ' | ' . $slotLabel;
    }

    /**
     * @inheritdoc
     */
    protected function defineValueForSummary($value, ElementInterface $element = null): string
    {
        return $this->getValueAsString($value, $element);
    }

    /**
     * @inheritdoc
     */
    public static function getFrontEndInputTemplatePath(): string
    {
        return 'formie-booking-slot-field/fields/booking-slot/input';
    }

    /**
     * @inheritdoc
     */
    public static function getEmailTemplatePath(): string
    {
        return 'formie-booking-slot-field/fields/booking-slot/email';
    }

    /**
     * @inheritdoc
     */
    public function getPreviewInputHtml(): string
    {
        return '<div class="fui-booking-slot-preview">
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <div style="font-size: 12px; color: #666;">
                    <strong>Date Selection:</strong>
                </div>
                <div style="display: flex; gap: 8px;">
                    <div style="padding: 8px 12px; border: 2px solid #e5e7eb; border-radius: 4px; background: white; font-size: 14px;">
                        December 5th, 2025
                    </div>
                    <div style="padding: 8px 12px; border: 2px solid #2d5016; border-radius: 4px; background: #2d5016; color: white; font-size: 14px;">
                        December 6th, 2025
                    </div>
                </div>
                <div style="font-size: 12px; color: #666; margin-top: 8px;">
                    <strong>Time Slot:</strong>
                </div>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
                    <div style="padding: 8px; border: 2px solid #e5e7eb; border-radius: 4px; background: white; font-size: 12px; text-align: center;">
                        10:00 AM - 12:00 PM
                        <div style="font-size: 10px; color: #999; margin-top: 4px;">16 spots left</div>
                    </div>
                    <div style="padding: 8px; border: 2px solid #e5e7eb; border-radius: 4px; background: white; font-size: 12px; text-align: center;">
                        12:00 PM - 2:00 PM
                        <div style="font-size: 10px; color: #999; margin-top: 4px;">16 spots left</div>
                    </div>
                    <div style="padding: 8px; border: 2px solid #e5e7eb; border-radius: 4px; background: white; font-size: 12px; text-align: center;">
                        2:00 PM - 4:00 PM
                        <div style="font-size: 10px; color: #999; margin-top: 4px;">16 spots left</div>
                    </div>
                </div>
            </div>
        </div>';
    }

    /**
     * @inheritdoc
     */
    public function getInputTypeName(): string
    {
        return 'booking-slot';
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        // Register our asset bundle for the settings page to get date pickers
        Craft::$app->getView()->registerAssetBundle(BookingSlotFieldAsset::class);

        return parent::getSettingsHtml();
    }

    /**
     * @inheritdoc
     */
    public function getFieldDefaults(): array
    {
        $plugin = FormieBookingSlotField::$plugin;
        $settings = $plugin ? $plugin->getSettings() : null;

        return [
            'dateMode' => 'specific',
            'specificDates' => [],
            'specificDatesString' => '',
            'startDateOption' => 'today',
            'startDate' => null,
            'startDateString' => '',
            'endDateOption' => '+30',
            'endDate' => null,
            'endDateString' => '',
            'daysOfWeek' => [1, 2, 3, 4, 5], // Mon-Fri
            'blackoutDates' => [],
            'operatingHoursStart' => $settings->defaultOperatingHoursStart ?? '09:00',
            'operatingHoursEnd' => $settings->defaultOperatingHoursEnd ?? '17:00',
            'slotDuration' => $settings->defaultSlotDuration ?? 60,
            'maxCapacityPerSlot' => $settings->defaultMaxCapacityPerSlot ?? 10,
            'showRemainingCapacity' => $settings->defaultShowRemainingCapacity ?? true,
            'dateDisplayType' => $settings->defaultDateDisplayType ?? 'radio',
            'slotDisplayType' => $settings->defaultSlotDisplayType ?? 'radio',
            'dateSelectionLabel' => $settings->defaultDateSelectionLabel ?? 'Select Date',
            'dateSelectionLabelPosition' => '',
            'slotSelectionLabel' => $settings->defaultSlotSelectionLabel ?? 'Select Time Slot',
            'slotSelectionLabelPosition' => '',
            'datePlaceholder' => $settings->defaultDatePlaceholder ?? 'Select a date...',
            'slotPlaceholder' => $settings->defaultSlotPlaceholder ?? 'Select a time slot...',
            'capacityTemplate' => $settings->defaultCapacityTemplate ?? '{count} spot(s) left',
            'fullyBookedText' => $settings->defaultFullyBookedText ?? 'Fully Booked',
            'dateDisplayFormat' => $settings->defaultDateDisplayFormat ?? 'F jS, Y',
            'timeDisplayFormat' => $settings->defaultTimeDisplayFormat ?? 'g:i A',
        ];
    }

    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['maxCapacityPerSlot'], 'required'];
        $rules[] = [['maxCapacityPerSlot'], 'integer', 'min' => 1];
        $rules[] = [['slotDuration'], 'required'];
        $rules[] = [['operatingHoursStart', 'operatingHoursEnd'], 'required'];

        // Custom validation
        $rules[] = [['operatingHoursEnd'], 'validateOperatingHours'];
        $rules[] = [['slotDuration'], 'validateSlotDuration'];

        return $rules;
    }

    /**
     * Validate that end time is after start time
     */
    public function validateOperatingHours($attribute, $params)
    {
        if (!$this->operatingHoursStart || !$this->operatingHoursEnd) {
            return;
        }

        $start = new \DateTime($this->operatingHoursStart);
        $end = new \DateTime($this->operatingHoursEnd);

        if ($end <= $start) {
            $this->addError($attribute, Craft::t('formie', 'End time must be after start time.'));
        }
    }

    /**
     * Validate that slot duration fits within operating hours
     */
    public function validateSlotDuration($attribute, $params)
    {
        if (!$this->operatingHoursStart || !$this->operatingHoursEnd) {
            return;
        }

        $start = new \DateTime($this->operatingHoursStart);
        $end = new \DateTime($this->operatingHoursEnd);

        // If end is before start, they've set an invalid time range
        if ($end <= $start) {
            // The operatingHoursEnd validation will catch this
            return;
        }

        $diffMinutes = ($end->getTimestamp() - $start->getTimestamp()) / 60;

        if ($this->slotDuration >= $diffMinutes) {
            $this->addError($attribute, Craft::t('formie', 'Slot duration ({duration} min) must be less than operating hours ({hours} min).', [
                'duration' => $this->slotDuration,
                'hours' => $diffMinutes,
            ]));
        }
    }

    /**
     * @inheritdoc
     */
    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),

            // Date Configuration Mode
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Date Selection Mode'),
                'help' => Craft::t('formie', 'Choose how to configure available dates.'),
                'name' => 'dateMode',
                'options' => [
                    ['label' => Craft::t('formie', 'Specific Dates'), 'value' => 'specific'],
                    ['label' => Craft::t('formie', 'Date Range'), 'value' => 'range'],
                ],
            ]),

            // Specific Dates Mode - Multi-date picker
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Specific Dates'),
                'help' => Craft::t('formie', 'Click to open the date picker and select multiple dates. Selected dates will appear as a comma-separated list.'),
                'name' => 'specificDatesString',
                'if' => '$get(dateMode).value == specific',
                'validation' => 'requiredIf:dateMode,specific',
                'placeholder' => Craft::t('formie', 'Click to select dates...'),
                'inputClass' => 'text fullwidth code fui-specific-dates-picker',
            ]),

            // Date Range Mode
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Start Date'),
                'help' => Craft::t('formie', 'Choose when bookings should start.'),
                'name' => 'startDateOption',
                'if' => '$get(dateMode).value == range',
                'options' => [
                    ['label' => Craft::t('formie', 'Today'), 'value' => 'today'],
                    ['label' => Craft::t('formie', 'Tomorrow'), 'value' => 'tomorrow'],
                    ['label' => Craft::t('formie', 'Specific Date'), 'value' => 'specific'],
                ],
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Specific Start Date'),
                'help' => Craft::t('formie', 'Click to select the start date.'),
                'name' => 'startDateString',
                'if' => '$get(dateMode).value == range && $get(startDateOption).value == specific',
                'validation' => 'requiredIf:startDateOption,specific',
                'placeholder' => Craft::t('formie', 'Click to select start date...'),
                'inputClass' => 'text fullwidth code fui-start-date-picker',
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'End Date'),
                'help' => Craft::t('formie', 'Choose when bookings should end.'),
                'name' => 'endDateOption',
                'if' => '$get(dateMode).value == range',
                'options' => [
                    ['label' => Craft::t('formie', '+7 days'), 'value' => '+7'],
                    ['label' => Craft::t('formie', '+14 days'), 'value' => '+14'],
                    ['label' => Craft::t('formie', '+30 days'), 'value' => '+30'],
                    ['label' => Craft::t('formie', '+60 days'), 'value' => '+60'],
                    ['label' => Craft::t('formie', '+90 days'), 'value' => '+90'],
                    ['label' => Craft::t('formie', 'Specific Date'), 'value' => 'specific'],
                ],
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Specific End Date'),
                'help' => Craft::t('formie', 'Click to select the end date.'),
                'name' => 'endDateString',
                'if' => '$get(dateMode).value == range && $get(endDateOption).value == specific',
                'validation' => 'requiredIf:endDateOption,specific',
                'placeholder' => Craft::t('formie', 'Click to select end date...'),
                'inputClass' => 'text fullwidth code fui-end-date-picker',
            ]),
            SchemaHelper::checkboxSelectField([
                'label' => Craft::t('formie', 'Days of Week'),
                'help' => Craft::t('formie', 'Which days of the week are available for booking.'),
                'name' => 'daysOfWeek',
                'if' => '$get(dateMode).value == range',
                'options' => [
                    ['label' => Craft::t('formie', 'Sunday'), 'value' => '0'],
                    ['label' => Craft::t('formie', 'Monday'), 'value' => '1'],
                    ['label' => Craft::t('formie', 'Tuesday'), 'value' => '2'],
                    ['label' => Craft::t('formie', 'Wednesday'), 'value' => '3'],
                    ['label' => Craft::t('formie', 'Thursday'), 'value' => '4'],
                    ['label' => Craft::t('formie', 'Friday'), 'value' => '5'],
                    ['label' => Craft::t('formie', 'Saturday'), 'value' => '6'],
                ],
            ]),

            // Blackout Dates - Multi-date picker
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Blackout Dates (Optional)'),
                'help' => Craft::t('formie', 'Click to select dates to exclude from availability (holidays, closures). You can select multiple dates.'),
                'name' => 'blackoutDatesString',
                'placeholder' => Craft::t('formie', 'Click to select dates to exclude...'),
                'inputClass' => 'text fullwidth code fui-blackout-dates-picker',
            ]),

            // Text Customization
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Date Selection Label'),
                'help' => Craft::t('formie', 'Label shown above date selection.'),
                'name' => 'dateSelectionLabel',
                'placeholder' => 'Select Date',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Slot Selection Label'),
                'help' => Craft::t('formie', 'Label shown above time slot selection.'),
                'name' => 'slotSelectionLabel',
                'placeholder' => 'Select Time Slot',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Date Placeholder'),
                'help' => Craft::t('formie', 'Placeholder for date dropdown.'),
                'name' => 'datePlaceholder',
                'placeholder' => 'Select a date...',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Slot Placeholder'),
                'help' => Craft::t('formie', 'Placeholder for time slot dropdown.'),
                'name' => 'slotPlaceholder',
                'placeholder' => 'Select a time slot...',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Capacity Text Template'),
                'help' => Craft::t('formie', 'Template for showing remaining spots. Use {count} for the number.'),
                'name' => 'capacityTemplate',
                'placeholder' => '{count} spot(s) left',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Fully Booked Text'),
                'help' => Craft::t('formie', 'Text shown when a slot is full.'),
                'name' => 'fullyBookedText',
                'placeholder' => 'Fully Booked',
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Date Format'),
                'help' => Craft::t('formie', 'How dates should be formatted for display.'),
                'name' => 'dateDisplayFormat',
                'options' => [
                    ['label' => 'YYYY-MM-DD (' . date('Y-m-d') . ')', 'value' => 'Y-m-d'],
                    ['label' => 'MM-DD-YYYY (' . date('m-d-Y') . ')', 'value' => 'm-d-Y'],
                    ['label' => 'DD-MM-YYYY (' . date('d-m-Y') . ')', 'value' => 'd-m-Y'],
                    ['label' => 'YYYY/MM/DD (' . date('Y/m/d') . ')', 'value' => 'Y/m/d'],
                    ['label' => 'MM/DD/YYYY (' . date('m/d/Y') . ')', 'value' => 'm/d/Y'],
                    ['label' => 'DD/MM/YYYY (' . date('d/m/Y') . ')', 'value' => 'd/m/Y'],
                    ['label' => 'YYYY.MM.DD (' . date('Y.m.d') . ')', 'value' => 'Y.m.d'],
                    ['label' => 'MM.DD.YYYY (' . date('m.d.Y') . ')', 'value' => 'm.d.Y'],
                    ['label' => 'DD.MM.YYYY (' . date('d.m.Y') . ')', 'value' => 'd.m.Y'],
                    ['label' => 'Month Day, Year (' . date('F jS, Y') . ')', 'value' => 'F jS, Y'],
                    ['label' => 'Mon Day, Year (' . date('M j, Y') . ')', 'value' => 'M j, Y'],
                ],
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Time Format'),
                'help' => Craft::t('formie', 'How times should be formatted for display.'),
                'name' => 'timeDisplayFormat',
                'options' => [
                    ['label' => '23:59:59 (HH:MM:SS)', 'value' => 'H:i:s'],
                    ['label' => '03:59:59 PM (H:MM:SS AM/PM)', 'value' => 'h:i:s A'],
                    ['label' => '23:59 (HH:MM)', 'value' => 'H:i'],
                    ['label' => '03:59 PM (H:MM AM/PM)', 'value' => 'h:i A'],
                    ['label' => '3:59 PM (H:MM AM/PM)', 'value' => 'g:i A'],
                ],
            ]),
        ];
    }

    /**
     * @inheritdoc
     */
    public function defineSettingsSchema(): array
    {
        return [
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Required Field'),
                'help' => Craft::t('formie', 'Whether this field should be required when filling out the form.'),
                'name' => 'required',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Error Message'),
                'help' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
                'name' => 'errorMessage',
                'if' => '$get(required).value',
            ]),

            // Operating Hours
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Operating Hours - Start'),
                'help' => Craft::t('formie', 'What time do bookings start each day?'),
                'name' => 'operatingHoursStart',
                'validation' => 'required',
                'required' => true,
                'options' => $this->getTimeSelectOptions(),
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Operating Hours - End'),
                'help' => Craft::t('formie', 'What time do bookings end each day?'),
                'name' => 'operatingHoursEnd',
                'validation' => 'required',
                'required' => true,
                'options' => $this->getTimeSelectOptions(),
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Slot Duration'),
                'help' => Craft::t('formie', 'How long is each booking slot?'),
                'name' => 'slotDuration',
                'options' => [
                    ['label' => Craft::t('formie', '15 minutes'), 'value' => 15],
                    ['label' => Craft::t('formie', '30 minutes'), 'value' => 30],
                    ['label' => Craft::t('formie', '45 minutes'), 'value' => 45],
                    ['label' => Craft::t('formie', '1 hour'), 'value' => 60],
                    ['label' => Craft::t('formie', '1.5 hours'), 'value' => 90],
                    ['label' => Craft::t('formie', '2 hours'), 'value' => 120],
                    ['label' => Craft::t('formie', '3 hours'), 'value' => 180],
                    ['label' => Craft::t('formie', '4 hours'), 'value' => 240],
                ],
            ]),

            // Capacity
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Max Capacity Per Slot'),
                'help' => Craft::t('formie', 'Maximum number of people allowed per time slot.'),
                'name' => 'maxCapacityPerSlot',
                'validation' => 'required|min:1',
                'required' => true,
            ]),
            SchemaHelper::checkboxSelectField([
                'label' => Craft::t('formie', 'Count As Booked Statuses (Optional)'),
                'help' => Craft::t('formie', 'Select which submission statuses should count towards capacity. Leave empty to count all submissions regardless of status. Useful for excluding "Cancelled" submissions.'),
                'name' => 'bookedStatusIds',
                'options' => $this->getFormieStatusOptions(),
            ]),
            SchemaHelper::includeInEmailField(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),

            // Date Section
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Date Label Position'),
                'help' => Craft::t('formie', 'How the date selection label should be positioned.'),
                'name' => 'dateSelectionLabelPosition',
                'options' => $this->getLabelPositionOptions(),
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Date Display Type'),
                'help' => Craft::t('formie', 'How should dates be displayed to users? Buttons for few dates, Dropdown for many.'),
                'name' => 'dateDisplayType',
                'options' => [
                    ['label' => Craft::t('formie', 'Buttons (Radio)'), 'value' => 'radio'],
                    ['label' => Craft::t('formie', 'Dropdown (Select)'), 'value' => 'select'],
                ],
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Date Format'),
                'help' => Craft::t('formie', 'How dates should be formatted for display.'),
                'name' => 'dateDisplayFormat',
                'options' => [
                    ['label' => 'YYYY-MM-DD (' . date('Y-m-d') . ')', 'value' => 'Y-m-d'],
                    ['label' => 'MM-DD-YYYY (' . date('m-d-Y') . ')', 'value' => 'm-d-Y'],
                    ['label' => 'DD-MM-YYYY (' . date('d-m-Y') . ')', 'value' => 'd-m-Y'],
                    ['label' => 'YYYY/MM/DD (' . date('Y/m/d') . ')', 'value' => 'Y/m/d'],
                    ['label' => 'MM/DD/YYYY (' . date('m/d/Y') . ')', 'value' => 'm/d/Y'],
                    ['label' => 'DD/MM/YYYY (' . date('d/m/Y') . ')', 'value' => 'd/m/Y'],
                    ['label' => 'YYYY.MM.DD (' . date('Y.m.d') . ')', 'value' => 'Y.m.d'],
                    ['label' => 'MM.DD.YYYY (' . date('m.d.Y') . ')', 'value' => 'm.d.Y'],
                    ['label' => 'DD.MM.YYYY (' . date('d.m.Y') . ')', 'value' => 'd.m.Y'],
                    ['label' => 'Month Day, Year (' . date('F jS, Y') . ')', 'value' => 'F jS, Y'],
                    ['label' => 'Mon Day, Year (' . date('M j, Y') . ')', 'value' => 'M j, Y'],
                ],
            ]),

            // Slot Section
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Slot Label Position'),
                'help' => Craft::t('formie', 'How the slot selection label should be positioned.'),
                'name' => 'slotSelectionLabelPosition',
                'options' => $this->getLabelPositionOptions(),
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Slot Display Type'),
                'help' => Craft::t('formie', 'How should time slots be displayed to users?'),
                'name' => 'slotDisplayType',
                'options' => [
                    ['label' => Craft::t('formie', 'Buttons (Radio)'), 'value' => 'radio'],
                    ['label' => Craft::t('formie', 'Dropdown (Select)'), 'value' => 'select'],
                ],
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Time Format'),
                'help' => Craft::t('formie', 'How times should be formatted for display.'),
                'name' => 'timeDisplayFormat',
                'options' => [
                    ['label' => '23:59:59 (HH:MM:SS)', 'value' => 'H:i:s'],
                    ['label' => '03:59:59 PM (H:MM:SS AM/PM)', 'value' => 'h:i:s A'],
                    ['label' => '23:59 (HH:MM)', 'value' => 'H:i'],
                    ['label' => '03:59 PM (H:MM AM/PM)', 'value' => 'h:i A'],
                    ['label' => '3:59 PM (H:MM AM/PM)', 'value' => 'g:i A'],
                ],
            ]),

            // Capacity
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Show Remaining Capacity'),
                'help' => Craft::t('formie', 'Display how many spots are left for each time slot.'),
                'name' => 'showRemainingCapacity',
            ]),

            // Standard Formie fields at end
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
        ];
    }

    /**
     * @inheritdoc
     */
    public function defineAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
            SchemaHelper::inputAttributesField(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function defineConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'dateMode';
        $attributes[] = 'startDateOption';
        $attributes[] = 'startDate';
        $attributes[] = 'startDateString';
        $attributes[] = 'endDateOption';
        $attributes[] = 'endDate';
        $attributes[] = 'endDateString';
        $attributes[] = 'specificDates';
        $attributes[] = 'specificDatesString';
        $attributes[] = 'daysOfWeek';
        $attributes[] = 'blackoutDates';
        $attributes[] = 'blackoutDatesString';
        $attributes[] = 'operatingHoursStart';
        $attributes[] = 'operatingHoursEnd';
        $attributes[] = 'slotDuration';
        $attributes[] = 'maxCapacityPerSlot';
        $attributes[] = 'showRemainingCapacity';
        $attributes[] = 'bookedStatusIds';
        $attributes[] = 'dateDisplayType';
        $attributes[] = 'slotDisplayType';
        $attributes[] = 'dateSelectionLabel';
        $attributes[] = 'dateSelectionLabelPosition';
        $attributes[] = 'slotSelectionLabel';
        $attributes[] = 'slotSelectionLabelPosition';
        $attributes[] = 'datePlaceholder';
        $attributes[] = 'slotPlaceholder';
        $attributes[] = 'capacityTemplate';
        $attributes[] = 'fullyBookedText';
        $attributes[] = 'dateDisplayFormat';
        $attributes[] = 'timeDisplayFormat';

        return $attributes;
    }


    /**
     * @inheritdoc
     */
    public function getContentGqlType(): Type|array
    {
        return Type::string();
    }

    /**
     * @inheritdoc
     */
    public function getFrontEndJsModules(): ?array
    {
        // Register the asset bundle
        Craft::$app->getView()->registerAssetBundle(BookingSlotFieldAsset::class);

        // Get the published URL
        $assetPath = dirname((new \ReflectionClass(BookingSlotFieldAsset::class))->getFileName());
        $publishedUrl = Craft::$app->getAssetManager()->getPublishedUrl($assetPath, true);

        return [
            'src' => $publishedUrl . '/booking-slot.js',
            'module' => 'FormieBookingSlot',
            'settings' => [
                'availableDates' => $this->getAvailableDates(),
                'timeSlots' => $this->getTimeSlots(),
                'maxCapacityPerSlot' => $this->maxCapacityPerSlot,
                'showRemainingCapacity' => $this->showRemainingCapacity,
                'slotAvailability' => $this->getSlotAvailability(),
                'capacityTemplate' => Craft::t('formie', $this->capacityTemplate),
                'fullyBookedText' => Craft::t('formie', $this->fullyBookedText),
            ],
        ];
    }
}
