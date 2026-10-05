/**
 * StudentHub Portal - External JSON Rendering Module
 * Practical 6: Fetch API, Search, Filter, Sort, Pagination, Offline Cache & Modal Details
 */

document.addEventListener('DOMContentLoaded', () => {
    initEventsLoader();
});

function initEventsLoader() {
    const eventsContainer = document.getElementById('events-container');
    if (!eventsContainer) return;

    let allEvents = [];
    let filteredEvents = [];
    let currentPage = 1;
    const itemsPerPage = 6;
    let selectedCategory = 'All';
    let currentSort = 'date-asc';
    let searchQuery = '';

    const searchInput = document.getElementById('events-search');
    const categoryContainer = document.getElementById('category-pills');
    const sortSelect = document.getElementById('events-sort');
    const paginationContainer = document.getElementById('pagination-container');
    const spinner = document.getElementById('events-spinner');

    // 1. Fetch Events Data from JSON (with localStorage cache fallback)
    loadEvents();

    async function loadEvents() {
        // Try localStorage cache first for fast display (Practical 6 Advanced Extension)
        const cachedData = localStorage.getItem('studenthub_cached_events');
        if (cachedData) {
            try {
                allEvents = JSON.parse(cachedData);
                applyFilters();
            } catch (e) {
                console.warn('Cached data invalid', e);
            }
        }

        try {
            // Determine relative path based on page location
            const jsonPath = window.location.pathname.includes('/pages/') 
                ? '../data/events.json' 
                : 'data/events.json';

            const response = await fetch(jsonPath);
            if (!response.ok) throw new Error(`HTTP error: ${response.status}`);
            
            const data = await response.json();
            allEvents = data;
            
            // Cache fresh data
            localStorage.setItem('studenthub_cached_events', JSON.stringify(data));
            
            if (spinner) spinner.style.display = 'none';
            applyFilters();
        } catch (error) {
            console.error('Fetch error:', error);
            if (spinner) spinner.style.display = 'none';
            if (allEvents.length === 0) {
                eventsContainer.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--text-secondary);">
                        <p>⚠️ Unable to load live events. Please ensure you are viewing via a web server or check connection.</p>
                    </div>
                `;
            }
        }
    }

    // 2. Search, Filter & Sort Logic
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value.toLowerCase().trim();
            currentPage = 1;
            applyFilters();
        });
    }

    if (categoryContainer) {
        categoryContainer.addEventListener('click', (e) => {
            const pill = e.target.closest('.category-pill');
            if (!pill) return;

            categoryContainer.querySelectorAll('.category-pill').forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            selectedCategory = pill.dataset.category || 'All';
            currentPage = 1;
            applyFilters();
        });
    }

    if (sortSelect) {
        sortSelect.addEventListener('change', (e) => {
            currentSort = e.target.value;
            applyFilters();
        });
    }

    function applyFilters() {
        filteredEvents = allEvents.filter(event => {
            const matchesCategory = (selectedCategory === 'All' || event.category === selectedCategory);
            const matchesSearch = searchQuery === '' || 
                event.title.toLowerCase().includes(searchQuery) ||
                event.speaker.toLowerCase().includes(searchQuery) ||
                event.location.toLowerCase().includes(searchQuery) ||
                event.description.toLowerCase().includes(searchQuery);

            return matchesCategory && matchesSearch;
        });

        // Sorting
        filteredEvents.sort((a, b) => {
            if (currentSort === 'date-asc') {
                return new Date(a.date) - new Date(b.date);
            } else if (currentSort === 'date-desc') {
                return new Date(b.date) - new Date(a.date);
            } else if (currentSort === 'title-asc') {
                return a.title.localeCompare(b.title);
            }
            return 0;
        });

        renderEvents();
        renderPagination();
    }

    // 3. Render Events Grid
    function renderEvents() {
        eventsContainer.innerHTML = '';

        if (filteredEvents.length === 0) {
            eventsContainer.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 50px 20px; background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-color);">
                    <div style="font-size: 2.5rem; margin-bottom: 10px;">🔍</div>
                    <h3 style="margin-bottom: 8px;">No matching campus events found</h3>
                    <p style="color: var(--text-secondary);">Try adjusting your search terms or selecting another category.</p>
                </div>
            `;
            return;
        }

        const startIndex = (currentPage - 1) * itemsPerPage;
        const pageItems = filteredEvents.slice(startIndex, startIndex + itemsPerPage);

        pageItems.forEach(event => {
            const eventDate = new Date(event.date);
            const monthStr = eventDate.toLocaleString('default', { month: 'short' }).toUpperCase();
            const dayStr = String(eventDate.getDate()).padStart(2, '0');

            const card = document.createElement('div');
            card.className = 'event-card';
            card.innerHTML = `
                <div class="event-date">
                    <span class="month">${monthStr}</span>
                    <span class="day">${dayStr}</span>
                </div>
                <div class="event-details">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
                        <h3 style="margin: 0;">${escapeHtml(event.title)}</h3>
                        ${event.badge ? `<span style="background: rgba(13,148,136,0.1); color: var(--accent-green); padding: 2px 8px; border-radius: 12px; font-size: 0.72rem; font-weight: 700; white-space: nowrap;">${event.badge}</span>` : ''}
                    </div>
                    <p class="event-meta">📍 ${escapeHtml(event.location)} • ⏰ ${escapeHtml(event.time)}</p>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 6px;">🎙️ <strong>${escapeHtml(event.speaker)}</strong></p>
                    <p style="font-size: 0.9rem; line-height: 1.5; color: var(--text-secondary);">${escapeHtml(event.description.substring(0, 110))}...</p>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                        <span style="font-size: 0.8rem; color: var(--text-secondary);">💺 ${event.registered}/${event.seats} Registered</span>
                        <button class="btn-join view-event-btn" data-event-id="${event.id}">Details & Join</button>
                    </div>
                </div>
            `;

            // Card click listener opens Modal
            const btn = card.querySelector('.view-event-btn');
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                openEventModal(event);
            });

            eventsContainer.appendChild(card);
        });
    }

    // 4. Render Pagination Controls
    function renderPagination() {
        if (!paginationContainer) return;
        paginationContainer.innerHTML = '';

        const totalPages = Math.ceil(filteredEvents.length / itemsPerPage);
        if (totalPages <= 1) return;

        // Prev Button
        const prevBtn = document.createElement('button');
        prevBtn.className = 'page-btn';
        prevBtn.innerHTML = '← Prev';
        prevBtn.disabled = currentPage === 1;
        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderEvents();
                renderPagination();
                eventsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
        paginationContainer.appendChild(prevBtn);

        // Page Number Buttons
        for (let i = 1; i <= totalPages; i++) {
            const pageNumBtn = document.createElement('button');
            pageNumBtn.className = `page-btn ${i === currentPage ? 'active' : ''}`;
            pageNumBtn.textContent = i;
            pageNumBtn.addEventListener('click', () => {
                currentPage = i;
                renderEvents();
                renderPagination();
                eventsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
            paginationContainer.appendChild(pageNumBtn);
        }

        // Next Button
        const nextBtn = document.createElement('button');
        nextBtn.className = 'page-btn';
        nextBtn.innerHTML = 'Next →';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                renderEvents();
                renderPagination();
                eventsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
        paginationContainer.appendChild(nextBtn);
    }

    // 5. Open Event Details in Modal Popup (Practical 4 & 6 Integration)
    function openEventModal(event) {
        const modal = document.getElementById('event-detail-modal');
        if (!modal) return;

        document.getElementById('modal-event-title').textContent = event.title;
        document.getElementById('modal-event-meta').textContent = `${event.date} • ${event.time} • ${event.location}`;
        document.getElementById('modal-event-speaker').textContent = event.speaker;
        document.getElementById('modal-event-desc').textContent = event.description;
        document.getElementById('modal-event-seats').textContent = `${event.registered} of ${event.seats} seats filled`;

        const confirmBtn = document.getElementById('modal-confirm-join-btn');
        if (confirmBtn) {
            confirmBtn.onclick = () => {
                alert(`🎉 You have registered for "${event.title}"! A confirmation has been added to your dashboard schedule.`);
                window.StudentHub.closeModal(modal);
            };
        }

        window.StudentHub.openModal('event-detail-modal');
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
}
