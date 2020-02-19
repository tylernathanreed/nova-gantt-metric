<?php

namespace Reedware\NovaGanttMetric;

use JsonSerializable;

class GanttResult implements JsonSerializable
{
    /**
     * The date labels of the result.
     *
     * @var array
     */
    public $labels = [];

    /**
     * The series data of the result.
     *
     * @var array
     */
    public $series = [];

    /**
     * The tick data of the result.
     *
     * @var array
     */
    public $ticks = [];

    /**
     * Set the date labels for this metric.
     *
     * @param  array  $labels
     *
     * @return $this
     */
    public function labels(array $labels)
    {
        $this->labels = $labels;

        return $this;
    }

    /**
     * Set the series data for this metric.
     *
     * @param  array  $series
     *
     * @return $this
     */
    public function series(array $series)
    {
        $this->series = $series;

        return $this;
    }

    /**
     * Set the tick data for this metric.
     *
     * @param  array  $ticks
     *
     * @return $this
     */
    public function ticks(array $ticks)
    {
        $this->ticks = $ticks;

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
            'labels' => $this->labels,
            'series' => $this->series,
            'ticks' => $this->ticks
        ];
    }
}
