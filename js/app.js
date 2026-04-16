// js/app.js
const app = {
    async init() {
        const res = await api.get('auth.php?action=check');
        if (!res || res.status !== 'success') {
            window.location.href = 'index.html';
            return;
        }
        document.getElementById('nav-name').innerText = res.name;
        document.getElementById('nav-avatar').innerText = res.name.charAt(0).toUpperCase();

        this.setupNavigation();
        this.runSetup(); // Run DB setup on first load (for simplicity)
    },

    async logout() {
        await api.post('auth.php?action=logout', {});
        window.location.href = 'index.html';
    },

    async openProfileModal() {
        const res = await api.get('auth.php?action=profile');
        if (res && res.status === 'success') {
            document.getElementById('prof-name').value = res.data.name;
            document.getElementById('prof-dob').value = res.data.dob || '';
            this.openModal('profile-modal');
        }
    },

    async submitProfile(e) {
        e.preventDefault();
        const name = document.getElementById('prof-name').value;
        const dob = document.getElementById('prof-dob').value;
        const password = document.getElementById('prof-password').value;
        const res = await api.put('auth.php?action=profile', { name, dob, password });
        if (res && res.status === 'success') {
            document.getElementById('nav-name').innerText = name;
            document.getElementById('nav-avatar').innerText = name.charAt(0).toUpperCase();
            this.closeModal('profile-modal');
            alert("Profile updated!");
        }
    },

    setupNavigation() {
        const navItems = document.querySelectorAll('.nav-item');
        navItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                // Update active state
                navItems.forEach(n => n.classList.remove('active'));
                item.classList.add('active');

                // Switch views
                const viewId = item.getAttribute('data-view');
                document.querySelectorAll('.view').forEach(v => v.classList.add('hidden'));
                document.getElementById(viewId).classList.remove('hidden');

                // Update page title
                document.getElementById('page-title').textContent =
                    viewId.charAt(0).toUpperCase() + viewId.slice(1);

                // Load view data
                this.loadView(viewId);
            });
        });
    },

    async runSetup() {
        await api.get('setup.php');
        this.loadView('dashboard');
    },

    loadView(viewId) {
        switch (viewId) {
            case 'dashboard': this.loadDashboard(); break;
            case 'vehicles': this.loadVehicles(); break;
            case 'drivers': this.loadDrivers(); break;
            case 'trips': this.loadTrips(); break;
        }
    },

    openModal(modalId) {
        if (modalId === 'trip-modal') {
            this.populateTripSelects();
        }
        document.getElementById(modalId).classList.remove('hidden');
    },

    closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    },

    // ----------------------------------------------------
    // DASHBOARD View Methods
    // ----------------------------------------------------
    async loadDashboard() {
        const res = await api.get('dashboard.php');
        if (res && res.status === 'success') {
            const data = res.data;
            document.getElementById('stat-total-vehicles').textContent = data.total_vehicles;
            document.getElementById('stat-active-vehicles').textContent = data.active_vehicles;
            document.getElementById('stat-total-drivers').textContent = data.total_drivers;

            if (document.getElementById('stat-scheduled-trips')) {
                document.getElementById('stat-scheduled-trips').textContent = data.scheduled_trips || 0;
                document.getElementById('stat-intransit-trips').textContent = data.in_transit_trips || 0;
                document.getElementById('stat-completed-trips').textContent = data.completed_trips || 0;
                document.getElementById('stat-cancelled-trips').textContent = data.cancelled_trips || 0;
            }

            const tbody = document.querySelector('#recent-trips-table tbody');
            tbody.innerHTML = '';

            data.recent_trips.forEach(trip => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>#${trip.id}</td>
                    <td>${trip.destination}</td>
                    <td>${trip.license_plate || 'N/A'}</td>
                    <td>${trip.driver_name || 'N/A'}</td>
                    <td><span class="badge ${trip.status.toLowerCase()}">${trip.status}</span></td>
                `;
                tbody.appendChild(tr);
            });
        }
    },

    // ----------------------------------------------------
    // VEHICLES View Methods
    // ----------------------------------------------------
    async loadVehicles() {
        const res = await api.get('vehicles.php');
        if (res && res.status === 'success') {
            const tbody = document.querySelector('#vehicles-table tbody');
            tbody.innerHTML = '';
            res.data.forEach(v => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>#${v.id}</td>
                    <td>${v.license_plate}</td>
                    <td>${v.make}</td>
                    <td>${v.model}</td>
                    <td>${v.year}</td>
                    <td><span class="badge ${v.status.toLowerCase()}">${v.status}</span></td>
                    <td>
                        <button class="btn btn-icon" style="color:var(--accent-blue)" onclick="app.editVehicle(${v.id}, '${v.make}', '${v.model}', ${v.year}, '${v.license_plate}')">✏️</button>
                        <button class="btn btn-danger btn-icon" onclick="app.deleteEntity('vehicles.php', ${v.id}, 'vehicles')">🗑️</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
    },

    editVehicle(id, make, model, year, license_plate) {
        this.editingVehicleId = id;
        document.getElementById('veh-make').value = make;
        document.getElementById('veh-model').value = model;
        document.getElementById('veh-year').value = year;
        document.getElementById('veh-plate').value = license_plate;
        this.openModal('vehicle-modal');
    },

    async submitVehicle(e) {
        e.preventDefault();
        const make = document.getElementById('veh-make').value;
        const model = document.getElementById('veh-model').value;
        const year = document.getElementById('veh-year').value;
        const license_plate = document.getElementById('veh-plate').value;

        let res;
        if (this.editingVehicleId) {
            res = await api.put('vehicles.php', { id: this.editingVehicleId, make, model, year, license_plate });
            this.editingVehicleId = null;
        } else {
            res = await api.post('vehicles.php', { make, model, year, license_plate });
        }
        if (res && res.status === 'success') {
            e.target.reset();
            this.closeModal('vehicle-modal');
            this.loadVehicles();
        } else {
            alert(res.message || 'Error occurred');
        }
    },

    // ----------------------------------------------------
    // DRIVERS View Methods
    // ----------------------------------------------------
    async loadDrivers() {
        const res = await api.get('drivers.php');
        if (res && res.status === 'success') {
            const tbody = document.querySelector('#drivers-table tbody');
            tbody.innerHTML = '';
            res.data.forEach(d => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>#${d.id}</td>
                    <td>${d.name}</td>
                    <td>${d.license_number}</td>
                    <td>${d.phone || 'N/A'}</td>
                    <td><span class="badge ${d.status.toLowerCase()}">${d.status}</span></td>
                    <td>
                        <button class="btn btn-icon" style="color:var(--accent-blue)" onclick="app.editDriver(${d.id}, '${d.name}', '${d.license_number}', '${d.phone || ''}')">✏️</button>
                        <button class="btn btn-danger btn-icon" onclick="app.deleteEntity('drivers.php', ${d.id}, 'drivers')">🗑️</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
    },

    editDriver(id, name, license_number, phone) {
        this.editingDriverId = id;
        document.getElementById('drv-name').value = name;
        document.getElementById('drv-license').value = license_number;
        document.getElementById('drv-phone').value = phone;
        this.openModal('driver-modal');
    },

    async submitDriver(e) {
        e.preventDefault();
        const name = document.getElementById('drv-name').value;
        const license_number = document.getElementById('drv-license').value;
        const phone = document.getElementById('drv-phone').value;

        let res;
        if (this.editingDriverId) {
            res = await api.put('drivers.php', { id: this.editingDriverId, name, license_number, phone });
            this.editingDriverId = null;
        } else {
            res = await api.post('drivers.php', { name, license_number, phone });
        }
        if (res && res.status === 'success') {
            e.target.reset();
            this.closeModal('driver-modal');
            this.loadDrivers();
        } else {
            alert(res.message || 'Error occurred');
        }
    },

    // ----------------------------------------------------
    // TRIPS View Methods
    // ----------------------------------------------------
    async loadTrips() {
        const res = await api.get('trips.php');
        if (res && res.status === 'success') {
            const tbody = document.querySelector('#trips-table tbody');
            tbody.innerHTML = '';
            res.data.forEach(t => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>#${t.id}</td>
                    <td>${t.destination}</td>
                    <td>${t.start_date}</td>
                    <td>${t.license_plate || 'N/A'}</td>
                    <td>${t.driver_name || 'N/A'}</td>
                    <td>
                        <select onchange="app.updateTripStatus(${t.id}, this.value)" class="status-dropdown ${t.status.toLowerCase().replace(' ', '-')}">
                            <option value="Scheduled" ${t.status === 'Scheduled' ? 'selected' : ''}>Scheduled</option>
                            <option value="In Transit" ${t.status === 'In Transit' ? 'selected' : ''}>In Transit</option>
                            <option value="Completed" ${t.status === 'Completed' ? 'selected' : ''}>Completed</option>
                            <option value="Cancelled" ${t.status === 'Cancelled' ? 'selected' : ''}>Cancelled</option>
                        </select>
                    </td>
                    <td>
                        <button class="btn btn-danger btn-icon" onclick="app.deleteEntity('trips.php', ${t.id}, 'trips')">🗑️</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
    },

    async populateTripSelects() {
        const vehRes = await api.get('vehicles.php');
        const drvRes = await api.get('drivers.php');

        const vSelect = document.getElementById('trip-vehicle');
        const dSelect = document.getElementById('trip-driver');

        if (vehRes && vehRes.status === 'success') {
            vSelect.innerHTML = vehRes.data.map(v => `<option value="${v.id}">${v.license_plate} - ${v.make}</option>`).join('');
        }
        if (drvRes && drvRes.status === 'success') {
            dSelect.innerHTML = drvRes.data.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
        }
    },

    async submitTrip(e) {
        e.preventDefault();
        const destination = document.getElementById('trip-dest').value;
        const start_date = document.getElementById('trip-start').value;
        const vehicle_id = document.getElementById('trip-vehicle').value;
        const driver_id = document.getElementById('trip-driver').value;

        const res = await api.post('trips.php', { destination, start_date, vehicle_id, driver_id });
        if (res && res.status === 'success') {
            e.target.reset();
            this.closeModal('trip-modal');
            this.loadTrips();
        } else {
            alert(res.message || 'Error occurred');
        }
    },

    async updateTripStatus(id, newStatus) {
        const res = await api.put('trips.php', { id, status: newStatus });
        if (res && res.status === 'success') {
            this.loadTrips();
            this.loadDashboard(); // Refresh background data
        } else {
            alert(res.message || 'Error updating status');
        }
    },

    // ----------------------------------------------------
    // SHARED Utilities
    // ----------------------------------------------------
    async deleteEntity(endpoint, id, viewId) {
        if (confirm('Are you sure you want to delete this record?')) {
            const res = await api.delete(endpoint, id);
            if (res && res.status === 'success') {
                this.loadView(viewId);
            } else {
                alert(res.message || 'Error deleting record');
            }
        }
    }
};

// Initialize app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    app.init();
});
