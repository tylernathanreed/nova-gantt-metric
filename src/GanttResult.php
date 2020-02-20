<?php

namespace Reedware\NovaGanttMetric;

use Closure;
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
     * Format the labels for the gantt result.
     *
     * @param  \Closure  $callback
     *
     * @return $this
     */
    public function label(Closure $callback)
    {
        $this->value = collect($this->value)->mapWithKeys(function ($value, $label) use ($callback) {
            return [$callback($label) => $value];
        })->all();

        return $this;
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
