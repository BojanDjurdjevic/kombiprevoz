import axios from "./configadd";

export default {
    getAddress(query) {
        return axios.get("", {params: {query}}, {
            headers: {
                "Content-type": "json/application",
                "Authorization": "prj_test_pk_d423c022cf7219b2314dc5acbc506114edca54aa" 
            }
        })
    }
}