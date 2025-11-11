# Sidebar Layout

The sidebar layout is a common UI pattern for web applications that provides navigation while keeping the main content in focus.

## Overview

The sidebar layout in FluxUI consists of two main components:
- `flux:sidebar` - The navigation sidebar
- `flux:main` - The main content area

Optionally, it can include:
- `flux:header` - A top header for secondary navigation

## Examples

### Basic Sidebar Layout

```html
<flux:sidebar class="bg-zinc-50 border-r">
  <!-- Sidebar content -->
</flux:sidebar>
<flux:main>
  <!-- Main content -->
</flux:main>
```

### Sidebar with Header

```html
<flux:sidebar class="bg-zinc-50 border-r">
  <!-- Sidebar content -->
</flux:sidebar>
<flux:header class="bg-white border-b">
  <!-- Header content -->
</flux:header>
<flux:main>
  <!-- Main content -->
</flux:main>
```

## Responsive Behavior

The sidebar layout can be made responsive using the `stashable` property on the sidebar and a toggle button:

```html
<flux:sidebar stashable class="bg-zinc-50 border-r">
  <!-- Sidebar content -->
</flux:sidebar>
<flux:toggle-sidebar icon="bars-2" class="lg:hidden" inset="left"></flux:toggle-sidebar>
<flux:main>
  <!-- Main content -->
</flux:main>
```

## Component References

For detailed documentation on each component, see:
- [flux:sidebar](./flux-sidebar.md)
- [flux:main](./flux-main.md)

## Demo Links

- [Basic Sidebar Layout](https://fluxui.dev/demo/sidebar)
- [Sidebar with Header](https://fluxui.dev/demo/sidebar-with-header)