class LocationSelector {
    // Initialize
    constructor(config) {
        this.countryId = config.countryId;
        this.stateId = config.stateId;
        this.cityId = config.cityId;
        this.thanaId = config.thanaId;
        this.unionId = config.unionId;
        this.zipcodeId = config.zipcodeId;
        this.baseUrl = config.baseUrl || '/admin/warehouse';
        this.baseUrl = config.baseUrl || '/admin/branch';
        this.init();
    }

    // Initialize
    init() {
        this.bindEvents();
    }

    // Bind events
    bindEvents() {
        const country = document.getElementById(this.countryId);
        const state = document.getElementById(this.stateId);
        const city = document.getElementById(this.cityId);
        const thana = document.getElementById(this.thanaId);
        const union = document.getElementById(this.unionId);
        const zipcode = document.getElementById(this.zipcodeId);

        if (!country || !state || !city) return;

        country.addEventListener('change', () => this.loadStates(country.value, state, city, thana, union, zipcode));
        state.addEventListener('change', () => this.loadCities(state.value, city, thana, union, zipcode));
        city.addEventListener('change', () => this.loadThanas(city.value, thana, union, zipcode));
        thana.addEventListener('change', () => this.loadUnions(thana.value, union, zipcode));
        union.addEventListener('change', () => this.loadZipcode(union.value, zipcode));
    }

    // Load states
    async loadStates(countryId, state, city, thana, union, zipcode) {
        this.reset([state, city, thana, union], zipcode);
        if (!countryId) return;
        const res = await fetch(`${this.baseUrl}/get-states/${countryId}`);
        const data = await res.json();
        data.forEach(d => state.innerHTML += `<option value="${d.id}">${d.name}</option>`);
    }

    // Load cities
    async loadCities(stateId, city, thana, union, zipcode) {
        this.reset([city, thana, union], zipcode);
        if (!stateId) return;
        const res = await fetch(`${this.baseUrl}/get-cities/${stateId}`);
        const data = await res.json();
        data.forEach(d => city.innerHTML += `<option value="${d.id}">${d.name}</option>`);
    }

    // Load thanas
    async loadThanas(cityId, thana, union, zipcode) {
        this.reset([thana, union], zipcode);
        if (!cityId) return;
        const res = await fetch(`${this.baseUrl}/get-thanas/${cityId}`);
        const data = await res.json();
        data.forEach(d => thana.innerHTML += `<option value="${d.id}">${d.name}</option>`);
    }

    // Load unions
    async loadUnions(thanaId, union, zipcode) {
        this.reset([union], zipcode);
        if (!thanaId) return;
        const res = await fetch(`${this.baseUrl}/get-unions/${thanaId}`);
        const data = await res.json();
        data.forEach(d => union.innerHTML += `<option value="${d.id}">${d.name}</option>`);
    }

    // Load zipcode
    async loadZipcode(unionId, zipcode) {
        zipcode.value = '';
        if (!unionId) return;
        const res = await fetch(`${this.baseUrl}/get-zipcode/${unionId}`);
        const data = await res.json();
        zipcode.value = data.zipcode || '';
    }

    // Reset
    reset(selects, zipcode) {
        selects.forEach(sel => sel.innerHTML = '<option value="">--Select--</option>');
        if (zipcode) zipcode.value = '';
    }
}

function initLocationSelector(config) {
    return new LocationSelector(config);
}
