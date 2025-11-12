# Booking Slot Field Assets

This directory contains the CSS and JavaScript assets for the Formie Booking Slot Field module.

## Files

- **booking-slot.js** - The main JavaScript file that handles the booking slot field functionality
- **booking-slot.min.js** - Minified version of booking-slot.js (used in production)
- **booking-slot.css** - Styles for the booking slot field
- **booking-slot.min.css** - Minified version of booking-slot.css (used in production)
- **BookingSlotFieldAsset.php** - Asset bundle that registers the CSS and JS files

## How it Works

1. When a Formie form with a booking slot field is rendered, the `BookingSlotFieldAsset` bundle is registered
2. The JavaScript initializes the booking slot field on page load
3. It handles date selection and dynamically enables time slot selection
4. Capacity tracking updates slot availability based on existing submissions
5. Supports both radio button and dropdown display modes

## JavaScript API

The JavaScript exposes a global `FormieBookingSlot` class:

```javascript
// Automatically initialized by Formie
new FormieBookingSlot({
    $form: formElement,
    $field: fieldElement,
    settings: {
        availableDates: [...],
        timeSlots: [...],
        slotAvailability: {...},
        maxCapacityPerSlot: 16,
        showRemainingCapacity: true
    }
});
```

## CSS Classes

The CSS provides classes for:
- Container: `.fui-booking-slot-wrapper`
- Date options: `.fui-date-options`, `.fui-date-option`, `.fui-date-label`
- Slot options: `.fui-slot-options`, `.fui-slot-option`, `.fui-slot-label`
- Capacity display: `.fui-slot-capacity`
- States: `.is-selected`, `.is-disabled`, `.is-full`
- Selects: `.fui-date-select`, `.fui-slot-select`

## Integration with Formie

The field automatically integrates with Formie's system:
- Uses Formie's field configuration system
- Maintains compatibility with Formie's validation and submission
- Supports conditional logic
- Includes email templates

## Development

To modify the assets:

1. Edit `booking-slot.js` and `booking-slot.css`
2. Run `npm install` (first time only)
3. Run `npm run minify` to create minified versions
4. Test in both dev mode (unminified) and production mode (minified)

The asset bundle automatically uses the appropriate version based on Craft's `devMode` setting.

## Minification Commands

```bash
# Install dependencies
npm install

# Minify both CSS and JS
npm run minify

# Minify only CSS
npm run minify:css

# Minify only JS
npm run minify:js
```
