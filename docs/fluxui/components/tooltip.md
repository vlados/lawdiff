# flux:tooltip

Provide additional information when users hover over or focus on an element.

Because tooltips rely on hover states, touch devices like mobile phones often don't show them. Therefore, it is recommended that you convey essential information via the UI rather than relying on a tooltip for mobile users.

## Basic Usage

```html
<flux:tooltip content="Settings">
    <flux:button icon="cog-6-tooth" icon:variant="outline" />
</flux:tooltip>
```

As a shorthand, you can pass a tooltip prop into a button component directly:

```html
<flux:button tooltip="Settings" ... />
```

## Info Tooltip

In cases where a tooltip's content is essential, you should make it toggleable. This way, users on touch devices will be able to trigger it on click/press rather than hover.

```html
<flux:heading class="flex items-center gap-2">
    Tax identification number
    <flux:tooltip toggleable>
        <flux:button icon="information-circle" size="sm" variant="ghost" />
        <flux:tooltip.content class="max-w-[20rem] space-y-2">
            <p>For US businesses, enter your 9-digit Employer Identification Number (EIN) without hyphens.</p>
            <p>For European companies, enter your VAT number including the country prefix (e.g., DE123456789).</p>
        </flux:tooltip.content>
    </flux:tooltip>
</flux:heading>
```

## Position

Position tooltips around the element for optimal visibility. Choose from top, right, bottom, or left.

```html
<flux:tooltip content="Settings" position="top">
    <flux:button icon="cog-6-tooth" icon:variant="outline" />
</flux:tooltip>

<flux:tooltip content="Settings" position="right">
    <flux:button icon="cog-6-tooth" icon:variant="outline" />
</flux:tooltip>

<flux:tooltip content="Settings" position="bottom">
    <flux:button icon="cog-6-tooth" icon:variant="outline" />
</flux:tooltip>

<flux:tooltip content="Settings" position="left">
    <flux:button icon="cog-6-tooth" icon:variant="outline" />
</flux:tooltip>
```

## Disabled Buttons

By default, tooltips on disabled buttons won't be triggered because pointer events are disabled as well. However, as a workaround, you can target a wrapping element instead of the button directly.

```html
<flux:tooltip content="Cannot merge until reviewed by a team member">
    <div>
        <flux:button disabled icon="arrow-turn-down-right">Merge pull request</flux:button>
    </div>
</flux:tooltip>
```

## Additional Examples

### With Keyboard Shortcut

Display a keyboard shortcut within the tooltip:

```html
<flux:tooltip content="Save document" kbd="⌘S">
    <flux:button icon="document-arrow-down">Save</flux:button>
</flux:tooltip>
```

### Rich Tooltip Content

Create more complex tooltip content:

```html
<flux:tooltip>
    <flux:button>User Profile</flux:button>
    <flux:tooltip.content>
        <div class="flex items-center gap-3">
            <flux:avatar size="sm" src="https://example.com/avatar.jpg" />
            <div>
                <div class="font-medium">John Doe</div>
                <div class="text-xs text-zinc-500">Administrator</div>
            </div>
        </div>
    </flux:tooltip.content>
</flux:tooltip>
```

### Input Validation Help

Use tooltips to provide input validation help:

```html
<flux:field>
    <flux:label class="flex items-center gap-2">
        Password
        <flux:tooltip toggleable>
            <flux:button icon="information-circle" size="xs" variant="ghost" inset />
            <flux:tooltip.content>
                Password must:
                <ul class="list-disc ml-4 mt-1">
                    <li>Be at least 8 characters</li>
                    <li>Include one uppercase letter</li>
                    <li>Include one number</li>
                    <li>Include one special character</li>
                </ul>
            </flux:tooltip.content>
        </flux:tooltip>
    </flux:label>
    <flux:input type="password" />
</flux:field>
```

### Interactive Tooltips

Make tooltips interactive for more complex interactions:

```html
<flux:tooltip interactive toggleable>
    <flux:button icon="bell">Notifications</flux:button>
    <flux:tooltip.content>
        <div class="p-2">
            <flux:heading size="sm" class="mb-2">Notification Settings</flux:heading>
            <div class="space-y-2">
                <flux:checkbox label="Email notifications" checked />
                <flux:checkbox label="Push notifications" />
                <flux:checkbox label="SMS notifications" />
            </div>
            <div class="mt-3">
                <flux:button size="sm" variant="ghost" class="w-full">Save</flux:button>
            </div>
        </div>
    </flux:tooltip.content>
</flux:tooltip>
```

### Tooltip on Form Elements

Provide additional context for form fields:

```html
<flux:tooltip content="Enter your 9-digit tax ID (e.g., 123-45-6789)" position="right" align="start">
    <flux:input label="Tax ID" placeholder="123-45-6789" />
</flux:tooltip>
```

## Reference

### flux:tooltip

| Prop | Description |
| --- | --- |
| `content` | Text content to display in the tooltip. Alternative to using the flux:tooltip.content component. |
| `position` | Position of the tooltip relative to the trigger element. Options: top (default), right, bottom, left. |
| `align` | Alignment of the tooltip. Options: center (default), start, end. |
| `gap` | Spacing between the trigger element and the tooltip. Default: 5px. |
| `offset` | Offset of the tooltip from the trigger element. Default: 0px. |
| `toggleable` | Makes the tooltip clickable instead of hover-only. Useful for touch devices. |
| `interactive` | Uses the proper ARIA attributes (aria-expanded and aria-controls) to signal that the tooltip has interactive content. |
| `kbd` | Keyboard shortcut hint displayed at the end of the tooltip. |

| Attribute | Description |
| --- | --- |
| `data-flux-tooltip` | Applied to the root element for styling and identification. |

### flux:tooltip.content

| Prop | Description |
| --- | --- |
| `kbd` | Keyboard shortcut hint displayed at the end of the tooltip content. |