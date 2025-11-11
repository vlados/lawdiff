# flux:dropdown

A composable dropdown component that can handle both simple navigation menus as well as complex action menus with checkboxes, radios, and submenus.

## Basic Usage

```html
<flux:dropdown>
    <flux:button icon:trailing="chevron-down">Options</flux:button>
    <flux:menu>
        <flux:menu.item icon="plus">New post</flux:menu.item>
        <flux:menu.separator />
        <flux:menu.submenu heading="Sort by">
            <flux:menu.radio.group>
                <flux:menu.radio checked>Name</flux:menu.radio>
                <flux:menu.radio>Date</flux:menu.radio>
                <flux:menu.radio>Popularity</flux:menu.radio>
            </flux:menu.radio.group>
        </flux:menu.submenu>
        <flux:menu.submenu heading="Filter">
            <flux:menu.checkbox checked>Draft</flux:menu.checkbox>
            <flux:menu.checkbox checked>Published</flux:menu.checkbox>
            <flux:menu.checkbox>Archived</flux:menu.checkbox>
        </flux:menu.submenu>
        <flux:menu.separator />
        <flux:menu.item variant="danger" icon="trash">Delete</flux:menu.item>
    </flux:menu>
</flux:dropdown>
```

You can also display a simple set of links in a dropdown menu:

```html
<flux:dropdown position="bottom" align="end">
    <flux:profile avatar="/img/demo/user.png" name="Olivia Martin" />
    <flux:navmenu>
        <flux:navmenu.item href="#" icon="user">Account</flux:navmenu.item>
        <flux:navmenu.item href="#" icon="building-storefront">Profile</flux:navmenu.item>
        <flux:navmenu.item href="#" icon="credit-card">Billing</flux:navmenu.item>
        <flux:navmenu.item href="#" icon="arrow-right-start-on-rectangle">Logout</flux:navmenu.item>
        <flux:navmenu.item href="#" icon="trash" variant="danger">Delete</flux:navmenu.item>
    </flux:navmenu>
</flux:dropdown>
```

> Use the navmenu component for a simple collections of links. Otherwise, use the menu component for action menus that require keyboard navigation, submenus, etc.

## Positioning

Customize the position of the dropdown menu via the position and align props. You can first pass the base position: top, bottom, left, and right, then an alignment modifier like start, center, or end.

```html
<flux:dropdown position="top" align="start">
<!-- More positions... -->
<flux:dropdown position="right" align="center">
<flux:dropdown position="bottom" align="center">
<flux:dropdown position="left" align="end">
```

## Offset & Gap

Customize the offset/gap of the dropdown menu via the offset and gap props. These properties accept values in pixels.

```html
<flux:dropdown offset="-15" gap="2">
```

## Keyboard Hints

Add keyboard shortcut hints to menu items to teach users how to navigate your app faster.

```html
<flux:dropdown>
    <flux:button icon:trailing="chevron-down">Options</flux:button>
    <flux:menu>
        <flux:menu.item icon="pencil-square" kbd="⌘S">Save</flux:menu.item>
        <flux:menu.item icon="document-duplicate" kbd="⌘D">Duplicate</flux:menu.item>
        <flux:menu.item icon="trash" variant="danger" kbd="⌘⌫">Delete</flux:menu.item>
    </flux:menu>
</flux:dropdown>
```

## Checkbox Items

Select one or many menu options.

```html
<flux:dropdown>
    <flux:button icon:trailing="chevron-down">Permissions</flux:button>
    <flux:menu>
        <flux:menu.checkbox wire:model="read" checked>Read</flux:menu.checkbox>
        <flux:menu.checkbox wire:model="write" checked>Write</flux:menu.checkbox>
        <flux:menu.checkbox wire:model="delete">Delete</flux:menu.checkbox>
    </flux:menu>
</flux:dropdown>
```

## Radio Items

Select a single menu option.

```html
<flux:dropdown>
    <flux:button icon:trailing="chevron-down">Sort by</flux:button>
    <flux:menu>
        <flux:menu.radio.group wire:model="sortBy">
            <flux:menu.radio checked>Latest activity</flux:menu.radio>
            <flux:menu.radio>Date created</flux:menu.radio>
            <flux:menu.radio>Most popular</flux:menu.radio>
        </flux:menu.radio.group>
    </flux:menu>
</flux:dropdown>
```

## Groups

Visually group related menu items with a separator line.

