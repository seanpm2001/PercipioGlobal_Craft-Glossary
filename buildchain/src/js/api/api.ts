export const configureApi = (url) => ({
    baseURL: url,
    headers: {
        'X-Requested-With': 'XMLHttpRequest'
    }
})

export const executeApi = async (api, url, variables = '', callback) => {
    try {
        const response = await api.get(url + variables)
        if (callback && response.data) {
            callback(response.data)
        }
    } catch (error) {
        console.error('xhr', error)
    }
}
