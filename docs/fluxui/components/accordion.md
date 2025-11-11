# flux:accordion

Collapse and expand sections of content. Perfect for FAQs and content-heavy areas.

## Basic Usage

```html
<flux:accordion>
    <flux:accordion.item>
        <flux:accordion.heading>What's your refund policy?</flux:accordion.heading>
        <flux:accordion.content>
            If you are not satisfied with your purchase, we offer a 30-day money-back guarantee. Please contact our support team for assistance.
        </flux:accordion.content>
    </flux:accordion.item>
    <flux:accordion.item>
        <flux:accordion.heading>Do you offer any discounts for bulk purchases?</flux:accordion.heading>
        <flux:accordion.content>
            Yes, we offer special discounts for bulk orders. Please reach out to our sales team with your requirements.
        </flux:accordion.content>
    </flux:accordion.item>
    <flux:accordion.item>
        <flux:accordion.heading>How do I track my order?</flux:accordion.heading>
        <flux:accordion.content>
            Once your order is shipped, you will receive an email with a tracking number. Use this number to track your order on our website.
        </flux:accordion.content>
    </flux:accordion.item>
</flux:accordion>
```

## Shorthand

You can save on markup by passing the heading text as a prop directly.

```html
<flux:accordion.item heading="What's your refund policy?">
    If you are not satisfied with your purchase, we offer a 30-day money-back guarantee. Please contact our support team for assistance.
</flux:accordion.item>
```

## With Transition

Enable expanding transitions for smoother interactions.

```html
<flux:accordion transition>
    <!-- ... -->
</flux:accordion>
```

## Disabled

Restrict an accordion item from being expanded.

```html
<flux:accordion.item disabled>
    <!-- ... -->
</flux:accordion.item>
```

## Exclusive

Enforce that only a single accordion item is expanded at a time.

```html
<flux:accordion exclusive>
    <!-- ... -->
</flux:accordion>
```

## Expanded

Expand a specific accordion by default.

```html
<flux:accordion.item expanded>
    <!-- ... -->
</flux:accordion.item>
```

## Leading Icon

Display the icon before the heading instead of after it.

```html
<flux:accordion variant="reverse">
    <!-- ... -->
</flux:accordion>
```

## Additional Examples

### Styled FAQ Section

```html
<div class="max-w-3xl mx-auto">
    <flux:heading size="xl" class="mb-6">Frequently Asked Questions</flux:heading>
    <flux:accordion transition exclusive>
        <flux:accordion.item expanded heading="What's your refund policy?">
            <div class="space-y-2">
                <p>If you are not satisfied with your purchase, we offer a 30-day money-back guarantee.</p>
                <p>To request a refund:</p>
                <ol class="list-decimal ml-6">
                    <li>Log into your account</li>
                    <li>Navigate to Order History</li>
                    <li>Select the order and click "Request Refund"</li>
                </ol>
            </div>
        </flux:accordion.item>
        <flux:accordion.item heading="Do you offer any discounts for bulk purchases?">
            Yes, we offer special discounts for bulk orders. Please reach out to our sales team with your requirements.
        </flux:accordion.item>
        <flux:accordion.item heading="How do I track my order?">
            Once your order is shipped, you will receive an email with a tracking number. Use this number to track your order on our website.
        </flux:accordion.item>
    </flux:accordion>
</div>
```

### Nested Accordions

```html
<flux:accordion>
    <flux:accordion.item heading="Shipping Information">
        <div class="space-y-4">
            <p>We ship to all countries worldwide. Shipping costs depend on your location and the products ordered.</p>
            
            <flux:accordion>
                <flux:accordion.item heading="Domestic Shipping">
                    <ul class="list-disc ml-6">
                        <li>Standard Shipping: 3-5 business days</li>
                        <li>Express Shipping: 1-2 business days</li>
                    </ul>
                </flux:accordion.item>
                <flux:accordion.item heading="International Shipping">
                    <ul class="list-disc ml-6">
                        <li>Standard Shipping: 7-14 business days</li>
                        <li>Express Shipping: 3-5 business days</li>
                    </ul>
                </flux:accordion.item>
            </flux:accordion>
        </div>
    </flux:accordion.item>
</flux:accordion>
```

### Accordion with Rich Content

```html
<flux:accordion>
    <flux:accordion.item heading="Product Specifications">
        <div class="space-y-4">
            <table class="w-full border-collapse">
                <tr class="border-b">
                    <td class="py-2 font-medium">Dimensions</td>
                    <td class="py-2">10" x 8" x 2"</td>
                </tr>
                <tr class="border-b">
                    <td class="py-2 font-medium">Weight</td>
                    <td class="py-2">2.5 lbs</td>
                </tr>
                <tr class="border-b">
                    <td class="py-2 font-medium">Material</td>
                    <td class="py-2">Aluminum</td>
                </tr>
                <tr class="border-b">
                    <td class="py-2 font-medium">Color Options</td>
                    <td class="py-2">Silver, Black, Gold</td>
                </tr>
            </table>
            
            <div class="flex justify-center">
                <img src="product-diagram.jpg" alt="Product Dimensions Diagram" class="max-w-sm" />
            </div>
        </div>
    </flux:accordion.item>
</flux:accordion>
```

### Accordion with Custom Styling

```html
<flux:accordion class="rounded-lg border border-blue-200 bg-blue-50">
    <flux:accordion.item class="border-b border-blue-200 last:border-0">
        <flux:accordion.heading class="text-blue-800 font-medium">
            How do I reset my password?
        </flux:accordion.heading>
        <flux:accordion.content class="bg-white">
            <p>You can reset your password by clicking on the "Forgot Password" link on the login page.</p>
        </flux:accordion.content>
    </flux:accordion.item>
    <!-- Additional items... -->
</flux:accordion>
```

## Reference

### flux:accordion

| Prop | Description |
| --- | --- |
| `variant` | When set to reverse, displays the icon before the heading instead of after it. |
| `transition` | If true, enables expanding transitions for smoother interactions. Default: false. |
| `exclusive` | If true, only one accordion item can be expanded at a time. Default: false. |

### flux:accordion.item

| Prop | Description |
| --- | --- |
| `heading` | Shorthand for flux:accordion.heading content. |
| `expanded` | If true, the accordion item is expanded by default. Default: false. |
| `disabled` | If true, the accordion item cannot be expanded or collapsed. Default: false. |

### flux:accordion.heading

| Slot | Description |
| --- | --- |
| `default` | The heading text. |

### flux:accordion.content

| Slot | Description |
| --- | --- |
| `default` | The content to display when the accordion item is expanded. |