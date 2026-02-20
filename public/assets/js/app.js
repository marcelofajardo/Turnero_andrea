/**
 * app.js — Public booking flow JavaScript
 * 4-step interactive booking: Service → Date → Slot → Customer Form
 * Uses Fetch API, no jQuery.
 */

(function () {
    'use strict';

    // --- State ---
    let state = {
        serviceId: null,
        serviceName: '',
        servicePrice: '',
        date: null,
        time: null,
    };

    // --- DOM helpers ---
    const $ = id => document.getElementById(id);
    const show = (id) => { const el = $(id); if (el) el.classList.remove('d-none'); };
    const hide = (id) => { const el = $(id); if (el) el.classList.add('d-none'); };

    // -----------------------------------------------------------------------
    // Step 1: Service Selection
    // -----------------------------------------------------------------------
    document.querySelectorAll('.service-card').forEach(card => {
        card.addEventListener('click', () => {
            document.querySelectorAll('.service-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');

            state.serviceId = card.dataset.serviceId;
            state.serviceName = card.querySelector('strong').textContent.trim();
            state.servicePrice = card.dataset.price;
            state.date = null;
            state.time = null;

            // Reset downstream
            const dp = $('datePicker');
            if (dp) dp.value = '';
            $('slotsContainer').innerHTML = '';
            hide('step-slot');
            hide('step-form');

            show('step-date');
            initCalendar();
            if (fp) fp.clear(); // Reset date on service change
            $('step-date').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    });

    // -----------------------------------------------------------------------
    // Step 2: Date Selection (Flatpickr)
    // -----------------------------------------------------------------------
    let fp = null;
    function initCalendar() {
        const calendarEl = $('calendarInline');
        if (!calendarEl || fp) return;

        fp = flatpickr(calendarEl, {
            inline: true,
            locale: 'es',
            minDate: 'today',
            maxDate: new Date().fp_incr(60),
            dateFormat: 'Y-m-d',
            onChange: (selectedDates, dateStr) => {
                state.date = dateStr;
                state.time = null;

                if (!state.serviceId || !state.date) return;

                show('step-slot');
                hide('noSlotsMsg');
                $('slotsContainer').innerHTML = '<div class="text-muted py-3 w-100" id="slotsLoading"><div class="spinner-border spinner-border-sm me-2"></div> Cargando horarios...</div>';
                hide('step-form');

                loadSlots(state.serviceId, state.date);
            }
        });
    }

    // --- Subfolder handling ---
    const BASE_PATH = document.body.dataset.basePath || '';

    // -----------------------------------------------------------------------
    // Load Available Slots (Fetch API)
    // -----------------------------------------------------------------------
    async function loadSlots(serviceId, date) {
        try {
            const res = await fetch(`${BASE_PATH}/api/available-slots?service_id=${serviceId}&date=${date}`);
            const data = await res.json();
            // ...
        } catch (err) {
            // ...
        }
    }

    // -----------------------------------------------------------------------
    // Step 4: Booking Form Submit
    // -----------------------------------------------------------------------
    // ... inside form listener ...
    try {
        const res = await fetch(`${BASE_PATH}/api/appointments`, { method: 'POST', body });
        const data = await res.json();
        const data = await res.json();

        if (data.success && data.data?.init_point) {
            // Redirect to MercadoPago
            window.location.href = data.data.init_point;
        } else if (data.data?.errors) {
            showErrors(data.data.errors);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-lock-fill me-2"></i>Confirmar y Pagar';
        } else {
            showFormError(data.message || 'Error al reservar. Intente nuevamente.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-lock-fill me-2"></i>Confirmar y Pagar';
        }
    } catch (err) {
        showFormError('Error de conexión. Verifique su conexión e intente nuevamente.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-lock-fill me-2"></i>Confirmar y Pagar';
    }
});
    }

function clearErrors() {
    document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
    document.querySelectorAll('.form-control.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    hide('formError');
}

function showErrors(errors) {
    Object.entries(errors).forEach(([field, msg]) => {
        const input = document.querySelector(`[name="${field}"]`);
        const errEl = document.querySelector(`#err_${field}`);
        if (input) input.classList.add('is-invalid');
        if (errEl) errEl.textContent = msg;
    });
}

function showFormError(msg) {
    const el = $('formError');
    if (el) { el.textContent = msg; show('formError'); }
}

}) ();
