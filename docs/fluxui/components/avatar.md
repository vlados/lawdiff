# flux:avatar

Display an image or initials as an avatar for users or entities.

## Basic Usage

```html
<flux:avatar src="https://unavatar.io/x/calebporzio" />
```

## Tooltip

Use the tooltip prop to display a tooltip when hovering over the avatar.

```html
<flux:avatar tooltip="Caleb Porzio" src="https://unavatar.io/x/calebporzio" />

<!-- Or infer from the name prop... -->
<flux:avatar tooltip name="Caleb Porzio" src="https://unavatar.io/x/calebporzio" />
```

## Initials

When no src is provided, the name prop will be used to automatically generate initials. You can also use the initials prop directly.

```html
<flux:avatar name="Caleb Porzio" />
<flux:avatar name="calebporzio" />
<flux:avatar name="calebporzio" initials:single />

<!-- Or use the initials prop directly... -->
<flux:avatar initials="CP" />
```

## Size

Use the size prop to change the size of the avatar.

```html
<!-- Extra large: size-16 (64px) -->
<flux:avatar size="xl" src="https://unavatar.io/x/calebporzio" />

<!-- Large: size-12 (48px) -->
<flux:avatar size="lg" src="https://unavatar.io/x/calebporzio" />

<!-- Default: size-10 (40px) -->
<flux:avatar src="https://unavatar.io/x/calebporzio" />

<!-- Small: size-8 (32px) -->
<flux:avatar size="sm" src="https://unavatar.io/x/calebporzio" />

<!-- Extra small: size-6 (24px) -->
<flux:avatar size="xs" src="https://unavatar.io/x/calebporzio" />
```

## Icon

Use the icon prop to display an icon instead of an image.

```html
<flux:avatar icon="user" />
<flux:avatar icon="phone" />
<flux:avatar icon="computer-desktop" />
```

## Colors

Use the color prop to change the color of the avatar.

```html
<flux:avatar name="Caleb Porzio" color="red" />
<flux:avatar name="Caleb Porzio" color="orange" />
<flux:avatar name="Caleb Porzio" color="amber" />
<flux:avatar name="Caleb Porzio" color="yellow" />
<flux:avatar name="Caleb Porzio" color="lime" />
<flux:avatar name="Caleb Porzio" color="green" />
<flux:avatar name="Caleb Porzio" color="emerald" />
<flux:avatar name="Caleb Porzio" color="teal" />
<flux:avatar name="Caleb Porzio" color="cyan" />
<flux:avatar name="Caleb Porzio" color="sky" />
<flux:avatar name="Caleb Porzio" color="blue" />
<flux:avatar name="Caleb Porzio" color="indigo" />
<flux:avatar name="Caleb Porzio" color="violet" />
<flux:avatar name="Caleb Porzio" color="purple" />
<flux:avatar name="Caleb Porzio" color="fuchsia" />
<flux:avatar name="Caleb Porzio" color="pink" />
<flux:avatar name="Caleb Porzio" color="rose" />
```

## Auto Color

Deterministically generate a color based on a user's name.

```html
<flux:avatar name="Caleb Porzio" color="auto" />

<!-- Use color:seed to generate a consistent color based -->
<!-- on something unchanging like a user's ID... -->
<flux:avatar name="Caleb Porzio" color="auto" color:seed="{{ $user->id }}" />
```

## Circle

Use the circle prop to make the avatar circular.

```html
<flux:avatar circle src="https://unavatar.io/x/calebporzio" />
```

## Badge

Add badges to avatars in various ways. Use the badge prop by itself for a simple dot indicator, provide content like numbers or emojis, or even pass in custom HTML via a slot.

```html
<flux:avatar badge badge:color="green" src="https://unavatar.io/x/calebporzio" />

<flux:avatar 
    badge 
    badge:color="zinc" 
    badge:position="top right" 
    badge:circle 
    badge:variant="outline" 
    src="https://unavatar.io/x/calebporzio" 
/>

<flux:avatar badge="25" src="https://unavatar.io/x/calebporzio" />

<flux:avatar circle badge="👍" badge:circle src="https://unavatar.io/x/calebporzio" />

<flux:avatar circle src="https://unavatar.io/x/calebporzio">
    <x-slot:badge>
        <img class="size-3" src="https://unavatar.io/github/hugosaintemarie" />
    </x-slot:badge>
</flux:avatar>
```

