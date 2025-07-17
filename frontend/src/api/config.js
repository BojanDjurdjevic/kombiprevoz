import axios from 'axios';

const instance = axios.create({
    baseURL: "http://localhost:8080/",
    withCredentials: true,
    headers: {
        "Content-type": "json/application"
    }
})

export default instance