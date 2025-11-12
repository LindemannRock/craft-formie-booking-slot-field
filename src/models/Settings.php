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
     * @inheritdoc
     */
    public function defineRules(): array
    {
        return [
            ['pluginName', 'string'],
            ['pluginName', 'default', 'value' => 'Formie Booking Slot Field'],
        ];
    }
}
