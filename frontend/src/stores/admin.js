import { ref, onMounted, computed, watch, onBeforeUnmount } from "vue";
import { defineStore } from "pinia";
import { useUserStore } from "./user";
import { useSearchStore } from "./search";
import api from "@/api";
import router from "@/router";
import { useRoute } from "vue-router";
import europeCities from "@/data/country-city.json";
import { useDestStore } from "./destinations";

export const useAdminStore = defineStore("admin", () => {

  const user = useUserStore();
  const search = useSearchStore();
  const dest = useDestStore();

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
    user.clearMsg(6000)
    if(user.errorMsg) manageDialog.value = false
  }

  watch(() => {
    if (user.errorMsg) bNum.value = "Primer: 1234567KP";
    else bNum.value = "Broj rezervacije";
  });

  // ADMIN DRAWER

  const drawer = ref(true)

  function toggleDrawer() {
    drawer.value = !drawer.value
  }

  const filter = ref(false)

  function toggleFilter() {
    filter.value = !filter.value
  }

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

  

  // ------------- FROM API BY Filters ----------------//

  const page = ref(1)
  const filteredOrders = ref(null)
  const pageCount = computed(() => Math.ceil(reservations.value.length / 5))
  
  function formatDate(date) {
    const d = new Date(date)
    return d.toLocaleDateString('sr-RS', { day: '2-digit', month: '2-digit', year: 'numeric' })
  }
  // Fetch available dates:
  const allowedDays = ref({
    fullyBooked: [],
    fulls: [],
    allowed: []
  })
  async function adminDateQuery(from, to) {
    let formD = search.qDateForm()
    let dto = {
      days: {
        from: from,
        to: to,
        format: formD
      }
    }
    console.log(dto)
    try {
      const res = await api.checkAvailableDates(dto)
      console.log(res.data)
      allowedDays.value.fullyBooked = res.data.fullyBooked
      allowedDays.value.allowed = res.data.allowed
      console.log(allowedDays.value.fullyBooked)
    } catch (error) {
      console.log(error)
    }
    
  }
  const isDateAllowed = (dateStr) => {
    const date = new Date(dateStr)
    const dayOfWeek = date.getDay()

    let d = date
    let year = String(d.getFullYear()) 
    let months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12']
    let m = d.getMonth()
    let month = months[m]
    let dates = String(d.getDate())
    let formated = year + "-" + month + "-" + dates

    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);

    return allowedDays.value.allowed.includes(dayOfWeek) && !allowedDays.value.fullyBooked.includes(formated) && date >= new Date()
  } 
  // ALL BOOKING MANAGE Dialogs:
  const dialog = ref({
    open: false,
    type: null, // 'details' | 'rides' | 'confirm'
    payload: null
  })

  function openDialog(type, payload = null) {
    dialog.value.open = true
    dialog.value.type = type
    dialog.value.payload = payload
  }

  function closeDialog() {
      dialog.value.open = false
      dialog.value.type = null
      dialog.value.payload = null
  }

  //old refs
  const selected = ref(null)
  const manageDialog = ref(false)
  const manageTab = ref('details')
  const confirmManage = ref(false)
  const cancelDialog = ref(false)
  const restoreDialog = ref(false)

  const orderHistoryDialog = ref(false)

  async function showDetails(order) {
    selected.value = order
    console.log(selected.value)
    changeFromAddress.value = selected.value.pickup
    changeToAddress.value = selected.value.dropoff
    changeDate.value = new Date(selected.value.date)
    changeSeats.value = Number(selected.value.places) || 0
    await adminDateQuery(selected.value.from_city, selected.value.to_city)
    setTimeout(() => {
      manageDialog.value = true
    }, 0)
    
  }

  // ------------ ON MANAGE BOOKING DIALOG -----------------------//
  
  const changeFromAddress = ref(null)
  const changeToAddress = ref(null)
  const changeDate = ref(null)
  const changeSeats = ref(null)

  // USERS
  const usrEmail = ref(null);

  // TOURS
  const tab_tours = ref(null)
  const items_tours = [
    "Postojeće rute",
    "Dodaj novu rutu",
    "Države i Gradovi",
    "Pretraga"
  ];

  const tourDays = [
    {id: 0, day: "Nedelja"},
    {id: 1, day: "Ponedeljak"},
    {id: 2, day: "Utorak"},
    {id: 3, day: "Sreda"},
    {id: 4, day: "Četvrtak"},
    {id: 5, day: "Petak"},
    {id: 6, day: "Subota"}
  ]
  //filters
  const tourName = ref(null);
  const toursFrom = ref(null);
  const toursTo = ref(null);

  const filteredTours = ref(null)

  // existing tours:

  const tourPage = ref(1)
  //const filteredOrders = ref(null)
  const tPageCount = computed(() => Math.ceil(tours.value.length / 10))

  function formatDepDays(str) {
    str = str.split(',')
    let days = []
    for(let s of str) {
      switch (s) {
        case '0': 
          days.push('Nedelja')
          break;
        case '1':
          days.push('Ponedeljak')
          break;
        case '2':
          days.push('Utorak')
          break;
        case '3':
          days.push('Sreda')
          break;
        case '4':
          days.push('Četvrtak')
          break;
        case '5':
          days.push('Petak')
          break;
        case '6':
          days.push('Subota')
          break;
      }
    }

    return days.join(', ')
  }

  // ALL TOUR MANAGE Dialogs:
  const selectedTour = ref(null)
  const manageTourDialog = ref(false)
  const confirmTourManage = ref(false)
  const cancelTourDialog = ref(false)
  const restoreTourDialog = ref(false)

  const changeTime = ref(null)
  const changeTourSeats = ref(null)
  const changeDuration = ref(null)
  const changePrice = ref(null)
  const changeDeps = ref(null)

  function fillDeps(str) {
    str = str.split(',')
    const deps = []
    for(let s of str) {
      tourDays.forEach(day => {
        if(s == day.id) {
          deps.push(day)
        }
      });
    }
    return deps
  }

  async function showTour(tour) {
    selectedTour.value = tour
    console.log(selectedTour.value)
    changeTime.value = selectedTour.value.time 
    changeTourSeats.value = Number(selectedTour.value.seats) || 8
    changeDuration.value = Number(selectedTour.value.duration) || 0
    changePrice.value = Number(selectedTour.value.price) || 0
    changeDeps.value = fillDeps(selectedTour.value.departures)
    setTimeout(() => {
      manageTourDialog.value = true
    }, 0)
    
  }

  //add destinations
  const toAddCountry = ref(null);
  const selectedCountry = ref(null);
  const selectedCity = ref(null);
  const cityOptions = computed(() => {
    if (!selectedCountry.value) return [];
    const countryData = europeCities.find(
      (c) => c.country === selectedCountry.value.name
    );
    return countryData ? countryData.cities : [];
  });
  const userOptions = ref([])
  function userCityOptions(val) {
    if (!val) return [];
    const countryData = europeCities.find(
      (c) => c.country === val.name
    );
    userOptions.value = countryData ? countryData.cities : [];
  }
  //countries
  const preview = ref(null)
  const previewKey = ref(Date.now())
  const flag = ref(null)

  function selectFlag() {
      const file = Array.isArray(flag.value) ? flag.value[0] : flag.value
      if(file instanceof File) {
        if (preview.value) {
          URL.revokeObjectURL(preview.value)
        }
        preview.value = URL.createObjectURL(file)
        previewKey.value = Date.now()
        console.log(preview.value)
      } else preview.value = null
  }
  function clearFlag() {
    if(preview.value) {
      URL.revokeObjectURL(preview.value)
    }

    preview.value = null
    flag.value = null
    previewKey.value = Date.now()
  }

  //cities
  const dbCountries = ref([])
  const cityPics = ref(null)
  const cityPreview = ref(null)
  const cityPreviewKey = ref(null)

  function selectCityPics() {
    if (!cityPics.value || cityPics.value.length === 0) {
      cityPreview.value = null
      return
    }

    if (Array.isArray(cityPreview.value)) {
      cityPreview.value.forEach(url => URL.revokeObjectURL(url))
    }

    cityPreview.value = Array.from(cityPics.value).map(file => URL.createObjectURL(file))
    cityPreviewKey.value = Date.now()

    console.log(cityPreview.value)
  }

  function clearCityPics() {
    if (cityPreview.value && cityPreview.value.length > 0) {
      cityPreview.value.forEach(url => URL.revokeObjectURL(url))
    }
    cityPreview.value = null
    cityPics.value = null
    cityPreviewKey.value = Date.now()
  }

  function clearCountryPic() {
    if(preview.value) {
      URL.revokeObjectURL(preview.value)
      preview.value = null
    }
    flag.value = null
    toAddCountry.value = null
  }


  onBeforeUnmount(() => {
    if(cityPreview.value) {
      if (cityPreview.value.length > 0) {
        cityPreview.value.forEach(url => URL.revokeObjectURL(url))
        cityPreview.value = null
      }
    }
    
    if (preview.value) {
      URL.revokeObjectURL(preview.value)
      preview.value = null
    }
  })

  //Add new tour:

  const countryFrom = ref(null)
  const countryTo = ref(null)
  const cityFrom = ref(null)
  const cityTo = ref(null)
  const daysOfTour = ref([])
  const hours = ref(3)
  const pax = ref(1)
  const price = ref(30)
  const tourTime = ref(null)

  const validateTime = (value) => {
    if (!value) return pattern.test(value) || 'Polje ne može biti prazno! Unesi ispravno vreme (HH:MM:SS)'

    const pattern = /^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/
    return pattern.test(value) || 'Unesi ispravno vreme (HH:MM:SS)'
  }

  const disableTour = () => {
    if(!cityFrom.value || !cityTo.value || !daysOfTour.value || !hours.value || !pax.value || !price.value || !tourTime.value) return true
    else return false
  }

  function filterDeps(str) {
    let deps = []
    str.forEach(obj => {
      deps.push(String(obj.id))
    });
    deps = deps.toString()
    return deps
  }

  // Countries&Cities Managment:

  const myCountry = ref(null)
  const citiesByCountry = ref(null)

  // Country&City DIALOG
  const countryDialog = ref(false)
  const cityDialog = ref(false)

  const myCity = ref(null)
  const myCityPics = ref(null)
  const cityDeletedPics = ref(null)

  function openCityDialog(city) {
    cityDialog.value = true
    let arr = []
    let arr_deleted = []
    if(city.pictures.length > 0) {
      city.pictures.forEach(p => {
        let pic = dest.adminCountryImage(p)
        if(pic) {
          if(p.deleted === 0) {
            arr.push(
              {
                photo_id: p.photo_id,
                file_path: pic,
                deleted: p.deleted 
              }
            )
          } else {
            arr_deleted.push(
              {
                photo_id: p.photo_id,
                file_path: pic,
                deleted: p.deleted 
              }
            )
          }
        }
      });
    }
    myCity.value = city
    myCityPics.value = arr
    cityDeletedPics.value = arr_deleted
    console.log(myCity.value)
    console.log(myCityPics.value)
    console.log(cityDeletedPics.value)
    console.log("Aktivne fotke: " + myCityPics.value.length + "\n" + "Neaktivne fotke: " + cityDeletedPics.value.length)
  }

  function closeCityDialog() {
    myCity.value = null
    myCityPics.value = []
    cityDeletedPics.value = []
    selectedPictures.value = []
    unSelectedPictures.value = []
    cityDialog.value = false
  }

  const selectedPictures = ref([])
  const unSelectedPictures = ref([]) // to restore removed images

  // USERS

  const tab_users = ref(null);

  const items_users = ["Kreiraj novog korisnika", "Pretraga"];

  const userByAdmin = ref(null)
  const userLogs = ref([])

  const userEditDialog = ref(false)
  const userContactDialog = ref(false)

  const editedUser = ref({
    updateByAdmin: false,
    users: {
      id: null,
      name: '',
      address: '',
      city: '',
      phone: '',
      status: null
    } 
  })

  // -------------------------------------------- ALL API CALLS --------------------------------- //
  

  const actions = ref({
    // -------------- SEARCH BY FILTER - BOOKINGS -----------------//
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
        filter.value = false
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
    // -------------- ADMIN MANAGE BOOKINGS -----------------//
    manageBookingItems: () => {
      if(!changeDate.value && !changeFromAddress.value && !changeToAddress.value && !changeSeats.value) {
        displayError("Sva polja su prazna! Unesite bar jednu izmenu u formu!")
        setTimeout(() => {
          manageDialog.value = true
        }, 4400)
        return
      }
      if(search.dateFormat(changeDate.value) == selected.value.date && changeFromAddress.value == selected.value.pickup 
        && changeToAddress.value == selected.value.dropoff && changeSeats.value == selected.value.places) {
        displayError("Niste uneli nikakvu izmenu! Pokušavate da pošaljete već postojeće podatke.")
        setTimeout(() => {
          manageDialog.value = true
        }, 4400)
        return
      }
      confirmManage.value = true
    },
    confimBookingItemsChange: async () => {
      loading.value = true

      if(search.dateFormat(changeDate.value) == selected.value.date) changeDate.value = null
      if(changeFromAddress.value == selected.value.pickup) changeFromAddress.value = null
      if(changeToAddress.value == selected.value.dropoff) changeToAddress.value = null
      if(changeSeats.value == selected.value.places) changeSeats.value = null

      let resch = {
        outDate: null,
        inDate: null
      } 
      if(changeDate.value) {
        if(selected.value.user_city == selected.value.from_city) {
          resch.outDate = search.dateFormat(changeDate.value)
          resch.inDate = null
        } else {
          resch.outDate = null
          resch.inDate = search.dateFormat(changeDate.value)
        }
      }
      const dto = {
        orders: {
          user_id: user.user.id,
          update: {
            id: selected.value.item_id
          },
          address: {
            add_from: changeFromAddress.value,
            add_to: changeToAddress.value
          },
          new_places: changeSeats.value,
          reschedule: resch
        }
      } 
      try { 
        const res = await api.orderItemUpdate(dto)
        if(res.data.success) user.showSucc(res, 3000)
        console.log(res.data) 
        console.log(dto)
      } catch(error) {
        console.dir(error, {depth: null})
        user.showErr(error, 3000)
      } finally {
        actions.value.clearManageItems()
        actions.value.searchBooking()
        manageDialog.value = false
        confirmManage.value = false
        loading.value = false
      }
      
      //console.log(dto)
    },
    clearManageItems: () => {
      changeDate.value = null
      changeFromAddress.value = null
      changeToAddress.value = null
      changeSeats.value = null
    },
    // -------------- VOUCHER/CANCEL ACTIONS - BOOKINGS -----------------//
    resendVoucher: async () => {
      loading.value = true
      const dto = {
        orders: {
            user_id: user.user.id,
            voucher: {
                item_id: selected.value.item_id
            }
        }
      }
      console.log(dto) 
      try {
        const res = await api.orderItemUpdate(dto)
        console.log(res.data)
        if(res.data.success) user.showSucc(res, 3000)
      } catch (error) {
        console.dir(error, {depth: null})
        user.showErr(error, 3000)
      } finally {
        manageDialog.value = false
        actions.value.searchBooking()
        loading.value = false
      } 
    },
    confirmCancelBookingItem: async () => {
      loading.value = true
      const dto = {
        orders: {
            user_id: user.user.id,
            delete: {
                item_id: selected.value.item_id
            }
        }
      }
      console.log(dto) 
      try {
        const res = await api.orderItemDelete(dto)
        console.log(res.data)
        if(res.data.success) user.showSucc(res, 3000)
      } catch (error) {
        console.dir(error, {depth: null})
        user.showErr(error, 3000)
      } finally {
        cancelDialog.value = false
        manageDialog.value = false
        loading.value = false
        actions.value.searchBooking()
      } 
    },
    restoreBookingItem: async () => {
      loading.value = true
      const dto = {
        orders: {
            user_id: user.user.id,
            restore: {
                item_id: selected.value.item_id
            }
        }
      }
      console.log(dto) 
      try {
        const res = await api.orderItemDelete(dto)
        console.log(res.data)
        if(res.data.success) user.showSucc(res, 3000)
      } catch (error) {
        console.dir(error, {depth: null})
        user.showErr(error, 3000)
      } finally {
        restoreDialog.value = false
        manageDialog.value = false
        loading.value = false
        actions.value.searchBooking()
      } 
    },
    // -------------- 24/48 TO ASSIGN - BOOKINGS -----------------//
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
    // -------------- SEARCH BY FILTER - USER -----------------//
    searchUser: async () => {
      const dto = {
        email: usrEmail.value,
        byEmail: true
      };
      try {
        const res = await api.getUser(dto)
        userByAdmin.value = res.data.user
        userLogs.value = res.data.logs ? res.data.logs : []
        console.log(userByAdmin.value)
        console.log('Svi logovi traženog USER-a: ', userLogs.value)
      } catch (error) {
        console.log(error)
      } finally {
        tab_users.value = 'Pretraga'
        filter.value = false
      } 
    },
    // ---------------------- USER MANAGE by ADMIN ----------------------- //
    createUser: async (users) => {
      console.log(users)
      try {
          const res = await api.logUser(users)
          if(res.data.success) {
              user.showSucc(res, 6000)
          } else {
              console.log(res.data)
          }
      } catch (error) {
          console.dir(error, {depth: null})
          if(error.response.data.error) {
              user.showErr(error, 6000)
          } else {
              console.log('pogrešno dohvatanje')
          }
      }
    },
    openUserEditDialog: () => {
      editedUser.value.users = userByAdmin.value
      editedUser.value.updateByAdmin = true
      setTimeout(() => {
        userEditDialog.value = true
      }, 10)
    },
    closeUserEditDialog: () => {
      userEditDialog.value = false
      actions.value.resetUserEdit 
      editedUser.value.updateByAdmin = false
    },
    resetUserEdit: () => {
      let id = userByAdmin.value?.id ?? null
      editedUser.value.users = {
        id: id,
        name: '',
        address: '',
        city: '',
        phone: '',
        status: null
      } 
    },
    confirmEditUser: async () => {
      console.log(editedUser.value)
      if(!editedUser.value.users.id || !editedUser.value.users.name || !editedUser.value.users.address 
        || !editedUser.value.users.city || !editedUser.value.users.phone || !editedUser.value.users.status
      ) {
        userEditDialog.value = false
        return displayError('Molimo vas da popunite sva polja ukoliko želite da ažurirate profil!')
      } 
      try {
        const res = await api.requestReset(editedUser.value)
        console.log(res.data)
        user.showSucc(res, 6000)
      } catch (error) {
        console.log(error)
        user.showErr(error, 6000)
      } finally {
        actions.value.closeUserEditDialog()
      }
    },
    // ------------------ TOURS ---------------------//
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
    searchTour: async () => {
      let id = tourName.value?.id || null
      const dto = {
        byFilter: {
          id: id,
          from_city: toursFrom.value,
          to_city: toursTo.value,
        }       
      };

      console.log(dto);

      try {
          const res = await api.getTours(dto)
          console.log(res.data)
          if(res.data.has_tours) filteredTours.value = res.data.tours
          tab_tours.value = "Pretraga"
      } catch (error) {
          console.log(error)
      } finally {
        filter.value = false
      } 
    },
    clearTourFilters: () => {
      tourName.value = null
      toursFrom.value = null
      toursTo.value = null
    },
    addCountry: async () => {
      const formData = new FormData()
      formData.append("flag", flag.value)
      formData.append("country_name", toAddCountry.value)
      formData.append("country", "create")

      try {
        const res = await api.insertCountry(formData)
        console.log(res.data)
        actions.value.fetchCountries()
        user.showSucc(res, 3000)
      } catch (error) {
        console.log(error)
        user.showErr(error, 3000)
      } finally {
        clearCountryPic()
      }
    },
    updateCountry: async () => {
      let id = myCountry.value?.id || null
      let name = myCountry.value?.name || ''
      if(!id || name == '') return displayError('Došlo je do greške! Pokušajte ponovo!')
      const formData = new FormData()
      formData.append("flag", flag.value)
      formData.append("country_id", id)
      formData.append("country_name", name)
      formData.append("country", "update")

      try {
        const res = await api.insertCountry(formData)
        console.log(res.data)
        actions.value.fetchCountries()
        actions.value.searchByCountry()
        
        user.showSucc(res, 3000)
      } catch (error) {
        console.log(error)
        user.showErr(error, 3000)
      } finally {
        clearCountryPic()
        countryDialog.value = false
      }
    },
    fetchCountries: async () => {
      const dto = {
        country: {
          id: "",
          name: ""
        }
      }
      try {
        const res = await api.getCountries(dto)
        //let input = Object.values(msg.data.drzave)
        dbCountries.value = res.data.drzave
        console.log(dbCountries.value) 
      } catch (error) {
        console.log(error)
      }
    }, 
    addCity: async () => {
      if(!selectedCountry.value || !selectedCity.value || !cityPics.value) return displayError("Molimo popunite sva polja pre dodavanja grada!")
      const formData = new FormData()
      formData.append("country_id", selectedCountry.value.id)
      formData.append("name", selectedCity.value)
      formData.append("cities", "create")
      cityPics.value.forEach(file => formData.append('photos[]', file))

      try { 
        const res = await api.insertCity(formData)
        console.log(res.data)
        user.showSucc(res, 3000)
      } catch (error) {
        console.log(error)
        user.showErr(error, 3000)
      } finally {
        clearCityPics()
        
      }
    },
    removeCity: async () => {

    },
    // ADD-REMOVE city pictures - UPDATE
    deleteSelected: async () => {
      console.log(selectedPictures.value)
      if(selectedPictures.value?.length < 1) return displayError('Nije selektovana nijedna fotografija!')
      let dto = {
        cities: {
          ids: selectedPictures.value
        }
      }
      try {
        const res = await api.updateCity(dto)
        if(res.data.success) user.showSucc(res, 6000)
        console.log(res.data)
        closeCityDialog()
        actions.value.searchByCountry()
      } catch (error) {
        console.log(error)
      } finally {
        closeCityDialog()
      }
    },
    restoreSelected: async () => {
      console.log(unSelectedPictures.value)
      if(unSelectedPictures.value?.length < 1) return displayError('Nije selektovana nijedna fotografija!')
      let dto = {
        cities: {
          ids_restore: unSelectedPictures.value
        }
      }
      try {
        const res = await api.updateCity(dto)
        if(res.data.success) user.showSucc(res, 6000)
        console.log(res.data)
        
        actions.value.searchByCountry()
      } catch (error) {
        console.log(error)
      } finally {
        closeCityDialog()
      }
    },
    addCityPics: async () => {
      let id = myCity.value?.city_id || null
      let name = myCity.value?.name || null
      if(!id || !name) return displayError('Došlo je do greške! Nepoznat ID i ime grada.')
      const formData = new FormData()
      formData.append("city_id", id)
      formData.append("name", name)
      formData.append("cities", "update")
      cityPics.value.forEach(file => formData.append('photos[]', file))

      try { 
        const res = await api.insertCity(formData)
        console.log(res.data)
        user.showSucc(res, 3000)
        actions.value.searchByCountry()
      } catch (error) {
        console.log(error)
        user.showErr(error, 3000)
      } finally {
        clearCityPics()
        cityDialog.value = false
      }
    },
    clearTourForm: () => {
      countryFrom.value = null,
      countryTo.value = null,
      cityFrom.value = null,
      cityTo.value = null,
      /*
      daysOfTour.value = null,
      tourTime.value = null,
      hours.value = 3,
      price.value = 50,
      pax.value = 8 
      */
      changeDeps.value = null
      changeTime.value = null,
      changeDuration.value = null,
      changeTourSeats.value = null,
      changePrice.value = null
    },
    addTour: async () => {
      //if(!cityFrom.value || !cityTo.value || !daysOfTour.value || !hours.value || !pax.value || !price.value || !tourTime.value) return displayError('Sva polja su obavezna!')
      if(!cityFrom.value || !cityTo.value || !changeDeps.value || !changeDuration.value || 
        !changeTourSeats.value || !changePrice.value || 
        !changeTime.value) return displayError('Sva polja su obavezna!')
      let departures = filterDeps(changeDeps.value) //daysOfTour
      const dto = {
        tours : {
          from: cityFrom.value.name,
          to: cityTo.value.name,
          departures: departures, 
          /*
          time: tourTime.value,
          duration: hours.value,
          price: price.value,
          seats: pax.value
          */
          time: changeTime.value,
          duration: changeDuration.value,
          price: changePrice.value,
          seats: changeTourSeats.value
        }
      }
      try {
        const res = await api.createTour(dto)
        console.log(res)  
        user.showSucc(res, 3000)
        actions.value.clearTourForm()
        actions.value.fetchAllTours()
      } catch (error) { 
        console.log(error)
        user.showErr(error, 3000)
      }
    },
    updateTour: async () => {
      let deps = filterDeps(changeDeps.value)
      if(changeTime.value == selectedTour.value?.time && changeDuration.value == selectedTour.value?.duration &&
        changePrice.value == selectedTour.value?.price && changeTourSeats.value == selectedTour.value?.seats &&
        deps == selectedTour.value?.departures
      ) {
        manageTourDialog.value = false
        displayError('Nije izmenjena nijedna stavka! Promenite bar jedno polje da biste zatražili promenu rute.')
        setTimeout(() => {
          manageTourDialog.value = true
        }, 3900)
        return
      }
      const dto = {
        tours: {
          update: true,
          id: selectedTour.value.id,
          departures: deps,
          time: changeTime.value,
          duration: changeDuration.value,
          price: changePrice.value,
          seats: changeTourSeats.value
        }
      }
      try {
        const res = await api.updateTour(dto)
        user.showSucc(res, 6000)
        console.log(res.data)
        actions.value.fetchAllTours()
        if(tab_tours.value == "Pretraga") actions.value.searchTour()
      } catch (error) {
        console.log(error)
        user.showErr(error, 6000)
      } finally {
        actions.value.clearTourEdit()
        manageTourDialog.value = false
      }
    },
    clearTourEdit: () => {
      changeDeps.value = null
      changeDuration.value = null
      changePrice.value = null
      changeTourSeats.value = null
      changeTime.value = null
    },
    removeTour: async () => {
      const tours = {
        tours: {
          id: selectedTour.value?.id,
          to_city: selectedTour.value?.to_city,
          delete: true
        }
      }
      try {
        const res = await api.deleteTour(tours)
        console.log(res.data)
        user.showSucc(res, 6000)
        await actions.value.fetchAllTours()
      } catch (error) {
        console.log(error)
        user.showErr(error, 6000)
      } finally {
        actions.value.clearTourEdit
        manageTourDialog.value = false
      }
    },
    restoreTour: async () => {
      const tours = {
        tours: {
          id: selectedTour.value.id,
          restore: true
        }
      }
      try {
        const res = await api.deleteTour(tours)
        console.log(res.data)
        user.showSucc(res, 6000)
        await actions.value.fetchAllTours()
      } catch (error) {
        console.log(error)
        user.showErr(error, 6000)
      } finally {
        actions.value.clearTourEdit
        manageTourDialog.value = false
      }
    },
    permanentDeleteTour: async () => {
      const tours = {
        tours: {
          id: selectedTour.value.id,
          permanentDelete: true
        }
      }
      try {
        const res = await api.deleteTour(tours)
        console.log(res.data)
        user.showSucc(res, 6000)
        await actions.value.fetchAllTours()
      } catch (error) {
        console.log(error)
        user.showErr(error, 6000)
      } finally {
        actions.value.clearTourEdit
        manageTourDialog.value = false
      }
    },
    searchByCountry: async () => {
      if(!myCountry.value) return
      let id = myCountry.value?.id || null
      if(!id) return
      const dto = {
        countryID: id
      }
      console.log(myCountry.value)

      try {
        const res = await api.getCities(dto)
        citiesByCountry.value = res.data
        console.log(citiesByCountry.value)
      } catch (error) {
        console.log(error)
      }
    },
    
  });

  return {
    actions, bNum, adminView, depDay, tourID, bCode, driverID,
    tours, usrEmail, tourName, toursFrom, toursTo, selectedCity, selectedCountry, 
    cityOptions, toAddCountry, tab_bookings, items_bookings, in24Search,
    headers, in48Search, in24, in48, drivers_24, drivers_48,
    assignedDriverID_24, assignedDriverID_48, cities,
    dep_city, arr_city, filteredOrders, page, pageCount, selected, manageDialog,
    changeDate, changeFromAddress, changeSeats, changeToAddress, confirmManage,
    cancelDialog, restoreDialog, preview, flag, dbCountries, cityPics, cityPreview,
    cityPreviewKey, countryFrom, countryTo, cityFrom, cityTo, tourTime, daysOfTour,
    pax, price, hours, tPageCount, tourPage, manageTourDialog, confirmTourManage,
    cancelTourDialog, restoreTourDialog, changeTime, changeTourSeats, changeDuration,
    changePrice, changeDeps, selectedTour, tab_tours, items_tours, filteredTours,
    myCountry, citiesByCountry, countryDialog, cityDialog, myCity, selectedPictures,
    myCityPics, cityDeletedPics, unSelectedPictures, tab_users, items_users,
    userOptions, userByAdmin, userEditDialog, userContactDialog, editedUser,
    userLogs, orderHistoryDialog,

    // admin drawer & layout - AFTER REFACTOR
    drawer, filter,
    // adminDialogs - AFTER REFACTOR
    manageTab,
    //
    formatDate, showDetails, adminDateQuery, isDateAllowed, selectFlag, selectCityPics,
    clearCityPics, clearFlag, validateTime, disableTour, formatDepDays, showTour,
    openCityDialog, closeCityDialog, userCityOptions,

    // admin layout - AFTER REFACTOR
    toggleDrawer, toggleFilter,
    // Dialogs

  };
});
