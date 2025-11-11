# flux:sidebar

The sidebar component provides a navigation layout to keep your application content front and center.

## Examples

### Basic Sidebar

```html
<flux:sidebar>
  <!-- Your sidebar content here -->
</flux:sidebar>
<flux:main>
  <!-- Your main content here -->
</flux:main>
```

### Sidebar with Header

Use a top header for secondary navigation.

```html
<flux:sidebar>
  <!-- Your sidebar content here -->
</flux:sidebar>
<flux:header>
  <!-- Your header content here -->
</flux:header>
<flux:main>
  <!-- Your main content here -->
</flux:main>
```

## Props

| Prop | Description |
| --- | --- |
| `sticky` | When present, makes the sidebar sticky when scrolling. |
| `stashable` | When present, allows the sidebar to be toggled on/off on smaller screens. |

## Slots

| Slot | Description |
| --- | --- |
| `default` | Content to display within the sidebar, typically including branding, navigation elements, and user profile. |

## CSS Classes

| CSS | Description |
| --- | --- |
| `class` | Additional CSS classes applied to the sidebar. Common uses: `bg-zinc-50`, `border-r`, etc. |

## Toggle Button Attributes

When the sidebar is stashable, a toggle button will be available. The button accepts the following attributes:

| Attribute | Description |
| --- | --- |
| `icon` | The icon to display in the toggle button (e.g., `bars-2`, `x-mark`). |
| `inset` | Positioning of the toggle button (e.g., `left`). |

## Toggle Button CSS

| CSS | Description |
| --- | --- |
| `class` | Additional CSS classes applied to the toggle button. Common uses: `lg:hidden` to show only on mobile. |

## Usage

The sidebar component is typically used with the `flux:main` component to create a sidebar layout:

```html
<flux:sidebar class="bg-zinc-50 border-r">
  <!-- Sidebar content -->
  <div class="p-4">
    <h2 class="text-xl font-bold">My App</h2>
  </div>
  <nav class="mt-4">
    <ul>
      <li class="px-4 py-2 hover:bg-zinc-200"><a href="/">Home</a></li>
      <li class="px-4 py-2 hover:bg-zinc-200"><a href="/dashboard">Dashboard</a></li>
      <li class="px-4 py-2 hover:bg-zinc-200"><a href="/settings">Settings</a></li>
    </ul>
  </nav>
</flux:sidebar>
<flux:main>
  <div class="p-6">
    <h1 class="text-2xl font-bold">Main Content</h1>
    <p class="mt-4">Your main content goes here.</p>
  </div>
</flux:main>
```

### Responsive Sidebar

To create a responsive sidebar that can be toggled on smaller screens:

```html
<flux:sidebar stashable class="bg-zinc-50 border-r">
  <!-- Sidebar content -->
</flux:sidebar>
<flux:toggle-sidebar icon="bars-2" class="lg:hidden" inset="left"></flux:toggle-sidebar>
<flux:main>
  <!-- Main content -->
</flux:main>
```

In this example, the sidebar can be toggled on smaller screens, and the toggle button is only visible on mobile devices due to the `lg:hidden` class.