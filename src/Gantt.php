<?php

namespace Reedware\NovaGanttMetric;

use Cake\Chronos\Chronos;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Laravel\Nova\Metrics\RangedMetric;
use Laravel\Nova\Metrics\TrendDateExpressionFactory;

abstract class Gantt extends RangedMetric
{
    /**
     * Gantt metric unit constants.
     */
    const BY_MONTHS = 'month';
    const BY_WEEKS = 'week';
    const BY_DAYS = 'day';
    const BY_HOURS = 'hour';
    const BY_MINUTES = 'minute';

    /**
     * The element's component.
     *
     * @var string
     */
    public $component = 'gantt-metric';

    /**
     * Create a new trend metric result.
     *
     * @param  string|null  $value
     * @return \Laravel\Nova\Metrics\GanttResult
     */
    public function result($value = null)
    {
        return new GanttResult($value);
    }

    /**
     * Return a value result showing a count spread over months.
     *
     * @param  \Illuminate\Http\Request                      $request
     * @param  \Illuminate\Database\Eloquent\Builder|string  $model
     * @param  string                                        $groupBy
     * @param  string|array|null                             $column
     *
     * @return \Laravel\Nova\Metrics\GanttResult
     */
    public function spreadByMonths($request, $model, $groupBy, $column = null)
    {
        return $this->spread($request, $model, self::BY_MONTHS, $groupBy, $column);
    }

    /**
     * Return a value result showing a spread spread over weeks.
     *
     * @param  \Illuminate\Http\Request                      $request
     * @param  \Illuminate\Database\Eloquent\Builder|string  $model
     * @param  string                                        $groupBy
     * @param  string|array|null                             $column
     *
     * @return \Laravel\Nova\Metrics\GanttResult
     */
    public function spreadByWeeks($request, $model, $groupBy, $column = null)
    {
        return $this->spread($request, $model, self::BY_WEEKS, $groupBy, $column);
    }

    /**
     * Return a value result showing a spread spread over days.
     *
     * @param  \Illuminate\Http\Request                      $request
     * @param  \Illuminate\Database\Eloquent\Builder|string  $model
     * @param  string                                        $groupBy
     * @param  string|array|null                             $column
     *
     * @return \Laravel\Nova\Metrics\GanttResult
     */
    public function spreadByDays($request, $model, $groupBy, $column = null)
    {
        return $this->spread($request, $model, self::BY_DAYS, $groupBy, $column);
    }

    /**
     * Return a value result showing a spread spread over hours.
     *
     * @param  \Illuminate\Http\Request                      $request
     * @param  \Illuminate\Database\Eloquent\Builder|string  $model
     * @param  string                                        $groupBy
     * @param  string|array|null                             $column
     *
     * @return \Laravel\Nova\Metrics\GanttResult
     */
    public function spreadByHours($request, $model, $groupBy, $column = null)
    {
        return $this->spread($request, $model, self::BY_HOURS, $groupBy, $column);
    }

    /**
     * Return a value result showing a spread spread over minutes.
     *
     * @param  \Illuminate\Http\Request                      $request
     * @param  \Illuminate\Database\Eloquent\Builder|string  $model
     * @param  string                                        $groupBy
     * @param  string|array|null                             $column
     *
     * @return \Laravel\Nova\Metrics\GanttResult
     */
    public function spreadByMinutes($request, $model, $groupBy, $column = null)
    {
        return $this->spread($request, $model, self::BY_MINUTES, $groupBy, $column);
    }

    /**
     * Return a value result showing a spread spread over time.
     *
     * @param  \Illuminate\Http\Request                      $request
     * @param  \Illuminate\Database\Eloquent\Builder|string  $model
     * @param  string                                        $groupBy
     * @param  string|array|null                             $column
     *
     * @return \Laravel\Nova\Metrics\GanttResult
     */
    public function spread($request, $model, $unit, $groupBy, $column = null)
    {
        $resource = $model instanceof Builder ? $model->getModel() : new $model;

        $column = $column ?? $resource->getCreatedAtColumn();

        return $this->aggregate($request, $model, $unit, $groupBy, $column);
    }

