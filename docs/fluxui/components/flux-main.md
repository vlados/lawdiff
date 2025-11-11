# flux:main

The main content area component to be used in conjunction with the sidebar layout.

## Examples

### Basic Usage

```html
<flux:sidebar>
  <!-- Your sidebar content here -->
</flux:sidebar>
<flux:main>
  <!-- Your main content here -->
</flux:main>
```

### With Container Width

```html
<flux:sidebar>
  <!-- Your sidebar content here -->
</flux:sidebar>
<flux:main container>
  <!-- Your main content here, constrained to a container width -->
</flux:main>
```

## Props

| Prop | Description |
| --- | --- |
| `container` | When present, constrains the main content to a container width. |

## Slots

| Slot | Description |
| --- | --- |
| `default` | Content to display within the main content area. |

## Usage

The `flux:main` component is used to define the main content area in a sidebar layout:

```html
<flux:sidebar class="bg-zinc-50 border-r">
  <!-- Sidebar content -->
</flux:sidebar>
<flux:main>
  <div class="p-6">
    <h1 class="text-2xl font-bold">Main Content</h1>
    <p class="mt-4">Your main content goes here.</p>
  </div>
</flux:main>
```

### With Container Width

To constrain the main content to a container width:

```html
<flux:sidebar class="bg-zinc-50 border-r">
  <!-- Sidebar content -->
</flux:sidebar>
<flux:main container>
  <div class="p-6">
    <h1 class="text-2xl font-bold">Main Content</h1>
    <p class="mt-4">Your main content is constrained to a container width.</p>
  </div>
</flux:main>
```

## Related Components

- `flux:sidebar` - The sidebar component that works with this main content area.
- `flux:header` - Optional header component that can be used with the sidebar layout.