```html
<flux:dropdown>
    <flux:button icon:trailing="chevron-down">Options</flux:button>
    <flux:menu>
        <flux:menu.item>View</flux:menu.item>
        <flux:menu.item>Transfer</flux:menu.item>
        <flux:menu.separator />
        <flux:menu.item>Publish</flux:menu.item>
        <flux:menu.item>Share</flux:menu.item>
        <flux:menu.separator />
        <flux:menu.item variant="danger">Delete</flux:menu.item>
    </flux:menu>
</flux:dropdown>
```

## Groups with Headings

Group options under headings to make them more discoverable.

```html
<flux:dropdown>
    <flux:button icon:trailing="chevron-down">Options</flux:button>
    <flux:menu>
        <flux:menu.group heading="Account">
            <flux:menu.item>Profile</flux:menu.item>
            <flux:menu.item>Permissions</flux:menu.item>
        </flux:menu.group>
        <flux:menu.group heading="Billing">
            <flux:menu.item>Transactions</flux:menu.item>
            <flux:menu.item>Payouts</flux:menu.item>
            <flux:menu.item>Refunds</flux:menu.item>
        </flux:menu.group>
        <flux:menu.item>Logout</flux:menu.item>
    </flux:menu>
</flux:dropdown>
```

## Submenus

Nest submenus for more condensed menus.

```html
<flux:dropdown>
    <flux:button icon:trailing="chevron-down">Options</flux:button>
    <flux:menu>
        <flux:menu.submenu heading="Sort by">
            <flux:menu.radio checked>Name</flux:menu.radio>
            <flux:menu.radio>Date</flux:menu.radio>
            <flux:menu.radio>Popularity</flux:menu.radio>
        </flux:menu.submenu>
        <flux:menu.submenu heading="Filter">
            <flux:menu.checkbox checked>Draft</flux:menu.checkbox>
            <flux:menu.checkbox checked>Published</flux:menu.checkbox>
            <flux:menu.checkbox>Archived</flux:menu.checkbox>
        </flux:menu.submenu>
        <flux:menu.separator />
        <flux:menu.item variant="danger">Delete</flux:menu.item>
    </flux:menu>
</flux:dropdown>
```

## Additional Examples

### Context Menu

```html
<div x-data="{ open: false, x: 0, y: 0 }"
     @contextmenu.prevent="
         open = true;
         x = $event.clientX;
         y = $event.clientY;
     "
     @click.away="open = false"
     class="relative p-10 border border-dashed border-zinc-300 rounded-lg">
    <div class="text-center p-10">Right-click anywhere in this area</div>
    
    <div x-show="open" 
         :style="`position: fixed; left: ${x}px; top: ${y}px;`"
         class="bg-white shadow-lg rounded-md p-1 border"
         @click="open = false">
        <div class="flex flex-col space-y-1 min-w-[160px]">
            <button class="flex items-center px-3 py-2 hover:bg-zinc-100 rounded-md">
                <flux:icon.document-duplicate class="mr-2 size-4" /> Copy
            </button>
            <button class="flex items-center px-3 py-2 hover:bg-zinc-100 rounded-md">
                <flux:icon.clipboard class="mr-2 size-4" /> Paste
            </button>
            <div class="h-px bg-zinc-200 my-1"></div>
            <button class="flex items-center px-3 py-2 hover:bg-zinc-100 rounded-md">
                <flux:icon.trash class="mr-2 size-4 text-red-500" /> 
                <span class="text-red-500">Delete</span>
            </button>
        </div>
    </div>
</div>
```

### Multi-level Navigation

```html
<flux:dropdown>
    <flux:button icon:trailing="chevron-down">Products</flux:button>
    <flux:menu>
        <flux:menu.submenu heading="Software" icon="code-bracket">
            <flux:menu.submenu heading="Developer Tools">
                <flux:menu.item>IDE</flux:menu.item>
                <flux:menu.item>Version Control</flux:menu.item>
                <flux:menu.item>CI/CD</flux:menu.item>
            </flux:menu.submenu>
            <flux:menu.item>Databases</flux:menu.item>
            <flux:menu.item>Cloud Services</flux:menu.item>
        </flux:menu.submenu>
        <flux:menu.submenu heading="Hardware" icon="computer-desktop">
            <flux:menu.item>Laptops</flux:menu.item>
            <flux:menu.item>Desktops</flux:menu.item>
            <flux:menu.item>Accessories</flux:menu.item>
        </flux:menu.submenu>
        <flux:menu.separator />
        <flux:menu.item icon="shopping-cart">View All Products</flux:menu.item>
    </flux:menu>
</flux:dropdown>
```

### Filter Menu

