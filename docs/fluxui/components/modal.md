# flux:modal

Display content in a layer above the main page. Ideal for confirmations, alerts, and forms.

## Basic Usage

```html
<flux:modal.trigger name="edit-profile">
    <flux:button>Edit profile</flux:button>
</flux:modal.trigger>

<flux:modal name="edit-profile" class="md:w-96">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Update profile</flux:heading>
            <flux:text class="mt-2">Make changes to your personal details.</flux:text>
        </div>
        <flux:input label="Name" placeholder="Your name" />
        <flux:input label="Date of birth" type="date" />
        <div class="flex">
            <flux:spacer />
            <flux:button type="submit" variant="primary">Save changes</flux:button>
        </div>
    </div>
</flux:modal>
```

## Unique Modal Names

If you are placing modals inside a loop, ensure that you are dynamically generating unique modal names. Otherwise, one modal trigger, will trigger all modals of that name on the page causing unexpected behavior.

```php
@foreach ($users as $user)
    <flux:modal :name="'edit-profile-'.$user->id">
        ...
    </flux:modal>
@endforeach
```

## Livewire Methods

In addition to triggering modals in your Blade templates, you can also control them directly from Livewire.

Consider a "confirm" modal in your Blade template like so:

```html
<flux:modal name="confirm">
    <!-- ... -->
</flux:modal>
```

You can now open and close this modal from your Livewire component using the following methods:

```php
<?php
class ShowPost extends \Livewire\Component {
    public function delete() {
        // Control "confirm" modals anywhere on the page...
        Flux::modal('confirm')->show();
        Flux::modal('confirm')->close();
        
        // Control "confirm" modals within this Livewire component...
        $this->modal('confirm')->show();
        $this->modal('confirm')->close();
        
        // Closes all modals on the page...
        Flux::modals()->close();
    }
}
```

## JavaScript Methods

You can also control modals from Alpine directly using Flux's magic methods:

```html
<button x-on:click="$flux.modal('confirm').show()">
    Open modal
</button>

<button x-on:click="$flux.modal('confirm').close()">
    Close modal
</button>

<button x-on:click="$flux.modals().close()">
    Close all modals
</button>
```

Or you can use the window.Flux global object to control modals from any JavaScript in your application:

```javascript
// Control "confirm" modals anywhere on the page...
Flux.modal('confirm').show()
Flux.modal('confirm').close()

// Closes all modals on the page...
Flux.modals().close()
```

## Data Binding

If you prefer, you can bind a Livewire property directly to a modal to control its states from your Livewire component.

Consider a confirmation modal in your Blade template like so:

```html
<flux:modal wire:model.self="showConfirmModal">
    <!-- ... -->
</flux:modal>
```

It's important to add the `.self` modifier to the wire:model attribute to prevent nested elements from dispatching input events that would interfere with the state of the modal.

You can now open and close this modal from your Livewire component by toggling the wire:model property.

```php
<?php
class ShowPost extends \Livewire\Component {
    public $showConfirmModal = false;
    
    public function delete() {
        $this->showConfirmModal = true;
    }
}
```

One advantage of this approach is being able to control the state of the modal directly from the browser without making a server roundtrip:

```html
<flux:button x-on:click="$wire.showConfirmModal = true">Delete post</flux:button>
```

## Close Events

If you need to perform some logic after a modal closes, you can register a close listener like so:

```html
<flux:modal @close="someLivewireAction">
    <!-- ... -->
</flux:modal>
```

You can also use `wire:close` or `x-on:close` if you prefer those syntaxes.

## Cancel Events

If you need to perform some logic after a modal is cancelled, you can register a cancel listener like so:

```html
<flux:modal @cancel="someLivewireAction">
    <!-- ... -->
</flux:modal>
```

You can also use `wire:cancel` or `x-on:cancel` if you prefer those syntaxes.

## Disable Click Outside

By default, clicking outside the modal will close it. If you want to disable this behavior, you can use the `:dismissible="false"` prop.

```html
<flux:modal :dismissible="false">
    <!-- ... -->
</flux:modal>
```

## Confirmation

Prompt a user for confirmation before performing a dangerous action.

```html
<flux:modal.trigger name="delete-profile">
    <flux:button variant="danger">Delete</flux:button>
</flux:modal.trigger>

<flux:modal name="delete-profile" class="min-w-[22rem]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Delete project?</flux:heading>
            <flux:text class="mt-2">
                <p>You're about to delete this project.</p>
                <p>This action cannot be reversed.</p>
            </flux:text>
        </div>
        <div class="flex gap-2">
            <flux:spacer />
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="danger">Delete project</flux:button>
        </div>
    </div>
</flux:modal>
```

## Flyout

Use the "flyout" variant for a more anchored and long-form dialog.

```html
<flux:modal.trigger name="edit-profile">
    <flux:button>Edit profile</flux:button>
</flux:modal.trigger>

<flux:modal name="edit-profile" variant="flyout">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Update profile</flux:heading>
            <flux:text class="mt-2">Make changes to your personal details.</flux:text>
        </div>
        <flux:input label="Name" placeholder="Your name" />
        <flux:input label="Date of birth" type="date" />
        <div class="flex">
            <flux:spacer />
            <flux:button type="submit" variant="primary">Save changes</flux:button>
        </div>
    </div>
</flux:modal>
```

