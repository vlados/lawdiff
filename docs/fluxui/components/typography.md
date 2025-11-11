# Typography Components

This document covers the typography-related components in Flux UI, including Heading, Text, Separator, and Editor.

## Heading Component

A consistent heading component for your application.

### Basic Usage

```html
<flux:heading>User profile</flux:heading>
<flux:text class="mt-2">This information will be displayed publicly.</flux:text>
```

### Sizes

Flux offers three different heading sizes:

```html
<flux:heading>Default</flux:heading>
<flux:heading size="lg">Large</flux:heading>
<flux:heading size="xl">Extra large</flux:heading>
```

- Default (14px): Use liberally—think input and toast labels.
- Large (16px): Use sparingly—think modal and card headings.
- Extra large (24px): Use rarely—think hero text.

### Heading Level

Control the heading level (h1, h2, h3) that will be used for the heading element:

```html
<flux:heading level="3">User profile</flux:heading>
<flux:text class="mt-2">This information will be displayed publicly.</flux:text>
```

Without a level prop, the heading will default to a div.

### Example: Leading Subheading

Subheadings can be placed above headings for a more interesting arrangement:

```html
<div>
    <flux:text>Year to date</flux:text>
    <flux:heading size="xl" class="mb-1">$7,532.16</flux:heading>
    <div class="flex items-center gap-2">
        <flux:icon.arrow-trending-up variant="micro" class="text-green-600 dark:text-green-500" />
        <span class="text-sm text-green-600 dark:text-green-500">15.2%</span>
    </div>
</div>
```

### Reference

#### flux:heading

| Prop | Description |
| --- | --- |
| size | Size of the heading. Options: base, lg, xl. Default: base. |
| level | HTML heading level. Options: 1, 2, 3, 4. Default: renders as a div if not specified. |
| accent | If true, applies accent color styling to the heading. |

## Text Component

Consistent typographical components like text and link.

### Basic Usage

```html
<flux:heading>Text component</flux:heading>
<flux:text class="mt-2">This is the standard text component for body copy and general content throughout your application.</flux:text>
```

### Size

Use standard Tailwind to control the size of the text:

```html
<flux:text class="text-base">Base text size</flux:text>
<flux:text>Default text size</flux:text>
<flux:text class="text-xs">Smaller text</flux:text>
```

### Color

Use standard Tailwind or variants to control the color of the text:

```html
<flux:text variant="strong">Strong text color</flux:text>
<flux:text>Default text color</flux:text>
<flux:text variant="subtle">Muted text color</flux:text>
<flux:text color="blue">Colored text</flux:text>
```

### Link

Use the link component to create clickable text:

```html
<flux:text>Visit our <flux:link href="#">documentation</flux:link> for more information.</flux:text>
```

### Link Variants

Links can be styled differently based on their context and importance:

```html
<flux:link href="#">Default link</flux:link>
<flux:link href="#" variant="ghost">Ghost link</flux:link>
<flux:link href="#" variant="subtle">Subtle link</flux:link>
```

### Reference

#### flux:text

| Prop | Description |
| --- | --- |
| size | Size of the text. Options: sm, default, lg, xl. Default: default. |
| variant | Text variant. Options: strong, subtle. Default: default. |
| color | Color of the text. Options: default, red, orange, yellow, lime, green, emerald, teal, cyan, sky, blue, indigo, violet, purple, fuchsia, pink, rose. Default: default. |
| inline | If true, the text element will be a span instead of a p. |

#### flux:link

| Prop | Description |
| --- | --- |
| href | The URL that the link points to. Required. |
| variant | Link style variant. Options: default, ghost, subtle. Default: default. |
| external | If true, the link will open in a new tab. |

## Separator Component

Visually divide sections of content or groups of items.

### Basic Usage

```html
<flux:separator />
```

### With Text

Add text to the separator for a more descriptive element:

```html
<flux:separator text="or" />
```

### Vertical

Separate contents with a vertical separator when horizontally stacked:

```html
<flux:separator vertical />
```

### Limited Height

You can limit the height of the vertical separator by adding vertical margin:

```html
<flux:separator vertical class="my-2" />
```

### Subtle

Flux offers a subtle variant for a separator that blends into the background:

```html
<flux:separator vertical variant="subtle" />
```

### Reference

#### flux:separator

| Prop | Description |
| --- | --- |
| vertical | Displays a vertical separator. Default is horizontal. |
| variant | Visual style variant. Options: subtle. Default: standard separator. |
| text | Optional text to display in the center of the separator. |
| orientation | Alternative to vertical prop. Options: horizontal, vertical. Default: horizontal. |

| Class | Description |
| --- | --- |
| my-\* | Commonly used to shorten vertical separators. |

| Attribute | Description |
| --- | --- |
| data-flux-separator | Applied to the root element for styling and identification. |

## Editor Component

A basic rich text editor for your application, built using ProseMirror and Tiptap.

### Basic Usage

```html
<flux:editor wire:model="content" label="Release notes" description="Explain what's new in this release." />
```

Note: Because of large external dependencies, the editor's JavaScript is not included in the core Flux bundle. It will be loaded on-the-fly as a separate JS file when you use the flux:editor component.

### Toolbar

The editor toolbar is both keyboard/screen-reader accessible and completely customizable. You can configure which toolbar items are visible, and in what order, by passing in a space-separated list of items:

```html
<flux:editor toolbar="heading | bold italic underline | align ~ undo redo" />
```

The following toolbar items are available:
- heading
- bold
- italic
- strike
- underline
- bullet
- ordered
- blockquote
- subscript
- superscript
- highlight
- link
- code
- undo
- redo

Use | for separator and ~ for spacer.

### Custom Toolbar Items

You can add your own toolbar items by adding new files to the resources/views/flux/editor directory in your project.

### Height

By default, the editor will have a minimum height of 200px, and a maximum height of 500px. You can customize this behavior using Tailwind utilities:

```html
<flux:editor class="**:data-[slot=content]:min-h-[100px]!" />
```

### Shortcut Keys

Flux's editor uses Tiptap's default shortcut keys which are common amongst most rich text editors:

| Operation | Windows/Linux | Mac |
| --- | --- | --- |
| Bold | Ctrl+B | Cmd+B |
| Italic | Ctrl+I | Cmd+I |
| Underline | Ctrl+U | Cmd+U |
| Blockquote | Ctrl+Shift+B | Cmd+Shift+B |
| And many more... | | |

### Markdown Syntax

Although Flux's editor isn't a markdown editor itself, it allows you to use markdown syntax to trigger styling changes:

| Markdown | Operation |
| --- | --- |
| # | Apply heading level 1 |
| ## | Apply heading level 2 |
| ### | Apply heading level 3 |
| \*\* | Bold |
| \* | Italic |
| ~~ | Strikethrough |
| - | Bullet list |
| 1. | Ordered list |
| > | Blockquote |
| ` | Inline code |
| ``` | Code block |

### Reference

#### flux:editor

| Prop | Description |
| --- | --- |
| wire:model | Binds the editor to a Livewire property. |
| value | Initial content for the editor. Used when not binding with wire:model. |
| label | Label text displayed above the editor. |
| description | Help text displayed below the editor. |
| badge | Badge text displayed at the end of the flux:label component when the label prop is provided. |
| placeholder | Placeholder text displayed when the editor is empty. |
| toolbar | Space-separated list of toolbar items to display. |
| disabled | Prevents user interaction with the editor. |
| invalid | Applies error styling to the editor. |

| Slot | Description |
| --- | --- |
| default | The editor content and toolbar components. |