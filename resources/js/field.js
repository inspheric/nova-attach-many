Nova.booting((Vue, router, store) => {
    Vue.component('form-nova-attach-many', require('./components/FormField'))
    Vue.component('detail-nova-attach-many', require('./components/DetailField'))
})
