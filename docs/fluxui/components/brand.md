# flux:brand

Display your company or application's logo and name in a clean, consistent way across your interface.

## Basic Usage

```html
<flux:brand href="#" logo="/img/demo/logo.png" name="Acme Inc." />

<flux:brand href="#" name="Acme Inc.">
    <x-slot name="logo">
        <div class="size-6 rounded shrink-0 bg-accent text-accent-foreground flex items-center justify-center"><i class="font-serif font-bold">A</i></div>
    </x-slot>
</flux:brand>
```

## Logo Slot

Use the logo slot to provide a custom logo for your brand.

```html
<flux:brand href="#" name="Launchpad">
    <x-slot name="logo" class="size-6 rounded-full bg-cyan-500 text-white text-xs font-bold">
        <flux:icon name="rocket-launch" variant="micro" />
    </x-slot>
</flux:brand>
```

## Logo Only

Display just the logo without the company name by omitting the name prop.

```html
<flux:brand href="#" logo="/img/demo/logo.png" />
```

## Examples

### Header with Brand

```html
<flux:header class="px-4! w-full bg-zinc-50 dark:bg-zinc-800 rounded-lg border border-zinc-100 dark:border-white/5">
    <flux:brand href="#" name="Acme Inc.">
        <x-slot name="logo" class="bg-accent text-accent-foreground">
            <i class="font-serif font-bold">A</i>
        </x-slot>
    </flux:brand>
    <flux:navbar variant="outline">
        <flux:navbar.item href="#" current>Home</flux:navbar.item>
        <flux:navbar.item badge="12" href="#">Inbox</flux:navbar.item>
    </flux:navbar>
    <flux:spacer class="min-w-24" />
    <flux:profile circle :chevron="false" avatar="https://unavatar.io/x/calebporzio" />
</flux:header>
```

## Additional Examples

### Brand with Custom SVG Logo

```html
<flux:brand href="#" name="VectorWorks">
    <x-slot name="logo">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2L2 7L12 12L22 7L12 2Z" fill="#4F46E5"/>
            <path d="M2 17L12 22L22 17M2 12L12 17L22 12" stroke="#4F46E5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </x-slot>
</flux:brand>
```

### Brand in Sidebar

```html
<flux:sidebar class="bg-zinc-50 border-r">
    <div class="p-4">
        <flux:brand href="#" name="Dashboard Pro">
            <x-slot name="logo" class="bg-indigo-600 text-white">
                <flux:icon name="chart-bar" variant="micro" />
            </x-slot>
        </flux:brand>
    </div>
    <flux:navlist>
        <flux:navlist.item href="#" icon="home">Dashboard</flux:navlist.item>
        <flux:navlist.item href="#" icon="chart-bar">Analytics</flux:navlist.item>
        <flux:navlist.item href="#" icon="cog">Settings</flux:navlist.item>
    </flux:navlist>
</flux:sidebar>
```

### Brand with Dark/Light Mode Logo

```html
<flux:brand href="#" name="Adaptive UI">
    <x-slot name="logo">
        <img class="size-8 dark:hidden" src="/img/logo-light.png" alt="Light Logo">
        <img class="size-8 hidden dark:block" src="/img/logo-dark.png" alt="Dark Logo">
    </x-slot>
</flux:brand>
```

### Brand with Tagline

```html
<div class="flex items-center">
    <flux:brand href="#" logo="/img/company-logo.png" name="TechSolutions" />
    <span class="ml-2 text-xs text-zinc-500">| Enterprise Software</span>
</div>
```

### Centered Brand with Decorative Elements

```html
<div class="flex items-center justify-center py-6">
    <div class="h-px w-12 bg-zinc-200 mr-4"></div>
    <flux:brand href="#" name="Artisan Studio">
        <x-slot name="logo" class="bg-rose-500 text-white">
            <flux:icon name="paint-brush" variant="micro" />
        </x-slot>
    </flux:brand>
    <div class="h-px w-12 bg-zinc-200 ml-4"></div>
</div>
```

## Reference

### flux:brand

| Prop | Description |
| --- | --- |
| `name` | Company or application name to display next to the logo. |
| `logo` | URL to the image to display as logo, or can pass content via slot. |
| `alt` | Alternative text for the logo. |
| `href` | URL to navigate to when the brand is clicked. Default: '/'. |

| Slot | Description |
| --- | --- |
| `logo` | Custom content for the logo section, typically containing an image, SVG, or custom HTML. |