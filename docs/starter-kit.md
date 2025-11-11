# Laravel Starter Kits Documentation

Source: https://laravel.com/docs/12.x/starter-kits

## Table of Contents

* [Introduction](#introduction)
* [Creating an Application Using a Starter Kit](#creating-an-application)
* [Available Starter Kits](#available-starter-kits)
  + [React](#react)
  + [Vue](#vue)
  + [Livewire](#livewire)
* [Starter Kit Customization](#starter-kit-customization)
  + [React](#react-customization)
  + [Vue](#vue-customization)
  + [Livewire](#livewire-customization)
* [WorkOS AuthKit Authentication](#workos)
* [Inertia SSR](#inertia-ssr)
* [Community Maintained Starter Kits](#community-maintained-starter-kits)
* [Frequently Asked Questions](#faqs)

## Introduction

To give you a head start building your new Laravel application, we are happy to offer [application starter kits](https://laravel.com/starter-kits). These starter kits give you a head start on building your next Laravel application, and include the routes, controllers, and views you need to register and authenticate your application's users.

While you are welcome to use these starter kits, they are not required. You are free to build your own application from the ground up by simply installing a fresh copy of Laravel. Either way, we know you will build something great!

## Creating an Application Using a Starter Kit

To create a new Laravel application using one of our starter kits, you should first [install PHP and the Laravel CLI tool](about:/docs/12.x/installation#installing-php). If you already have PHP and Composer installed, you may install the Laravel installer CLI tool via Composer:

```bash
composer global require laravel/installer
```

Then, create a new Laravel application using the Laravel installer CLI. The Laravel installer will prompt you to select your preferred starter kit:

```bash
laravel new my-app
```

After creating your Laravel application, you only need to install its frontend dependencies via NPM and start the Laravel development server:

```bash
cd my-app
npm install && npm run build
composer run dev
```

Once you have started the Laravel development server, your application will be accessible in your web browser at [http://localhost:8000](http://localhost:8000/).

## Available Starter Kits

### React

Our React starter kit provides a robust, modern starting point for building Laravel applications with a React frontend using [Inertia](https://inertiajs.com/).

Inertia allows you to build modern, single-page React applications using classic server-side routing and controllers. This lets you enjoy the frontend power of React combined with the incredible backend productivity of Laravel and lightning-fast Vite compilation.

The React starter kit utilizes React 19, TypeScript, Tailwind, and the [shadcn/ui](https://ui.shadcn.com/) component library.

### Vue

Our Vue starter kit provides a great starting point for building Laravel applications with a Vue frontend using [Inertia](https://inertiajs.com/).

Inertia allows you to build modern, single-page Vue applications using classic server-side routing and controllers. This lets you enjoy the frontend power of Vue combined with the incredible backend productivity of Laravel and lightning-fast Vite compilation.

The Vue starter kit utilizes the Vue Composition API, TypeScript, Tailwind, and the [shadcn-vue](https://www.shadcn-vue.com/) component library.

### Livewire

Our Livewire starter kit provides the perfect starting point for building Laravel applications with a [Laravel Livewire](https://livewire.laravel.com/) frontend.

Livewire is a powerful way of building dynamic, reactive, frontend UIs using just PHP. It's a great fit for teams that primarily use Blade templates and are looking for a simpler alternative to JavaScript-driven SPA frameworks like React and Vue.

The Livewire starter kit utilizes Livewire, Tailwind, and the [Flux UI](https://fluxui.dev/) component library.

## Starter Kit Customization

### React Customization

Our React starter kit is built with Inertia 2, React 19, Tailwind 4, and [shadcn/ui](https://ui.shadcn.com/). As with all of our starter kits, all of the backend and frontend code exists within your application to allow for full customization.

The majority of the frontend code is located in the `resources/js` directory. You are free to modify any of the code to customize the appearance and behavior of your application:

```
resources/js/
├── components/    # Reusable React components
├── hooks/         # React hooks
├── layouts/       # Application layouts
├── lib/           # Utility functions and configuration
├── pages/         # Page components
└── types/         # TypeScript definitions
```

To publish additional shadcn components, first [find the component you want to publish](https://ui.shadcn.com/). Then, publish the component using `npx`:

```bash
npx shadcn@latest add switch
```

In this example, the command will publish the Switch component to `resources/js/components/ui/switch.tsx`. Once the component has been published, you can use it in any of your pages:

```tsx
import { Switch } from "@/components/ui/switch"

const MyPage = () => {
  return (
    <div>
      <Switch />
    </div>
  );
};

export default MyPage;
```

#### Available Layouts

The React starter kit includes two different primary layouts for you to choose from: a "sidebar" layout and a "header" layout. The sidebar layout is the default, but you can switch to the header layout by modifying the layout that is imported at the top of your application's `resources/js/layouts/app-layout.tsx` file:

```tsx
import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import AppLayoutTemplate from '@/layouts/app/app-header-layout';
```

The sidebar layout includes three different variants: the default sidebar variant, the "inset" variant, and the "floating" variant. You may choose the variant you like best by modifying the `resources/js/components/app-sidebar.tsx` component:

```tsx
<Sidebar collapsible="icon" variant="sidebar">
<Sidebar collapsible="icon" variant="inset">
```

#### Authentication Page Layout Variants

The authentication pages included with the React starter kit, such as the login page and registration page, also offer three different layout variants: "simple", "card", and "split".

To change your authentication layout, modify the layout that is imported at the top of your application's `resources/js/layouts/auth-layout.tsx` file:

```tsx
import AuthLayoutTemplate from '@/layouts/auth/auth-simple-layout';
import AuthLayoutTemplate from '@/layouts/auth/auth-split-layout';
```

### Vue Customization

Our Vue starter kit is built with Inertia 2, Vue 3 Composition API, Tailwind, and [shadcn-vue](https://www.shadcn-vue.com/). As with all of our starter kits, all of the backend and frontend code exists within your application to allow for full customization.

The majority of the frontend code is located in the `resources/js` directory. You are free to modify any of the code to customize the appearance and behavior of your application:

```
resources/js/
├── components/    # Reusable Vue components
├── composables/   # Vue composables / hooks
├── layouts/       # Application layouts
├── lib/           # Utility functions and configuration
├── pages/         # Page components
└── types/         # TypeScript definitions
```

To publish additional shadcn-vue components, first [find the component you want to publish](https://www.shadcn-vue.com/). Then, publish the component using `npx`:

```bash
npx shadcn-vue@latest add switch
```

In this example, the command will publish the Switch component to `resources/js/components/ui/Switch.vue`. Once the component has been published, you can use it in any of your pages:

```vue
<script setup lang="ts">
import { Switch } from '@/Components/ui/switch'
</script>

<template>
    <div>
        <Switch />
    </div>
</template>
```

#### Available Layouts

The Vue starter kit includes two different primary layouts for you to choose from: a "sidebar" layout and a "header" layout. The sidebar layout is the default, but you can switch to the header layout by modifying the layout that is imported at the top of your application's `resources/js/layouts/AppLayout.vue` file:

```vue
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import AppLayout from '@/layouts/app/AppHeaderLayout.vue';
```

The sidebar layout includes three different variants: the default sidebar variant, the "inset" variant, and the "floating" variant. You may choose the variant you like best by modifying the `resources/js/components/AppSidebar.vue` component:

```vue
<Sidebar collapsible="icon" variant="sidebar">
<Sidebar collapsible="icon" variant="inset">
```

#### Authentication Page Layout Variants

The authentication pages included with the Vue starter kit, such as the login page and registration page, also offer three different layout variants: "simple", "card", and "split".

To change your authentication layout, modify the layout that is imported at the top of your application's `resources/js/layouts/AuthLayout.vue` file:

```vue
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';
import AuthLayout from '@/layouts/auth/AuthSplitLayout.vue';
```

### Livewire Customization

Our Livewire starter kit is built with Livewire 3, Tailwind, and [Flux UI](https://fluxui.dev/). As with all of our starter kits, all of the backend and frontend code exists within your application to allow for full customization.

#### Livewire and Volt

The majority of the frontend code is located in the `resources/views` directory. You are free to modify any of the code to customize the appearance and behavior of your application:

```
resources/views
├── components            # Reusable Livewire components
├── flux                  # Customized Flux components
├── livewire              # Livewire pages
├── partials              # Reusable Blade partials
├── dashboard.blade.php   # Authenticated user dashboard
├── welcome.blade.php     # Guest user welcome page
```

#### Traditional Livewire Components

The frontend code is located in the `resouces/views` directory, while the `app/Livewire` directory contains the corresponding backend logic for the Livewire components.

#### Available Layouts

The Livewire starter kit includes two different primary layouts for you to choose from: a "sidebar" layout and a "header" layout. The sidebar layout is the default, but you can switch to the header layout by modifying the layout that is used by your application's `resources/views/components/layouts/app.blade.php` file. In addition, you should add the `container` attribute to the main Flux component:

```blade
<x-layouts.app.header>
    <flux:main container>
        {{ $slot }}
    </flux:main>
</x-layouts.app.header>
```

#### Authentication Page Layout Variants

The authentication pages included with the Livewire starter kit, such as the login page and registration page, also offer three different layout variants: "simple", "card", and "split".

To change your authentication layout, modify the layout that is used by your application's `resources/views/components/layouts/auth.blade.php` file:

```blade
<x-layouts.auth.split>
    {{ $slot }}
</x-layouts.auth.split>
```

## WorkOS AuthKit Authentication

By default, the React, Vue, and Livewire starter kits all utilize Laravel's built-in authentication system to offer login, registration, password reset, email verification, and more. In addition, we also offer a [WorkOS AuthKit](https://authkit.com/) powered variant of each starter kit that offers:

* Social authentication (Google, Microsoft, GitHub, and Apple)
* Passkey authentication
* Email based "Magic Auth"
* SSO

Using WorkOS as your authentication provider [requires a WorkOS account](https://workos.com/). WorkOS offers free authentication for applications up to 1 million monthly active users.

To use WorkOS AuthKit as your application's authentication provider, select the WorkOS option when creating your new starter kit powered application via `laravel new`.

### Configuring Your WorkOS Starter Kit

After creating a new application using a WorkOS powered starter kit, you should set the `WORKOS_CLIENT_ID`, `WORKOS_API_KEY`, and `WORKOS_REDIRECT_URL` environment variables in your application's `.env` file. These variables should match the values provided to you in the WorkOS dashboard for your application:

```env
WORKOS_CLIENT_ID=your-client-id
WORKOS_API_KEY=your-api-key
WORKOS_REDIRECT_URL="${APP_URL}/authenticate"
```

Additionally, you should configure the application homepage URL in your WorkOS dashboard. This URL is where users will be redirected after they log out of your application.

#### Configuring AuthKit Authentication Methods

When using a WorkOS powered starter kit, we recommend that you disable "Email + Password" authentication within your application's WorkOS AuthKit configuration settings, allowing users to only authenticate via social authentication providers, passkeys, "Magic Auth", and SSO. This allows your application to totally avoid handling user passwords.

#### Configuring AuthKit Session Timeouts

In addition, we recommend that you configure your WorkOS AuthKit session inactivity timeout to match your Laravel application's configured session timeout threshold, which is typically two hours.

## Inertia SSR

The React and Vue starter kits are compatible with Inertia's [server-side rendering](https://inertiajs.com/server-side-rendering) capabilities. To build an Inertia SSR compatible bundle for your application, run the `build:ssr` command:

```bash
npm run build:ssr
```

For convenience, a `composer dev:ssr` command is also available. This command will start the Laravel development server and Inertia SSR server after building an SSR compatible bundle for your application, allowing you to test your application locally using Inertia's server-side rendering engine:

```bash
composer dev:ssr
```

## Community Maintained Starter Kits

When creating a new Laravel application using the Laravel installer, you may provide any community maintained starter kit available on Packagist to the `--using` flag:

```bash
laravel new my-app --using=example/starter-kit
```

### Creating Starter Kits

To ensure your starter kit is available to others, you will need to publish it to [Packagist](https://packagist.org/). Your starter kit should define its required environment variables in its `.env.example` file, and any necessary post-installation commands should be listed in the `post-create-project-cmd` array of the starter kit's `composer.json` file.

## Frequently Asked Questions

### How do I upgrade?

Every starter kit gives you a solid starting point for your next application. With full ownership of the code, you can tweak, customize, and build your application exactly as you envision. However, there is no need to update the starter kit itself.

### How do I enable email verification?

Email verification can be added by uncommenting the `MustVerifyEmail` import in your `App/Models/User.php` model and ensuring the model implements the `MustVerifyEmail` interface:

```php
<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
// ...

class User extends Authenticatable implements MustVerifyEmail
{
    // ...
}
```

After registration, users will receive a verification email. To restrict access to certain routes until the user's email address is verified, add the `verified` middleware to the routes:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});
```

Email verification is not required when using the [WorkOS](#workos) variant of the starter kits.

### How do I modify the default email template?

You may want to customize the default email template to better align with your application's branding. To modify this template, you should publish the email views to your application with the following command:

```bash
php artisan vendor:publish --tag=laravel-mail
```

This will generate several files in `resources/views/vendor/mail`. You can modify any of these files as well as the `resources/views/vendor/mail/themes/default.css` file to change the look and appearance of the default email template.