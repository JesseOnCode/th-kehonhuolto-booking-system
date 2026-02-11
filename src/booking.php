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
                    <strong>Klassinen Hieronta</strong>
                    <span>45 min | 45,00 €</span>
                </div>
                <div class="total-price">
                    <small>YHTEENSÄ</small>
                    <h2>45,00 €</h2>
                </div>
            </div>
        </div>

        <div class="booking-main">
            <header class="main-header">
                <h1>Valitse ajankohta</h1>
                <p>Valitse kalenterista päivä nähdäksesi vapaat aikani. Järjestelmä huomioi automaattisesti tauot varausten välillä.</p>
            </header>

            <div class="selection-grid">
                
                <div class="calendar-card">
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
                        <tbody id="calendarBody">
                            </tbody>
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
     * Dynaaminen kalenteri ja AJAX-haku tietokantaan.
     */

    let currentViewDate = new Date(2026, 1, 1); // Aloitus helmikuusta 2026
    
    const dateInput = document.getElementById('selectedDateInput');
    const timeInput = document.getElementById('selectedTimeInput');
    const displayDate = document.getElementById('displayDate');
    const submitBtn = document.getElementById('submitBtn');

    /**
     * RENDERÖI KALENTERI
     * Rakentaa kuukausinäkymän ja asettaa päiville data-attribuutit.
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
        // Suomessa viikko alkaa maanantaista (muunnos JS:n sunnuntai-aloituksesta)
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
     * Päivittää otsikon ja kutsuu vapaiden aikojen hakua.
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

        // Kutsutaan hakua
        loadAvailableTimes(dateStr);
    }

    /**
     * AJAX: NOUDA VAPAAT AJAT
     * Tomi: Tämä funktio kutsuu get_available_times.php:tä.
     */
    function loadAvailableTimes(dateStr) {
        const container = document.getElementById('timeSlotContainer');
        container.innerHTML = '<p class="info-text">Haetaan vapaita aikoja...</p>';

        fetch('get_available_times.php?date=' + dateStr)
            .then(response => response.json())
            .then(times => {
                container.innerHTML = ''; 

                if (times.length === 0) {
                    container.innerHTML = '<p class="info-text">Ei vapaita aikoja valitulle päivälle.</p>';
                    return;
                }

                times.forEach(time => {
                    const btn = document.createElement('button');
                    btn.type = "button";
                    btn.className = "time-slot";
                    // Näytetään aika muodossa HH:MM
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
                container.innerHTML = '<p class="info-text">Yhteysvirhe. Yritä uudelleen.</p>';
            });
    }

    // NAVIGOINTI NUOLILLA
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