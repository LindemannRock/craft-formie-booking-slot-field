<?php
/**
 * Formie Booking Slot Field plugin for Craft CMS 5.x
 *
 * @link      https://lindemannrock.com
 * @copyright Copyright (c) 2025 LindemannRock
 */

namespace lindemannrock\formiebookingslotfield\web\assets\field;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use Craft;

/**
 * Asset bundle for the Booking Slot field
 */
class BookingSlotFieldAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = __DIR__;

        // Use minified versions in production
        $isDevMode = Craft::$app->getConfig()->getGeneral()->devMode;

        $this->css = [
            $isDevMode ? 'booking-slot.css' : 'booking-slot.min.css',
        ];

        $this->js = [
            $isDevMode ? 'booking-slot.js' : 'booking-slot.min.js',
        ];

        // Add settings page JS if in CP
        if (Craft::$app->request->getIsCpRequest()) {
            $this->js[] = 'settings.js';
        }

        parent::init();
    }
}
