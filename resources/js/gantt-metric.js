Nova.booting((Vue, router, store) => {

    Vue.component('base-gantt-metric', require('./components/Base/GanttMetric'));
    Vue.component('gantt-metric', require('./components/GanttMetric'));

})