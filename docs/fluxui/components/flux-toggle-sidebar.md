# flux:toggle-sidebar

The toggle sidebar component provides a button to show or hide the sidebar on smaller screens.

## Examples

```html
<flux:sidebar stashable>
  <!-- Sidebar content -->
</flux:sidebar>
<flux:toggle-sidebar icon="bars-2" class="lg:hidden" inset="left"></flux:toggle-sidebar>
<flux:main>
  <!-- Main content -->
</flux:main>
```

## Attributes

| Attribute | Description |
| --- | --- |
| `icon` | The icon to display in the toggle button (e.g., `bars-2`, `x-mark`). |
| `inset` | Positioning of the toggle button (e.g., `left`). |

## CSS Classes

| CSS | Description |
| --- | --- |
| `class` | Additional CSS classes applied to the toggle button. Common uses: `lg:hidden` to show only on mobile. |

## Usage

The toggle sidebar component is used in conjunction with a stashable sidebar:

```html
<flux:sidebar stashable class="bg-zinc-50 border-r">
  <!-- Sidebar content -->
</flux:sidebar>
<flux:toggle-sidebar icon="bars-2" class="lg:hidden" inset="left"></flux:toggle-sidebar>
<flux:main>
  <!-- Main content -->
</flux:main>
```

In this example:
- The sidebar has the `stashable` attribute, making it togglable
- The toggle button uses the `bars-2` icon
- The button is only visible on smaller screens due to the `lg:hidden` class
- The button is positioned on the left side of the screen with the `inset="left"` attribute

## Requirements

This component requires:
- A sidebar with the `stashable` attribute
- The sidebar must be a sibling element of the toggle button

## Customization

You can customize the appearance of the toggle button using utility classes:

```html
<flux:toggle-sidebar 
  icon="bars-2" 
  class="lg:hidden bg-zinc-200 hover:bg-zinc-300 p-2 rounded-md shadow-md" 
  inset="left">
</flux:toggle-sidebar>
```