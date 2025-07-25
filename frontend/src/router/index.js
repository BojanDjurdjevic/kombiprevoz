import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import MyBookings from '@/views/MyBookingsView.vue'
import Destinations from '@/views/DestinationsView.vue'
import SearchResult from '@/views/SearchResultView.vue'
import CitiesView from '@/views/CitiesView.vue'
import CityBook from '@/views/CityBookView.vue'
import BookNow from '@/views/BookNowView.vue'
import ManageBooking from '@/views/ManageBookingView.vue'
import Contact from '@/views/ContactView.vue'
import SignInView from '@/views/SignInView.vue'
import LoginView from '@/views/LoginView.vue'
import ResetPassView from '@/views/ResetPassView.vue'
import { useUserStore } from '@/stores/user'
import RequestPassResetView from '@/views/RequestPassResetView.vue'
import MyProfileView from '@/views/MyProfileView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
    },
    {
      path: '/about',
      name: 'about',
      // route level code-splitting
      // this generates a separate chunk (About.[hash].js) for this route
      // which is lazy-loaded when the route is visited.
      component: () => import('../views/AboutView.vue'),
    },
    {
      path: '/kontakt',
      name: 'kontakt',
      component: Contact,
    },
    {
      path: '/rezervacije',
      name: 'rezervacije',
      component: MyBookings,
      meta: {
        requireAuth: true
      }
    },
    {
      path: '/destinacije',
      name: 'destinacije',
      component: Destinations,
    },
    {
      path: '/rezultati',
      name: 'rezultati',
      component: SearchResult,
    },
    {
      path: '/gradovi',
      name: 'gradovi',
      component: CitiesView,
    },
    {
      path: '/grad',
      name: 'grad',
      component: CityBook,
    },
    {
      path: '/korpa',
      name: 'korpa',
      component: BookNow,
      meta: {
        requireAuth: true
      }
    },
    {
      path: '/uredi',
      name: 'uredi',
      component: ManageBooking,
    },
    {
      path: '/registracija',
      name: 'signin',
      component: SignInView,
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
    },
    {
      path: '/password-reset',
      name: 'password-reset',
      component: ResetPassView,
    },
    {
      path: '/request-password-reset',
      name: 'request-password-reset',
      component: RequestPassResetView,
    },
    {
      path: '/profil',
      name: 'profil',
      component: MyProfileView,
      meta: {
        requireAuth: true
      }
    },
  ],
})

router.beforeEach((to, from, next) => {
  const user = useUserStore()
  console.log(user)
  if(to.meta.requireAuth && !user.getters.isAuthenticated(user.user)) {
    user.errorMsg = "Niste ulogovani! Molimo da se ulogujete pre nego što pristupite ovoj stranici."
    setTimeout(() => {
      next({
        path: '/login',
        query: { redirect: to.fullPath }
      })
      user.errorMsg = null
    }, 1000);
    
  } else {
    next()
  }
})

export default router
