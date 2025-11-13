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
use verbb\formie\base\Field;
use verbb\formie\base\FieldInterface;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\HtmlTag;
use yii\db\Schema;
use lindemannrock\formiebookingslotfield\web\assets\field\BookingSlotFieldAsset;
use lindemannrock\formiebookingslotfield\FormieBookingSlotField;

/**
 * Booking Slot field
 *
 * @author LindemannRock
 * @since 1.0.0
 */
class BookingSlot extends Field implements FieldInterface
{
    // Properties
    // =========================================================================

    /**
     * @var string Date configuration mode (range or specific)
     */
    public string $dateMode = 'specific';

    /**
     * @var mixed Start date for range mode (can be string or array from date picker)
     */
    public mixed $startDate = null;

    /**
     * @var string Start date as string (for UI)
     */
    public ?string $startDateString = null;

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
    public string $dateSelectionLabelPosition = 'verbb\\formie\\positions\\AboveInput';

    /**
     * @var string Label for slot selection
     */
    public string $slotSelectionLabel = 'Select Time Slot';

    /**
     * @var string Position for slot selection label
     */
    public string $slotSelectionLabelPosition = 'verbb\\formie\\positions\\AboveInput';

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

        if ($this->dateMode === 'range' && $this->startDate && $this->endDate) {
            // Normalize date picker values (can be array or string)
            $startDateStr = is_array($this->startDate) ? ($this->startDate['date'] ?? null) : $this->startDate;
            $endDateStr = is_array($this->endDate) ? ($this->endDate['date'] ?? null) : $this->endDate;

            if (!$startDateStr || !$endDateStr) {
                return [];
            }

            // Generate dates from range
            $start = new \DateTime($startDateStr);
            $end = new \DateTime($endDateStr);
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
                    $dates[] = [
                        'date' => $dateConfig['date'],
                        'label' => $dateConfig['label'] ?? date($this->dateDisplayFormat, strtotime($dateConfig['date'])),
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
    public function getDefaultValue(): mixed
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
        // Get the form from the field's layout
        $form = null;
        if ($this->layoutId) {
            $form = \verbb\formie\elements\Form::find()->layoutId($this->layoutId)->one();
        }

        if (!$form) {
            // Can't determine capacity without knowing the form - return max capacity
            return $this->maxCapacityPerSlot;
        }

        // Get all submissions for this form and field
        $submissions = \verbb\formie\elements\Submission::find()
            ->formId($form->id)
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
    protected function cpInputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $displayValue = '';

        if (is_array($value)) {
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

            $displayValue = $dateLabel . ' | ' . $slotLabel;
        }

        return '<div class="fui-booking-slot-display" style="padding: 10px; background: #f7f7f7; border-radius: 4px;">' .
               '<strong>Booking:</strong> ' . htmlspecialchars($displayValue ?: 'No slot selected') .
               '</div>';
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
        return [
            'dateMode' => 'specific',
            'specificDates' => [],
            'specificDatesString' => '',
            'startDate' => null,
            'endDate' => null,
            'daysOfWeek' => [1, 2, 3, 4, 5], // Mon-Fri
            'blackoutDates' => [],
            'operatingHoursStart' => '09:00',
            'operatingHoursEnd' => '17:00',
            'slotDuration' => 60,
            'maxCapacityPerSlot' => 10,
            'showRemainingCapacity' => true,
            'dateDisplayType' => 'radio',
            'slotDisplayType' => 'radio',
            'dateSelectionLabel' => 'Select Date',
            'dateSelectionLabelPosition' => '',
            'slotSelectionLabel' => 'Select Time Slot',
            'slotSelectionLabelPosition' => '',
            'datePlaceholder' => 'Select a date...',
            'slotPlaceholder' => 'Select a time slot...',
            'capacityTemplate' => '{count} spot(s) left',
            'fullyBookedText' => 'Fully Booked',
            'dateDisplayFormat' => 'F jS, Y',
            'timeDisplayFormat' => 'g:i A',
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
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Start Date'),
                'help' => Craft::t('formie', 'Click to select the first date available for booking.'),
                'name' => 'startDateString',
                'if' => '$get(dateMode).value == range',
                'validation' => 'requiredIf:dateMode,range',
                'placeholder' => Craft::t('formie', 'Click to select start date...'),
                'inputClass' => 'text fullwidth code fui-start-date-picker',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'End Date'),
                'help' => Craft::t('formie', 'Click to select the last date available for booking.'),
                'name' => 'endDateString',
                'if' => '$get(dateMode).value == range',
                'validation' => 'requiredIf:dateMode,range',
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
                'help' => Craft::t('formie', 'Label shown for date selection.'),
                'name' => 'dateSelectionLabel',
                'placeholder' => 'Select Date',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Slot Selection Label'),
                'help' => Craft::t('formie', 'Label shown for time slot selection.'),
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
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Date Display Format'),
                'help' => Craft::t('formie', 'PHP date format (e.g., F jS, Y = December 5th, 2025 or Y-m-d = 2025-12-05). Reference: php.net/manual/en/datetime.format.php'),
                'name' => 'dateDisplayFormat',
                'placeholder' => 'F jS, Y',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Time Display Format'),
                'help' => Craft::t('formie', 'PHP time format (e.g., g:i A = 9:00 AM or H:i = 09:00). Reference: php.net/manual/en/datetime.format.php'),
                'name' => 'timeDisplayFormat',
                'placeholder' => 'g:i A',
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
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Date Label Position'),
                'help' => Craft::t('formie', 'How the date selection label should be positioned.'),
                'name' => 'dateSelectionLabelPosition',
                'options' => $this->getLabelPositionOptions(),
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Slot Label Position'),
                'help' => Craft::t('formie', 'How the time slot label should be positioned.'),
                'name' => 'slotSelectionLabelPosition',
                'options' => $this->getLabelPositionOptions(),
            ]),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Date Display Type'),
                'help' => Craft::t('formie', 'How should dates be displayed to users?'),
                'name' => 'dateDisplayType',
                'options' => [
                    ['label' => Craft::t('formie', 'Buttons (Radio)'), 'value' => 'radio'],
                    ['label' => Craft::t('formie', 'Dropdown (Select)'), 'value' => 'select'],
                ],
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
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Show Remaining Capacity'),
                'help' => Craft::t('formie', 'Display how many spots are left for each time slot.'),
                'name' => 'showRemainingCapacity',
            ]),
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
        $attributes[] = 'startDate';
        $attributes[] = 'startDateString';
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
    public function defineHtmlTag(string $key, array $context = []): ?HtmlTag
    {
        $form = $context['form'] ?? null;
        $errors = $context['errors'] ?? null;

        if ($key === 'fieldDateSelect') {
            return new HtmlTag('select', [
                'class' => 'fui-select',
                'name' => $this->getHtmlName() . '[date]',
                'required' => $this->required ? true : null,
                'data' => [
                    'date-input' => true,
                ],
            ]);
        }

        if ($key === 'fieldSlotSelect') {
            return new HtmlTag('select', [
                'class' => 'fui-select',
                'name' => $this->getHtmlName() . '[slot]',
                'required' => $this->required ? true : null,
                'data' => [
                    'slot-input' => true,
                ],
            ]);
        }

        if ($key === 'fieldDateOption') {
            return new HtmlTag('label', [
                'class' => 'fui-date-option',
            ]);
        }

        if ($key === 'fieldSlotOption') {
            return new HtmlTag('label', [
                'class' => 'fui-slot-option',
            ]);
        }

        return parent::defineHtmlTag($key, $context);
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