```html
<flux:dropdown>
    <flux:button icon="funnel" icon:trailing="chevron-down">Filter</flux:button>
    <flux:menu>
        <flux:menu.submenu heading="Status">
            <flux:menu.checkbox wire:model="filters.active" checked>Active</flux:menu.checkbox>
            <flux:menu.checkbox wire:model="filters.pending">Pending</flux:menu.checkbox>
            <flux:menu.checkbox wire:model="filters.archived">Archived</flux:menu.checkbox>
        </flux:menu.submenu>
        <flux:menu.submenu heading="Price Range">
            <flux:menu.radio.group wire:model="filters.priceRange">
                <flux:menu.radio value="all" checked>All Prices</flux:menu.radio>
                <flux:menu.radio value="under50">Under $50</flux:menu.radio>
                <flux:menu.radio value="50to100">$50 - $100</flux:menu.radio>
                <flux:menu.radio value="over100">Over $100</flux:menu.radio>
            </flux:menu.radio.group>
        </flux:menu.submenu>
        <flux:menu.separator />
        <flux:menu.item icon="arrow-path">Reset Filters</flux:menu.item>
    </flux:menu>
</flux:dropdown>
```

## Reference

### flux:dropdown

| Prop | Description |
| --- | --- |
| `position` | Position of the dropdown menu. Options: top, right, bottom (default), left. |
| `align` | Alignment of the dropdown menu. Options: start, center, end. Default: start. |
| `offset` | Offset in pixels from the trigger element. Default: 0. |
| `gap` | Gap in pixels between trigger and menu. Default: 4. |

| Attribute | Description |
| --- | --- |
| `data-flux-dropdown` | Applied to the root element for styling and identification. |

### flux:menu

A complex menu component that supports keyboard navigation, submenus, checkboxes, and radio buttons.

| Slot | Description |
| --- | --- |
| `default` | The menu items, separators, and submenus. |

| Attribute | Description |
| --- | --- |
| `data-flux-menu` | Applied to the root element for styling and identification. |

### flux:menu.item

| Prop | Description |
| --- | --- |
| `icon` | Name of the icon to display at the start of the item. |
| `icon:trailing` | Name of the icon to display at the end of the item. |
| `icon:variant` | Variant of the icon. Options: outline, solid, mini, micro. |
| `kbd` | Keyboard shortcut hint displayed at the end of the item. |
| `suffix` | Text displayed at the end of the item. |
| `variant` | Visual style of the item. Options: default, danger. |
| `disabled` | If true, prevents interaction with the menu item. |

| Attribute | Description |
| --- | --- |
| `data-flux-menu-item` | Applied to the root element for styling and identification. |
| `data-active` | Applied when the item is hovered/active. |

### flux:menu.submenu

| Prop | Description |
| --- | --- |
| `heading` | Text displayed as the submenu heading. |
| `icon` | Name of the icon to display at the start of the submenu. |
| `icon:trailing` | Name of the icon to display at the end of the submenu. |
| `icon:variant` | Variant of the icon. Options: outline, solid, mini, micro. |

| Slot | Description |
| --- | --- |
| `default` | The submenu items (checkboxes, radio buttons, etc.). |

### flux:menu.separator

A horizontal line that separates menu items.

### flux:menu.checkbox.group

| Prop | Description |
| --- | --- |
| `wire:model` | Binds the checkbox group to a Livewire property. See the [wire:model documentation](https://livewire.laravel.com/docs/wire-model) for more information. |

| Slot | Description |
| --- | --- |
| `default` | The checkboxes. |

### flux:menu.checkbox

| Prop | Description |
| --- | --- |
| `wire:model` | Binds the checkbox to a Livewire property. See the [wire:model documentation](https://livewire.laravel.com/docs/wire-model) for more information. |
| `checked` | If true, the checkbox is checked by default. |
| `disabled` | If true, prevents interaction with the checkbox. |

| Attribute | Description |
| --- | --- |
| `data-active` | Applied when the checkbox is hovered/active. |
| `data-checked` | Applied when the checkbox is checked. |

### flux:menu.radio.group

| Prop | Description |
| --- | --- |
| `wire:model` | Binds the radio group to a Livewire property. See the [wire:model documentation](https://livewire.laravel.com/docs/wire-model) for more information. |

| Slot | Description |
| --- | --- |
| `default` | The radio buttons. |

### flux:menu.radio

| Prop | Description |
| --- | --- |
| `checked` | If true, the radio button is selected by default. |
| `disabled` | If true, prevents interaction with the radio button. |

| Attribute | Description |
| --- | --- |
| `data-active` | Applied when the radio button is hovered/active. |
| `data-checked` | Applied when the radio button is selected. |