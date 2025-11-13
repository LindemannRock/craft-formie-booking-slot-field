<?php
/**
 * Formie Booking Slot Field plugin for Craft CMS 5.x
 *
 * Booking slot field for Formie - Provides date and time slot selection with capacity tracking
 *
 * @link      https://lindemannrock.com
 * @copyright Copyright (c) 2025 LindemannRock
 */

namespace lindemannrock\formiebookingslotfield;

use Craft;
use lindemannrock\formiebookingslotfield\fields\BookingSlot;
use lindemannrock\formiebookingslotfield\models\Settings;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\RegisterTemplateRootsEvent;
use craft\web\View;
use verbb\formie\events\RegisterFieldsEvent;
use verbb\formie\services\Fields;
use yii\base\Event;

/**
 * Formie Booking Slot Field Plugin
 *
 * @author    LindemannRock
 * @package   FormieBookingSlotField
 * @since     1.0.0
 *
 * @property-read Settings $settings
 * @method Settings getSettings()
 */
class FormieBookingSlotField extends Plugin
{
    /**
     * @var FormieBookingSlotField
     */
    public static FormieBookingSlotField $plugin;

    /**
     * @inheritdoc
     */
    public string $schemaVersion = '1.0.0';

    /**
     * @inheritdoc
     */
    public bool $hasCpSettings = true;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        // Set the alias for this plugin
        Craft::setAlias('@lindemannrock/formiebookingslotfield', __DIR__);
        Craft::setAlias('@formie-booking-slot-templates', __DIR__ . '/templates');

        // Register view paths for Formie
        if (Craft::$app->request->getIsSiteRequest()) {
            Event::on(
                View::class,
                View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
                function(RegisterTemplateRootsEvent $event) {
                    $event->roots['formie-booking-slot-field'] = __DIR__ . '/templates';
                }
            );
        }

        Event::on(
            View::class,
            View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $event) {
                $event->roots['formie-booking-slot-field'] = __DIR__ . '/templates';
            }
        );

        // Register our field
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELDS,
            function(RegisterFieldsEvent $event) {
                $event->fields[] = BookingSlot::class;

                Craft::info(
                    'Registered Booking Slot field for Formie',
                    __METHOD__
                );
            }
        );

        // Register Flatpickr enhancement for CP field settings
        if (Craft::$app->request->getIsCpRequest()) {
            Event::on(
                View::class,
                View::EVENT_END_BODY,
                function() {
                    Craft::$app->getView()->registerAssetBundle(
                        \lindemannrock\formiebookingslotfield\web\assets\field\BookingSlotCpAsset::class
                    );
                }
            );
        }

        // Set the plugin name from settings
        $settings = $this->getSettings();
        if ($settings && !empty($settings->pluginName)) {
            $this->name = $settings->pluginName;
        }

        Craft::info(
            'Formie Booking Slot Field plugin loaded',
            __METHOD__
        );
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate(
            'formie-booking-slot-field/settings',
            [
                'settings' => $this->getSettings(),
                'plugin' => $this,
            ]
        );
    }
}
