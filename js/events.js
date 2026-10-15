/**
 * StudentHub Portal - Events Management & Fetch API Module (Practicals 6 & 13)
 * Features:
 * - Fetch API integration with loading & error handling
 * - Offline localStorage fallback caching
 * - Debounced real-time search
 * - Category & status filters with dependent dropdowns
 * - Dynamic sorting (Date, Title, Seats)
 * - Dynamic client-side pagination
 * - Event detail modal display
 * - Asynchronous CRUD / registration action
 */

document.addEventListener('DOMContentLoaded', () => {
  const eventsContainer = document.getElementById('eventsGrid');
  if (eventsContainer) {
    initEventsModule();
  }
});

let allEvents = [];
let filteredEvents = [];
let currentPage = 1;
const itemsPerPage = 6;

async function initEventsModule() {
  const searchInput = document.getElementById('eventSearchInput');
  const categoryFilter = document.getElementById('eventCategoryFilter');
  const sortSelect = document.getElementById('eventSortSelect');
  const statusFilter = document.getElementById('eventStatusFilter');

  await loadEventsData();

  // Debounced Search Listener
  if (searchInput) {
    let debounceTimer;
    searchInput.addEventListener('input', () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        applyFiltersAndRender();
      }, 300);
    });
  }

  // Filter & Sort Change Listeners
  if (categoryFilter) categoryFilter.addEventListener('change', applyFiltersAndRender);
  if (statusFilter) statusFilter.addEventListener('change', applyFiltersAndRender);
  if (sortSelect) sortSelect.addEventListener('change', applyFiltersAndRender);
}

/**
 * Fetch events from JSON file with offline localStorage caching (Practical 6)
 */
async function loadEventsData() {
  const container = document.getElementById('eventsGrid');
  if (!container) return;

  container.innerHTML = `
    <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
      <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid var(--border-color); border-top-color: var(--primary); border-radius: 50%; animation: spin 1s linear infinite;"></div>
      <p style="margin-top: 1rem; color: var(--text-muted);">Fetching campus events...</p>
    </div>
  `;

  // Inject keyframe animation if missing
  if (!document.getElementById('spinnerKeyframe')) {
    const style = document.createElement('style');
    style.id = 'spinnerKeyframe';
    style.textContent = `@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }`;
    document.head.appendChild(style);
  }

  try {
    // Try fetching from local JSON file
    const response = await fetch('../data/events.json');
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    const data = await response.json();

    allEvents = data;
    // Cache response in localStorage for offline availability (Advanced Extension)
    localStorage.setItem('studenthub_events_cache', JSON.stringify(data));
    filteredEvents = [...allEvents];
    renderEvents();
  } catch (error) {
    console.warn('Network fetch failed, attempting to read from offline cache...', error);
    const cached = localStorage.getItem('studenthub_events_cache');
    if (cached) {
      allEvents = JSON.parse(cached);
      filteredEvents = [...allEvents];
      renderEvents();
      if (window.showToast) window.showToast('Loaded events from offline cache.', 'warning');
    } else {
      container.innerHTML = `
        <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
          <p style="color: var(--danger); font-size: 1.1rem; font-weight: 600;">⚠️ Failed to load events data.</p>
          <p style="color: var(--text-muted);">Please check if events.json exists in the data directory.</p>
          <button class="btn btn-primary" onclick="loadEventsData()" style="margin-top: 1rem;">Retry</button>
        </div>
      `;
    }
  }
}

/**
 * Filter, Sort, and Paginate Events
 */
function applyFiltersAndRender() {
  const searchTerm = (document.getElementById('eventSearchInput')?.value || '').toLowerCase().trim();
  const selectedCategory = document.getElementById('eventCategoryFilter')?.value || 'all';
  const selectedStatus = document.getElementById('eventStatusFilter')?.value || 'all';
  const sortBy = document.getElementById('eventSortSelect')?.value || 'date-asc';

  filteredEvents = allEvents.filter(event => {
    const matchSearch =
      event.title.toLowerCase().includes(searchTerm) ||
      event.description.toLowerCase().includes(searchTerm) ||
      event.venue.toLowerCase().includes(searchTerm);

    const matchCategory = selectedCategory === 'all' || event.category.toLowerCase() === selectedCategory.toLowerCase();
    const matchStatus = selectedStatus === 'all' || event.status.toLowerCase() === selectedStatus.toLowerCase();

    return matchSearch && matchCategory && matchStatus;
  });

  // Sorting Logic
  filteredEvents.sort((a, b) => {
    if (sortBy === 'date-asc') return new Date(a.date) - new Date(b.date);
    if (sortBy === 'date-desc') return new Date(b.date) - new Date(a.date);
    if (sortBy === 'title-asc') return a.title.localeCompare(b.title);
    if (sortBy === 'seats-desc') return (b.seats - b.registered) - (a.seats - a.registered);
    return 0;
  });

  currentPage = 1;
  renderEvents();
}

