class LocationSelector {
    // Initialize
    constructor(config) {
        this.countryId = config.countryId;
        this.stateId = config.stateId;
        this.cityId = config.cityId;
        this.upazilaId = config.upazilaId;
        this.areaId = config.areaId;
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
        const area = this.areaId ? document.getElementById(this.areaId) : null;

        if (!country || !state || !city || !upazila) return;

        country.addEventListener('change', () => this.loadStates(country.value, state, city, upazila, area));
        state.addEventListener('change', () => this.loadCities(state.value, city, upazila, area));
        city.addEventListener('change', () => this.loadUpazilas(city.value, upazila, area));
        upazila.addEventListener('change', () => this.loadZipcode(upazila.value));
        upazila.addEventListener('change', () => this.loadAreas(city.value, upazila.value, area));
    }

    // Load states
    async loadStates(countryId, state, city, upazila, area) {
        this.reset([state, city, upazila, area]);
        if (!countryId) return;
        const res = await fetch(`${this.baseUrl}/get-states/${countryId}`);
        const data = await res.json();
        data.forEach(d => state.innerHTML += `<option value="${d.id}">${d.name}</option>`);
    }

    // Load cities
    async loadCities(stateId, city, upazila, area) {
        this.reset([city, upazila, area]);
        if (!stateId) return;
        const res = await fetch(`${this.baseUrl}/get-cities/${stateId}`);
        const data = await res.json();
        data.forEach(d => city.innerHTML += `<option value="${d.id}">${d.name}</option>`);
    }

    // Load upazilas
    async loadUpazilas(cityId, upazila, area) {
        this.reset([upazila, area]);
        if (!cityId) return;
        const res = await fetch(`${this.baseUrl}/get-upazilas/${cityId}`);
        const data = await res.json();
        data.forEach(d => upazila.innerHTML += `<option value="${d.id}">${d.name}</option>`);
        this.loadAreas(cityId, '', area);
    }

    async loadAreas(cityId, upazilaId, area) {
        if (!area || !cityId) return;
        this.reset([area]);
        const res = await fetch(`${this.baseUrl}/get-areas/${cityId}?upazila_id=${upazilaId || ''}`);
        const data = await res.json();
        data.forEach(d => area.innerHTML += `<option value="${d.id}">${d.display_name}</option>`);
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
