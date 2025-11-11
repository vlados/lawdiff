# Header Layout

A full-width top navigation layout for your application.

## Overview

The header layout in FluxUI provides a top navigation bar for your application, with options for stickiness and container width constraints.

## Examples

### Basic Header Layout

```html
<flux:header class="bg-white border-b">
  <!-- Header content -->
</flux:header>
<flux:main>
  <!-- Main content -->
</flux:main>
```

### Header with Sidebar

Use a sidebar for secondary navigation:

```html
<flux:header class="bg-white border-b">
  <!-- Header content -->
</flux:header>
<flux:sidebar class="bg-zinc-50 border-r">
  <!-- Sidebar content -->
</flux:sidebar>
<flux:main>
  <!-- Main content -->
</flux:main>
```

## Component Details

### flux:header

#### Props

| Prop | Description |
| --- | --- |
| `sticky` | When present, makes the header sticky when scrolling. |
| `container` | When present, constrains the header content to a container width. |

#### Slots

| Slot | Description |
| --- | --- |
| `default` | Content to display within the header, typically including branding, navigation, and user profile elements. |

#### CSS Classes

| CSS | Description |
| --- | --- |
| `class` | Additional CSS classes applied to the header. Common uses: `bg-zinc-50`, `border-b`, `sticky`, etc. |

### flux:toggle-sidebar

When used with a sidebar, a toggle button can be included:

#### Attributes

| Attribute | Description |
| --- | --- |
| `icon` | The icon to display in the toggle button (e.g., `bars-2`, `x-mark`). |
| `inset` | Positioning of the toggle button (e.g., `left`). |

#### CSS Classes

| CSS | Description |
| --- | --- |
| `class` | Additional CSS classes applied to the toggle button. Common uses: `lg:hidden` to show only on mobile. |

### flux:main

#### Props

| Prop | Description |
| --- | --- |
| `container` | When present, constrains the main content to a container width. |

#### Slots

| Slot | Description |
| --- | --- |
| `default` | Content to display within the main content area. |

## Usage Examples

### Basic Header with Container

```html
<flux:header container class="bg-white border-b p-4">
  <div class="flex justify-between items-center">
    <div>
      <h1 class="text-xl font-bold">My Application</h1>
    </div>
    <nav>
      <ul class="flex gap-6">
        <li><a href="/" class="hover:text-zinc-500">Home</a></li>
        <li><a href="/features" class="hover:text-zinc-500">Features</a></li>
        <li><a href="/pricing" class="hover:text-zinc-500">Pricing</a></li>
        <li><a href="/contact" class="hover:text-zinc-500">Contact</a></li>
      </ul>
    </nav>
    <div>
      <button class="px-4 py-2 bg-blue-500 text-white rounded-md">Sign Up</button>
    </div>
  </div>
</flux:header>
<flux:main container>
  <div class="p-6">
    <h1 class="text-2xl font-bold">Welcome to My Application</h1>
    <p class="mt-4">This is the main content area.</p>
  </div>
</flux:main>
```

### Responsive Header with Sidebar Toggle

```html
<flux:header sticky class="bg-white border-b p-4">
  <div class="flex justify-between items-center">
    <div class="flex items-center">
      <flux:toggle-sidebar icon="bars-2" class="mr-4 lg:hidden" inset="left"></flux:toggle-sidebar>
      <h1 class="text-xl font-bold">My Dashboard</h1>
    </div>
    <div>
      <button class="px-4 py-2 bg-zinc-100 hover:bg-zinc-200 rounded-md">Profile</button>
    </div>
  </div>
</flux:header>
<flux:sidebar stashable class="bg-zinc-50 border-r">
  <!-- Sidebar content -->
  <nav class="p-4">
    <ul>
      <li class="py-2"><a href="/dashboard">Dashboard</a></li>
      <li class="py-2"><a href="/analytics">Analytics</a></li>
      <li class="py-2"><a href="/settings">Settings</a></li>
    </ul>
  </nav>
</flux:sidebar>
<flux:main container>
  <div class="p-6">
    <h1 class="text-2xl font-bold">Dashboard</h1>
    <p class="mt-4">Your dashboard content goes here.</p>
  </div>
</flux:main>
```

## Demo Links

- [Basic Header Layout](https://fluxui.dev/demo/header)
- [Header with Sidebar](https://fluxui.dev/demo/header-with-sidebar)

## Related Components

- [flux:sidebar](./flux-sidebar.md) - Sidebar component for use with header layouts
- [flux:main](./flux-main.md) - Main content area component