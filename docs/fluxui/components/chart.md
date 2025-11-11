# Chart Component

Flux's Chart component is a lightweight, zero-dependency tool for building charts in your Livewire applications. It is designed to be simple but extremely flexible, so that you can assemble and style your charts exactly as you need them.

## Basic Usage

The Chart component allows you to create various types of charts including line charts, area charts, and more.

### Line Chart

```html
<flux:chart>
    <flux:chart.svg>
        <flux:chart.line />
    </flux:chart.svg>
</flux:chart>
```

You can also render circular points on top of the line using the `<flux:chart.point>` component.

### Area Chart

Similar to a line chart but with a filled area beneath the line. Great for showing cumulative values or emphasizing volume.

```html
<flux:chart>
    <flux:chart.svg>
        <flux:chart.area />
    </flux:chart.svg>
</flux:chart>
```

### Multiple Lines

You can plot multiple lines in the same chart by including multiple `<flux:chart.line>` components:

```html
<flux:chart>
    <flux:chart.viewport>
        <flux:chart.svg>
            <flux:chart.line class="text-blue-500" field="visitors" />
            <flux:chart.line class="text-red-500" field="views" />
        </flux:chart.svg>
    </flux:chart.viewport>
</flux:chart>
```

The `<flux:chart.viewport>` component is used to constrain the chart SVG within the chart component so that you can render siblings like legends or summaries above or below it.

### Live Summary

Flux charts support live summaries, which are updated as the user hovers over the chart:

```html
<flux:chart>
    <flux:chart.summary>
        <flux:chart.summary.value field="visitors" :format="['notation' => 'compact']" />
    </flux:chart.summary>
    <flux:chart.svg>
        <flux:chart.line field="visitors" />
    </flux:chart.svg>
</flux:chart>
```

### Sparkline

A compact, single-line chart used in tables or dashboards for quick visual insights:

```html
<flux:chart>
    <flux:chart.svg gutter="0">
        <flux:chart.line field="trend" />
    </flux:chart.svg>
</flux:chart>
```

By default, the chart will be rendered with a padding of 8px on all sides. For simple charts like a sparkline, you can set the gutter to 0 to remove it.

### Dashboard Stat

A small card displaying a key metric with an embedded chart in the background:

```html
<div class="p-4 relative">
    <div class="absolute inset-0 opacity-25">
        <flux:chart>
            <flux:chart.svg gutter="0">
                <flux:chart.area field="revenue" />
            </flux:chart.svg>
        </flux:chart>
    </div>
    <div class="relative">
        <div class="text-sm text-zinc-500">Revenue</div>
        <div class="text-2xl font-semibold">$45,231</div>
        <div class="flex items-center text-sm text-green-500">
            <span>+12%</span>
        </div>
    </div>
</div>
```

## Chart Customization

### Chart Padding

By default, the chart will be rendered with a padding of 8px on all sides. You can control this by setting the gutter attribute on the `<flux:chart.svg>` component:

```html
<flux:chart>
    <flux:chart.svg gutter="12 0 12 8">
        <!-- ... -->
    </flux:chart.svg>
</flux:chart>
```

The gutter attribute accepts between one and four values, which correspond to the top, right, bottom, and left padding respectively (similar to the CSS padding property shorthand).

### Axis Scale

You can configure the scale of an axis and its ticks by setting the scale attribute on the `<flux:chart.axis>` component:

```html
<flux:chart.axis axis="y" scale="linear">
    <!-- ... -->
</flux:chart.axis>
```

There are three available types of scales:
* **categorical**: For categorical data like names or categories.
* **linear**: For numeric data like stock prices or temperatures.
* **time**: For date and time data

The "y" axis is linear by default, but you can change it to a time axis by setting the scale attribute to time.

The "x" axis will automatically use a time scale if the data is date or time values. Otherwise, it will use a categorical scale.

### Axis Lines

By default, axes do not include a visible baseline. You can add an axis line using `<flux:chart.axis.line>`:

```html
<flux:chart.svg>
    <!-- ... -->
    <flux:chart.axis axis="x">
        <!-- Horizontal "X" axis line: -->
        <flux:chart.axis.line />
    </flux:chart.axis>
    <flux:chart.axis axis="y">
        <!-- Vertical "Y" axis line: -->
        <flux:chart.axis.line />
    </flux:chart.axis>
</flux:chart.svg>
```

You can style the axis line using any SVG attributes available for the `<line>` element:

```html
<!-- A dark gray axis line that is 2px wide and has a gray color: -->
<flux:chart.axis.line class="text-zinc-800" stroke-width="2" />
```

### Zero Line

The zero line represents the zero value on the axis and will only be visible if the axis includes a negative value:

```html
<flux:chart.svg>
    <!-- ... -->
    <!-- Zero line: -->
    <flux:chart.zero-line />
</flux:chart.svg>
```

You can style the zero line using SVG attributes:

```html
<!-- A dark gray zero line that is 2px wide and has a gray color: -->
<flux:chart.zero-line class="text-zinc-800" stroke-width="2" />
```

### Grid Lines

You can render horizontal and vertical grid lines using the `<flux:chart.axis.grid>` component:

```html
<flux:chart.svg>
    <!-- ... -->
    <flux:chart.axis axis="x">
        <!-- Vertical grid lines: -->
        <flux:chart.axis.grid />
    </flux:chart.axis>
    <flux:chart.axis axis="y">
        <!-- Horizontal grid lines: -->
        <flux:chart.axis.grid />
    </flux:chart.axis>
</flux:chart.svg>
```

You can style grid lines using SVG attributes:

```html
<!-- A dashed grid line that is 2px wide and has a gray color: -->
<flux:chart.axis.grid class="text-zinc-200/50" stroke-width="2" stroke-dasharray="4,4" />
```