## Groups

Stack avatars together. By default, grouped avatars have rings that adapt to your theme - white in light mode and a dark color in dark mode. If you need to customize the ring color, to match a different background, you can do so by adding a custom class to the flux:avatar.group component.

```html
<flux:avatar.group>
    <flux:avatar src="https://unavatar.io/x/calebporzio" />
    <flux:avatar src="https://unavatar.io/github/hugosaintemarie" />
    <flux:avatar src="https://unavatar.io/github/joshhanley" />
    <flux:avatar>3+</flux:avatar>
</flux:avatar.group>

<!-- Adapt rings to custom background... -->
<flux:avatar.group class="**:ring-zinc-100 dark:**:ring-zinc-800">
    <flux:avatar circle src="https://unavatar.io/x/calebporzio" />
    <flux:avatar circle src="https://unavatar.io/github/hugosaintemarie" />
    <flux:avatar circle src="https://unavatar.io/github/joshhanley" />
    <flux:avatar circle>3+</flux:avatar>
</flux:avatar.group>
```

## As Button

Use the as prop to make the avatar a button.

```html
<flux:avatar as="button" src="https://unavatar.io/x/calebporzio" />
```

## As Link

Use the href prop to make the avatar a link.

```html
<flux:avatar href="https://x.com/calebporzio" src="https://unavatar.io/x/calebporzio" />
```

## Examples

### Testimonial

```html
<div>
    <div class="flex items-center gap-2">
        <flux:icon.star variant="solid" />
        <flux:icon.star variant="solid" />
        <flux:icon.star variant="solid" />
        <flux:icon.star variant="solid" />
        <flux:icon.star variant="solid" />
    </div>
    <flux:heading size="xl" class="mt-4 italic">
        <p>IMO Livewire takes Blade to the next level. It's basically what Blade should be by default. 🔥</p>
    </flux:heading>
    <div class="mt-6 flex items-center gap-4">
        <flux:avatar size="lg" src="https://unavatar.io/x/taylorotwell" />
        <div>
            <flux:heading size="lg">Taylor Otwell</flux:heading>
            <flux:text>Creator of Laravel</flux:text>
        </div>
    </div>
</div>
```

### Grouped Feature

```html
<div>
    <flux:heading size="xl">The Laravel Podcast <flux:badge inset="top bottom" class="ml-1 max-sm:hidden">New</flux:badge></flux:heading>
    <flux:text class="mt-2">
        A podcast about Laravel, development best practices, and the PHP ecosystem—hosted by Jeffrey Way, Matt Stauffer, and Taylor Otwell, later joined by Adam Wathan.
    </flux:text>
    <flux:avatar.group class="mt-6">
        <flux:avatar circle size="lg" src="https://unavatar.io/x/taylorotwell" />
        <flux:avatar circle size="lg" src="https://unavatar.io/x/adamwathan" />
        <flux:avatar circle size="lg" src="https://unavatar.io/x/jeffrey_way" />
        <flux:avatar circle size="lg" src="https://unavatar.io/x/stauffermatt" />
    </flux:avatar.group>
</div>
```

### Select Options

Enhance select options with avatars to make them more visually recognizable.

```html
<flux:select variant="listbox" label="Assign to">
    <flux:select.option selected>
        <div class="flex items-center gap-2 whitespace-nowrap">
            <flux:avatar circle size="xs" src="https://unavatar.io/github/calebporzio" /> Caleb Porzio
        </div>
    </flux:select.option>
    <flux:select.option>
        <div class="flex items-center gap-2 whitespace-nowrap">
            <flux:avatar circle size="xs" src="https://unavatar.io/github/hugosaintemarie" /> Hugo Sainte-Marie
        </div>
    </flux:select.option>
    <flux:select.option>
        <div class="flex items-center gap-2 whitespace-nowrap">
            <flux:avatar circle size="xs" src="https://unavatar.io/github/joshhanley" /> Josh Hanley
        </div>
    </flux:select.option>
    <flux:select.option>
        <div class="flex items-center gap-2 whitespace-nowrap">
            <flux:avatar circle size="xs" src="https://unavatar.io/github/jasonlbeggs" /> Jason Beggs
        </div>
    </flux:select.option>
</flux:select>
```

