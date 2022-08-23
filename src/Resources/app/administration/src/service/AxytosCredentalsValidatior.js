const ApiService = Shopware.Classes.ApiService;
const { Application } = Shopware;

class AxytosCredentalsValidatior extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'AxytosKaufAufRechnung') {
        super(httpClient, loginService, apiEndpoint);
    }

    check(values) {
        const headers = this.getBasicHeaders({});

        return this.httpClient
            .post(`/v1/${this.getApiBasePath()}/Credentials/validate`, values,{
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

Application.addServiceProvider('AxytosCredentalsValidatior', (container) => {
    const initContainer = Application.getContainer('init');
    return new AxytosCredentalsValidatior(initContainer.httpClient, container.loginService);
});