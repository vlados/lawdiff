# flux:icon

Flux uses the excellent Heroicons project for its icon collection. Heroicons is a set of beautiful and functional icons crafted by the fine folks at [Tailwind Labs](https://tailwindcss.com/)

Search for the exact icon you need over at [Heroicons](https://heroicons.com/)

## Basic Usage

```html
<flux:icon.bolt />
```

## Variants

There are four variants for each icon: outline (default), solid, mini, and micro.

```html
<flux:icon.bolt />                  <!-- 24px, outline -->
<flux:icon.bolt variant="solid" />  <!-- 24px, filled -->
<flux:icon.bolt variant="mini" />   <!-- 20px, filled -->
<flux:icon.bolt variant="micro" />  <!-- 16px, filled -->
```

## Sizes

Control the size (height/width) of an icon using the size-\* Tailwind utility.

```html
<flux:icon.bolt class="size-12" />
<flux:icon.bolt class="size-10" />
<flux:icon.bolt class="size-8" />
```

Avoid tweaking icon sizes. Each variant is specifically designed for its default size.

## Color

You can customize the color of an icon using Tailwind's [text color utilities](https://tailwindcss.com/docs/text-color)

```html
<flux:icon.bolt variant="solid" class="text-amber-500 dark:text-amber-300" />
```

## Loading Spinner

Flux has a special loading spinner icon that isn't part of the Heroicons collection. You can use this special icon anywhere you would normally use a standard icon.

```html
<flux:icon.loading />
```

## Lucide Icons

We love Heroicons, but we acknowledge that it's a fairly limited icon set. If you need more icons, we recommend using [Lucide](https://lucide.dev/) instead.

To make it trivial to use Lucide icons, Flux provides a convenient artisan command to import them into your project:

```bash
php artisan flux:icon
```

This command will prompt you to select which icons you want to import. You can also manually specify the icons you want to import by passing in their names as arguments to the command:

```bash
php artisan flux:icon crown grip-vertical github
```

Once imported, you can use them just like Heroicons:

```html
<flux:icon.crown />
<flux:icon.grip-vertical />
<flux:icon.github />
```

## Custom Icons

For full control over your icons, you can create your own Blade files in the resources/views/flux/icon directory in your project.

```
- resources
    - views
        - flux
            - icon
                - wink.blade.php
```

You can simply paste SVG code directly into the Blade file, however we recommend using the following template structure to ensure compatibility with other components:

```php
@php $attributes = $unescapedForwardedAttributes ?? $attributes; @endphp
@props([
    'variant' => 'outline',
])

@php
$classes = Flux::classes('shrink-0')
    ->add(match($variant) {
        'outline' => '[:where(&)]:size-6',
        'solid' => '[:where(&)]:size-6',
        'mini' => '[:where(&)]:size-5',
        'micro' => '[:where(&)]:size-4',
    });
@endphp

{{-- Your SVG code here: --}}
<svg {{ $attributes->class($classes) }} data-flux-icon aria-hidden="true" ... >
    ...
</svg>
```

Then you can use your custom icon:

```html
<flux:icon.wink />
```

## Common Usage Examples

### Icons in Buttons

```html
<flux:button icon="plus">Add Item</flux:button>
<flux:button icon:trailing="arrow-right">Next</flux:button>
<flux:button icon="trash" variant="danger" />
```

### Icons with Text

```html
<div class="flex items-center gap-2">
    <flux:icon.check-circle class="text-green-500" />
    <span>Payment successful</span>
</div>
```

### Icons in Navigation

```html
<flux:navlist>
    <flux:navlist.item href="#" icon="home">Dashboard</flux:navlist.item>
    <flux:navlist.item href="#" icon="user">Profile</flux:navlist.item>
    <flux:navlist.item href="#" icon="cog">Settings</flux:navlist.item>
</flux:navlist>
```

### Decorative Icons

```html
<div class="flex flex-col items-center text-center">
    <flux:icon.shield-check class="size-12 text-blue-500 mb-2" />
    <flux:heading>Secure Payments</flux:heading>
    <flux:text>All transactions are secured with 256-bit encryption.</flux:text>
</div>
```

### Status Indicators

```html
<div class="flex items-center gap-2">
    <flux:icon.check-circle class="text-green-500" variant="solid" />
    <span>Active</span>
</div>

<div class="flex items-center gap-2">
    <flux:icon.exclamation-triangle class="text-amber-500" variant="solid" />
    <span>Warning</span>
</div>

<div class="flex items-center gap-2">
    <flux:icon.x-circle class="text-red-500" variant="solid" />
    <span>Error</span>
</div>
```

## Reference

### flux:icon.*

All icon components (e.g., flux:icon.bolt, flux:icon.loading) share the same props and behavior.

| Prop | Description |
| --- | --- |
| `variant` | Visual style of the icon. Options: outline (default), solid, mini, micro. |

| Class | Description |
| --- | --- |
| `size-*` | Control the size of the icon using Tailwind's size utilities (e.g., size-8, size-12). |
| `text-*` | Control the color of the icon using Tailwind's text color utilities (e.g., text-blue-500). |

| Attribute | Description |
| --- | --- |
| `data-flux-icon` | Applied to the root SVG element for styling and identification. |

### Icon Sizes

| Size | Description |
| --- | --- |
| outline | 24x24 pixels (default) |
| solid | 24x24 pixels |
| mini | 20x20 pixels |
| micro | 16x16 pixels |