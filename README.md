# Nova Gantt Metric
Adds Gantt metrics to your Nova application

[![Latest Stable Version](https://poser.pugx.org/reedware/nova-gantt-metric/v/stable)](https://packagist.org/packages/reedware/nova-gantt-metric)
[![Total Downloads](https://poser.pugx.org/reedware/nova-gantt-metric/downloads)](https://packagist.org/packages/reedware/nova-gantt-metric)

## Introduction

This package implements a new type of metric, being specifically for showing high level timelines using a [Gantt Chart](https://en.wikipedia.org/wiki/Gantt_chart).

Here's some examples of the metric being used in the wild:

![Tasks](https://github.com/tylernathanreed/nova-gantt-metric/blob/master/docs/example-tasks.png)

## Installation

Use `composer require reedware/nova-gantt-metric`.

## Usage

```php
<?php

namespace App\Nova\Metrics;

use Illuminate\Http\Request;
use Reedware\NovaGanttMetric\Gantt;

class MyExampleGantt extends Gantt
{
    /**
     * The element's component.
     *
     * @var string
     */
    public $component = 'gantt-metric';

    /**
     * The displayable name of the metric.
     *
     * @var string
     */
    public $name = 'Estimated Completion Date';

    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        $query = /* ... */;

        // This will group by the "label_name" column, and use the min
        // and max of the "date_column" values to create the chart
        // data. See Gantt@aggregate for additional features.

        return $this->spreadByDays($request, $query, 'label_name', 'date_column');
    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array
     */
    public function ranges()
    {
        return [
            90 => 'Next 90 Days',
            180 => 'Next 180 Days',
            270 => 'Next 270 Days'
        ];
    }
}
```
