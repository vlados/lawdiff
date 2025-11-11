# flux:toast

A message that provides feedback to users about an action or event, often temporary and dismissible.

## Basic Usage

To use the Toast component from Livewire, you must include it somewhere on the page; often in your layout file:

```html
<body>
    <!-- ... -->
    <flux:toast />
</body>
```

If you are using wire:navigate to navigate between pages, you may want to persist the toast component so that toast messages don't suddenly disappear when navigating away from the page.

```html
<body>
    <!-- ... -->
    @persist('toast')
        <flux:toast />
    @endpersist
</body>
```

Once the toast component is present on the page, you can use the Flux::toast() method to trigger a toast message from your Livewire components:

```php
<?php
namespace App\Livewire;

use Livewire\Component;
use Flux\Flux;

class EditPost extends Component
{
    public function save()
    {
        // ...
        Flux::toast('Your changes have been saved.');
    }
}
```

You can also trigger a toast from Alpine directly using Flux's magic methods:

```html
<button x-on:click="$flux.toast('Your changes have been saved.')">
    Save changes
</button>
```

Or you can use the window.Flux global object to trigger a toast from any JavaScript in your application:

```html
<script>
    let button = document.querySelector('...')
    button.addEventListener('alpine:init', () => {
        Flux.toast('Your changes have been saved.')
    })
</script>
```

Both $flux and window.Flux support the following method parameter signatures:

```javascript
Flux.toast('Your changes have been saved.')

// Or...
Flux.toast({
    heading: 'Changes saved',
    text: 'Your changes have been saved.',
    variant: 'success',
})
```

## With Heading

Use a heading to provide additional context for the toast.

```php
Flux::toast(
    heading: 'Changes saved.',
    text: 'You can always update this in your settings.',
);
```

## Variants

Use the variant prop to change the visual style of the toast.

```php
Flux::toast(variant: 'success', ...);
Flux::toast(variant: 'warning', ...);
Flux::toast(variant: 'danger', ...);
```

## Positioning

By default, the toast will appear in the bottom right corner of the page. You can customize this position using the position prop.

```html
<flux:toast position="top right" />

<!-- Customize top padding for things like navbars... -->
<flux:toast position="top right" class="pt-24" />
```

## Duration

By default, the toast will automatically dismiss after 5 seconds. You can customize this duration by passing a number of milliseconds to the duration prop.

```php
// 1 second...
Flux::toast(duration: 1000, ...);
```

## Permanent

Use a value of 0 as the duration prop to make the toast stay open indefinitely.

```php
// Show indefinitely...
Flux::toast(duration: 0, ...);
```

## Additional Examples

### Success Toast with Action

```html
<button x-on:click="$flux.toast({
    heading: 'Success!',
    text: 'Your file has been uploaded successfully.',
    variant: 'success',
    actions: `<a href='#' class='font-medium underline'>View file</a>`
})">
    Upload File
</button>
```

### Toast for Error Feedback

```php
try {
    // Some operation that might fail
    $this->processPayment();
    
    Flux::toast(
        variant: 'success',
        heading: 'Payment successful',
        text: 'Your payment has been processed.'
    );
} catch (\Exception $e) {
    Flux::toast(
        variant: 'danger',
        heading: 'Payment failed',
        text: 'There was an error processing your payment. Please try again.'
    );
}
```

### Multiple Sequential Toasts

```html
<button x-on:click="
    $flux.toast('Step 1 complete');
    
    setTimeout(() => {
        $flux.toast({
            heading: 'Step 2 complete',
            variant: 'success'
        });
    }, 1000);
    
    setTimeout(() => {
        $flux.toast({
            heading: 'All steps complete',
            text: 'Your process has finished successfully',
            variant: 'success',
            duration: 0
        });
    }, 2000);
">
    Start Process
</button>
```

### Toast with Icon

By combining the toast with Heroicons or custom SVG:

```html
<button x-on:click="$flux.toast({
    heading: 'Export complete',
    text: `
        <div class='flex items-center'>
            <svg class='mr-2 h-5 w-5 text-green-500' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 13l4 4L19 7'></path>
            </svg>
            Your file has been exported successfully
        </div>
    `,
    variant: 'success'
})">
    Export File
</button>
```

## Reference

### flux:toast

| Prop | Description |
| --- | --- |
| `position` | Position of the toast on the screen. Options: bottom right (default), bottom left, top right, top left. |
| `duration` | Duration in milliseconds before the toast auto-dismisses. Use 0 for permanent toasts. Default: 5000. |
| `variant` | Visual style of the toast. Options: success, warning, danger. |

### Flux::toast()

The PHP method used to trigger toasts from Livewire components.

| Parameter | Description |
| --- | --- |
| `heading` | Optional heading text for the toast. |
| `text` | Main content text of the toast. |
| `variant` | Visual style. Options: success, warning, danger. |
| `duration` | Duration in milliseconds. Use 0 for permanent toasts. Default: 5000. |

### $flux.toast()

The Alpine.js magic method used to trigger toasts from Alpine components. It can be used in two ways:

```javascript
// Simple usage with just a message...
$flux.toast('Your changes have been saved')

// Advanced usage with full configuration...
$flux.toast({
    heading: 'Success!',
    text: 'Your changes have been saved',
    variant: 'success',
    duration: 3000
})
```

| Parameter | Description |
| --- | --- |
| `message` | A string containing the toast message. When using this simple form, the message becomes the toast's text content. |
| `options` | Alternatively, an object containing: - heading: Optional title text - text: Main message text - variant: Visual style (success, warning, danger) - duration: Display time in milliseconds |