### Flyout Positioning

By default, flyouts will open from the right. You can change this behavior by passing "left", or "bottom" into the position prop.

```html
<flux:modal variant="flyout" position="left">
    <!-- ... -->
</flux:modal>
```

## Additional Examples

### Multiple Steps Modal

```html
<flux:modal.trigger name="multi-step-modal">
    <flux:button>Start Process</flux:button>
</flux:modal.trigger>

<flux:modal name="multi-step-modal" class="md:w-96" x-data="{ step: 1 }">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg" x-text="step === 1 ? 'Step 1: Basic Info' : 'Step 2: Preferences'"></flux:heading>
            <flux:text class="mt-2" x-text="step === 1 ? 'Enter your basic information.' : 'Select your preferences.'"></flux:text>
        </div>
        
        <div x-show="step === 1">
            <flux:input label="Name" placeholder="Your name" />
            <flux:input label="Email" type="email" placeholder="your@email.com" class="mt-4" />
        </div>
        
        <div x-show="step === 2">
            <flux:checkbox.group label="Notifications">
                <flux:checkbox label="Email notifications" checked />
                <flux:checkbox label="Push notifications" />
            </flux:checkbox.group>
        </div>
        
        <div class="flex justify-between">
            <flux:button x-show="step === 2" @click="step = 1" variant="ghost">Back</flux:button>
            <flux:spacer x-show="step === 1" />
            <flux:button x-show="step === 1" @click="step = 2" variant="primary">Next</flux:button>
            <flux:button x-show="step === 2" variant="primary">Submit</flux:button>
        </div>
    </div>
</flux:modal>
```

### Modal with Image

```html
<flux:modal name="product-detail" class="md:max-w-2xl">
    <div class="flex flex-col md:flex-row gap-6">
        <div class="md:w-1/2">
            <img src="/img/product.jpg" alt="Product" class="w-full rounded-md">
        </div>
        <div class="md:w-1/2 space-y-4">
            <flux:heading size="lg">Premium Headphones</flux:heading>
            <flux:text>Experience crystal clear audio with our premium noise-cancelling headphones.</flux:text>
            <flux:heading size="lg">$299.99</flux:heading>
            <div class="flex gap-2">
                <flux:button variant="primary" class="w-full">Add to Cart</flux:button>
                <flux:modal.close>
                    <flux:button variant="ghost">Close</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </div>
</flux:modal>
```

### Form Submission Modal

```html
<flux:modal name="contact-form" class="md:w-96" x-data="{ sending: false }">
    <form @submit.prevent="sending = true; setTimeout(() => sending = false, 1500)">
        <div class="space-y-6">
            <flux:heading size="lg">Contact Us</flux:heading>
            <flux:input label="Name" required />
            <flux:input label="Email" type="email" required />
            <flux:textarea label="Message" rows="4" required />
            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" x-bind:disabled="sending">
                    <span x-show="!sending">Send Message</span>
                    <span x-show="sending" class="flex items-center">
                        <flux:icon.loading class="mr-2" /> Sending...
                    </span>
                </flux:button>
            </div>
        </div>
    </form>
</flux:modal>
```

## Reference

### flux:modal

| Prop | Description |
| --- | --- |
| `name` | Unique identifier for the modal. Required when using triggers. |
| `variant` | Visual style of the modal. Options: default, flyout, bare. |
| `position` | For flyout modals, the direction they open from. Options: right (default), left, bottom. |
| `dismissible` | If false, prevents closing the modal by clicking outside. Default: true. |
| `wire:model` | Optional Livewire property to bind the modal's open state to. |

| Event | Description |
| --- | --- |
| `close` | Triggered when the modal is closed by any means. |
| `cancel` | Triggered when the modal is closed by clicking outside or pressing escape. |

| Slot | Description |
| --- | --- |
| `default` | The modal content. |

| Class | Description |
| --- | --- |
| `w-*` | Common use: md:w-96 for width. |

### flux:modal.trigger

| Prop | Description |
| --- | --- |
| `name` | Name of the modal to trigger. Must match the modal's name. |
| `shortcut` | Keyboard shortcut to open the modal (e.g., cmd.k). |

| Slot | Description |
| --- | --- |
| `default` | The trigger element (e.g., button). |

### flux:modal.close

| Slot | Description |
| --- | --- |
| `default` | The close trigger element (e.g., button). |

### Flux::modal()

PHP method for controlling modals from Livewire components.

| Parameter | Description |
| --- | --- |
| `default\|name` | Name of the modal to control. |

| Method | Description |
| --- | --- |
| `close()` | Closes the modal. |

### Flux::modals()

PHP method for controlling all modals on the page.

| Method | Description |
| --- | --- |
| `close()` | Closes all modals on the page. |

### $flux.modal()

Alpine.js magic method for controlling modals.

| Parameter | Description |
| --- | --- |
| `default\|name` | Name of the modal to control. |

| Method | Description |
| --- | --- |
| `show()` | Shows the modal. |
| `close()` | Closes the modal. |