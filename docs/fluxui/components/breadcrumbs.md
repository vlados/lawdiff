# flux:breadcrumbs

Help users navigate and understand their place within your application.

## Basic Usage

```html
<flux:breadcrumbs>
    <flux:breadcrumbs.item href="#">Home</flux:breadcrumbs.item>
    <flux:breadcrumbs.item href="#">Blog</flux:breadcrumbs.item>
    <flux:breadcrumbs.item>Post</flux:breadcrumbs.item>
</flux:breadcrumbs>
```

## With Slashes

Use slashes instead of chevrons to separate breadcrumb items.

```html
<flux:breadcrumbs>
    <flux:breadcrumbs.item href="#" separator="slash">Home</flux:breadcrumbs.item>
    <flux:breadcrumbs.item href="#" separator="slash">Blog</flux:breadcrumbs.item>
    <flux:breadcrumbs.item separator="slash">Post</flux:breadcrumbs.item>
</flux:breadcrumbs>
```

## With Icon

Use an icon instead of text for a particular breadcrumb item.

```html
<flux:breadcrumbs>
    <flux:breadcrumbs.item href="#" icon="home" />
    <flux:breadcrumbs.item href="#">Blog</flux:breadcrumbs.item>
    <flux:breadcrumbs.item>Post</flux:breadcrumbs.item>
</flux:breadcrumbs>
```

## With Ellipsis

Truncate a long breadcrumb list with an ellipsis.

```html
<flux:breadcrumbs>
    <flux:breadcrumbs.item href="#" icon="home" />
    <flux:breadcrumbs.item icon="ellipsis-horizontal" />
    <flux:breadcrumbs.item>Post</flux:breadcrumbs.item>
</flux:breadcrumbs>
```

## With Ellipsis Dropdown

Truncate a long breadcrumb list into a single ellipsis dropdown.

```html
<flux:breadcrumbs>
    <flux:breadcrumbs.item href="#" icon="home" />
    <flux:breadcrumbs.item>
        <flux:dropdown>
            <flux:button icon="ellipsis-horizontal" variant="ghost" size="sm" />
            <flux:navmenu>
                <flux:navmenu.item>Client</flux:navmenu.item>
                <flux:navmenu.item icon="arrow-turn-down-right">Team</flux:navmenu.item>
                <flux:navmenu.item icon="arrow-turn-down-right">User</flux:navmenu.item>
            </flux:navmenu>
        </flux:dropdown>
    </flux:breadcrumbs.item>
    <flux:breadcrumbs.item>Post</flux:breadcrumbs.item>
</flux:breadcrumbs>
```

## Custom Styling

You can customize the appearance of breadcrumbs with additional classes:

```html
<flux:breadcrumbs class="text-sm text-zinc-600">
    <flux:breadcrumbs.item href="#" class="hover:text-blue-600">Home</flux:breadcrumbs.item>
    <flux:breadcrumbs.item href="#" class="hover:text-blue-600">Products</flux:breadcrumbs.item>
    <flux:breadcrumbs.item class="font-medium">Cameras</flux:breadcrumbs.item>
</flux:breadcrumbs>
```

## With Route Names

When using Laravel routing, you can use named routes:

```html
<flux:breadcrumbs>
    <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
    <flux:breadcrumbs.item href="{{ route('products.index') }}">Products</flux:breadcrumbs.item>
    <flux:breadcrumbs.item href="{{ route('products.category', $category) }}">{{ $category->name }}</flux:breadcrumbs.item>
    <flux:breadcrumbs.item>{{ $product->name }}</flux:breadcrumbs.item>
</flux:breadcrumbs>
```

## Responsive Breadcrumbs

For responsive designs, you might want to show fewer items on smaller screens:

```html
<flux:breadcrumbs>
    <flux:breadcrumbs.item href="#" icon="home" />
    <flux:breadcrumbs.item href="#" class="hidden md:flex">Category</flux:breadcrumbs.item>
    <flux:breadcrumbs.item href="#" class="hidden md:flex">Subcategory</flux:breadcrumbs.item>
    <flux:breadcrumbs.item>Current Page</flux:breadcrumbs.item>
</flux:breadcrumbs>
```

## Reference

### flux:breadcrumbs

| Slot | Description |
| --- | --- |
| `default` | The breadcrumb items to display. |

### flux:breadcrumbs.item

| Prop | Description |
| --- | --- |
| `href` | URL the breadcrumb item links to. If omitted, renders as non-clickable text. |
| `icon` | Name of the icon to display before the badge text. |
| `icon:variant` | Icon variant. Options: outline, solid, mini, micro. Default: mini. |
| `separator` | Name of the icon to display as the separator. Default: chevron-right. |