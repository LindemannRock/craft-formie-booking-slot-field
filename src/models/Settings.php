<?php
/**
 * Formie Booking Slot Field plugin for Craft CMS 5.x
 *
 * @link      https://lindemannrock.com
 * @copyright Copyright (c) 2025 LindemannRock
 */

namespace lindemannrock\formiebookingslotfield\models;

use craft\base\Model;

/**
 * Settings model
 *
 * @author    LindemannRock
 * @package   FormieBookingSlotField
 * @since     1.0.0
 */
class Settings extends Model
{
    /**
     * @var string Plugin name
     */
    public string $pluginName = 'Formie Booking Slot Field';

    /**
     * @var string Default date display type
     */
    public string $defaultDateDisplayType = 'radio';

    /**
     * @var string Default slot display type
     */
    public string $defaultSlotDisplayType = 'radio';

    /**
     * @var bool Default show remaining capacity
     */
    public bool $defaultShowRemainingCapacity = true;

    /**
     * @var string Default date selection label
     */
    public string $defaultDateSelectionLabel = 'Select Date';

    /**
     * @var string Default slot selection label
     */
    public string $defaultSlotSelectionLabel = 'Select Time Slot';

    /**
     * @var string Default date placeholder
     */
    public string $defaultDatePlaceholder = 'Select a date...';

    /**
     * @var string Default slot placeholder
     */
    public string $defaultSlotPlaceholder = 'Select a time slot...';

    /**
     * @var string Default capacity template
     */
    public string $defaultCapacityTemplate = '{count} spot(s) left';

    /**
     * @var string Default fully booked text
     */
    public string $defaultFullyBookedText = 'Fully Booked';

    /**
     * @var string Default operating hours start
     */
    public string $defaultOperatingHoursStart = '09:00';

    /**
     * @var string Default operating hours end
     */
    public string $defaultOperatingHoursEnd = '17:00';

    /**
     * @var int Default slot duration in minutes
     */
    public int $defaultSlotDuration = 60;

    /**
     * @var int Default max capacity per slot
     */
    public int $defaultMaxCapacityPerSlot = 10;

    /**
     * @var string Default date display format
     */
    public string $defaultDateDisplayFormat = 'F jS, Y';

    /**
     * @var string Default time display format
     */
    public string $defaultTimeDisplayFormat = 'g:i A';

    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        return [
            ['pluginName', 'string'],
            ['pluginName', 'default', 'value' => 'Formie Booking Slot Field'],
            [['defaultDateDisplayType', 'defaultSlotDisplayType'], 'string'],
            ['defaultShowRemainingCapacity', 'boolean'],
            [['defaultDateSelectionLabel', 'defaultSlotSelectionLabel', 'defaultDatePlaceholder', 'defaultSlotPlaceholder', 'defaultCapacityTemplate', 'defaultFullyBookedText', 'defaultOperatingHoursStart', 'defaultOperatingHoursEnd', 'defaultDateDisplayFormat', 'defaultTimeDisplayFormat'], 'string'],
            [['defaultSlotDuration', 'defaultMaxCapacityPerSlot'], 'integer'],
        ];
    }

    /**
     * Check if a setting is overridden in config file
     *
     * @param string $setting
     * @return bool
     */
    public function isOverriddenByConfig(string $setting): bool
    {
        $configFileSettings = \Craft::$app->getConfig()->getConfigFromFile('formie-booking-slot-field');
        return isset($configFileSettings[$setting]);
    }
}
