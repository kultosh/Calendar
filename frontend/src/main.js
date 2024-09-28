import Vue from 'vue'
// import App from './App.vue'
import AppCalendar from './AppCalendar.vue'
import '../public/styles/app.css'

Vue.config.productionTip = false

new Vue({
  render: h => h(AppCalendar),
}).$mount('#app')
