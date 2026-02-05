<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Hieronta - Varaus</title>
    <link rel="stylesheet" href="style.css">
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
                    <img src="logo.jpg" alt="Yrityksen logo">
                </div>
                <h3>Artisan Massage</h3>
                <p class="specialist-title">Koulutettu hieroja</p>
            </div>

            <div class="service-details">
                <div class="service-row">
                    <strong>45min hieronta 45€</strong>
                    <span>Klassinen hoito</span>
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
                <p>Valitse kalenterista sopiva päivä ja vapaa kellonaika.</p>
            </header>

            <div class="selection-grid">
                <div class="calendar-card">
                    <div class="calendar-header">
                        <span id="monthDisplay">Helmikuu 2026</span>
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
                    <button type="submit" class="confirm-btn" id="submitBtn" disabled>Vahvista valinta</button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    let currentViewDate = new Date(2026, 1, 1); // Aloitetaan helmikuusta 2026
    const dateInput = document.getElementById('selectedDateInput');
    const timeInput = document.getElementById('selectedTimeInput');
    const displayDate = document.getElementById('displayDate');
    const submitBtn = document.getElementById('submitBtn');

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
        let startingDay = firstDay === 0 ? 6 : firstDay - 1; // Muunnos: ma=0, su=6

        for (let i = 0; i < 6; i++) { // Max 6 viikkoa
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
                    
                    // Valitun päivän korostus jos se on jo valittu
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

    function selectDate(element, dateStr) {
        document.querySelector('.calendar-table td.selected')?.classList.remove('selected');
        element.classList.add('selected');
        dateInput.value = dateStr;

        // Suomenkielinen päivämäärämuotoilu otsikkoon
        const dateObj = new Date(dateStr);
        const options = { weekday: 'long', day: 'numeric', month: 'numeric', year: 'numeric' };
        let formatted = dateObj.toLocaleDateString('fi-FI', options);
        displayDate.innerText = formatted.charAt(0).toUpperCase() + formatted.slice(1);

        // Demo-aikojen lataus (Tässä kohtaa Tomi tekee myöhemmin AJAX-pyynnön)
        loadDemoTimes();
    }

    function loadDemoTimes() {
        const container = document.getElementById('timeSlotContainer');
        container.innerHTML = ''; // Tyhjennetään vanhat
        const demoTimes = ["09:00", "10:30", "12:00", "14:00", "15:30", "17:00"];

        demoTimes.forEach(time => {
            const btn = document.createElement('button');
            btn.type = "button";
            btn.className = "time-slot";
            btn.innerText = time;
            btn.onclick = () => {
                document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('active'));
                btn.classList.add('active');
                timeInput.value = time;
                submitBtn.disabled = false;
            };
            container.appendChild(btn);
        });
    }

    // Nuolien toiminnallisuus
    document.getElementById('prevMonth').onclick = () => { currentViewDate.setMonth(currentViewDate.getMonth() - 1); renderCalendar(); };
    document.getElementById('nextMonth').onclick = () => { currentViewDate.setMonth(currentViewDate.getMonth() + 1); renderCalendar(); };

    // Alustus
    renderCalendar();
</script>

</body>
</html>