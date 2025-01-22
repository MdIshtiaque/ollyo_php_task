# Event Management System

A web-based event management system that allows users to create, manage, and register for events.

## Features

### User Management
- User registration and login with secure password hashing
- Admin and regular user roles
- Session-based authentication

### Event Management
- Create, edit, and delete events
- Event details include:
  - Name
  - Description
  - Date and Time
  - Location
  - Maximum Capacity
- Real-time attendee tracking
- Event ownership controls

### Event Registration
- Register/unregister for events
- Capacity limit enforcement
- Real-time registration updates
- Attendee list management

### Search and Filtering
- Search events by name, description, or location
- Filter events by date (upcoming/past)
- Search attendees within events
- Real-time search results

### Admin Features
- Export attendee lists to CSV
- View all events and attendees
- System management capabilities

### API Integration
- RESTful API endpoints
- API key authentication
- Event listing and details
- Search functionality

## Technical Stack

### Backend
- PHP 7.4+
- MySQL 5.7+
- PDO for database operations

### Frontend
- HTML5
- CSS3 with Bootstrap 5
- JavaScript (Vanilla)
- AJAX for asynchronous operations

### Security Features
- Password hashing
- CSRF protection
- Input sanitization
- Prepared statements
- Session management

## Installation

1. **Database Setup**
   ```bash
   mysql -u root -p < database.sql
   ```

2. **Configuration**
   - Copy `config/database.example.php` to `config/database.php`
   - Update database credentials in `config/database.php`

3. **Server Requirements**
   - PHP 7.4 or higher
   - MySQL 5.7 or higher
   - PDO PHP Extension
   - Apache/Nginx web server

4. **Installation**
   ```bash
   # Clone the repository
   git clone https://github.com/yourusername/event-management.git
   
   # Set permissions
   chmod 755 -R event-management/
   chmod 777 -R event-management/uploads/
   ```

5. **Web Server Configuration**
   Apache (.htaccess):
   ```apache
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^(.*)$ index.php [QSA,L]
   ```

6. **First Run**
   - Navigate to `http://your-domain/register.html`
   - Create an admin account
   - Login and start managing events

## Security Notes
- Ensure proper file permissions
- Keep configuration files secure
- Regularly update dependencies
- Use HTTPS in production 

## Database Structure

### Users Table
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_admin BOOLEAN DEFAULT FALSE
);
```

### Events Table
```sql
CREATE TABLE events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    location VARCHAR(255) NOT NULL,
    max_capacity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### Attendees Table
```sql
CREATE TABLE attendees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_registration (event_id, user_id)
);
```

## API Documentation

### Authentication
All API requests require an API key header:
```
X-API-Key: your_api_key_here
```

### Endpoints

#### List Events
```
GET /api/events/list.php
```
Parameters:
- search (optional): Search term
- date_filter (optional): 'upcoming' or 'past'

#### Get Event Details
```
GET /api/events/details.php?id={event_id}
```

#### Search Events
```
GET /api/events/search.php
```
Parameters:
- query: Search term
- filter: upcoming/past/all

## Security Considerations

1. **Password Security**
   - Passwords are hashed using password_hash()
   - Minimum password requirements enforced

2. **SQL Injection Prevention**
   - Prepared statements used throughout
   - PDO for database operations
   - Input sanitization

3. **CSRF Protection**
   - CSRF tokens required for forms
   - Token validation on submissions

4. **XSS Prevention**
   - Input sanitization
   - Output escaping
   - Content Security Policy

## Directory Structure
```
├── api/
│   ├── events/
│   └── auth.php
├── assets/
│   ├── css/
│   └── js/
├── auth/
│   ├── login.html
│   ├── register.html
│   └── logout.php
├── config/
│   └── database.php
├── events/
│   ├── create_event.html
│   ├── edit_event.php
│   └── view_event.php
├── includes/
│   ├── csrf.php
│   └── sanitizer.php
└── dashboard.php
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License. # ollyo_php_task
