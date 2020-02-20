<template>
    <loading-card :loading="loading" class="px-6 py-4">
        <div class="flex mb-4">
            <h3 class="mr-3 text-base text-80 font-bold">{{ title }}</h3>

            <div v-if="helpText" class="absolute pin-r pin-b p-2 z-50">
                <tooltip trigger="hover">
                    <icon
                        type="help"
                        viewBox="0 0 17 17"
                        height="16"
                        width="16"
                        class="cursor-pointer text-60 -mb-1"
                    />

                    <tooltip-content
                        slot="content"
                        v-html="helpText"
                        :max-width="helpWidth"
                    />
                </tooltip>
            </div>

            <select
                v-if="ranges.length > 0"
                @change="handleChange"
                class="select-box-sm ml-auto min-w-24 h-6 text-xs appearance-none bg-40 pl-2 pr-6 active:outline-none active:shadow-outline focus:outline-none focus:shadow-outline"
            >
                <option
                    v-for="option in ranges"
                    :key="option.value"
                    :value="option.value"
                    :selected="selectedRangeKey == option.value"
                >
                    {{ option.label }}
                </option>
            </select>
        </div>

        <div
            ref="chart"
            class="absolute pin rounded-b-lg ct-chart ct-grantt-chart"
            style="top: 33%"
        />
    </loading-card>
</template>

<script>
import numbro from 'numbro'
import numbroLanguages from 'numbro/dist/languages.min'
Object.values(numbroLanguages).forEach(l => numbro.registerLanguage(l))
import _ from 'lodash'
import Chartist from 'chartist'
import 'chartist-plugin-tooltips'
import 'chartist/dist/chartist.min.css'
import { SingularOrPlural } from 'laravel-nova'
import 'chartist-plugin-tooltips/dist/chartist-plugin-tooltip.css'

const colorForIndex = index => [
    '#F5573B',
    '#F99037',
    '#F2CB22',
    '#8FC15D',
    '#098F56',
    '#47C1BF',
    '#1693EB',
    '#6474D7',
    '#9C6ADE',
    '#E471DE',
][index]

export default {
    name: 'BaseGanttMetric',

    props: {
        loading: Boolean,
        title: {},
        helpText: {},
        helpWidth: {},
        value: {},
        chartData: {},
        maxWidth: {},
        prefix: '',
        suffix: '',
        suffixInflection: true,
        ranges: { type: Array, default: () => [] },
        selectedRangeKey: [String, Number],
        format: {
            type: String,
            default: '0[.]00a',
        },
    },

    data: () => ({
        chartist: null,
        options: {
            lineSmooth: Chartist.Interpolation.none(),
            fullWidth: true,
            showPoint: true,
            showLine: true,
            showArea: false,
            chartPadding: {
                top: 5,
                right: 5,
                bottom: 20,
                left: 0,
            },
            axisX: {
                showGrid: false,
                showLabel: false,
                offset: 0
            },
            axisY: {
                showGrid: false,
                showLabel: true,
                offset: 120,
                labelOffset: {
                    x: 25,
                    y: 5
                },
                low: 1,
                high: 5,
                type: Chartist.FixedScaleAxis,
                onlyInteger: true
            },
            plugins: [
                Chartist.plugins.tooltip({
                    anchorToPoint: true,
                    transformTooltipTextFnc: value => '',
                }),
            ],
        }
    }),

    watch: {
        selectedRangeKey: function(newRange, oldRange) {
            this.renderChart()
        },

        chartData: function(newData, oldData) {
            this.renderChart()
        },
    },

    mounted() {
        if (Nova.config.locale) {
            numbro.setLanguage(Nova.config.locale.replace('_', '-'))
        }

        this.chartist = new Chartist.Line(this.$refs.chart, this.chartData, this.options)

        this.chartist.on('draw', context => {
            if(context.type === 'point' || context.type === 'line') {
                context.element.attr({
                    style: `stroke: ${context.series.color} !important`
                })
            }
        })
    },

    methods: {
        renderChart() {

            this.options.axisY.ticks = this.chartData.ticks.reduce(function(t, v, k) { return t.push(k + 1) && t; }, [])
            this.options.axisY.high = this.options.axisY.ticks.length
            this.options.axisY.labelInterpolationFnc = (value, index) => {
                return this.chartData.ticks[this.chartData.series.length - index - 1] || value
            }

            this.chartist.update(this.formattedChartData, this.options)
        },

        getItemColor(item, index) {
            return typeof item.color === 'string' ? item.color : colorForIndex(index)
        },

        handleChange(event) {
            this.$emit('selected', event.target.value)
        },
    },

    computed: {

        formattedChartData() {

            return {
                labels: this.chartData.labels,
                values: this.chartData.values,
                series: _.map(this.chartData.series, (series, index) => {

                    let meta = 'N/A'
                    let values = series.value
                    let range = values.filter((value) => value.value != null)

                    if(range.length > 0) {

                        let start = range.shift().meta
                        let end = range.length > 0 ? range.pop().meta : start
                        let limit = values[values.length - 1].meta

                        meta = start + ' to ' + end + (end == limit ? '+' : '')

                    }

                    return {
                        meta: meta,
                        value: values,
                        color: this.getItemColor(series, index)
                    }

                }),
                ticks: this.chartData.ticks
            };

        }

    }
}
</script>
