# CSS Customization Guide

The Formie Booking Slot Field uses CSS custom properties (CSS variables) for easy customization without modifying the plugin files.

## Quick Start

Add your custom CSS to your site's stylesheet:

```css
:root {
    /* Change the primary color */
    --booking-slot-primary-color: #your-color;
    --booking-slot-selected-bg: #your-color;
}
```

## Available CSS Variables

### Colors

| Variable | Default | Description |
|----------|---------|-------------|
| `--booking-slot-primary-color` | `#2d5016` | Primary brand color |
| `--booking-slot-primary-hover` | `#365a1c` | Hover state color |
| `--booking-slot-border-color` | `#e5e7eb` | Border color for options |
| `--booking-slot-border-hover` | `#9ca3af` | Border color on hover |
| `--booking-slot-bg-color` | `white` | Background color |
| `--booking-slot-text-color` | `#1f2937` | Text color |
| `--booking-slot-disabled-opacity` | `0.5` | Opacity for disabled/full slots |

### Selected State

| Variable | Default | Description |
|----------|---------|-------------|
| `--booking-slot-selected-bg` | `#2d5016` | Background when selected |
| `--booking-slot-selected-color` | `white` | Text color when selected |
| `--booking-slot-selected-border` | `#2d5016` | Border color when selected |

### Capacity Display

| Variable | Default | Description |
|----------|---------|-------------|
| `--booking-slot-capacity-available-color` | `#059669` | Color for available spots |
| `--booking-slot-capacity-full-color` | `#dc2626` | Color for fully booked |
| `--booking-slot-capacity-font-size` | `0.8125rem` | Font size for capacity text |

### Layout & Spacing

| Variable | Default | Description |
|----------|---------|-------------|
| `--booking-slot-option-padding` | `0.75rem 1.5rem` | Padding for buttons |
| `--booking-slot-option-border-radius` | `0.5rem` | Border radius for buttons |
| `--booking-slot-option-border-width` | `2px` | Border width |
| `--booking-slot-option-gap` | `1rem` | Gap between options |
| `--booking-slot-wrapper-gap` | `1.5rem` | Gap between sections |
| `--booking-slot-label-margin` | `0.75rem` | Margin below labels |

### Transitions

| Variable | Default | Description |
|----------|---------|-------------|
| `--booking-slot-transition` | `all 0.2s ease` | Transition timing |

## Example Customizations

### Brand Color Customization

```css
:root {
    --booking-slot-primary-color: #0066cc;
    --booking-slot-primary-hover: #0052a3;
    --booking-slot-selected-bg: #0066cc;
    --booking-slot-selected-border: #0066cc;
}
```

### Larger Buttons

```css
:root {
    --booking-slot-option-padding: 1rem 2rem;
    --booking-slot-option-border-radius: 0.75rem;
    --booking-slot-option-gap: 1.5rem;
}
```

### Custom Capacity Colors

```css
:root {
    --booking-slot-capacity-available-color: #10b981;
    --booking-slot-capacity-full-color: #ef4444;
}
```

### Rounded Design

```css
:root {
    --booking-slot-option-border-radius: 2rem;
}
```

### Minimal/Flat Design

```css
:root {
    --booking-slot-option-border-width: 1px;
    --booking-slot-border-color: #d1d5db;
    --booking-slot-transition: none;
}
```

## Advanced Customization

### Direct Class Targeting

You can also target specific classes directly:

```css
/* Date selection buttons */
.fui-date-option {
    /* Custom styles */
}

.fui-date-label {
    /* Custom label styles */
}

.fui-date-option.is-selected .fui-date-label {
    /* Selected state */
}

/* Time slot buttons */
.fui-slot-option {
    /* Custom styles */
}

.fui-slot-label {
    /* Custom label styles */
}

.fui-slot-capacity {
    /* Capacity indicator */
}

/* Dropdown selects */
.fui-select {
    /* Custom dropdown styles */
}
```

### Responsive Customization

```css
@media (max-width: 768px) {
    :root {
        --booking-slot-option-padding: 0.5rem 1rem;
        --booking-slot-option-gap: 0.75rem;
    }
}
```

## Formie Theme Config

You can also customize via Formie's theme config in `config/formie.php`:

```php
'themeConfig' => [
    'bookingSlot' => [
        'fieldDateSelect' => [
            'attributes' => [
                'class' => 'custom-date-select-class',
            ],
        ],
        'fieldSlotSelect' => [
            'attributes' => [
                'class' => 'custom-slot-select-class',
            ],
        ],
    ],
],
```

## Tips

1. **Use CSS variables** for global changes (colors, spacing)
2. **Use direct classes** for specific element styling
3. **Use Formie theme config** for adding utility classes (Tailwind, etc.)
4. Test both **radio button** and **dropdown** display modes
5. Check **mobile responsiveness** after customization

## Support

For questions about styling, see the main [README](../README.md) or contact support@lindemannrock.com.
