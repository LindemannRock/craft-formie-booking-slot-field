<?php
/**
 * Formie Booking Slot Field plugin for Craft CMS 5.x
 *
 * @link      https://lindemannrock.com
 * @copyright Copyright (c) 2025 LindemannRock
 */

namespace lindemannrock\formiebookingslotfield\web\assets\field;

use craft\web\AssetBundle;

/**
 * Asset bundle for the Booking Slot field CP settings
 * Includes Flatpickr for date picking in the field editor
 */
class BookingSlotCpAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = __DIR__;

        // Include Flatpickr library
        $this->css = [
            'flatpickr.min.css',
        ];

        $this->js = [
            'flatpickr.min.js',
            'settings.js',
        ];

        parent::init();
    }
}
