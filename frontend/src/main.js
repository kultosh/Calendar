import Vue from 'vue'
// import App from './App.vue'
import AppCalendar from './AppCalendar.vue'

Vue.config.productionTip = false

new Vue({
  render: h => h(AppCalendar),
}).$mount('#app')