/**
 * Render Events Grid & Pagination Controls
 */
function renderEvents() {
  const container = document.getElementById('eventsGrid');
  const countBadge = document.getElementById('eventsCountBadge');
  const paginationContainer = document.getElementById('eventsPagination');

  if (!container) return;

  if (countBadge) {
    countBadge.textContent = `${filteredEvents.length} Events Found`;
  }

  if (filteredEvents.length === 0) {
    container.innerHTML = `
      <div style="grid-column: 1/-1; text-align: center; padding: 3rem; background: var(--bg-surface); border-radius: var(--radius-lg); border: 1px dashed var(--border-color);">
        <p style="font-size: 1.2rem; font-weight: 600; color: var(--text-main);">🔍 No matching events found</p>
        <p style="color: var(--text-muted); margin-top: 0.5rem;">Try modifying your search query or filters.</p>
      </div>
    `;
    if (paginationContainer) paginationContainer.innerHTML = '';
    return;
  }

  // Calculate slice for pagination
  const startIndex = (currentPage - 1) * itemsPerPage;
  const endIndex = startIndex + itemsPerPage;
  const pageItems = filteredEvents.slice(startIndex, endIndex);

  container.innerHTML = pageItems.map(event => {
    const availableSeats = Math.max(0, event.seats - event.registered);
    const badgeClass =
      event.category === 'Technical' ? 'badge-primary' :
      event.category === 'Workshop' ? 'badge-info' :
      event.category === 'Sports' ? 'badge-success' : 'badge-warning';

    const statusBadge = event.status === 'Open'
      ? `<span class="badge badge-success">Open</span>`
      : `<span class="badge badge-danger">Closed</span>`;

    return `
      <div class="event-card" id="event-${event.id}">
        <div class="event-poster-wrapper">
          <img src="${event.poster || '../images/StudentHub.png'}" alt="${event.title}" class="event-poster">
          <div class="event-category-badge">
            <span class="badge ${badgeClass}">${event.category}</span>
          </div>
        </div>
        <div class="event-body">
          <div class="event-meta">
            <span>📅 ${formatDate(event.date)}</span>
            <span>📍 ${event.venue}</span>
          </div>
          <h3 class="event-title">${escapeHTML(event.title)}</h3>
          <p class="event-description">${escapeHTML(event.description)}</p>
          <div style="margin-bottom: 1rem; font-size: 0.85rem; color: var(--text-muted);">
            <span><strong>Available Seats:</strong> ${availableSeats} / ${event.seats}</span>
          </div>
          <div class="event-footer">
            ${statusBadge}
            <div style="display: flex; gap: 0.5rem;">
              <button class="btn btn-secondary btn-sm" onclick="showEventDetails(${event.id})">Details</button>
              ${event.status === 'Open' && availableSeats > 0 ? `
                <button class="btn btn-primary btn-sm" onclick="registerForEvent(${event.id})">Join</button>
              ` : `
                <button class="btn btn-secondary btn-sm" disabled>Full</button>
              `}
            </div>
          </div>
        </div>
      </div>
    `;
  }).join('');

  renderPagination();
}

/**
 * Render Pagination Buttons
 */
function renderPagination() {
  const paginationContainer = document.getElementById('eventsPagination');
  if (!paginationContainer) return;

  const totalPages = Math.ceil(filteredEvents.length / itemsPerPage);
  if (totalPages <= 1) {
    paginationContainer.innerHTML = '';
    return;
  }

  let html = `
    <button class="page-btn" ${currentPage === 1 ? 'disabled' : ''} onclick="goToPage(${currentPage - 1})">« Prev</button>
  `;

  for (let i = 1; i <= totalPages; i++) {
    html += `
      <button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>
    `;
  }

  html += `
    <button class="page-btn" ${currentPage === totalPages ? 'disabled' : ''} onclick="goToPage(${currentPage + 1})">Next »</button>
  `;

  paginationContainer.innerHTML = html;
}

