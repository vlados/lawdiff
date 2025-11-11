# flux:switch

Toggle a setting on or off. Suitable for binary options like enabling or disabling features.

Use switches as auto-saving controls outside forms; checkboxes otherwise.

## Basic Usage

```html
<flux:field variant="inline">
    <flux:label>Enable notifications</flux:label>
    <flux:switch wire:model.live="notifications" />
    <flux:error name="notifications" />
</flux:field>
```

## Fieldset

Group related switches within a fieldset.

```html
<flux:fieldset>
    <flux:legend>Email notifications</flux:legend>
    <div class="space-y-4">
        <flux:switch wire:model.live="communication" label="Communication emails" description="Receive emails about your account activity." />
        <flux:separator variant="subtle" />
        <flux:switch wire:model.live="marketing" label="Marketing emails" description="Receive emails about new products, features, and more." />
        <flux:separator variant="subtle" />
        <flux:switch wire:model.live="social" label="Social emails" description="Receive emails for friend requests, follows, and more." />
        <flux:separator variant="subtle" />
        <flux:switch wire:model.live="security" label="Security emails" description="Receive emails about your account activity and security." />
    </div>
</flux:fieldset>
```

## Left Align

Left align switches for more compact layouts using the align prop.

```html
<flux:fieldset>
    <flux:legend>Email notifications</flux:legend>
    <div class="space-y-3">
        <flux:switch label="Communication emails" align="left" />
        <flux:switch label="Marketing emails" align="left" />
        <flux:switch label="Social emails" align="left" />
        <flux:switch label="Security emails" align="left" />
    </div>
</flux:fieldset>
```

## When to Use Switches vs. Checkboxes

- **Switches**: Use for toggles that take immediate effect (like turning on/off features)
- **Checkboxes**: Use for form inputs that require submission 

Switches are best for settings that save automatically as soon as the user flips the switch. Checkboxes are better for collecting data in forms that will be submitted later.

## Usage with Livewire

For auto-saving functionality, use the `.live` modifier with your `wire:model`:

```html
<flux:switch wire:model.live="notifications" label="Enable notifications" />
```

This will automatically update the server-side value when the switch is toggled.

## Switch with Description

Add a description below the switch label to provide additional context:

```html
<flux:switch 
    wire:model.live="darkMode" 
    label="Dark Mode" 
    description="Switch to a darker color theme that's easier on the eyes." 
/>
```

## Disabled State

Prevent user interaction with a switch by adding the `disabled` attribute:

```html
<flux:switch 
    wire:model="featureAccess" 
    label="Advanced Features" 
    description="This feature requires a premium subscription." 
    disabled 
/>
```

## Reference

### flux:switch

| Prop | Description |
| --- | --- |
| `wire:model` | Binds the switch to a Livewire property. See the [wire:model documentation](https://livewire.laravel.com/docs/wire-model) for more information. |
| `label` | Label text displayed above the switch. When provided, wraps the switch in a flux:field component with an adjacent flux:label component. See the [field component](/components/field). |
| `description` | Help text displayed below the switch. When provided alongside label, appears between the label and switch within the flux:field wrapper. See the [field component](/components/field). |
| `align` | Alignment of the switch relative to its label. Options: right\|start (default), left\|end. |
| `disabled` | Prevents user interaction with the switch. |

| Attribute | Description |
| --- | --- |
| `data-flux-switch` | Applied to the root element for styling and identification. |
| `data-checked` | Applied when the switch is in the "on" state. |