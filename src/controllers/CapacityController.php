<?php
/**
 * Formie Booking Slot Field plugin for Craft CMS 5.x
 *
 * @link      https://lindemannrock.com
 * @copyright Copyright (c) 2025 LindemannRock
 */

namespace lindemannrock\formiebookingslotfield\controllers;

use Craft;
use craft\web\Controller;
use verbb\formie\elements\Form;
use yii\web\Response;

/**
 * Capacity Controller
 *
 * Provides API endpoints for refreshing booking slot capacity data
 * Used for static caching scenarios (Blitz, etc.)
 *
 * @author LindemannRock
 * @since 2.0.0
 */
class CapacityController extends Controller
{
    /**
     * @inheritdoc
     */
    protected array|bool|int $allowAnonymous = true;

    /**
     * Get fresh capacity data for a booking slot field
     *
     * GET /actions/formie-booking-slot-field/capacity/get?formId=123&fieldHandle=bookingSlot
     *
     * @return Response
     */
    public function actionGet(): Response
    {
        $formId = Craft::$app->getRequest()->getQueryParam('formId');
        $fieldHandle = Craft::$app->getRequest()->getQueryParam('fieldHandle');

        if (!$formId || !$fieldHandle) {
            return $this->asJson([
                'success' => false,
                'error' => 'Missing formId or fieldHandle parameter',
            ]);
        }

        // Get the form
        $form = Form::find()->id($formId)->one();

        if (!$form) {
            return $this->asJson([
                'success' => false,
                'error' => 'Form not found',
            ]);
        }

        // Get the field from the form layout
        $field = null;
        $layout = $form->getFormFieldLayout();

        if ($layout) {
            foreach ($layout->getCustomFields() as $customField) {
                if ($customField->handle === $fieldHandle) {
                    $field = $customField;
                    break;
                }
            }
        }

        if (!$field) {
            return $this->asJson([
                'success' => false,
                'error' => 'Field not found in form',
            ]);
        }

        // Check if field is actually a BookingSlot field
        if (!method_exists($field, 'getSlotAvailability')) {
            return $this->asJson([
                'success' => false,
                'error' => 'Field is not a BookingSlot field',
            ]);
        }

        // Get fresh availability data
        $availability = $field->getSlotAvailability();

        return $this->asJson([
            'success' => true,
            'availability' => $availability,
        ]);
    }
}
