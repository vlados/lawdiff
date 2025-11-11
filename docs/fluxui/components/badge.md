# flux:badge

Highlight information like status, category, or count.

## Basic Usage

```html
<flux:badge color="lime">New</flux:badge>
```

## Sizes

Choose between three different sizes for your badges with the size prop.

```html
<flux:badge size="sm">Small</flux:badge>
<flux:badge>Default</flux:badge>
<flux:badge size="lg">Large</flux:badge>
```

## Icons

Add icons to badges with the icon and icon:trailing props.

```html
<flux:badge icon="user-circle">Users</flux:badge>
<flux:badge icon="document-text">Files</flux:badge>
<flux:badge icon:trailing="video-camera">Videos</flux:badge>
```

## Pill Variant

Display badges with a fully rounded border radius using the variant="pill" prop.

```html
<flux:badge variant="pill" icon="user">Users</flux:badge>
```

## As Button

Make the entire badge clickable by wrapping it in a button element.

```html
<flux:badge as="button" variant="pill" icon="plus" size="lg">Amount</flux:badge>
```

## With Close Button

Make a badge removable by appending a close button.

```html
<flux:badge>
    Admin <flux:badge.close />
</flux:badge>
```

## Colors

Choose from an array of colors to differentiate between badges and convey emotion.

```html
<flux:badge color="zinc">Zinc</flux:badge>
<flux:badge color="red">Red</flux:badge>
<flux:badge color="orange">Orange</flux:badge>
<flux:badge color="amber">Amber</flux:badge>
<flux:badge color="yellow">Yellow</flux:badge>
<flux:badge color="lime">Lime</flux:badge>
<flux:badge color="green">Green</flux:badge>
<flux:badge color="emerald">Emerald</flux:badge>
<flux:badge color="teal">Teal</flux:badge>
<flux:badge color="cyan">Cyan</flux:badge>
<flux:badge color="sky">Sky</flux:badge>
<flux:badge color="blue">Blue</flux:badge>
<flux:badge color="indigo">Indigo</flux:badge>
<flux:badge color="violet">Violet</flux:badge>
<flux:badge color="purple">Purple</flux:badge>
<flux:badge color="fuchsia">Fuchsia</flux:badge>
<flux:badge color="pink">Pink</flux:badge>
<flux:badge color="rose">Rose</flux:badge>
```

## Solid Variant

Bold, high-contrast badges for more important status indicators or alerts.

```html
<flux:badge variant="solid" color="zinc">Zinc</flux:badge>
<flux:badge variant="solid" color="red">Red</flux:badge>
<flux:badge variant="solid" color="orange">Orange</flux:badge>
<flux:badge variant="solid" color="amber">Amber</flux:badge>
<flux:badge variant="solid" color="yellow">Yellow</flux:badge>
<flux:badge variant="solid" color="lime">Lime</flux:badge>
<flux:badge variant="solid" color="green">Green</flux:badge>
<flux:badge variant="solid" color="emerald">Emerald</flux:badge>
<flux:badge variant="solid" color="teal">Teal</flux:badge>
<flux:badge variant="solid" color="cyan">Cyan</flux:badge>
<flux:badge variant="solid" color="sky">Sky</flux:badge>
<flux:badge variant="solid" color="blue">Blue</flux:badge>
<flux:badge variant="solid" color="indigo">Indigo</flux:badge>
<flux:badge variant="solid" color="violet">Violet</flux:badge>
<flux:badge variant="solid" color="purple">Purple</flux:badge>
<flux:badge variant="solid" color="fuchsia">Fuchsia</flux:badge>
<flux:badge variant="solid" color="pink">Pink</flux:badge>
<flux:badge variant="solid" color="rose">Rose</flux:badge>
```

## Inset

If you're using badges alongside inline text, you might run into spacing issues because of the extra padding around the badge. Use the inset prop to add negative margins to the top and bottom of the badge to avoid this.

```html
<flux:heading>
    Page builder <flux:badge color="lime" inset="top bottom">New</flux:badge>
</flux:heading>
<flux:text class="mt-2">Easily author new pages without leaving your browser.</flux:text>
```

## Usage Examples

### Status Indicators

```html
<div class="flex gap-2">
    <flux:badge color="green">Active</flux:badge>
    <flux:badge color="yellow">Pending</flux:badge>
    <flux:badge color="red">Failed</flux:badge>
    <flux:badge color="zinc">Inactive</flux:badge>
</div>
```

### Tag Lists

```html
<div>
    <flux:heading>Blog Post Tags</flux:heading>
    <div class="flex flex-wrap gap-2 mt-2">
        <flux:badge as="button" variant="pill" color="blue">Laravel</flux:badge>
        <flux:badge as="button" variant="pill" color="blue">PHP</flux:badge>
        <flux:badge as="button" variant="pill" color="blue">Web Development</flux:badge>
        <flux:badge as="button" variant="pill" color="blue">Livewire</flux:badge>
        <flux:badge as="button" variant="pill" icon="plus">Add Tag</flux:badge>
    </div>
</div>
```

### Notification Count

```html
<flux:button>
    Notifications
    <flux:badge color="red" inset="top bottom left" class="ml-2">5</flux:badge>
</flux:button>
```

### Feature Labels

```html
<flux:card>
    <flux:heading>
        Advanced Analytics <flux:badge color="purple" inset="top bottom">Pro</flux:badge>
    </flux:heading>
    <flux:text class="mt-2">
        Get detailed insights into your application's performance and user behavior.
    </flux:text>
</flux:card>
```

### Removable Tags

```html
<div class="flex flex-wrap gap-2">
    <flux:badge variant="pill" color="indigo">
        Design <flux:badge.close />
    </flux:badge>
    <flux:badge variant="pill" color="indigo">
        Development <flux:badge.close />
    </flux:badge>
    <flux:badge variant="pill" color="indigo">
        Marketing <flux:badge.close />
    </flux:badge>
</div>
```

## Reference

### flux:badge

| Prop | Description |
| --- | --- |
| `color` | Badge color (e.g., zinc, red, blue). Default: zinc. |
| `size` | Badge size. Options: sm, lg. |
| `variant` | Badge style variant. Options: pill, solid. |
| `icon` | Name of the icon to display before the badge text. |
| `icon:trailing` | Name of the icon to display after the badge text. |
| `icon:variant` | Icon variant. Options: outline, solid, mini, micro. Default: mini. |
| `as` | HTML element to render the badge as. Options: button. Default: div. |
| `inset` | Add negative margins to specific sides. Options: top, bottom, left, right, or any combination of the four. |

### flux:badge.close

| Prop | Description |
| --- | --- |
| `icon` | Name of the icon to display. Default: x-mark. |
| `icon:variant` | Icon variant. Options: outline, solid, mini, micro. Default: mini. |