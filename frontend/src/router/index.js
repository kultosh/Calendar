import Vue from 'vue';
import Router from 'vue-router';
import GoogleLogin from '@/components/auth/GoogleLogin';
import GoogleAuth from '@/components/auth/GoogleAuth';
import AppCalendar from '@/components/calendar/AppCalendar';

Vue.use(Router);

const router = new Router({
  mode: 'history',
  routes: [
    { path: '/login', name: 'GoogleLogin', component: GoogleLogin },
    { path: '/auth', name: 'GoogleAuth', component: GoogleAuth },
    { path: '/app-calendar', name: 'AppCalendar', component: AppCalendar, meta: { requiresAuth: true } },
    { path: '*', redirect: '/login' }
  ]
});

router.beforeEach((to, from, next) => {
  const isAuthenticated = localStorage.getItem('auth_token');
  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!isAuthenticated) {
      next({ name: 'GoogleLogin' });
    } else {
      next();
    }
  } else {
    next();
  }
});

export default router;
