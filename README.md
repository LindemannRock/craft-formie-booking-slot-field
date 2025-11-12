# Formie Booking Slot Field Plugin

[![Latest Version](https://img.shields.io/packagist/v/lindemannrock/craft-formie-booking-slot-field.svg)](https://packagist.org/packages/lindemannrock/craft-formie-booking-slot-field)
[![Craft CMS](https://img.shields.io/badge/Craft%20CMS-5.0+-orange.svg)](https://craftcms.com/)
[![Formie](https://img.shields.io/badge/Formie-3.0+-purple.svg)](https://verbb.io/craft-plugins/formie)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net/)
[![License](https://img.shields.io/packagist/l/lindemannrock/craft-formie-booking-slot-field.svg)](LICENSE)

A Craft CMS plugin that provides a flexible booking slot field for Verbb's Formie form builder, with date and time slot selection, automatic slot generation, and real-time capacity tracking.

## Requirements

- Craft CMS 5.0 or greater
- PHP 8.2 or greater
- Formie 3.0 or greater

## Features

### Flexible Date Configuration
- **Specific Dates**: Define exact dates for events and special occasions
- **Date Range**: Set start/end dates with automatic date generation
- **Day of Week Filtering**: Choose which weekdays are available (e.g., Mon-Fri only)
- **Blackout Dates**: Exclude specific dates (holidays, closures)

### Automatic Time Slot Generation
- **Operating Hours**: Set daily start/end times with hour/minute dropdowns
- **Slot Duration**: Choose from 15 minutes to 4 hours
- **Smart Generation**: Slots automatically created based on your rules

### Capacity Tracking
- **Real-time Availability**: Shows remaining spots per slot
- **Submission Status Integration**: Count only confirmed bookings, exclude cancellations
- **Full Slot Detection**: Automatically disables fully booked slots
- **Visual Indicators**: Clear display of available vs. full slots

### Display Options
- **Radio Buttons**: Visual button cards for dates and slots
- **Dropdown Selects**: Compact dropdowns for many options
- **Responsive Design**: Works seamlessly on all devices
- **Customizable**: Configurable per field

### Seamless Integration
- Native Formie field with full validation support
- Email notification templates included
- Control panel submission display
- GraphQL support for headless implementations

## Installation

### Via Composer

```bash
cd /path/to/project
```

```bash
composer require lindemannrock/craft-formie-booking-slot-field
```

```bash
./craft plugin/install formie-booking-slot-field
```

### Using DDEV

```bash
cd /path/to/project
```

```bash
ddev composer require lindemannrock/craft-formie-booking-slot-field
```

```bash
ddev craft plugin/install formie-booking-slot-field
```

### Via Control Panel

In the Control Panel, go to Settings → Plugins and click "Install" for Formie Booking Slot Field.

## Configuration

### Plugin Settings

Navigate to **Settings → Plugins → Formie Booking Slot Field** to configure the plugin name.

## Usage

### Adding a Booking Slot Field

1. Open your form in the Formie form builder
2. Click "Add Field" and select "Booking Slot" from the field types
3. Configure the field settings across four tabs:

#### General Tab

**Date Selection Mode**
- **Specific Dates**: For events with fixed dates (e.g., Dec 5-6, 2025)
  - Add dates in YYYY-MM-DD format (e.g., 2025-12-05)
  - Optional display labels (e.g., "December 5th, 2025")

- **Date Range**: For ongoing bookings (e.g., next 30 days)
  - Set start and end dates
  - Choose which days of week are available
  - Add blackout dates if needed

#### Settings Tab

**Time Configuration**
- **Start Time**: Hour and minute when bookings begin each day
- **End Time**: Hour and minute when bookings end each day
- **Slot Duration**: Choose from 15 min, 30 min, 1 hour, 2 hours, etc.

**Capacity Management**
- **Max Capacity Per Slot**: How many people per time slot
- **Show Remaining Capacity**: Display spots left to users
- **Count As Booked Statuses**: Select which submission statuses count (e.g., only "Confirmed")

**Display Options**
- **Date Display Type**: Radio buttons (visual) or Dropdown (compact)
- **Slot Display Type**: Radio buttons (visual) or Dropdown (compact)

#### Appearance Tab
- Label position, instructions, visibility settings

#### Advanced Tab
- Handle, CSS classes, container attributes

### Example Configurations

#### Event Booking (e.g., M Cars Driving Experience)
```
Date Mode: Specific Dates
Dates: 2025-12-05, 2025-12-06
Operating Hours: 10:00 - 22:00
Slot Duration: 2 hours
Max Capacity: 16
Display: Dropdown

Result: 12 slots (6 per day × 2 days), 192 total capacity
```

#### Doctor Appointments
```
Date Mode: Date Range
Start: 2025-01-01
End: 2025-01-31
Days: Mon-Fri
Operating Hours: 09:00 - 17:00
Slot Duration: 30 minutes
Max Capacity: 1
Display: Dropdown

Result: 16 slots/day × ~22 weekdays = ~352 appointments
```

#### Workshop Series
```
Date Mode: Specific Dates
Dates: Workshop dates
Operating Hours: 09:00 - 16:00
Slot Duration: 3 hours
Max Capacity: 25
Display: Radio Buttons

Result: 2-3 sessions per day with group registration
```

### Handling Cancellations

#### Option 1: Delete Submission (Simple)
- Go to Formie → Submissions
- Delete the booking
- Capacity automatically increases

#### Option 2: Status Tracking (Recommended)
1. **Set up submission statuses** in Formie → Settings → Statuses:
   - Add status: "Confirmed"
   - Add status: "Cancelled"

2. **Configure form default status**:
   - Form → Settings → Default Status: "Confirmed"

3. **Configure booking field**:
   - Settings → Count As Booked Statuses: ✅ Confirmed

4. **To cancel a booking**:
   - Find submission in Formie CP
   - Change status: Confirmed → Cancelled
   - Capacity automatically updates
   - Booking record preserved

### Templating

In your templates, the booking field is rendered automatically by Formie:

```twig
{# Render the entire form #}
{{ craft.formie.renderForm('bookingForm') }}

{# Or render a specific field #}
{% set form = craft.formie.forms.handle('bookingForm').one() %}
{{ craft.formie.renderField(form, 'bookingSlot') }}
```

### Accessing Booking Data

```twig
{# In email notifications or templates #}
{% set booking = submission.bookingSlotHandle %}
{{ booking.date }} {# e.g., 2025-12-05 #}
{{ booking.slot }} {# e.g., 10:00-12:00 #}
```

### GraphQL Support

Query booking slot data via GraphQL:

```graphql
query {
  formieSubmissions(form: "bookingForm") {
    ... on bookingForm_Submission {
      bookingSlot
    }
  }
}
```

## Field Settings Reference

### General Settings

| Setting | Description | Options |
|---------|-------------|---------|
| **Date Selection Mode** | How dates are configured | `specific`, `range` |
| **Specific Dates** | Individual dates (specific mode) | Table: Date, Label |
| **Start Date** | First available date (range mode) | Date picker |
| **End Date** | Last available date (range mode) | Date picker |
| **Days of Week** | Available weekdays (range mode) | Sun-Sat checkboxes |
| **Blackout Dates** | Dates to exclude | Table: Date |

### Settings Tab

| Setting | Description | Options |
|---------|-------------|---------|
| **Start Time - Hour** | Hour when bookings start | 00-23 |
| **Start Time - Minute** | Minute when bookings start | 00, 15, 30, 45 |
| **End Time - Hour** | Hour when bookings end | 00-23 |
| **End Time - Minute** | Minute when bookings end | 00, 15, 30, 45 |
| **Slot Duration** | Length of each booking slot | 15min-4hours |
| **Max Capacity Per Slot** | People per slot | Number |
| **Show Remaining Capacity** | Display spots left | true/false |
| **Count As Booked Statuses** | Which statuses count | Formie statuses |
| **Date Display Type** | How dates appear | `radio`, `select` |
| **Slot Display Type** | How slots appear | `radio`, `select` |

## File Structure

```
plugins/formie-booking-slot-field/
├── src/
│   ├── fields/
│   │   └── BookingSlot.php          # Main field class
│   ├── models/
│   │   └── Settings.php             # Plugin settings model
│   ├── templates/
│   │   ├── fields/
│   │   │   └── booking-slot/
│   │   │       ├── input.html       # Field input template
│   │   │       └── email.html       # Email template
│   │   └── settings.html            # Plugin settings template
│   ├── web/
│   │   └── assets/
│   │       └── field/
│   │           ├── BookingSlotFieldAsset.php
│   │           ├── booking-slot.js
│   │           ├── booking-slot.min.js
│   │           ├── booking-slot.css
│   │           └── booking-slot.min.css
│   └── FormieBookingSlotField.php   # Main plugin class
├── CHANGELOG.md
├── LICENSE.md
├── README.md
├── package.json
└── composer.json
```

## Development

### Building Minified Assets

After editing CSS or JavaScript:

```bash
cd plugins/formie-booking-slot-field
npm install           # First time only
npm run minify        # Build minified versions
```

The asset bundle automatically uses minified versions in production (when devMode is off).

## Support

- **Documentation**: [https://github.com/LindemannRock/craft-formie-booking-slot-field](https://github.com/LindemannRock/craft-formie-booking-slot-field)
- **Issues**: [https://github.com/LindemannRock/craft-formie-booking-slot-field/issues](https://github.com/LindemannRock/craft-formie-booking-slot-field/issues)
- **Email**: [support@lindemannrock.com](mailto:support@lindemannrock.com)

## License

This plugin is licensed under the MIT License. See [LICENSE](LICENSE) for details.

---

Developed by [LindemannRock](https://lindemannrock.com)

Built for use with [Formie](https://verbb.io/craft-plugins/formie) by Verbb