### User Popover

Display a user's profile information in a popover.

```html
<flux:dropdown hover position="bottom center">
    <flux:avatar as="button" name="calebporzio" src="https://unavatar.io/x/calebporzio" />
    <flux:popover class="relative max-w-[15rem]">
        <div class="absolute top-0 right-0 p-2">
            <flux:button icon="user-plus" variant="filled" size="sm">Follow back</flux:button>
        </div>
        <flux:avatar size="xl" name="calebporzio" src="https://unavatar.io/x/calebporzio" />
        <flux:heading class="mt-2">Caleb Porzio</flux:heading>
        <flux:text>@calebporzio <flux:badge color="zinc" size="sm" inset="top bottom">Follows you</flux:badge></flux:text>
        <flux:text class="mt-3">
            I'm a full stack developer with a passion for building web applications. Currently working on a new project called <flux:link href="https://fluxui.dev">Flux</flux:link>.
        </flux:text>
        <div class="flex gap-4 mt-3">
            <div class="flex gap-2 items-center">
                <flux:heading>1.2k</flux:heading> <flux:text>Followers</flux:text>
            </div>
            <div class="flex gap-2 items-center">
                <flux:heading>1.2k</flux:heading> <flux:text>Following</flux:text>
            </div>
        </div>
    </flux:popover>
</flux:dropdown>
```

## Reference

### flux:avatar

| Prop | Description |
| --- | --- |
| `name` | User's name to display as initials. If provided without initials, this will be used to generate initials automatically. |
| `src` | URL to the image to display as avatar. |
| `initials` | Custom initials to display when no src is provided. Will override name if provided. |
| `alt` | Alternative text for the avatar image. (Default: name if provided) |
| `size` | Size of the avatar. Options: xs (24px), sm (32px), (default: 40px), lg (48px). |
| `color` | Background color when displaying initials or icons. Options: red, orange, amber, yellow, lime, green, emerald, teal, cyan, sky, blue, indigo, violet, purple, fuchsia, pink, rose, auto. Default: none (uses system colors). |
| `color:seed` | Value used when color="auto" to deterministically generate consistent colors. Useful for using user IDs to generate consistent colors. |
| `circle` | If present or true, makes the avatar fully circular instead of rounded corners. |
| `icon` | Name of the icon to display instead of an image or initials. |
| `icon:variant` | Icon variant to use. Options: outline, solid. Default: solid. |
| `tooltip` | Text to display in a tooltip when hovering over the avatar. If set to true, uses the name prop as tooltip content. |
| `tooltip:position` | Position of the tooltip. Options: top, right, bottom, left. Default: top. |
| `badge` | Content to display as a badge. Can be a string, boolean, or a slot. |
| `badge:color` | Color of the badge. Options: same color options as the color prop. |
| `badge:circle` | If present or true, makes the badge fully circular instead of slightly rounded corners. |
| `badge:position` | Position of the badge. Options: top left, top right, bottom left, bottom right. Default: bottom right. |
| `badge:variant` | Variant of the badge. Options: solid, outline. Default: solid. |
| `as` | Element to render the avatar as. Options: button, div (default). |
| `href` | URL to link to, making the avatar a link element. |

| Slot | Description |
| --- | --- |
| `default` | Custom content to display inside the avatar. Will override initials if provided. |
| `badge` | Custom content to display in the badge (for more complex badge content). |

### flux:avatar.group

| Prop | Description |
| --- | --- |
| `class` | CSS classes to apply to the group, including customizing ring colors using \*:ring-{color} format. |

| Slot | Description |
| --- | --- |
| `default` | Place multiple flux:avatar components here to display them as a group. |