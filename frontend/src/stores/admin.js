import { ref, onMounted, computed, watch } from "vue";
import { defineStore } from "pinia";
import { useUserStore } from "./user";
import { useSearchStore } from "./search";
import api from "@/api";
import router from "@/router";
import { useRoute } from "vue-router";
import europeCities from "@/data/country-city.json";

export const useAdminStore = defineStore("admin", () => {
  const user = useUserStore();
  const search = useSearchStore();

  const adminView = ref("Bookings");
  const loading = ref(false);

  const bNum = ref("Broj rezervacije");

  function displayError(
    str = "Neispravan broj rezervacije! " +
      "\n" +
      "Broj mora imati tačno 7 brojeva ili tačno 7 brojeva + 2 slova: KP. " +
      "\n" +
      "Primer: 1234567KP / 1234567kp -> Bez razmaka."
  ) {
    user.errorMsg = str;

    user.clearMsg(6000);
  }

  watch(() => {
    if (user.errorMsg) bNum.value = "Primer: 1234567KP";
    else bNum.value = "Broj rezervacije";
  });

  // BOOKINGS
  const tab_bookings = ref(null);

  const items_bookings = ["Pretraga", "U narednih 24h", "U narednih 48h"];

  const lastFetch = ref(null);
  const lastFetch48 = ref(null);

  const in24 = ref(null);
  const in48 = ref(null);

  const in24Search = ref("");
  const in48Search = ref("");
  const headers = [
    { key: "from_city", title: "Grad polaska" },
    { key: "to_city", title: "Grad dolaska" },
    { key: "pickuptime", title: "Vreme polaska" },
    { key: "total_places", title: "Broj putnika" },
    { key: "actions", title: "Dodaj slobodnog vozača", sortable: false },
    { key: "assign", title: "Dodeli vozača", sortable: false },
    { key: "details", title: "Detalji", sortable: false },
  ];

  const drivers_24 = ref({});
  const drivers_48 = ref({});

  const assignedDriverID_24 = ref(null);
  const assignedDriverID_48 = ref(null);

  // SEARCH BOOKINGS BY FILTERS

  const depDay = ref({
    date: null,
    range: null,
  });
  const tourID = ref(null);
  const bCode = ref(null);
  const driverID = ref(null);

  const dep_city = ref();
  const arr_city = ref();

  const tours = ref([
    { id: 1, name: "Novi Sad - Rijeka" },
    { id: 2, name: "Rijeka - Novi Sad" },
  ]);
  const cities = ref({
    from: [],
    to: [],
  });

  

  // ------------- FROM API ----------------//

  const page = ref(1)
  const filteredOrders = ref(null)
  const reservations = ref([
    // Primer podataka iz API-ja
    {
      order_id: 1,
      from_city: 'Beograd',
      to_city: 'Berlin',
      date: '2025-10-15',
      price: 120,
      places: 2,
      code: '1234567KP',
      user: 'Marko Marković',
    },
    // ...
  ])
  
  const pageCount = computed(() => Math.ceil(reservations.value.length / 5))
  
  function formatDate(date) {
    const d = new Date(date)
    return d.toLocaleDateString('sr-RS', { day: '2-digit', month: '2-digit', year: 'numeric' })
  }
  
  function showDetails(order) {
    console.log('Detalji:', order)
    // Ovde možeš otvoriti modal, navigirati ili prikazati dodatne info
  }

  // USERS
  const usrEmail = ref(null);

  // TOURS
  const tourName = ref(null);
  const toursFrom = ref(null);
  const toursTo = ref(null);

  const toAddCountry = ref(null);
  const selectedCountry = ref(null);
  const selectedCity = ref(null);
  const cityOptions = computed(() => {
    if (!selectedCountry.value) return [];
    const countryData = europeCities.find(
      (c) => c.country === selectedCountry.value
    );
    return countryData ? countryData.cities : [];
  });

  const actions = ref({
    searchBooking: async () => {
      if (
        !depDay.value.date &&
        !bCode.value &&
        !tourID.value &&
        !usrEmail.value &&
        !dep_city.value &&
        !arr_city.value
      ) return displayError("Potrebno je uneti bar jedan filter!")

      let code = null;
      if (bCode.value) code = bCode.value.trim().toUpperCase()
      if (code) {
        if (code.length !== 7 && code.length !== 9) return displayError()
        let codeNum = code
        let codeStr = ""

        if (code.length === 9) {
          codeNum = code.slice(0, 7)
          codeStr = code.slice(-2)
          // const allNumbers = [...code].every(ch => !isNaN(ch) && ch !== ' ');
          if (codeStr !== "KP") return displayError()
        }

        const validNumber = /^[0-9]{7}$/.test(codeNum)
        if (!validNumber) return displayError()

        if (code.length === 7) code = code + "KP"
        code = code.trim()
      }

      loading.value = true;

      let outDate = null; //
      let toDate = null; //
      if (depDay.value.date) outDate = search.dateFormat(depDay.value.date)
      if (depDay.value.range) toDate = search.dateFormat(depDay.value.range)
      let tID = null;
      if (tourID.value) tID = tourID.value.id;
      if (usrEmail.value) {
        const validEmail = /^[\w.-]+@[a-zA-Z\d.-]+\.[a-zA-Z]{2,}$/.test(usrEmail.value)
        if (!validEmail) return displayError("Neispravan email, molimo unesite validan email!")
      }
      const dto = {
        user_id: user.user.id,
        filters: {
            departure: outDate,
            tour_id: tID,
            code: code,
            from_city: dep_city.value,
            to_city: arr_city.value,
            user_email: usrEmail.value,
        }
      }

      try {
        const res = await api.getOrder(dto)
        filteredOrders.value = res.data
        console.log(filteredOrders.value)
      } catch (error) {
        console.log(error)
      } finally {
        tab_bookings.value = "Pretraga"
        loading.value = false
      }
      //console.log(dto)
    },
    clearBookingSearch: () => {
        depDay.value.date = null,
        depDay.value.range = null,
        tourID.value = null,
        bCode.value = null,
        usrEmail.value = null,
        dep_city.value = null, 
        arr_city.value = null
    },
    fetchBookings: async (tab) => {
      if (tab == "U narednih 24h") {
        loading.value = true;
        const now = Date.now();
        if (lastFetch.value && lastFetch.value >= now - 6 * 60 * 1000) return
        const dto = {
          user_id: user.user.id,
          adminOrders: {
            all: true,
            in24: true,
            in48: "",
          },
        };
        try {
          const res = await api.getOrder(dto)
          in24.value = res.data
          //drivers_24.value = res.data.drivers
          console.log(in24.value)
          lastFetch.value = now
        } catch (error) {
          console.log(error)
        } finally {
          loading.value = false
        }
      } else if (tab == "U narednih 48h") {
        loading.value = true;
        const now = Date.now();
        if (lastFetch48.value && lastFetch48.value >= now - 6 * 60 * 1000)
          return;
        const dto = {
          user_id: user.user.id,
          adminOrders: {
            all: true,
            in48: true,
            in24: "",
          },
        };
        try {
          const res = await api.getOrder(dto)
          in48.value = res.data
          //drivers_48.value = res.data.drivers
          console.log(in48.value)
          lastFetch48.value = now
        } catch (error) {
          console.log(error)
        } finally {
          loading.value = false
        }
      } else {
        return
      }
    },
    openTour: (item) => {
      console.log(item)
    },
    assignDriver: async (driver, tour_id, rides) => {
      const dto = {
        orders: {
          user_id: user.user.id,
          driver: driver,
          tour_id: tour_id,
          selected: rides,
        },
      };
      if (!dto.orders.driver || !dto.orders.selected || !dto.orders.tour_id) 
      return displayError("Proverite sve podatke, nije moguće dodeliti vozača!")  
        /*
            console.log(dto)
            return */
      try {
        loading.value = true
        const res = await api.orderItemUpdate(dto)
        user.showSucc(res, 3000)
        console.log(res.data)
      } catch (error) {
        console.log(error)
      } finally {
        loading.value = false
      }
    },
    searchUser: () => {
      const dto = {
        email: usrEmail.value,
      };
      console.log(dto)
    },
    fetchAllTours: async () => {
      const dto = {
        tour: "all",
      };
      try {
        const res = await api.getTours(dto)
        tours.value = res.data.tours
        cities.value.from = res.data.from_cities
        cities.value.to = res.data.to_cities
        console.log(tours.value)
        console.log(cities.value)
      } catch (error) {
        console.log(error)
      }
    },
    searchTour: () => {
      const dto = {
        title: tourName.value,
        t_from: toursFrom.value,
        t_to: toursTo.value,
      };
      console.log(dto);
    },
  });

  return {
    actions, bNum, adminView, depDay, tourID, bCode, driverID,
    tours, usrEmail, tourName, toursFrom, toursTo, selectedCity, selectedCountry, 
    cityOptions, toAddCountry, tab_bookings, items_bookings, in24Search,
    headers, in48Search, in24, in48, drivers_24, drivers_48,
    assignedDriverID_24, assignedDriverID_48, cities,
    dep_city, arr_city, filteredOrders, page, reservations, pageCount, 

    formatDate, showDetails,
  };
});