window.goToPage = function(page) {
  currentPage = page;
  renderEvents();
  document.getElementById('eventsGrid')?.scrollIntoView({ behavior: 'smooth' });
};

/**
 * Event Detail Modal Action
 */
window.showEventDetails = function(eventId) {
  let event = allEvents.find(e => e.id == eventId);

  // If not found in memory, try offline localStorage cache
  if (!event) {
    try {
      const cached = JSON.parse(localStorage.getItem('studenthub_events_cache') || '[]');
      event = cached.find(e => e.id == eventId);
    } catch (err) {
      console.warn('Cache fallback parse error:', err);
    }
  }

  if (!event) {
    console.error(`Event with ID ${eventId} could not be found.`);
    return;
  }

  const modalBody = document.getElementById('eventModalBody');
  const modalTitle = document.getElementById('eventModalTitle');
  const modal = document.getElementById('eventDetailModal');

  const title = event.title || 'Event Details';
  const poster = event.poster || event.poster_image || '../images/StudentHub.png';
  const category = event.category || 'Technical';
  const status = event.status || 'Open';
  const desc = event.description || 'No detailed description available for this campus event.';
  const eventDate = formatDate(event.date || event.event_date || '2026-10-30');
  const eventTime = event.time || event.event_time || '10:00 AM - 04:00 PM';
  const venue = event.venue || 'Campus Auditorium';
  const organizer = event.organizer || 'StudentHub Committee';
  const registered = event.registered ?? event.registered_count ?? 0;
  const seats = event.seats ?? event.capacity ?? 100;

  if (modalTitle) {
    modalTitle.textContent = title;
  }

  if (modalBody) {
    modalBody.innerHTML = `
      <div style="display: flex; flex-direction: column; gap: 1rem;">
        <img src="${poster}" alt="${escapeHTML(title)}" style="max-height: 220px; width: 100%; object-fit: cover; border-radius: var(--radius-md);" onerror="this.src='../images/StudentHub.png'">
        <div style="display: flex; align-items: center; gap: 0.5rem;">
          <span class="badge badge-primary">${escapeHTML(category)}</span>
          <span class="badge ${status === 'Open' ? 'badge-success' : 'badge-danger'}">${escapeHTML(status)}</span>
        </div>
        <p style="font-size: 0.95rem; color: var(--text-main); line-height: 1.6;">${escapeHTML(desc)}</p>
        <ul style="list-style: none; padding: 0.85rem 1rem; background: var(--bg-surface-elevated); border-radius: var(--radius-md); border: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.9rem; color: var(--text-muted);">
          <li>📅 <strong>Date:</strong> ${eventDate}</li>
          <li>⏰ <strong>Time:</strong> ${escapeHTML(eventTime)}</li>
          <li>📍 <strong>Venue:</strong> ${escapeHTML(venue)}</li>
          <li>👥 <strong>Organizer:</strong> ${escapeHTML(organizer)}</li>
          <li>🎟️ <strong>Capacity:</strong> ${registered} / ${seats} Registered</li>
        </ul>
      </div>
    `;
  }

  if (modal) {
    modal.setAttribute('aria-hidden', 'false');
  }

  if (typeof window.openModal === 'function') {
    window.openModal('eventDetailModal');
  } else if (modal) {
    modal.classList.add('active');
    modal.classList.add('open');
    modal.style.display = 'flex';
  }
};

/**
 * Register Event Quick Action
 */
window.registerForEvent = function(eventId) {
  const event = allEvents.find(e => e.id === eventId);
  if (!event) return;

  if (event.registered >= event.seats) {
    if (window.showToast) window.showToast('Sorry, this event is already fully booked!', 'error');
    return;
  }

  event.registered++;
  renderEvents();
  if (window.showToast) {
    window.showToast(`🎉 Successfully registered for ${event.title}!`, 'success');
  }
};

function formatDate(dateStr) {
  try {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateStr).toLocaleDateString('en-US', options);
  } catch {
    return dateStr;
  }
}

function escapeHTML(str) {
  if (!str) return '';
  return str.replace(/[&<>'"]/g, tag => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    "'": '&#39;',
    '"': '&quot;'
  }[tag] || tag));
}
