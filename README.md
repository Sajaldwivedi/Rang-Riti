# Wedding Planner Web Application

A modern, visually stunning wedding planning application built with PHP, MySQL, HTML5, CSS3, and JavaScript.

## Features

- User Authentication System
- Interactive Dashboard
- Wedding Checklist Management
- Budget Planning Tools
- Guest List Management
- Vendor Contact Management
- Event Calendar
- Profile Management

## Requirements

- PHP 8.0 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

## Installation

1. Clone this repository to your web server's root directory
2. Import the database schema from `database/wedding_planner.sql`
3. Configure database connection in `config/database.php`
4. Ensure proper permissions are set for file uploads
5. Access the application through your web browser

## Directory Structure

```
wedding-planner/
├── assets/
│   ├── css/
│   ├── js/
│   ├── images/
│   └── fonts/
├── config/
├── database/
├── includes/
├── uploads/
└── vendor/
```

## Security Features

- Password hashing
- CSRF protection
- Input sanitization
- Prepared statements
- Session management

## License

MIT License
