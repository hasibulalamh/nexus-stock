class LocationSelector {
    // Initialize
    constructor(config) {
        this.countryId = config.countryId;
        this.stateId = config.stateId;
        this.cityId = config.cityId;
        this.upazilaId = config.upazilaId;
        this.baseUrl = config.baseUrl || '/admin/warehouse';
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
        const upazila = document.getElementById(this.upazilaId);

        if (!country || !state || !city || !upazila) return;

        country.addEventListener('change', () => this.loadStates(country.value, state, city, upazila));
        state.addEventListener('change', () => this.loadCities(state.value, city, upazila));
        city.addEventListener('change', () => this.loadUpazilas(city.value, upazila));
        upazila.addEventListener('change', () => this.loadZipcode(upazila.value));
    }

    // Load states
    async loadStates(countryId, state, city, upazila) {
        this.reset([state, city, upazila]);
        if (!countryId) return;
        const res = await fetch(`${this.baseUrl}/get-states/${countryId}`);
        const data = await res.json();
        data.forEach(d => state.innerHTML += `<option value="${d.id}">${d.name}</option>`);
    }

    // Load cities
    async loadCities(stateId, city, upazila) {
        this.reset([city, upazila]);
        if (!stateId) return;
        const res = await fetch(`${this.baseUrl}/get-cities/${stateId}`);
        const data = await res.json();
        data.forEach(d => city.innerHTML += `<option value="${d.id}">${d.name}</option>`);
    }

    // Load upazilas
    async loadUpazilas(cityId, upazila) {
        this.reset([upazila]);
        if (!cityId) return;
        const res = await fetch(`${this.baseUrl}/get-upazilas/${cityId}`);
        const data = await res.json();
        data.forEach(d => upazila.innerHTML += `<option value="${d.id}">${d.name}</option>`);
    }

    // Load upazila-based zipcode placeholder
    async loadZipcode(upazilaId) {
        const zipcodeEl = document.getElementById('zipcode');
        if (!zipcodeEl) return;
        zipcodeEl.value = '';
        if (!upazilaId) return;
        const res = await fetch(`${this.baseUrl}/get-zipcode/${upazilaId}`);
        const data = await res.json();
        zipcodeEl.value = data.zipcode || '';
    }

    // Reset
    reset(selects) {
        selects.forEach(sel => sel.innerHTML = '<option value="">--Select--</option>');
    }
}

// upazila select id updated from 'thana' to 'upazila'
function initLocationSelector(config) {
    // Rename thanaId to upazilaId if present in legacy config
    if (config.thanaId && !config.upazilaId) {
        config.upazilaId = config.thanaId;
    }
    return new LocationSelector(config);
}
