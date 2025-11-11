# Upgrade Guide

This guide provides instructions for upgrading between different versions of Flux UI.

## Keeping Flux Updated

To ensure you have the latest version of Flux, regularly update your composer dependencies:

```bash
composer update livewire/flux livewire/flux-pro
```

If you've published Flux components, make sure to check the changelog for any breaking changes before updating.

## Upgrading to Version 2.0

Flux 2.0 introduces several important changes that you should be aware of before upgrading.

### Prerequisites

Flux 2.0 requires:
- Laravel 10 or higher
- Livewire 3.0 or higher
- Tailwind CSS 4.0 or higher

### Breaking Changes

1. **Tailwind CSS 4.0 Required**

   Flux 2.0 requires Tailwind CSS 4.0 or higher. Make sure to update your Tailwind CSS configuration:

   ```css
   @import 'tailwindcss';
   @import '../../vendor/livewire/flux/dist/flux.css';
   @custom-variant dark (&:where(.dark, .dark *));
   ```

2. **Dark Mode Configuration**

   The dark mode configuration has changed. Instead of using `darkMode: 'class'` in your tailwind.config.js file, you now need to use the new custom variant syntax:

   ```css
   @custom-variant dark (&:where(.dark, .dark *));
   ```

3. **Component Changes**

   Some components have been renamed or removed. Check the full changelog for details.

4. **Icon Changes**

   The icon system has been updated to use Heroicons v2. If you were using specific icon names, you may need to update them.

### Update Steps

1. Update your composer dependencies:

   ```bash
   composer update livewire/flux livewire/flux-pro
   ```

2. Update your Tailwind CSS configuration to use the new dark mode variant.

3. Run the Tailwind build process to regenerate your CSS:

   ```bash
   npm run build
   ```

4. Test your application thoroughly, especially if you've published components or have custom implementations that use Flux components.

## Upgrading Published Components

If you've published components from a previous version of Flux, you may need to manually update them to match the latest version. The safest approach is to:

1. Backup your published components
2. Delete the published components
3. Re-publish them from the latest version of Flux
4. Apply your customizations to the newly published components

This ensures you get all the latest features and bug fixes while maintaining your customizations.

## Troubleshooting Common Upgrade Issues

### Styling Issues After Upgrade

If you notice styling inconsistencies after upgrading:

1. Clear your browser cache
2. Rebuild your Tailwind CSS
3. Check for any conflicting CSS rules

### Component Not Found Errors

If you encounter "Component not found" errors after upgrading:

1. Check if the component name has changed in the new version
2. Verify that you're using the correct namespace
3. Run `php artisan view:clear` to clear the view cache

### JavaScript Errors

If you encounter JavaScript errors after upgrading:

1. Check your browser console for specific error messages
2. Make sure all Flux scripts are properly included in your layout
3. Update any custom JavaScript that interacts with Flux components

## Getting Help

If you encounter any issues during the upgrade process that aren't covered in this guide, please refer to the [Help documentation](/guides/help.md) for ways to get assistance.