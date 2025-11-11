# flux:header

The header component provides a top navigation bar that can be used with the sidebar layout.

## Examples

```html
<flux:sidebar>
  <!-- Sidebar content -->
</flux:sidebar>
<flux:header class="bg-white border-b">
  <!-- Header content -->
</flux:header>
<flux:main>
  <!-- Main content -->
</flux:main>
```

## Slots

| Slot | Description |
| --- | --- |
| `default` | Content to display within the header, typically including navigation elements, search, and user actions. |

## CSS Classes

| CSS | Description |
| --- | --- |
| `class` | Additional CSS classes applied to the header. Common uses: `bg-white`, `border-b`, etc. |

## Usage

The header component is typically used with the sidebar layout to provide secondary navigation:

```html
<flux:sidebar class="bg-zinc-50 border-r">
  <!-- Sidebar content, typically primary navigation -->
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
<flux:header class="bg-white border-b p-4 flex justify-between items-center">
  <!-- Header content, typically secondary navigation, search, and user actions -->
  <div>
    <h1 class="text-xl font-bold">Dashboard</h1>
  </div>
  <div class="flex gap-4">
    <button class="px-4 py-2 rounded-md bg-zinc-100 hover:bg-zinc-200">Search</button>
    <button class="px-4 py-2 rounded-md bg-zinc-100 hover:bg-zinc-200">Profile</button>
  </div>
</flux:header>
<flux:main>
  <!-- Main content -->
  <div class="p-6">
    <h1 class="text-2xl font-bold">Main Content</h1>
    <p class="mt-4">Your main content goes here.</p>
  </div>
</flux:main>
```

## Related Components

- `flux:sidebar` - The sidebar component that works with this header.
- `flux:main` - The main content area component.

## Demo Links

- [Sidebar with Header](https://fluxui.dev/demo/sidebar-with-header)