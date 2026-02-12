<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Hieronta - Varaa Aika</title>
    
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body>

<form action="confirm_booking.php" method="POST" id="bookingForm">
    
    <input type="hidden" name="selected_date" id="selectedDateInput">
    <input type="hidden" name="selected_time" id="selectedTimeInput">
    <input type="hidden" name="treatment_id" id="treatmentIdInput" value="1">

    <div class="booking-wrapper">
        
        <div class="booking-sidebar">
            <button type="button" class="back-btn" onclick="history.back()">← Palaa takaisin</button>
            
            <div class="provider-info">
                <div class="profile-logo">
                    <img src="logo.jpg" alt="Artisan Massage Logo">
                </div>
                <h3>Artisan Massage</h3>
                <p class="specialist-title">Koulutettu hieroja</p>
            </div>

            <div class="service-details">
                <div class="service-row">
                    <strong id="summaryService">Hieronta 60min</strong>
                    <span id="summaryDuration">Kesto: 60 min</span>
                </div>
                <div class="total-price">
                    <small>YHTEENSÄ</small>
                    <h2 id="summaryPrice">55,00 €</h2>
                </div>
            </div>
        </div>

        <div class="booking-main">
            <header class="main-header">
                <h1>Varauksen tiedot</h1>
                <p>Valitse ensin palvelu ja sen jälkeen kalenterista sopiva ajankohta.</p>
            </header>

            <div class="calendar-card" style="margin-bottom: 30px; padding: 20px;">
                <h3 style="margin-top: 0;">1. Valitse palvelu</h3>
                <div class="form-group">
                    <select id="serviceSelect" class="confirm-btn" style="background: var(--card-bg); color: white; border: 1px solid var(--border); text-transform: none; font-weight: 400; letter-spacing: 0;">
                        <option value="1" data-price="55.00" data-duration="60" data-name="Hieronta 60min">Klassinen Hieronta 60min - 55,00 €</option>
                        <option value="2" data-price="75.00" data-duration="90" data-name="Hieronta 90min">Rentouttava Hieronta 90min - 75,00 €</option>
                    </select>
                </div>
            </div>

            <div class="selection-grid">
                
                <div class="calendar-card">
                    <h3 style="margin-top: 0;">2. Valitse päivä</h3>
                    <div class="calendar-header">
                        <span id="monthDisplay"></span>
                        <div class="nav-arrows">
                            <button type="button" id="prevMonth"><</button>
                            <button type="button" id="nextMonth">></button>
                        </div>
                    </div>
                    
                    <table class="calendar-table" id="calendar">
                        <thead>
                            <tr>
                                <th>MA</th><th>TI</th><th>KE</th><th>TO</th><th>PE</th><th>LA</th><th>SU</th>
                            </tr>
                        </thead>
                        <tbody id="calendarBody"></tbody>
                    </table>
                </div>

                <div class="time-section">
                    <h3 id="displayDate">Valitse päivä kalenterista</h3>
                    
                    <div class="time-slots" id="timeSlotContainer">
                        <p class="info-text">Valitse ensin päivä nähdäksesi vapaat ajat.</p>
                    </div>
                    
                    <button type="submit" class="confirm-btn" id="submitBtn" disabled>Jatka varaukseen</button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    /**
     * JAVASCRIPT-LOGIIKKA
     */

    let currentViewDate = new Date(2026, 1, 1); 
    
    const dateInput = document.getElementById('selectedDateInput');
    const timeInput = document.getElementById('selectedTimeInput');
    const treatmentInput = document.getElementById('treatmentIdInput');
    const displayDate = document.getElementById('displayDate');
    const submitBtn = document.getElementById('submitBtn');
    const serviceSelect = document.getElementById('serviceSelect');

    /**
     * PALVELUN VAIHTO: Päivittää sivupalkin ja hinnan
     */
    serviceSelect.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        document.getElementById('summaryService').innerText = selected.dataset.name;
        document.getElementById('summaryDuration').innerText = "Kesto: " + selected.dataset.duration + " min";
        document.getElementById('summaryPrice').innerText = selected.dataset.price.replace('.', ',') + " €";
        treatmentInput.value = this.value;
        
        // Jos päivä oli jo valittu, haetaan ajat uudelleen (jos logiikka muuttuisi keston mukaan)
        if (dateInput.value) {
            loadAvailableTimes(dateInput.value);
        }
    });

    /**
     * RENDERÖI KALENTERI
     */
    function renderCalendar() {
        const year = currentViewDate.getFullYear();
        const month = currentViewDate.getMonth();
        const monthNames = ["Tammikuu", "Helmikuu", "Maaliskuu", "Huhtikuu", "Toukokuu", "Kesäkuu", 
                            "Heinäkuu", "Elokuu", "Syyskuu", "Lokakuu", "Marraskuu", "Joulukuu"];
        
        document.getElementById('monthDisplay').innerText = `${monthNames[month]} ${year}`;
        
        const firstDay = new Date(year, month, 1).getDay(); 
        const daysInMonth = new Date(year, month + 1, 0).getDate(); 
        const calendarBody = document.getElementById('calendarBody');
        calendarBody.innerHTML = ''; 

        let date = 1;
        let startingDay = firstDay === 0 ? 6 : firstDay - 1; 

        for (let i = 0; i < 6; i++) {
            let row = document.createElement('tr');
            for (let j = 0; j < 7; j++) {
                let cell = document.createElement('td');
                if (i === 0 && j < startingDay) {
                    cell.innerText = "";
                    cell.classList.add('disabled');
                } else if (date > daysInMonth) {
                    break;
                } else {
                    cell.innerText = date;
                    const fullDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(date).padStart(2, '0')}`;
                    cell.setAttribute('data-date', fullDate);
                    
                    if (dateInput.value === fullDate) cell.classList.add('selected');

                    cell.addEventListener('click', () => selectDate(cell, fullDate));
                    date++;
                }
                row.appendChild(cell);
            }
            calendarBody.appendChild(row);
            if (date > daysInMonth) break;
        }
    }

    /**
     * VALITSE PÄIVÄ
     */
    function selectDate(element, dateStr) {
        document.querySelectorAll('.calendar-table td.selected').forEach(el => el.classList.remove('selected'));
        element.classList.add('selected');
        
        dateInput.value = dateStr;

        const dateObj = new Date(dateStr);
        const options = { weekday: 'long', day: 'numeric', month: 'numeric', year: 'numeric' };
        let formatted = dateObj.toLocaleDateString('fi-FI', options);
        displayDate.innerText = formatted.charAt(0).toUpperCase() + formatted.slice(1);

        timeInput.value = "";
        submitBtn.disabled = true;

        loadAvailableTimes(dateStr);
    }

    /**
     * AJAX: NOUDA VAPAAT AJAT (Tomi: get_available_times.php hakee nämä DB:stä)
     */
    function loadAvailableTimes(dateStr) {
        const container = document.getElementById('timeSlotContainer');
        container.innerHTML = '<p class="info-text">Haetaan vapaita aikoja...</p>';

        fetch('get_available_times.php?date=' + dateStr)
            .then(response => response.json())
            .then(times => {
                container.innerHTML = ''; 

                if (times.length === 0) {
                    container.innerHTML = '<p class="info-text">Ei vapaita aikoja valittuna päivänä.</p>';
                    return;
                }

                times.forEach(time => {
                    const btn = document.createElement('button');
                    btn.type = "button";
                    btn.className = "time-slot";
                    // Näytetään HH:MM
                    btn.innerText = time.substring(0, 5);
                    
                    btn.onclick = () => {
                        document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('active'));
                        btn.classList.add('active');
                        timeInput.value = time;
                        submitBtn.disabled = false;
                    };
                    container.appendChild(btn);
                });
            })
            .catch(error => {
                console.error('Virhe:', error);
                container.innerHTML = '<p class="info-text">Yhteysvirhe tietokantaan.</p>';
            });
    }

    // NUOLINAVIGOINTI
    document.getElementById('prevMonth').onclick = () => { 
        currentViewDate.setMonth(currentViewDate.getMonth() - 1); 
        renderCalendar(); 
    };
    document.getElementById('nextMonth').onclick = () => { 
        currentViewDate.setMonth(currentViewDate.getMonth() + 1); 
        renderCalendar(); 
    };

    renderCalendar();
</script>

</body>
</html>