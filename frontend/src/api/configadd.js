import axios from 'axios';

const instance = axios.create({
    baseURL: "https://api.radar.io/v1/search/autocomplete"
})

export default instance