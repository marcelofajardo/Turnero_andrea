/**
 * admin.js — Admin panel JavaScript
 */

(function () {
    'use strict';

    // ---- Sidebar toggle (mobile) ----
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.admin-sidebar');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => sidebar.classList.toggle('open'));
    }

    // ---- Edit Service Modal ----
    document.querySelectorAll('.btn-edit-service').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('edit_id').value = btn.dataset.id;
            document.getElementById('edit_name').value = btn.dataset.name;
            document.getElementById('edit_price').value = btn.dataset.price;
            document.getElementById('edit_dur').value = btn.dataset.dur;
            document.getElementById('edit_color').value = btn.dataset.color;
            document.getElementById('edit_desc').value = btn.dataset.desc;
            document.getElementById('edit_sort').value = btn.dataset.sort;
            document.getElementById('edit_active').checked = btn.dataset.active === '1';
            document.getElementById('edit_mp_token').value = btn.dataset.mpToken || '';
            document.getElementById('edit_mp_key').value = btn.dataset.mpKey || '';

            const modal = new bootstrap.Modal(document.getElementById('editServiceModal'));
            modal.show();
        });
    });

    // ---- Business Hours: Add Range ----
    document.querySelectorAll('.btn-add-range').forEach(btn => {
        btn.addEventListener('click', () => {
            const day = btn.dataset.day;
            const container = document.getElementById(`ranges-${day}`);
            const row = document.createElement('div');
            row.className = 'd-flex gap-2 align-items-center mb-1 hour-range-row';
            row.innerHTML = `
                <input type="hidden" name="day[]" value="${day}">
                <input type="time" name="start[]" class="form-control form-control-sm" style="width:130px" value="09:00">
                <span>–</span>
                <input type="time" name="end[]" class="form-control form-control-sm" style="width:130px" value="13:00">
                <button type="button" class="btn btn-sm btn-outline-danger btn-rm-range">
                    <i class="bi bi-dash-circle"></i>
                </button>
            `;
            const rmBtn = row.querySelector('.btn-rm-range');
            rmBtn.addEventListener('click', () => row.remove());
            container.appendChild(row);
        });
    });

    // ---- Business Hours: Remove Range (event delegation) ----
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-rm-range');
        if (btn) {
            btn.closest('.hour-range-row').remove();
        }
    });

    // ---- Service selector for hours form ----
    const serviceSelector = document.getElementById('serviceSelector');
    if (serviceSelector) {
        serviceSelector.addEventListener('change', () => {
            const serviceId = serviceSelector.value;
            const url = new URL(window.location.href);
            if (serviceId) {
                url.searchParams.set('service_id', serviceId);
            } else {
                url.searchParams.delete('service_id');
            }
            window.location.href = url.toString();
        });
    }

    // ---- Auto-dismiss success alerts ----
    document.querySelectorAll('.alert-success.alert-dismissible').forEach(el => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            bsAlert?.close();
        }, 4000);
    });

})();
