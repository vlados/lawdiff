# Getting Help

Our number one priority is to make Flux the best UI library for Livewire applications. If you're having trouble with Flux, please reach out to us for help.

## Found a Bug?

Please create an isolated, succinct, snippet of code that we can use to reproduce your issue and submit it via a GitHub issue on the livewire/flux repository.

When submitting a bug report, it's helpful to include:

1. A clear description of the problem
2. Steps to reproduce the issue
3. The expected behavior
4. The actual behavior
5. Screenshots if applicable
6. Information about your environment (Laravel version, Livewire version, Flux version, etc.)

## Have a Feature Request?

Please submit your feature request via a GitHub discussion on the livewire/flux repository.

When submitting a feature request, it's helpful to include:

1. A clear description of the feature
2. Use cases for the feature
3. Any examples of similar implementations in other libraries
4. Mockups or design ideas if applicable

## Need Help with Billing, Licensing, etc.

If you have any questions related to purchases, refunds, licensing, discounts, etc., you can email our support inbox.

## Common Issues and Solutions

### Component Styling Conflicts

When you pass in a class that conflicts with Flux's internal styling, you can use Tailwind's important (!) modifier:

```html
<flux:button class="bg-zinc-800! hover:bg-zinc-700!">
```

### Dark Mode Not Working

Make sure you have properly set up Tailwind CSS to use the selector strategy for dark mode:

```css
@import "tailwindcss";
@import '../../vendor/livewire/flux/dist/flux.css';
@custom-variant dark (&:where(.dark, .dark *));
```

Also ensure that the @fluxAppearance directive is included in your layout file:

```html
<head>
    ...
    @fluxAppearance
</head>
```

### Customizing Components

If you need to customize a component beyond what's possible with props and classes, publish the component into your project:

```bash
php artisan flux:publish
```

You will be prompted to search and select which components you want to publish.

### Wire:Model Not Working

Make sure you are using wire:model on the appropriate component. Some components require you to use wire:model on the group component rather than individual items:

```html
<!-- Correct -->
<flux:checkbox.group wire:model="options">
    <flux:checkbox value="1">Option 1</flux:checkbox>
    <flux:checkbox value="2">Option 2</flux:checkbox>
</flux:checkbox.group>

<!-- Incorrect -->
<flux:checkbox.group>
    <flux:checkbox wire:model="options" value="1">Option 1</flux:checkbox>
    <flux:checkbox wire:model="options" value="2">Option 2</flux:checkbox>
</flux:checkbox.group>
```

## Community Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Livewire Documentation](https://livewire.laravel.com/docs)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Heroicons](https://heroicons.com/) - The icon set used by Flux