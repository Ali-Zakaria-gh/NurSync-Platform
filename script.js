document.addEventListener('DOMContentLoaded', () => {
    const calendarBody = document.getElementById('calendar-body');
    const hospitalFilter = document.getElementById('hospital-filter');
    const monthLabel = document.getElementById('current-month-label');
    const monthPickerBtn = document.getElementById('month-picker-btn');
    const customPicker = document.getElementById('custom-month-picker');
    const cmpYearLabel = document.getElementById('cmp-year-label');
    const cmpMonthsGrid = document.getElementById('cmp-months-grid');
    const cmpPrevYear = document.getElementById('cmp-prev-year');
    const cmpNextYear = document.getElementById('cmp-next-year');
    const prevMonthBtn = document.getElementById('prev-month-btn');
    const nextMonthBtn = document.getElementById('next-month-btn');

    const MONTH_NAMES = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    const hospitalNames = {
        ahc: 'Health Cluster',
        kfgh: 'King Faisal Gen.',
        kfhh: 'King Fahad',
        pscc: 'Prince Sultan Cardiac',
        psbj: 'Prince Saud Bin Jalawy',
        mch: 'Maternity & Children',
        mhh: 'Mental Health',
        oyun: 'Oyun Hospital',
        jgh: 'Aljafar General',
        ogh: 'Alomran General',
        alafaleq: 'Alafaleq Hospital',
        bhdc: 'Blood Hereditary Center',
        ent: 'Aljabr Eye & ENT'
    };

    // Tracking the currently displayed month and year
    let currentDisplayDate = new Date(); // Defaults to today's current actual date

    function getCalendarMetrics(dateObj) {
        const year = dateObj.getFullYear();
        const month = dateObj.getMonth(); // 0-11
        return {
            startDayOfWeek: new Date(year, month, 1).getDay(), // 0 for Sunday
            daysInMonth: new Date(year, month + 1, 0).getDate(),
            daysInPrevMonth: new Date(year, month, 0).getDate()
        };
    }

    // Helper: build the key for the current displayed year/month/day
    function eventKey(day) {
        const y = currentDisplayDate.getFullYear();
        const m = currentDisplayDate.getMonth() + 1; // 1-indexed
        return `${y}-${m}-${day}`;
    }

    // We will make renderCalendar async and attach it globally so `submitActivity` can call it
    window.renderCalendar = async function (filterHospital = 'all') {
        calendarBody.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding: 40px; color: var(--text-muted);"><i class="fa-solid fa-spinner fa-spin fa-2x"></i></div>';
        let upcomingActivities = [];

        // Update Title Label
        const currentLang = typeof getCurrentLang === 'function' ? getCurrentLang() : 'en';
        monthLabel.innerText = '- ' + currentDisplayDate.toLocaleString(currentLang === 'ar' ? 'ar-SA' : 'en-US', { month: 'long', year: 'numeric' });

        const { startDayOfWeek, daysInMonth, daysInPrevMonth } = getCalendarMetrics(currentDisplayDate);

        // Calculate total cells needed to close out the last week nicely (multiple of 7)
        const totalCells = Math.ceil((startDayOfWeek + daysInMonth) / 7) * 7;

        let loadedEvents = {};
        try {
            const year = currentDisplayDate.getFullYear();
            const month = currentDisplayDate.getMonth() + 1;
            const res = await fetch(`api/activities.php?action=calendar&year=${year}&month=${month}&hospital=${filterHospital}`);
            const data = await res.json();
            if (data.success && data.data) {
                // map API response: { '14': [{title, hospital_name, color_hex, hospital_id}] }
                for (const [dayStr, actsList] of Object.entries(data.data)) {
                    const dayNum = parseInt(dayStr, 10);
                    const k = `${year}-${month}-${dayNum}`;
                    loadedEvents[k] = actsList.map(a => ({
                        title: a.title,
                        hospital: a.hospital_id,
                        hospital_name: a.hospital_name,
                        color_hex: a.color_hex
                    }));
                }
            }
        } catch (e) {
            console.error('Failed to load events from DB');
        }

        calendarBody.innerHTML = ''; // Clear spinner
        for (let i = 0; i < totalCells; i++) {
            const cell = document.createElement('div');

            if (i < startDayOfWeek) {
                // Days from previous month
                cell.className = 'cal-cell empty';
                const prevMonthDay = daysInPrevMonth - (startDayOfWeek - 1 - i);
                cell.innerHTML = `<span class="cal-date" style="color: #ccc;">${prevMonthDay}</span>`;
            } else if (i < startDayOfWeek + daysInMonth) {
                // Days in current month
                cell.className = 'cal-cell';
                const currentDate = i - startDayOfWeek + 1;

                let cellContent = `<span class="cal-date">${currentDate}</span>`;

                // Show events only if they belong to this exact year-month-day
                const key = eventKey(currentDate);
                if (loadedEvents[key]) {
                    loadedEvents[key].forEach(event => {
                        // Check if event matches the selected hospital filter
                        if (filterHospital === 'all' || event.hospital === filterHospital) {
                            cellContent += `
                                <div class="event-pill event-${event.hospital}" title="${event.title}" style="${event.color_hex ? `background-color: ${event.color_hex}` : ''}">
                                    ${event.title}
                                </div>
                            `;
                            upcomingActivities.push({ ...event, date: currentDate });
                        }
                    });
                }

                cell.innerHTML = cellContent;
            } else {
                // Days from next month
                cell.className = 'cal-cell empty';
                const nextMonthDay = i - (startDayOfWeek + daysInMonth) + 1;
                cell.innerHTML = `<span class="cal-date" style="color: #ccc;">${nextMonthDay}</span>`;
            }

            calendarBody.appendChild(cell);
        }

        // Subtle fade-in animation
        calendarBody.style.opacity = '0';
        setTimeout(() => {
            calendarBody.style.transition = 'opacity 0.3s ease';
            calendarBody.style.opacity = '1';
        }, 10);
    }


    // Initial render
    renderCalendar('all');

    // Listen for filter changes
    hospitalFilter.addEventListener('change', (e) => {
        renderCalendar(e.target.value);
    });

    // --- Custom Month Picker Logic ---
    let pickerYear = currentDisplayDate.getFullYear();

    function buildPickerMonths() {
        cmpYearLabel.innerText = pickerYear;
        cmpMonthsGrid.innerHTML = '';
        const activeMonth = currentDisplayDate.getMonth();
        const activeYear = currentDisplayDate.getFullYear();
        MONTH_NAMES.forEach((name, idx) => {
            const btn = document.createElement('button');
            btn.className = 'cmp-month-btn' + (pickerYear === activeYear && idx === activeMonth ? ' active' : '');
            btn.innerText = name;
            btn.addEventListener('click', () => {
                currentDisplayDate = new Date(pickerYear, idx, 1);
                renderCalendar(hospitalFilter.value);
                customPicker.classList.remove('open');
            });
            cmpMonthsGrid.appendChild(btn);
        });
    }

    monthPickerBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        pickerYear = currentDisplayDate.getFullYear();
        buildPickerMonths();
        customPicker.classList.toggle('open');
    });

    cmpPrevYear.addEventListener('click', (e) => { e.stopPropagation(); pickerYear--; buildPickerMonths(); });
    cmpNextYear.addEventListener('click', (e) => { e.stopPropagation(); pickerYear++; buildPickerMonths(); });

    // --- Arrow Navigation Logic ---
    prevMonthBtn.addEventListener('click', () => {
        currentDisplayDate.setMonth(currentDisplayDate.getMonth() - 1);
        renderCalendar(hospitalFilter.value);
    });

    nextMonthBtn.addEventListener('click', () => {
        currentDisplayDate.setMonth(currentDisplayDate.getMonth() + 1);
        renderCalendar(hospitalFilter.value);
    });

    // Close picker when clicking outside
    document.addEventListener('click', (e) => {
        if (!customPicker.contains(e.target) && e.target !== monthPickerBtn) {
            customPicker.classList.remove('open');
        }
    });

    // --- Modal Logic ---
    const modal = document.getElementById('poster-modal');
    const closeBtn = document.getElementById('modal-close');
    const modalImg = document.getElementById('modal-poster-img');

    // Using the placeholder poster we generated
    const defaultPoster = 'assets/event_poster.png';

    // Event delegation for opening modals from dynamically generated view buttons
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.view-poster-btn');
        if (btn && !btn.classList.contains('disabled')) {
            if (modalImg && modal) {
                const posterPath = btn.getAttribute('data-poster') || defaultPoster;
                modalImg.src = posterPath;
                modal.classList.add('active');
            }
        }
    });

    // Close modal function
    const closeModal = () => {
        if (modal) modal.classList.remove('active');
    };

    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    // Close when clicking outside the content area (on the overlay itself)
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
    }
    // Initialize Flatpickr for Date and Time
    if (window.flatpickr) {
        const forceEn = (selectedDates, dateStr, instance) => {
            const container = instance.calendarContainer;
            container.setAttribute('lang', 'en');
            container.setAttribute('dir', 'ltr');

            const translate = (node) => {
                if (!node) return;
                if (node.nodeType === 3) { // Text Node
                    const v = node.nodeValue;
                    const clean = v.replace(/[٠١٢٣٤٥٦٧٨٩۰۱۲۳۴۵۶۷۸۹]/g, d => {
                        const charCode = d.charCodeAt(0);
                        return charCode >= 1776 ? charCode - 1776 : charCode - 1632;
                    });
                    if (v !== clean) node.nodeValue = clean;
                } else if (node.tagName === 'INPUT' || node.tagName === 'SELECT') {
                    const v = node.value;
                    if (v) {
                        const clean = v.replace(/[٠١٢٣٤٥٦٧٨٩۰۱۲۳۴۵۶۷۸۹]/g, d => {
                            const charCode = d.charCodeAt(0);
                            return charCode >= 1776 ? charCode - 1776 : charCode - 1632;
                        });
                        if (v !== clean) node.value = clean;
                    }
                }
                node.childNodes.forEach(translate);
            };

            translate(container);

            if (!instance._observer) {
                instance._observer = new MutationObserver(() => translate(container));
                instance._observer.observe(container, { childList: true, subtree: true, characterData: true, attributes: true });
            }
            
            // Brute-force layer: Hammer the values for 1.5 seconds after opening
            if (instance._hammer) clearInterval(instance._hammer);
            let hammerCount = 0;
            instance._hammer = setInterval(() => {
                translate(container);
                hammerCount++;
                if (hammerCount > 15) clearInterval(instance._hammer);
            }, 100);
        };

        window.fpDate = flatpickr("#aa-date", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "F j, Y",
            minDate: "today",
            onReady: forceEn,
            onOpen: forceEn,
            onMonthChange: forceEn,
            onYearChange: forceEn,
            onValueUpdate: forceEn
        });

        window.fpTime = flatpickr("#aa-time", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            altInput: true,
            altFormat: "h:i K",
            onReady: forceEn,
            onOpen: forceEn,
            onValueUpdate: forceEn
        });
    }

    // Listen for language toggling to update calendar header
    window.addEventListener('languageChanged', () => {
        if (typeof window.renderCalendar === 'function' && hospitalFilter) {
            window.renderCalendar(hospitalFilter.value);
        }
    });
});
