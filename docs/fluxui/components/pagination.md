# flux:pagination

Display a series of buttons to navigate through a list of items.

## Basic Usage

```html
<!-- $orders = Order::paginate() -->
<flux:pagination :paginator="$orders" />
```

This component automatically handles the display of pagination links based on the Laravel paginator instance you provide.

## Simple Paginator

Use the simple paginator when working with large datasets where counting the total number of results would be expensive. The simple paginator provides "Previous" and "Next" buttons without displaying the total number of pages or records.

```html
<!-- $orders = Order::simplePaginate() -->
<flux:pagination :paginator="$orders" />
```

## Large Result Set

When working with large result sets, the pagination component automatically adapts to show a reasonable number of page links. It shows the first and last pages, along with a window of pages around the current page, and adds ellipses for any gaps to ensure efficient navigation through numerous pages.

```html
<!-- $orders = Order::paginate(5) -->
<flux:pagination :paginator="$orders" />
```

## Integration with Livewire

The pagination component works seamlessly with Livewire paginated results:

```html
<!-- In your Livewire component -->
public function render()
{
    return view('livewire.order-list', [
        'orders' => Order::paginate(10)
    ]);
}

<!-- In your Livewire template -->
<div>
    <table>
        <!-- Table headers and data -->
    </table>
    
    <div class="mt-4">
        <flux:pagination :paginator="$orders" />
    </div>
</div>
```

## Customizing Page Size

You can customize the number of items to show per page:

```php
// In your controller
$orders = Order::paginate(25); // Show 25 items per page

// Or in your Livewire component
public function render()
{
    return view('livewire.order-list', [
        'orders' => Order::paginate($this->perPage)
    ]);
}
```

## Using with Laravel Collections

You can also use the paginator with collections:

```php
$collection = collect([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
$paginator = new LengthAwarePaginator(
    $collection->forPage($page, $perPage),
    $collection->count(),
    $perPage,
    $page,
    ['path' => request()->url()]
);
```

```html
<flux:pagination :paginator="$paginator" />
```

## Customizing the View

Laravel allows you to customize pagination views. If you want to create a custom pagination view that uses Flux UI styling, you can publish and modify the pagination views:

```bash
php artisan vendor:publish --tag=laravel-pagination
```

Then modify the views in `resources/views/vendor/pagination` to use Flux UI components.

## Reference

| Prop | Description |
| --- | --- |
| `paginator` | The paginator instance to display. This should be an instance of Laravel's paginator (either `Illuminate\Pagination\LengthAwarePaginator` or `Illuminate\Pagination\Paginator`). |