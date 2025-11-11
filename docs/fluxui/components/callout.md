# flux:callout

Highlight important information or guide users toward key actions.

## Basic Usage

```html
<flux:callout icon="clock">
    <flux:callout.heading>Upcoming maintenance</flux:callout.heading>
    <flux:callout.text>
        Our servers will be undergoing scheduled maintenance this Sunday from 2 AM - 5 AM UTC. Some services may be temporarily unavailable.
        <flux:callout.link href="#">Learn more</flux:callout.link>
    </flux:callout.text>
</flux:callout>
```

## Icon Inside Heading

For a more compact layout, place the icon inside the heading by adding the icon prop to flux:callout.heading instead of the root flux:callout component.

```html
<flux:callout>
    <flux:callout.heading icon="newspaper">Policy update</flux:callout.heading>
    <flux:callout.text>We've updated our Terms of Service and Privacy Policy. Please review them to stay informed.</flux:callout.text>
</flux:callout>
```

## With Actions

Add action buttons to your callout to provide users with clear next steps.

```html
<flux:callout icon="clock">
    <flux:callout.heading>Subscription expiring soon</flux:callout.heading>
    <flux:callout.text>Your current plan will expire in 3 days. Renew now to avoid service interruption and continue accessing premium features.</flux:callout.text>
    <x-slot name="actions">
        <flux:button>Renew now</flux:button>
        <flux:button variant="ghost" href="/pricing">View plans</flux:button>
    </x-slot>
</flux:callout>
```

## Inline Actions

Use the inline prop to display actions inline with the callout.

```html
<flux:callout icon="cube" variant="secondary" inline>
    <flux:callout.heading>Your package is delayed</flux:callout.heading>
    <x-slot name="actions">
        <flux:button>Track order -></flux:button>
        <flux:button variant="ghost">Reschedule</flux:button>
    </x-slot>
</flux:callout>

<flux:callout icon="exclamation-triangle" variant="secondary" inline>
    <flux:callout.heading>Payment issue detected</flux:callout.heading>
    <flux:callout.text>Your last payment attempt failed. Update your billing details to prevent service interruption.</flux:callout.text>
    <x-slot name="actions">
        <flux:button>Update billing</flux:button>
    </x-slot>
</flux:callout>
```

## Dismissible

Add a close button, using the controls slot, to allow users to dismiss callouts they no longer care to see.

> Callouts do not include built-in dismiss functionality. This is intentional—dismissal behavior depends on your app's needs. You might want to remove it from the DOM on the frontend or persist the dismissal in a backend database.

```html
<flux:callout icon="bell" variant="secondary" inline x-data="{ visible: true }" x-show="visible">
    <flux:callout.heading class="flex gap-2 @max-md:flex-col items-start">Upcoming meeting <flux:text>10:00 AM</flux:text></flux:callout.heading>
    <x-slot name="controls">
        <flux:button icon="x-mark" variant="ghost" x-on:click="visible = false" />
    </x-slot>
</flux:callout>

<!-- Wrapping divs to add smooth exist transition... -->
<div x-data="{ visible: true }" x-show="visible" x-collapse>
    <div x-show="visible" x-transition>
        <flux:callout icon="finger-print" variant="secondary">
            <flux:callout.heading>Unusual login attempt</flux:callout.heading>
            <flux:callout.text>We detected a login from a new device in <span class="font-medium text-zinc-800 dark:text-white">New York, USA</span>. If this was you, no action is needed. If not, secure your account immediately.</flux:callout.text>
            <x-slot name="actions">
                <flux:button>Change password</flux:button>
                <flux:button variant="ghost">Review activity</flux:button>
            </x-slot>
            <x-slot name="controls">
                <flux:button icon="x-mark" variant="ghost" x-on:click="visible = false" />
            </x-slot>
        </flux:callout>
    </div>
</div>
```

## Variants

Use predefined variants to convey a specific tone or level of urgency.

```html
<flux:callout variant="secondary" icon="information-circle" heading="Your account has been successfully created." />
<flux:callout variant="success" icon="check-circle" heading="Your account is verified and ready to use." />
<flux:callout variant="warning" icon="exclamation-circle" heading="Please verify your account to unlock all features." />
<flux:callout variant="danger" icon="x-circle" heading="Something went wrong. Try again or contact support." />
```

## Colors

Use the color prop to change the color of the callout to match your use case.

