function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function updateEventsList(searchResults) {
    const tbody = document.querySelector('#events-table tbody');
    tbody.innerHTML = '';
    
    if (!searchResults || searchResults.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-muted">
                    <i class="bi bi-inbox me-2"></i>No events found
                </td>
            </tr>`;
        return;
    }
    
    searchResults.forEach(event => {
        const date = new Date(event.event_date);
        const formattedDate = date.toLocaleDateString();
        
        tbody.innerHTML += `
            <tr>
                <td>${escapeHtml(event.name)}</td>
                <td>${formattedDate}</td>
                <td>${event.event_time}</td>
                <td>${escapeHtml(event.location)}</td>
                <td>
                    <span class="badge ${event.registered_attendees >= event.max_capacity ? 'bg-danger' : 'bg-success'}">
                        ${event.registered_attendees}/${event.max_capacity}
                    </span>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <a href="events/view_event.php?id=${event.id}" 
                           class="btn btn-info">
                            <i class="bi bi-eye me-1"></i>View
                        </a>
                        ${event.is_owner ? `
                            <a href="events/edit_event.php?id=${event.id}" 
                               class="btn btn-warning">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                            <a href="events/delete_event.php?id=${event.id}" 
                               class="btn btn-danger"
                               onclick="return confirm('Are you sure you want to delete this event?')">
                                <i class="bi bi-trash me-1"></i>Delete
                            </a>
                        ` : ''}
                    </div>
                </td>
            </tr>`;
    });
}

const searchEvents = debounce((searchTerm, dateFilter) => {
    const searchStatus = document.getElementById('search-status');
    searchStatus.textContent = 'Searching...';
    
    fetch(`search_events.php?search=${encodeURIComponent(searchTerm)}&date_filter=${dateFilter}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            updateEventsList(data.events);
            searchStatus.textContent = '';
        })
        .catch(error => {
            console.error('Error:', error);
            searchStatus.textContent = 'Error occurred while searching';
            updateEventsList([]); // Clear table on error
        });
}, 300);

// Event listeners
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('search-input');
    const dateFilter = document.getElementById('date-filter');
    
    searchInput?.addEventListener('input', (e) => {
        searchEvents(e.target.value, dateFilter.value);
    });
    
    dateFilter?.addEventListener('change', (e) => {
        searchEvents(searchInput.value, e.target.value);
    });

    // Initial load of events
    searchEvents('', 'all');
}); 