<template>
    <BaseGanttMetric
        @selected="handleRangeSelected"
        :title="card.name"
        :help-text="card.helpText"
        :help-width="card.helpWidth"
        :value="value"
        :chart-data="data"
        :ranges="card.ranges"
        :format="format"
        :prefix="prefix"
        :suffix="suffix"
        :suffix-inflection="suffixInflection"
        :selected-range-key="selectedRangeKey"
        :loading="loading"
    />
</template>

<script>
import _ from 'lodash'
import { InteractsWithDates, Minimum } from 'laravel-nova'
import BaseGanttMetric from './Base/GanttMetric'

export default {
    name: 'GanttMetric',

    mixins: [InteractsWithDates],

    components: {
        BaseGanttMetric,
    },

    props: {
        card: {
            type: Object,
            required: true,
        },

        resourceName: {
            type: String,
            default: '',
        },

        resourceId: {
            type: [Number, String],
            default: '',
        },

        lens: {
            type: String,
            default: '',
        },
    },

    data: () => ({
        loading: true,
        value: '',
        data: [],
        format: '(0[.]00a)',
        prefix: '',
        suffix: '',
        suffixInflection: true,
        selectedRangeKey: null,
    }),

    watch: {
        resourceId() {
            this.fetch()
        },
    },

    created() {
        if (this.hasRanges) {
            this.selectedRangeKey = this.card.ranges[0].value
        }

        if (this.card.refreshWhenActionRuns) {
            Nova.$on('action-executed', () => this.fetch())
        }
    },

    mounted() {
        this.fetch()
    },

    methods: {
        handleRangeSelected(key) {
            this.selectedRangeKey = key
            this.fetch()
        },

        fetch() {
            this.loading = true

            Minimum(Nova.request().get(this.metricEndpoint, this.metricPayload)).then(
                ({
                    data: {
                        value: {
                            values
                        },
                    },
                }) => {
                    this.data = {
                        labels: Object.keys(values[0].value),
                        values: values,
                        series: _.map(values, (series) => {
                            return {
                                color: series.color,
                                value: _.map(series.value, (value, date) => {
                                    return {
                                        meta: date,
                                        value: value
                                    }
                                })
                            }
                        }),
                        ticks: _.map(values, (value) => { return value.label })
                    }
                    this.loading = false
                }
            )
        },
    },

    computed: {
        hasRanges() {
            return this.card.ranges.length > 0
        },

        metricPayload() {
            const payload = {
                params: {
                    timezone: this.userTimezone,
                    twelveHourTime: this.usesTwelveHourTime,
                },
            }

            if (this.hasRanges) {
                payload.params.range = this.selectedRangeKey
            }

            return payload
        },

        metricEndpoint() {
            const lens = this.lens !== '' ? `/lens/${this.lens}` : ''
            if (this.resourceName && this.resourceId) {
                return `/nova-api/${this.resourceName}${lens}/${this.resourceId}/metrics/${this.card.uriKey}`
            } else if (this.resourceName) {
                return `/nova-api/${this.resourceName}${lens}/metrics/${this.card.uriKey}`
            } else {
                return `/nova-api/metrics/${this.card.uriKey}`
            }
        },
    },
}
</script>