    /**
     * Return a value result showing a aggregate over time.
     *
     * @param  \Illuminate\Http\Request                      $request
     * @param  \Illuminate\Database\Eloquent\Builder|string  $model
     * @param  string                                        $unit
     * @param  string                                        $groupBy
     * @param  string|array|null                             $dateColumn
     *
     * @return \Laravel\Nova\Metrics\GanttResult
     */
    protected function aggregate($request, $model, $unit, $groupBy, $dateColumn = null)
    {
        // Determine the query
        $query = $model instanceof Builder ? $model : (new $model)->newQuery();

        // Determinet the timezone
        $timezone = $request->timezone;

        // Determine the start and end date columns
        $startDateColumn = is_array($dateColumn) ? $dateColumn[0] : $dateColumn;
        $endDateColumn = is_array($dateColumn) ? $dateColumn[1] : $dateColumn;

        // Determine all possible date results
        $possibleDateResults = $this->getAllPossibleDateResults(
            $startingDate = Chronos::now(),
            $endingDate = $this->getAggregateEndingDate($request, $unit),
            $unit,
            $timezone,
            $request->twelveHourTime === 'true'
        );

        // Wrap the group by column
        $wrappedGroupBy = is_string($groupBy) ? $query->getQuery()->getGrammar()->wrap($groupBy) : $groupBy;

        // Wrap the start and end date columns
        $wrappedStartDateColumn = $query->getQuery()->getGrammar()->wrap($startDateColumn);
        $wrappedEndDateColumn = $query->getQuery()->getGrammar()->wrap($endDateColumn);

        // Determine the results
        $results = $query
                ->select(DB::raw("{$wrappedGroupBy} as label, min({$wrappedStartDateColumn}) as start_aggregate, max({$wrappedEndDateColumn}) as end_aggregate"))
                ->groupBy($groupBy)
                ->orderBy($groupBy)
                ->get()
                ->keyBy('label');

        $results = $results->mapWithKeys(function ($result) use ($request, $unit, $timezone, $startingDate, $endingDate) {
            return [
                $result->label => [
                    'start' => $this->formatAggregateResultDate($result->start_aggregate, $unit, $request->twelveHourTime === 'true'),
                    'end' => $this->formatAggregateResultDate($result->end_aggregate, $unit, $request->twelveHourTime === 'true'),
                    'range' => $this->getAllPossibleDateResults( $startingDate->max(new Chronos($result->start_aggregate)),
                        $endingDate->min(new Chronos($result->end_aggregate)),
                        $unit,
                        $timezone,
                        $request->twelveHourTime === 'true'
                    )
                ]
            ];
        })->all();

        $series = [];
        $index = count($results);

        foreach($results as $label => $result) {

            $data = [];

            foreach($possibleDateResults as $date) {
                $data[] = ['meta' => $date, 'value' => in_array($date, $result['range']) ? $index : null];
            }

            $series[] = $data;
            $index--;

        }

        return $this->result()->labels($possibleDateResults)->series($series)->ticks(array_keys($results));
    }

    /**
     * Determine the proper aggregate strating date.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $unit
     * @return \Cake\Chronos\Chronos
     */
    protected function getAggregateEndingDate($request, $unit)
    {
        $now = Chronos::now();

        switch ($unit) {
            case 'month':
                return $now->addMonths($request->range)->firstOfMonth()->setTime(0, 0);

            case 'week':
                return $now->addWeeks($request->range)->startOfWeek()->setTime(0, 0);

            case 'day':
                return $now->addDays($request->range)->setTime(0, 0);

            case 'hour':
                return with($now->addHours($request->range), function ($now) {
                    return $now->setTimeFromTimeString($now->hour.':00');
                });

            case 'minute':
                return with($now->addMinutes($request->range), function ($now) {
                    return $now->setTimeFromTimeString($now->hour.':'.$now->minute.':00');
                });

            default:
                throw new InvalidArgumentException('Invalid trend unit provided.');
        }
    }

    /**
     * Format the aggregate result date into a proper string.
     *
     * @param  string  $result
     * @param  string  $unit
     * @param  bool  $twelveHourTime
     * @return string
     */
    protected function formatAggregateResultDate($result, $unit, $twelveHourTime)
    {
        switch ($unit) {
            case 'month':
                return $this->formatAggregateMonthDate($result);

            case 'week':
                return $this->formatAggregateWeekDate($result);

            case 'day':
                return with(Chronos::createFromFormat('Y-m-d', $result), function ($date) {
                    return __($date->format('F')).' '.$date->format('j').', '.$date->format('Y');
                });

            case 'hour':
                return with(Chronos::createFromFormat('Y-m-d H:00', $result), function ($date) use ($twelveHourTime) {
                    return $twelveHourTime
                            ? __($date->format('F')).' '.$date->format('j').' - '.$date->format('g:00 A')
                            : __($date->format('F')).' '.$date->format('j').' - '.$date->format('G:00');
                });

            case 'minute':
                return with(Chronos::createFromFormat('Y-m-d H:i:00', $result), function ($date) use ($twelveHourTime) {
                    return $twelveHourTime
                            ? __($date->format('F')).' '.$date->format('j').' - '.$date->format('g:i A')
                            : __($date->format('F')).' '.$date->format('j').' - '.$date->format('G:i');
                });
        }
    }

