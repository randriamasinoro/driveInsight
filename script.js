document.addEventListener('DOMContentLoaded', () => {
    const overlay    = document.getElementById('modal-overlay');
    const modalClose = document.getElementById('modal-close');
    const modalForm  = document.getElementById('modal-form');
    const modalOk    = document.getElementById('modal-ok');
    const modalSuccess = document.getElementById('modal-success');
    const modalError   = document.getElementById('modal-error');

    if (!overlay) return; // page sans modale (ex: admin)

    // ── Init Flatpickr sur chaque carte voiture ───────────────────────────────
    document.querySelectorAll('.car-card').forEach(card => {
        const id    = card.dataset.id;
        const price = parseInt(card.dataset.price, 10);
        const input = card.querySelector('.date-input');
        const summary     = card.querySelector('.car-summary');
        const summDates   = card.querySelector('.summary-dates');
        const summTotal   = card.querySelector('.summary-total');
        const btnReserver = card.querySelector('.btn-reserver');

        fetch(`/api/disponibilite.php?id_voiture=${id}`)
            .then(r => r.json())
            .then(ranges => initPicker(ranges))
            .catch(() => initPicker([]));

        function initPicker(ranges) {
            flatpickr(input, {
                mode: 'range',
                minDate: 'today',
                dateFormat: 'Y-m-d',
                locale: 'fr',
                disable: ranges,
                disableMobile: true,
                onChange(selected) {
                    if (selected.length === 2) {
                        const [s, e] = selected;
                        const days  = Math.round((e - s) / 86400000);
                        const total = days * price;
                        const fmt = d => d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' });
                        summDates.textContent = `${fmt(s)} → ${fmt(e)} · ${days} jour${days > 1 ? 's' : ''}`;
                        summTotal.textContent = `${total.toLocaleString('fr-FR')} Ar`;
                        summary.classList.add('visible');
                    } else {
                        summary.classList.remove('visible');
                    }
                }
            });
        }

        btnReserver.addEventListener('click', () => {
            const fp = input._flatpickr;
            if (!fp || fp.selectedDates.length < 2) return;
            const [s, e] = fp.selectedDates;
            const days  = Math.round((e - s) / 86400000);
            const total = days * price;
            const toISO   = d => d.toISOString().split('T')[0];
            const fmtLong = d => d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' });

            document.getElementById('modal-car-name').textContent = card.dataset.name;
            document.getElementById('modal-dates').textContent =
                `Du ${fmtLong(s)} au ${fmtLong(e)} — ${days} jour${days > 1 ? 's' : ''}`;
            document.getElementById('modal-total').textContent =
                `Total : ${total.toLocaleString('fr-FR')} Ar`;

            const idInput    = document.getElementById('modal-id-voiture');
            const debutInput = document.getElementById('modal-date-debut');
            const finInput   = document.getElementById('modal-date-fin');
            idInput.value    = id;
            debutInput.value = toISO(s);
            finInput.value   = toISO(e);

            resetModal();
            // restore after resetModal clears form
            idInput.value    = id;
            debutInput.value = toISO(s);
            finInput.value   = toISO(e);

            overlay.classList.add('open');
        });
    });

    // ── Fermeture modale ─────────────────────────────────────────────────────
    let reservationConfirmee = false;

    modalClose.addEventListener('click', closeModal);
    overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
    modalOk.addEventListener('click', closeModal);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

    function closeModal() {
        overlay.classList.remove('open');
        if (reservationConfirmee) window.location.reload();
    }

    function resetModal() {
        reservationConfirmee = false;
        modalForm.reset();
        modalForm.style.display = '';
        modalSuccess.style.display = 'none';
        modalError.textContent = '';
        modalError.style.display = 'none';
        const btn = document.getElementById('modal-submit');
        btn.disabled = false;
        btn.textContent = 'Confirmer la réservation';
    }

    // ── Soumission AJAX ──────────────────────────────────────────────────────
    modalForm.addEventListener('submit', async e => {
        e.preventDefault();
        const btn = document.getElementById('modal-submit');
        btn.disabled = true;
        btn.textContent = 'En cours…';
        modalError.style.display = 'none';

        try {
            const res  = await fetch('/api/reserver.php', { method: 'POST', body: new FormData(modalForm) });
            const json = await res.json();
            if (json.success) {
                reservationConfirmee = true;
                document.getElementById('modal-success-msg').textContent = json.message;
                modalForm.style.display = 'none';
                modalSuccess.style.display = 'flex';
            } else {
                modalError.textContent = json.message;
                modalError.style.display = 'block';
                btn.disabled = false;
                btn.textContent = 'Confirmer la réservation';
            }
        } catch {
            modalError.textContent = 'Erreur réseau, veuillez réessayer.';
            modalError.style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'Confirmer la réservation';
        }
    });
});
