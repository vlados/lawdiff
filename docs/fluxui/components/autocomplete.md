# flux:autocomplete

Enhance an input field with autocomplete suggestions. This component provides a searchable dropdown of options that appear as the user types.

## Basic Usage

```html
<flux:autocomplete wire:model="state" label="State of residence">
    <flux:autocomplete.item>Alabama</flux:autocomplete.item>
    <flux:autocomplete.item>Arkansas</flux:autocomplete.item>
    <flux:autocomplete.item>California</flux:autocomplete.item>
    <!-- ... -->
</flux:autocomplete>
```

## With Custom Values

You can specify a different value than the displayed text by using the `value` prop:

```html
<flux:autocomplete wire:model="stateCode" label="State of residence">
    <flux:autocomplete.item value="AL">Alabama</flux:autocomplete.item>
    <flux:autocomplete.item value="AR">Arkansas</flux:autocomplete.item>
    <flux:autocomplete.item value="CA">California</flux:autocomplete.item>
    <!-- ... -->
</flux:autocomplete>
```

## With Icons

You can add icons to enhance the visual appeal of your autocomplete component:

```html
<flux:autocomplete wire:model="country" label="Country" icon="globe">
    <flux:autocomplete.item value="US">United States</flux:autocomplete.item>
    <flux:autocomplete.item value="CA">Canada</flux:autocomplete.item>
    <flux:autocomplete.item value="MX">Mexico</flux:autocomplete.item>
    <!-- ... -->
</flux:autocomplete>
```

## Disabled Items

You can disable specific items from being selected:

```html
<flux:autocomplete wire:model="plan" label="Subscription plan">
    <flux:autocomplete.item value="basic">Basic</flux:autocomplete.item>
    <flux:autocomplete.item value="pro">Professional</flux:autocomplete.item>
    <flux:autocomplete.item value="enterprise" disabled>Enterprise (Contact sales)</flux:autocomplete.item>
</flux:autocomplete>
```

## Clearable

Make the input clearable with a single click:

```html
<flux:autocomplete wire:model="category" label="Category" clearable>
    <flux:autocomplete.item>Electronics</flux:autocomplete.item>
    <flux:autocomplete.item>Clothing</flux:autocomplete.item>
    <flux:autocomplete.item>Home & Garden</flux:autocomplete.item>
    <!-- ... -->
</flux:autocomplete>
```

## With Description

Add a description to provide additional context:

```html
<flux:autocomplete 
    wire:model="timeZone" 
    label="Time Zone" 
    description="Select your local time zone for accurate scheduling">
    <flux:autocomplete.item value="America/New_York">Eastern Time (ET)</flux:autocomplete.item>
    <flux:autocomplete.item value="America/Chicago">Central Time (CT)</flux:autocomplete.item>
    <flux:autocomplete.item value="America/Denver">Mountain Time (MT)</flux:autocomplete.item>
    <flux:autocomplete.item value="America/Los_Angeles">Pacific Time (PT)</flux:autocomplete.item>
    <!-- ... -->
</flux:autocomplete>
```

## Different Sizes

Adjust the size of the autocomplete input:

```html
<flux:autocomplete wire:model="tag" label="Tag" size="sm">
    <!-- Items -->
</flux:autocomplete>
```

## Dynamic Options with Livewire

You can dynamically generate autocomplete items using Livewire:

```html
<flux:autocomplete wire:model="selectedUser" label="User">
    @foreach ($this->users as $user)
        <flux:autocomplete.item value="{{ $user->id }}">
            {{ $user->name }}
        </flux:autocomplete.item>
    @endforeach
</flux:autocomplete>

<!-- In your Livewire component -->
<!--
public $selectedUser = null;
public $searchTerm = '';

#[\Livewire\Attributes\Computed]
public function users()
{
    return \App\Models\User::query()
        ->when($this->searchTerm, fn($query) => 
            $query->where('name', 'like', '%' . $this->searchTerm . '%')
        )
        ->limit(10)
        ->get();
}
-->
```

## Reference

### flux:autocomplete

| Prop | Description |
| --- | --- |
| `wire:model` | The name of the Livewire property to bind the input value to. |
| `type` | HTML input type (e.g., text, email, password, file, date). Default: text. |
| `label` | Label text displayed above the input. |
| `description` | Descriptive text displayed below the label. |
| `placeholder` | Placeholder text displayed when the input is empty. |
| `size` | Size of the input. Options: sm, xs. |
| `variant` | Visual style variant. Options: filled. Default: outline. |
| `disabled` | If true, prevents user interaction with the input. |
| `readonly` | If true, makes the input read-only. |
| `invalid` | If true, applies error styling to the input. |
| `multiple` | For file inputs, allows selecting multiple files. |
| `mask` | Input mask pattern using Alpine's mask plugin. Example: 99/99/9999. |
| `icon` | Name of the icon displayed at the start of the input. |
| `icon:trailing` | Name of the icon displayed at the end of the input. |
| `kbd` | Keyboard shortcut hint displayed at the end of the input. |
| `clearable` | If true, displays a clear button when the input has content. |
| `copyable` | If true, displays a copy button to copy the input's content. |
| `viewable` | For password inputs, if true, displays a toggle to show/hide the password. |
| `as` | Render the input as a different element. Options: button. Default: input. |
| `class:input` | CSS classes applied directly to the input element instead of the wrapper. |

| Slot | Description |
| --- | --- |
| `icon` | Custom content displayed at the start of the input (e.g., icons). |
| `icon:leading` | Custom content displayed at the start of the input (e.g., icons). |
| `icon:trailing` | Custom content displayed at the end of the input (e.g., buttons). |

### flux:autocomplete.item

| Prop | Description |
| --- | --- |
| `value` | The value to be set when this item is selected. If not provided, the item's text content is used. |
| `disabled` | If present or true, the item cannot be selected. |