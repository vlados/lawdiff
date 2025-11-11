# Dark Mode

Flux supports dark mode out of the box.

## Set Up Tailwind

To take full advantage of Flux's dark mode controls, you will need to ensure that Tailwind CSS is configured to use the selector strategy for dark mode by adding this to your resources/views/app.css file:

```css
@import "tailwindcss";
@import '../../vendor/livewire/flux/dist/flux.css';
@custom-variant dark (&:where(.dark, .dark *));
```

By doing this, Flux can now toggle on and off dark mode by adding/removing a .dark class to the `<html>` element of your application.

## Disabling Dark Mode Handling

By default, Flux will handle the appearance of your application by adding a dark class to the html element depending on the user's system preference or selected appearance.

If you don't want Flux to handle this for you, you can remove the @fluxAppearance directive from your layout file:

```html
<head>
    ...
    <!-- Remove this line -->
    @fluxAppearance
</head>
```

Now you can handle the appearance of your application manually.

## JavaScript Utilities

Managing dark mode in your own application is cumbersome. Here are a few essential behaviors you have to implement:

* Add/remove the .dark class to the `<html>` element
* Store the users preference in local storage
* Honor the system preference if system is selected
* Listen for changes in the system preference after a page has loaded

To save you from this complexity, Flux provides two JavaScript/Alpine utilities for making it easy to manage dark mode:

```javascript
// Get/set a users color scheme preference...
$flux.appearance = 'light|dark|system'

// Get/set the current color scheme of your app...
$flux.dark = 'true|false'
```

Given these two utilities, you can now use Alpine to easily build widgets to manage dark mode.

For example, here's how you would write a simple dark mode toggle button:

```html
<flux:button x-data x-on:click="$flux.dark = ! $flux.dark">Toggle</flux:button>
```

Or if you wanted to allow a user to choose their preferred color scheme, you could write:

```html
<flux:radio.group x-data x-model="$flux.appearance">
    <flux:radio value="light">Light</flux:radio>
    <flux:radio value="dark">Dark</flux:radio>
    <flux:radio value="system">System</flux:radio>
</flux:radio.group>
```

If you want to use these utilities outside of Alpine, you can instead access .appearance and .dark on the global window.Flux JavaScript object from anywhere in your application:

```javascript
let button = document.querySelector('...')
button.addEventListener('click', () => {
    Flux.dark = ! Flux.dark
})
```

## Examples

Rather than offer a one-size-fits-~~all~~none solution, Flux provides a few examples of how you can use these utilities to build your own dark mode controls.

### Dropdown Menu

More robust than a simple toggle button, this dropdown menu allows users to choose between light, dark, and system modes:

```html
<flux:dropdown>
    <flux:button>
        <template x-data x-if="$flux.dark">
            <flux:icon.moon variant="micro" class="size-4!" />
        </template>
        <template x-data x-if="!$flux.dark">
            <flux:icon.sun variant="micro" class="size-4!" />
        </template>
    </flux:button>
    <flux:menu>
        <div class="px-2 py-2 text-xs text-zinc-600 dark:text-zinc-400">
            Appearance
        </div>
        <flux:menu.radio.group x-data x-model="$flux.appearance">
            <flux:menu.radio value="light" icon="sun">Light</flux:menu.radio>
            <flux:menu.radio value="dark" icon="moon">Dark</flux:menu.radio>
            <flux:menu.radio value="system" icon="computer-desktop">System</flux:menu.radio>
        </flux:menu.radio.group>
    </flux:menu>
</flux:dropdown>
```

### Toggle Button

A simple toggle button for toggling between light and dark mode:

```html
<div x-data class="flex gap-2">
    <flux:button
        x-on:click="$flux.appearance = 'light'"
        variant="ghost"
        :class="[$flux.appearance === 'light' ? 'bg-zinc:50' : '']"
    >
        <flux:icon.sun variant="micro" />
    </flux:button>
    <flux:button
        x-on:click="$flux.appearance = 'dark'"
        variant="ghost"
        :class="[$flux.appearance === 'dark' ? 'bg-zinc:50' : '']"
    >
        <flux:icon.moon variant="micro" />
    </flux:button>
</div>
```