### Ticks

You can render tick mark lines and labels by adding the `<flux:chart.axis.mark>` and/or `<flux:chart.axis.tick>` components:

```html
<flux:chart.svg>
    <!-- ... -->
    <flux:chart.axis axis="x">
        <!-- X axis tick mark lines: -->
        <flux:chart.axis.mark />
        <!-- X axis tick labels: -->
        <flux:chart.axis.tick />
    </flux:chart.axis>
    <flux:chart.axis axis="y">
        <!-- Y axis tick mark lines: -->
        <flux:chart.axis.mark />
        <!-- Y axis tick labels: -->
        <flux:chart.axis.tick />
    </flux:chart.axis>
</flux:chart.svg>
```

Styling tick marks and labels:

```html
<!-- A tick mark line that is 10px long, 2px wide, and has a gray color: -->
<flux:chart.axis.mark class="text-zinc-300" stroke-width="2" y1="0" y2="10" />

<!-- A tick label that is 12px, has a blue color, is center aligned, and is 2.5rem from the axis line: -->
<flux:chart.axis.tick class="text-xs text-blue-500" text-anchor="middle" dy="2.5rem" />
```

### Tick Frequency

You can influence the number of ticks on an axis:

```html
<flux:chart.axis axis="y" tick-count="5">
    <!-- ... -->
</flux:chart.axis>
```

Note that the tick-count attribute is only a target number, and the actual number of ticks may vary based on the range of values on the axis.

You can also set where ticks start and end:

```html
<!-- Set tick start to minimum value -->
<flux:chart.axis axis="y" tick-start="min">
    <!-- ... -->
</flux:chart.axis>

<!-- Set tick end to maximum value -->
<flux:chart.axis axis="y" tick-end="max">
    <!-- ... -->
</flux:chart.axis>
```

You can also set explicit tick values:

```html
<flux:chart.axis axis="y" tick-values="[0, 128, 256, 384, 512]" tick-suffix="MB">
    <!-- ... -->
</flux:chart.axis>
```

### Tick Formatting

Tick labels can be formatted using the format attribute, which is passed to the Intl.NumberFormat constructor:

```html
<flux:chart.svg>
    <!-- Format the X axis tick labels to display the month and day: -->
    <flux:chart.axis axis="x" :format="['month' => 'long', 'day' => 'numeric']">
        <!-- X axis tick labels: -->
        <flux:chart.axis.tick />
    </flux:chart.axis>
    
    <!-- Format the Y axis tick labels to display the value in USD: -->
    <flux:chart.axis axis="y" :format="['style' => 'currency', 'currency' => 'USD']">
        <!-- Y axis tick labels: -->
        <flux:chart.axis.tick />
    </flux:chart.axis>
</flux:chart.svg>
```

You can also add prefixes and suffixes:

```html
<!-- Setting a prefix -->
<flux:chart.axis axis="y" tick-prefix="$">
    <!-- ... -->
</flux:chart.axis>

<!-- Setting a suffix -->
<flux:chart.axis axis="y" tick-suffix="MB">
    <!-- ... -->
</flux:chart.axis>
```

### Cursor

Adds a vertical guide when hovering over the chart to highlight values at specific points:

```html
<flux:chart.svg>
    <!-- ... -->
    <flux:chart.cursor />
</flux:chart.svg>
```

You can style the cursor:

```html
<!-- A dashed, black cursor that is 1px wide: -->
<flux:chart.cursor class="text-zinc-800" stroke-width="1" stroke-dasharray="4,4" />
```

### Tooltip

Displays contextual data on hover:

```html
<flux:chart>
    <flux:chart.svg>
        <!-- ... -->
    </flux:chart.svg>
    <flux:chart.tooltip>
        <flux:chart.tooltip.heading field="date" />
        <flux:chart.tooltip.value field="visitors" label="Visitors" />
        <flux:chart.tooltip.value field="views" label="Views" :format="['notation' => 'compact']" />
    </flux:chart.tooltip>
</flux:chart>
```

### Legend

Identifies multiple data series in the chart:

```html
<flux:chart wire:model="data">
    <flux:chart.viewport class="aspect-3/1">
        <flux:chart.svg>
            <flux:chart.line class="text-blue-500" field="visitors" />
            <flux:chart.line class="text-red-500" field="views" />
        </flux:chart.svg>
    </flux:chart.viewport>
    <div class="flex justify-center gap-4 pt-4">
        <flux:chart.legend label="Visitors">
            <flux:chart.legend.indicator class="bg-blue-400" />
        </flux:chart.legend>
        <flux:chart.legend label="Views">
            <flux:chart.legend.indicator class="bg-red-400" />
        </flux:chart.legend>
    </div>
</flux:chart>
```

### Summary

Displays a single value from the dataset in a bold, easy-to-read format:

```html
<flux:chart wire:model="data">
    <flux:chart.summary>
        <flux:chart.summary.value field="visitors" :format="['notation' => 'compact']" />
    </flux:chart.summary>
    <flux:chart.viewport class="aspect-[3/1]">
        <!-- ... -->
    </flux:chart.viewport>
</flux:chart>
```

You can set a fallback value for when no data point is being hovered over:

```html
<flux:chart.summary.value field="visitors" fallback="1200" />
```

## Formatting

You can customize how numbers and dates appear in your charts to make them more readable using the `:format` prop.

### Formatting Numbers

```html
<flux:chart.axis axis="y" :format="['style' => 'currency', 'currency' => 'USD']" />
```

### Formatting Dates

```html
<flux:chart.axis axis="x" field="date" :format="['month' => 'long', 'day' => 'numeric']" />
```