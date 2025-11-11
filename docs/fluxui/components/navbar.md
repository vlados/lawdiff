# flux:navbar

Arrange navigation links vertically or horizontally for application navigation.

## Basic Usage

```html
<flux:navbar>
    <flux:navbar.item href="#">Home</flux:navbar.item>
    <flux:navbar.item href="#">Features</flux:navbar.item>
    <flux:navbar.item href="#">Pricing</flux:navbar.item>
    <flux:navbar.item href="#">About</flux:navbar.item>
</flux:navbar>
```

## Detecting the Current Page

Navbars and navlists will try to automatically detect and mark the current page based on the href attribute passed in. However, if you need full control, you can pass the current prop to the item directly.

```html
<flux:navbar.item href="/" current>Home</flux:navbar.item>
<flux:navbar.item href="/" :current="false">Home</flux:navbar.item>
<flux:navbar.item href="/" :current="request()->is('/')">Home</flux:navbar.item>
```

## With Icons

Add leading icons for visual context.

```html
<flux:navbar>
    <flux:navbar.item href="#" icon="home">Home</flux:navbar.item>
    <flux:navbar.item href="#" icon="puzzle-piece">Features</flux:navbar.item>
    <flux:navbar.item href="#" icon="currency-dollar">Pricing</flux:navbar.item>
    <flux:navbar.item href="#" icon="user">About</flux:navbar.item>
</flux:navbar>
```

## With Badges

Add a trailing badge to a navbar item using the badge prop.

```html
<flux:navbar>
    <flux:navbar.item href="#">Home</flux:navbar.item>
    <flux:navbar.item href="#" badge="12">Inbox</flux:navbar.item>
    <flux:navbar.item href="#">Contacts</flux:navbar.item>
    <flux:navbar.item href="#" badge="Pro" badge-color="lime">Calendar</flux:navbar.item>
</flux:navbar>
```

## Dropdown Navigation

Condense multiple navigation items into a single dropdown menu to save on space and group related items.

```html
<flux:navbar>
    <flux:navbar.item href="#">Dashboard</flux:navbar.item>
    <flux:navbar.item href="#">Transactions</flux:navbar.item>
    <flux:dropdown>
        <flux:navbar.item icon:trailing="chevron-down">Account</flux:navbar.item>
        <flux:navmenu>
            <flux:navmenu.item href="#">Profile</flux:navmenu.item>
            <flux:navmenu.item href="#">Settings</flux:navmenu.item>
            <flux:navmenu.item href="#">Billing</flux:navmenu.item>
        </flux:navmenu>
    </flux:dropdown>
</flux:navbar>
```

## Vertical Navigation with Navlist

Arrange your navbar vertically using the navlist component.

```html
<flux:navlist class="w-64">
    <flux:navlist.item href="#" icon="home">Home</flux:navlist.item>
    <flux:navlist.item href="#" icon="puzzle-piece">Features</flux:navlist.item>
    <flux:navlist.item href="#" icon="currency-dollar">Pricing</flux:navlist.item>
    <flux:navlist.item href="#" icon="user">About</flux:navlist.item>
</flux:navlist>
```

## Navlist Group

Group related navigation items.

```html
<flux:navlist>
    <flux:navlist.group heading="Account" class="mt-4">
        <flux:navlist.item href="#">Profile</flux:navlist.item>
        <flux:navlist.item href="#">Settings</flux:navlist.item>
        <flux:navlist.item href="#">Billing</flux:navlist.item>
    </flux:navlist.group>
</flux:navlist>
```

## Collapsible Groups

Group related navigation items into collapsible sections using the expandable prop.

```html
<flux:navlist class="w-64">
    <flux:navlist.item href="#" icon="home">Dashboard</flux:navlist.item>
    <flux:navlist.item href="#" icon="list-bullet">Transactions</flux:navlist.item>
    <flux:navlist.group heading="Account" expandable>
        <flux:navlist.item href="#">Profile</flux:navlist.item>
        <flux:navlist.item href="#">Settings</flux:navlist.item>
        <flux:navlist.item href="#">Billing</flux:navlist.item>
    </flux:navlist.group>
</flux:navlist>
```

If you want a group to be collapsed by default, you can use the expanded prop:

```html
<flux:navlist.group heading="Account" expandable :expanded="false">
    <!-- Navigation items -->
</flux:navlist.group>
```

## Navlist Badges

Show additional information related to a navlist item using the badge prop.

```html
<flux:navlist class="w-64">
    <flux:navlist.item href="#" icon="home">Home</flux:navlist.item>
    <flux:navlist.item href="#" icon="envelope" badge="12">Inbox</flux:navlist.item>
    <flux:navlist.item href="#" icon="user-group">Contacts</flux:navlist.item>
    <flux:navlist.item href="#" icon="calendar-days" badge="Pro" badge-color="lime">Calendar</flux:navlist.item>
</flux:navlist>
```

## Responsive Navigation

Create a responsive navigation bar that collapses into a mobile menu on smaller screens:

```html
<div class="relative">
    <!-- Desktop Navigation -->
    <flux:navbar class="hidden md:flex">
        <flux:navbar.item href="#">Home</flux:navbar.item>
        <flux:navbar.item href="#">Features</flux:navbar.item>
        <flux:navbar.item href="#">Pricing</flux:navbar.item>
        <flux:navbar.item href="#">About</flux:navbar.item>
    </flux:navbar>
    
    <!-- Mobile Navigation -->
    <div class="md:hidden">
        <flux:button icon="bars-3" variant="ghost" x-on:click="$refs.mobileMenu.classList.toggle('hidden')">
            Menu
        </flux:button>
        
        <div x-ref="mobileMenu" class="hidden absolute top-10 left-0 w-full bg-white shadow-md">
            <flux:navlist>
                <flux:navlist.item href="#">Home</flux:navlist.item>
                <flux:navlist.item href="#">Features</flux:navlist.item>
                <flux:navlist.item href="#">Pricing</flux:navlist.item>
                <flux:navlist.item href="#">About</flux:navlist.item>
            </flux:navlist>
        </div>
    </div>
</div>
```

## Reference

### flux:navbar

A horizontal navigation container.

| Slot | Description |
| --- | --- |
| `default` | The navigation items. |

| Attribute | Description |
| --- | --- |
| `data-flux-navbar` | Applied to the root element for styling and identification. |

### flux:navbar.item

| Prop | Description |
| --- | --- |
| `href` | URL the item links to. |
| `current` | If true, applies active styling to the item. Auto-detected based on current URL if not specified. |
| `icon` | Name of the icon to display at the start of the item. |
| `icon:trailing` | Name of the icon to display at the end of the item. |

| Attribute | Description |
| --- | --- |
| `data-current` | Applied when the item is active/current. |

### flux:navlist

A vertical navigation container (sidebar).

| Slot | Description |
| --- | --- |
| `default` | The navigation items and groups. |

| Attribute | Description |
| --- | --- |
| `data-flux-navlist` | Applied to the root element for styling and identification. |

### flux:navlist.item

| Prop | Description |
| --- | --- |
| `href` | URL the item links to. |
| `current` | If true, applies active styling to the item. Auto-detected based on current URL if not specified. |
| `icon` | Name of the icon to display at the start of the item. |

| Attribute | Description |
| --- | --- |
| `data-current` | Applied when the item is active/current. |

### flux:navlist.group

| Prop | Description |
| --- | --- |
| `heading` | Text displayed as the group heading. |
| `expandable` | If true, makes the group collapsible. |
| `expanded` | If true, expands the group by default when expandable. |

| Slot | Description |
| --- | --- |
| `default` | The group's navigation items. |