# Installation Guide

Flux is a robust, hand-crafted UI component library for your Livewire applications. It's built using [Tailwind CSS](https://tailwindcss.com/) and provides a set of components that are easy to use and customize.

## Prerequisites

Before installing Flux, you should have a Laravel project with Livewire installed.

## Getting Started

### 1. Install Flux

Flux can be installed via composer from your project root:

```bash
composer require livewire/flux
```

### 2. Install Flux Pro (optional)

If you have purchased a Flux Pro license, you can install it using the following command:

```bash
php artisan flux:activate
```

During the activation process, you will be prompted to enter an email and license key.

*Note: The above command will create an auth.json file in your project's root directory. This file contains your email and license key for downloading and installing Flux and should not be added to version control.*

Because auth.json is not version controlled, you will need to manually recreate it in every new project environment.

### 3. Include Flux Assets

Now, add the @fluxAppearance and @fluxScripts Blade directives to your layout file:

```html
<head>
    ...
    @fluxAppearance
</head>
<body>
    ...
    @fluxScripts
</body>
```

### 4. Set Up Tailwind CSS

The last step is to set up Tailwind CSS. Flux uses Tailwind CSS for its default styling.

*Flux v2.0 requires Tailwind CSS v4.0 or later.*

If you already have Tailwind installed in your project, just add the following configuration to your resources/css/app.css file:

```css
@import 'tailwindcss';
@import '../../vendor/livewire/flux/dist/flux.css';
@custom-variant dark (&:where(.dark, .dark *));
```

If you don't have Tailwind installed, you can learn how to install it on the [Tailwind website](https://tailwindcss.com/docs/guides/laravel).

### 5. Use the Inter Font Family (Optional)

Although completely optional, we recommend using the [Inter font family](https://rsms.me/inter) for your application.

Add the following to the head tag in your layout file to ensure the font is loaded:

```html
<head>
    ...
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />
</head>
```

You can configure Tailwind to use this font family in your resources/css/app.css file:

```css
@import 'tailwindcss';
@import '../../vendor/livewire/flux/dist/flux.css';
...
@theme {
    --font-sans: Inter, sans-serif;
}
```

## Publishing Components

To keep things simple, you can use the internal Flux components in your Blade files directly. However, if you'd like to customize a specific Flux component, you can publish its blade file(s) into your project using the following Artisan command:

```bash
php artisan flux:publish
```

You will be prompted to search and select which components you want to publish. If you want to publish all components at once, you can use the --all flag.

## Keeping Flux Updated

To ensure you have the latest version of Flux, regularly update your composer dependencies:

```bash
composer update livewire/flux livewire/flux-pro
```

If you've published Flux components, make sure to check the changelog for any breaking changes before updating.

## Activating using Laravel Forge

If you are using Laravel Forge, you can take advantage of their built-in [Packages](https://forge.laravel.com/docs/sites/packages.html) feature for authenticating private composer packages.

Laravel Forge allows you to manage packages on a server or site level. If you have multiple sites using Flux, then it's recommended to manage Packages on the server level.

To authenticate Flux, head over to the packages page on either the server or site and click the "Add Credential" button to authenticate with a new private composer package and enter the following details:

* Enter composer.fluxui.dev as the Repository URL
* Enter your Flux account email as the username
* Enter your Flux license key