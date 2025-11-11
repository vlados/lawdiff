# flux:card

A container for related content, such as a form, alert, or data list.

## Basic Usage

```html
<flux:card class="space-y-6">
    <div>
        <flux:heading size="lg">Log in to your account</flux:heading>
        <flux:text class="mt-2">Welcome back!</flux:text>
    </div>
    <div class="space-y-6">
        <flux:input label="Email" type="email" placeholder="Your email address" />
        <flux:field>
            <div class="mb-3 flex justify-between">
                <flux:label>Password</flux:label>
                <flux:link href="#" variant="subtle" class="text-sm">Forgot password?</flux:link>
            </div>
            <flux:input type="password" placeholder="Your password" />
            <flux:error name="password" />
        </flux:field>
    </div>
    <div class="space-y-2">
        <flux:button variant="primary" class="w-full">Log in</flux:button>
        <flux:button variant="ghost" class="w-full">Sign up for a new account</flux:button>
    </div>
</flux:card>
```

## Small Card

Use the small card variant for compact content like notifications, alerts, or brief summaries.

```html
<a href="#" aria-label="Latest on our blog">
    <flux:card size="sm" class="hover:bg-zinc-50 dark:hover:bg-zinc-700">
        <flux:heading class="flex items-center gap-2">Latest on our blog <flux:icon name="arrow-up-right" class="ml-auto text-zinc-400" variant="micro" /></flux:heading>
        <flux:text class="mt-2">Stay up to date with our latest insights, tutorials, and product updates.</flux:text>
    </flux:card>
</a>
```

## Card with Actions

Use the [button component](/components/button) to add actions to the header.

```html
<flux:card class="space-y-6">
    <div class="flex">
        <div class="flex-1">
            <flux:heading size="lg">Are you sure?</flux:heading>
            <flux:text class="mt-2">
                <p>Your post will be deleted permanently.</p>
                <p>This action cannot be undone.</p>
            </flux:text>
        </div>
        <div class="-mx-2 -mt-2">
            <flux:button variant="ghost" size="sm" icon="x-mark" inset="top right bottom" />
        </div>
    </div>
    <div class="flex gap-4">
        <flux:spacer />
        <flux:button variant="ghost">Undo</flux:button>
        <flux:button variant="danger">Delete</flux:button>
    </div>
</flux:card>
```

## Simple Card

Let's be honest, a card is just a div with a border and some padding.

```html
<flux:card>
    <flux:heading size="lg">Are you sure?</flux:heading>
    <flux:text class="mt-2 mb-4">
        <p>Your post will be deleted permanently.</p>
        <p>This action cannot be undone.</p>
    </flux:text>
    <flux:button variant="danger">Delete</flux:button>
</flux:card>
```

## Additional Examples

### Card with Image

```html
<flux:card class="overflow-hidden">
    <img src="https://images.unsplash.com/photo-1519681393784-d120267933ba" alt="Mountain landscape" class="w-full h-48 object-cover" />
    <div class="p-4">
        <flux:heading>Mountain Retreat</flux:heading>
        <flux:text class="mt-2">Escape to the serene mountains for a weekend of relaxation and adventure.</flux:text>
        <flux:button class="mt-4">Book Now</flux:button>
    </div>
</flux:card>
```

### Product Card

```html
<flux:card class="relative">
    <flux:badge color="green" class="absolute top-4 right-4">Sale</flux:badge>
    <img src="product-image.jpg" alt="Product" class="w-full h-48 object-contain" />
    <div class="mt-4">
        <flux:heading>Wireless Headphones</flux:heading>
        <div class="flex justify-between items-center mt-2">
            <flux:text class="font-bold">$129.99</flux:text>
            <flux:text class="line-through text-zinc-400">$199.99</flux:text>
        </div>
        <flux:button variant="primary" class="w-full mt-4">Add to Cart</flux:button>
    </div>
</flux:card>
```

### Dashboard Stat Card

```html
<flux:card>
    <div class="flex justify-between items-start">
        <div>
            <flux:text class="text-zinc-500">Total Revenue</flux:text>
            <flux:heading size="xl" class="mt-1">$45,231.89</flux:heading>
            <div class="flex items-center mt-1">
                <flux:icon name="arrow-up" class="text-green-500" variant="mini" />
                <flux:text class="text-green-500 ml-1">12.5%</flux:text>
                <flux:text class="text-zinc-400 ml-2">from last month</flux:text>
            </div>
        </div>
        <flux:icon name="currency-dollar" class="text-zinc-400" />
    </div>
</flux:card>
```

### Event Card

```html
<flux:card>
    <div class="flex gap-4">
        <div class="flex-shrink-0 w-16 text-center">
            <flux:heading class="text-red-500">15</flux:heading>
            <flux:text class="text-zinc-500">OCT</flux:text>
        </div>
        <div>
            <flux:heading>Annual Developer Conference</flux:heading>
            <flux:text class="mt-1">
                Join us for a day of talks, workshops, and networking with fellow developers.
            </flux:text>
            <div class="flex items-center gap-4 mt-4">
                <div class="flex items-center">
                    <flux:icon name="map-pin" variant="mini" class="text-zinc-400" />
                    <flux:text class="ml-1">San Francisco</flux:text>
                </div>
                <div class="flex items-center">
                    <flux:icon name="clock" variant="mini" class="text-zinc-400" />
                    <flux:text class="ml-1">9:00 AM - 5:00 PM</flux:text>
                </div>
            </div>
        </div>
    </div>
</flux:card>
```

## Customization

Cards can be customized with various utility classes:

```html
<!-- Border color -->
<flux:card class="border-blue-500">...</flux:card>

<!-- Background color -->
<flux:card class="bg-blue-50">...</flux:card>

<!-- Shadow -->
<flux:card class="shadow-lg">...</flux:card>

<!-- Hover effects -->
<flux:card class="hover:shadow-md transition-shadow">...</flux:card>

<!-- Spacing -->
<flux:card class="space-y-6">...</flux:card>

<!-- Rounded corners -->
<flux:card class="rounded-xl">...</flux:card>
```

## Reference

### flux:card

| Slot | Description |
| --- | --- |
| `default` | Content to display within the card. Can include headings, text, forms, buttons, and other components. |

| CSS | Description |
| --- | --- |
| `class` | Additional CSS classes applied to the card. Common uses: space-y-6 for spacing between child elements, max-w-md for width control, p-0 to remove padding. |

| Attribute | Description |
| --- | --- |
| `data-flux-card` | Applied to the root element for styling and identification. |