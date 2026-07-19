document.addEventListener("DOMContentLoaded", () => {
    const seatsData = window.seatsData;

    const scheduleSelect = document.getElementById('schedule_id');
    const seatMapEl = document.getElementById('seatMap');
    const selectedSeatsInput = document.getElementById('selected_seats');
    const seatCountEl = document.getElementById('seatCount');
    const seatTotalEl = document.getElementById('seatTotal');
    const basePriceTag = document.getElementById('basePriceTag');
    const confirmBtn = document.getElementById('confirmBtn');

    let selectedSeats = {};

    function renderSeatMap(scheduleId) {
        selectedSeats = {};
        updateSummary();

        const data = seatsData[scheduleId];
        seatMapEl.innerHTML = '';

        if (!data || !data.seats || data.seats.length === 0) {
            seatMapEl.innerHTML = '<p style="text-align:center;color:#888;">No seat map available.</p>';
            return;
        }

        if (basePriceTag) {
            basePriceTag.textContent = '₱' + Number(data.ticket_price).toFixed(2);
        }

        const rows = {};
        data.seats.forEach(seat => {
            if (!rows[seat.row]) rows[seat.row] = [];
            rows[seat.row].push(seat);
        });

        Object.keys(rows).sort().forEach(rowLetter => {
            const rowSeats = rows[rowLetter].sort((a, b) => a.num - b.num);
            const rowDiv = document.createElement('div');
            rowDiv.className = 'seat-row';

            // Left Label
            const labelLeft = document.createElement('span');
            labelLeft.className = 'row-label';
            labelLeft.textContent = rowLetter;
            rowDiv.appendChild(labelLeft);

            rowSeats.forEach((seat, idx) => {
                const seatDiv = document.createElement('div');
                seatDiv.className = 'seat';
                if (seat.occupied) seatDiv.classList.add('occupied');
                
                seatDiv.dataset.seatId = seat.id;
                seatDiv.dataset.price = seat.price;
                seatDiv.title = `Seat ${rowLetter}${seat.num} - ₱${Number(seat.price).toFixed(2)}`;
                seatDiv.textContent = seat.num;

                // 8 - 16 - 8 Layout Aisles
                if (idx === 7 || idx === 23) {
                    seatDiv.style.marginRight = '25px';
                }

                rowDiv.appendChild(seatDiv);
            });

            const labelRight = document.createElement('span');
            labelRight.className = 'row-label';
            labelRight.textContent = rowLetter;
            rowDiv.appendChild(labelRight);

            seatMapEl.appendChild(rowDiv);
        });
    }

    function updateSummary() {
        const ids = Object.keys(selectedSeats);
        const total = ids.reduce((sum, id) => sum + selectedSeats[id], 0);
        seatCountEl.textContent = ids.length;
        seatTotalEl.textContent = total.toFixed(2);
        selectedSeatsInput.value = ids.join(',');
        if (confirmBtn) confirmBtn.disabled = ids.length === 0;
    }

    seatMapEl.addEventListener('click', (e) => {
        const seatEl = e.target.closest('.seat');
        if (!seatEl || seatEl.classList.contains('occupied') || !seatEl.dataset.seatId) return;

        const seatId = seatEl.dataset.seatId;
        const price = parseFloat(seatEl.dataset.price);

        if (selectedSeats[seatId]) {
            delete selectedSeats[seatId];
            seatEl.classList.remove('selected');
        } else {
            selectedSeats[seatId] = price;
            seatEl.classList.add('selected');
        }
        updateSummary();
    });

    if (scheduleSelect) {
        scheduleSelect.addEventListener('change', () => {
            renderSeatMap(scheduleSelect.value);
        });

        if (scheduleSelect.value) {
            renderSeatMap(scheduleSelect.value);
        }
    }
});