```html
<flux:callout color="zinc" ... />
<flux:callout color="red" ... />
<flux:callout color="orange" ... />
<flux:callout color="amber" ... />
<flux:callout color="yellow" ... />
<flux:callout color="lime" ... />
<flux:callout color="green" ... />
<flux:callout color="emerald" ... />
<flux:callout color="teal" ... />
<flux:callout color="cyan" ... />
<flux:callout color="sky" ... />
<flux:callout color="blue" ... />
<flux:callout color="indigo" ... />
<flux:callout color="violet" ... />
<flux:callout color="purple" ... />
<flux:callout color="fuchsia" ... />
<flux:callout color="pink" ... />
<flux:callout color="rose" ... />
```

## Custom Icon

Use a custom icon to match your brand or specific use case using the icon slot.

```html
<flux:callout>
    <x-slot name="icon">
        <!-- Custom icon: https://lucide.dev/icons/alarm-clock -->
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alarm-clock"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2 2"/><path d="M5 3 2 6"/><path d="m22 6-3-3"/><path d="M6.38 18.7 4 21"/><path d="M17.64 18.67 20 21"/></svg>
    </x-slot>
    <flux:callout.heading>Notification system updated</flux:callout.heading>
    <flux:callout.text>
        <p>We've improved our notification system to deliver alerts faster and more reliably.</p>
    </flux:callout.text>
</flux:callout>
```

## Examples

### Feature Spotlight

Call attention to a new feature or update.

```html
<flux:callout icon="sparkles" color="purple">
    <flux:callout.heading>Have a question?</flux:callout.heading>
    <flux:callout.text>
        Try our new AI assistant, Jeffrey. Let him handle tasks and answer questions for you.
        <flux:callout.link href="#">Learn more</flux:callout.link>
    </flux:callout.text>
</flux:callout>
```

### Premium Upsell

Encourage users to upgrade.

```html
<flux:callout icon="shield-check" color="blue" inline>
    <flux:callout.heading>API access is restricted</flux:callout.heading>
    <flux:callout.text>Get access to all of our premium features and benefits.</flux:callout.text>
    <x-slot name="actions" class="@md:h-full m-0!">
        <flux:button>Upgrade to Pro -></flux:button>
    </x-slot>
</flux:callout>
```

### Upgrade Offer

Compact reminders to take advantage of special offers.

```html
<flux:callout icon="banknotes" color="lime" inline>
    <flux:callout.heading>You could save $4,900/yr on annual billing.</flux:callout.heading>
    <x-slot name="actions">
        <flux:button>Switch now -></flux:button>
    </x-slot>
</flux:callout>
```

### Engagement Prompt

Nudge users toward an action or feature.

```html
<flux:callout variant="secondary" icon="user-group">
    <flux:callout.heading>
        Team collaboration <flux:badge color="purple" size="sm" inset="top bottom">Available with Pro</flux:badge>
    </flux:callout.heading>
    <flux:callout.text>
        <p>Share projects, manage permissions, and collaborate in real time with your team. Upgrade now to access these features.</p>
    </flux:callout.text>
    <x-slot name="actions">
        <flux:button>Invite member</flux:button>
        <flux:button variant="ghost" class="@max-md:hidden">Manage team</flux:button>
    </x-slot>
</flux:callout>
```

## Reference

### flux:callout

| Prop | Description |
| --- | --- |
| `icon` | Name of the icon displayed next to the heading (e.g., clock). [Explore icons](/components/icon) |
| `icon:variant` | Variant of the icon displayed next to the heading (e.g., outline). [Explore icon variants](about:/components/icon#variants) |
| `variant` | Options: secondary, success, warning, danger. Default: secondary |
| `color` | Custom color (e.g., red, blue). [View available Tailwind colors ->](https://tailwindcss.com/docs/colors) |
| `inline` | If true, actions appear inline. Default: false. |
| `heading` | Shorthand for flux:callout.heading. |
| `text` | Shorthand for flux:callout.text. |

| Slot | Description |
| --- | --- |
| `icon` | Custom icon displayed next to the heading. |
| `actions` | Buttons or links inside the callout (flux:callout.button). |
| `controls` | Extra UI elements placed at the top right of the callout (e.g., close button). |

### flux:callout.heading

| Prop | Description |
| --- | --- |
| `icon` | Moves the icon inside the heading instead of the callout root. |
| `icon:variant` | Variant of the icon displayed next to the heading (e.g., outline). [Explore icon variants](about:/components/icon#variants) |

| Slot | Description |
| --- | --- |
| `icon` | Custom icon displayed next to the heading. |

### flux:callout.text

| Slot | Description |
| --- | --- |
| `default` | Text content inside the callout. |

### flux:callout.link

| Prop | Description |
| --- | --- |
| `href` | The URL the link points to. |
| `external` | If true, the link opens in a new tab. Default: false. |