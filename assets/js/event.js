function registerForEvent(eventId) {
    const button = document.querySelector(`#registration-buttons-${eventId} button`);
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Registering...';

    const headers = {
        'Content-Type': 'application/x-www-form-urlencoded'
    };

    // Add CSRF token if available
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        headers['X-CSRF-Token'] = csrfToken.content;
    }

    fetch('../events/register_event.php', {
        method: 'POST',
        headers: headers,
        body: 'event_id=' + eventId
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            updateEventUI(eventId, true);
            showAlert('success', data.message);
            // Refresh attendees list if the function exists
            if (typeof searchAttendees === 'function') {
                searchAttendees('');
            }
        } else {
            showAlert('danger', data.error);
            button.disabled = false;
            button.innerHTML = 'Register for Event';
        }
    })
    .catch(error => {
        console.log(error);
        showAlert('danger', 'An error occurred. Please try again.');
        button.disabled = false;
        button.innerHTML = 'Register for Event';
    });
}

function unregisterFromEvent(eventId) {
    if (!confirm('Are you sure you want to cancel your registration?')) {
        return;
    }

    const button = document.querySelector(`#registration-buttons-${eventId} button`);
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Canceling...';

    const headers = {
        'Content-Type': 'application/x-www-form-urlencoded'
    };

    // Add CSRF token if available
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        headers['X-CSRF-Token'] = csrfToken.content;
    }

    fetch('../events/unregister_event.php', {
        method: 'POST',
        headers: headers,
        body: 'event_id=' + eventId
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            updateEventUI(eventId, false);
            showAlert('success', data.message);
            // Refresh attendees list if the function exists
            if (typeof searchAttendees === 'function') {
                searchAttendees('');
            }
        } else {
            showAlert('danger', data.error);
            button.disabled = false;
            button.innerHTML = 'Cancel Registration';
        }
    })
    .catch(error => {
        console.log(error);
        showAlert('danger', 'An error occurred. Please try again.');
        button.disabled = false;
        button.innerHTML = 'Cancel Registration';
    });
}

function updateEventUI(eventId, isRegistered) {
    const registrationDiv = document.getElementById('registration-buttons-' + eventId);
    const attendeeCount = document.getElementById('attendee-count-' + eventId);
    
    if (registrationDiv && attendeeCount) {
        const currentCount = parseInt(attendeeCount.dataset.count);
        const maxCapacity = parseInt(attendeeCount.dataset.capacity);
        
        if (isRegistered) {
            attendeeCount.dataset.count = currentCount + 1;
            attendeeCount.textContent = `${currentCount + 1}/${maxCapacity}`;
            
            registrationDiv.innerHTML = `
                <button onclick="unregisterFromEvent(${eventId})" class="btn btn-danger">
                    Cancel Registration
                </button>`;
        } else {
            attendeeCount.dataset.count = currentCount - 1;
            attendeeCount.textContent = `${currentCount - 1}/${maxCapacity}`;
            
            if (currentCount - 1 < maxCapacity) {
                registrationDiv.innerHTML = `
                    <button onclick="registerForEvent(${eventId})" class="btn btn-primary">
                        Register for Event
                    </button>`;
            }
        }
    }
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}