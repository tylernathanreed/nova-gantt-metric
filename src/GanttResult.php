<?php

namespace Reedware\NovaGanttMetric;

use JsonSerializable;

class GanttResult implements JsonSerializable
{
    /**
     * The value of the result.
     *
     * @var array
     */
    public $value = [];

    /**
     * The custom series colors.
     *
     * @var array
     */
    public $colors = [];

    /**
     * Create a new gantt result instance.
     *
     * @param  string|null  $value
     *
     * @return void
     */
    public function __construct($value = null)
    {
        $this->value = $value;
        $this->colors = new GanttColors();
    }

    /**
     * Prepare the metric result for JSON serialization.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'values' => collect($this->value ?? [])->map(function($value, $label) {
                return [
                    'color' => $this->colors->get($label),
                    'label' => $label,
                    'value' => $value
                ];
            })->values()->all()
        ];
    }
}