    /**
     * Format the aggregate month result date into a proper string.
     *
     * @param  string  $result
     * @return string
     */
    protected function formatAggregateMonthDate($result)
    {
        [$year, $month] = explode('-', $result);

        return with(Chronos::create((int) $year, (int) $month, 1), function ($date) {
            return __($date->format('F')).' '.$date->format('Y');
        });
    }

    /**
     * Format the aggregate week result date into a proper string.
     *
     * @param  string  $result
     * @return string
     */
    protected function formatAggregateWeekDate($result)
    {
        [$year, $week] = explode('-', $result);

        $isoDate = (new DateTime)->setISODate($year, $week)->setTime(0, 0);

        [$startingDate, $endingDate] = [
            Chronos::instance($isoDate),
            Chronos::instance($isoDate)->endOfWeek(),
        ];

        return __($startingDate->format('F')).' '.$startingDate->format('j').' - '.
               __($endingDate->format('F')).' '.$endingDate->format('j');
    }

    /**
     * Get all of the possbile date results for the given units.
     *
     * @param  \Cake\Chronos\Chronos  $startingDate
     * @param  \Cake\Chronos\Chronos  $endingDate
     * @param  string  $unit
     * @param  mixed  $timezone
     * @param  bool  $twelveHourTime
     * @return array
     */
    protected function getAllPossibleDateResults(Chronos $startingDate, Chronos $endingDate, $unit, $timezone, $twelveHourTime)
    {
        $nextDate = $startingDate;

        if (! empty($timezone)) {
            $nextDate = $startingDate->setTimezone($timezone);
            $endingDate = $endingDate->setTimezone($timezone);
        }

        if($nextDate->gte($endingDate)) {
            return [];
        }

        $possibleDateResults = [$this->formatPossibleAggregateResultDate(
            $nextDate, $unit, $twelveHourTime
        )];

        while ($nextDate->lt($endingDate)) {
            if ($unit === self::BY_MONTHS) {
                $nextDate = $nextDate->addMonths(1);
            } elseif ($unit === self::BY_WEEKS) {
                $nextDate = $nextDate->addWeeks(1);
            } elseif ($unit === self::BY_DAYS) {
                $nextDate = $nextDate->addDays(1);
            } elseif ($unit === self::BY_HOURS) {
                $nextDate = $nextDate->addHours(1);
            } elseif ($unit === self::BY_MINUTES) {
                $nextDate = $nextDate->addMinutes(1);
            }

            if ($nextDate->lte($endingDate)) {
                $possibleDateResults[] = $this->formatPossibleAggregateResultDate(
                    $nextDate, $unit, $twelveHourTime
                );
            }
        }

        return $possibleDateResults;
    }

    /**
     * Format the possible aggregate result date into a proper string.
     *
     * @param  \Cake\Chronos\Chronos  $date
     * @param  string  $unit
     * @param  bool  $twelveHourTime
     * @return string
     */
    protected function formatPossibleAggregateResultDate(Chronos $date, $unit, $twelveHourTime)
    {
        switch ($unit) {
            case 'month':
                return __($date->format('F')).' '.$date->format('Y');

            case 'week':
                return __($date->startOfWeek()->format('F')).' '.$date->startOfWeek()->format('j').' - '.
                       __($date->endOfWeek()->format('F')).' '.$date->endOfWeek()->format('j');

            case 'day':
                return __($date->format('F')).' '.$date->format('j').', '.$date->format('Y');

            case 'hour':
                return $twelveHourTime
                        ? __($date->format('F')).' '.$date->format('j').' - '.$date->format('g:00 A')
                        : __($date->format('F')).' '.$date->format('j').' - '.$date->format('G:00');

            case 'minute':
                return $twelveHourTime
                        ? __($date->format('F')).' '.$date->format('j').' - '.$date->format('g:i A')
                        : __($date->format('F')).' '.$date->format('j').' - '.$date->format('G:i');
        }
    }
}
