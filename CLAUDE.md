# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a collaborative calendar application with a PHP backend and HTML/JavaScript frontend. The application is part of the "itmdev" (Ice Time Management Development) system.

We are building an ice time management web app with three types of users:
1. an arena administrator, who manages and distributes ice time available at an ice arena for one or more rinks at the arena
2. a skating program ice scheduler, who receives available ice time from an arena, and uses the web app to choose the ice time they would like to purchase.
3. a skating program customer, who registers for the clinics and ice time the scheduler buys from the arenas

The objective is to create an ice time management app, where two or more people can see events assigned to them and changes to the events in real-time. 
1. There will be any number of users, each owning their own calendar
2. the front end will consist of 
    a) a calendar view with Month, Week, Day, and List views. 
3. Users login to manage and assign their calendar, events, and events assigned to them
4. Any calendar events they create, edit, or delete and assign to another user are broadcast to the other users who have the appropriate permissions.
5. all users can select multiple user calendars with ice time assigned to them


## Architecture

### Backend (PHP)
- **Entry Point**: `index.php` - Checks authentication and redirects appropriately
- **API Router**: `backend/api.php` - Main API controller handling all HTTP requests
- **Authentication**: Cookie-based sessions using "calendar_session" cookie
- **Database**: MySQL database named "itmdev" using PDO with UTF-8
- **Real-time Updates**: Server-Sent Events (SSE) via `backend/workers/sse.php`

### Frontend (HTML/JavaScript)
- Pure JavaScript (no framework)
- Modular JS files: `auth.js`, `events.js`, `users.js`, `import.js`
- Real-time updates via EventSource API
- Cookie-based authentication

## Key Features

1. **Event Management**: CRUD operations on calendar events stored as "episodes"
2. **User System**: Registration, authentication, and user-specific calendars
3. **Import Functionality**: Supports JSON, CSV, and ICS/iCal formats (max 5MB, 20 events)
4. **Real-time Sync**: SSE-based broadcasting for live updates across clients

## Development Setup

Since this is a PHP application without package managers:

1. **Local Development**:
   - Requires PHP 7.4+ with PDO MySQL extension
   - MySQL/MariaDB database
   - Web server (Apache/Nginx) configured to serve PHP
   - Configure database connection in `backend/database/config.php`

2. **Database Setup**:
   - Create MySQL database named "itmdev"
   - Required tables: user, event, episode, calendar_updates, session, role, facility, program, team, resource

## Common Development Tasks

### Testing API Endpoints
```bash
# Login
curl -X POST http://localhost/backend/api.php \
  -H "Content-Type: application/json" \
  -d '{"action":"login","email":"user@example.com","password":"password"}'

# Get events
curl http://localhost/backend/api.php?action=events \
  -H "Cookie: calendar_session=YOUR_SESSION_ID"
```

### Database Queries
The application uses PDO prepared statements. Always use the helper functions in `backend/database/config.php`:
- `getConnection()` - Get PDO instance
- `createUpdateQuery()` - Build UPDATE queries
- `buildWhereClause()` - Build WHERE clauses

## Important Notes

- Authentication is required for all API endpoints except login/register
- Sessions expire after 24 hours (or 30 days with "Remember Me")
- All timestamps should be in 'Y-m-d H:i:s' format
- Error responses use standard HTTP status codes with JSON error messages
- CORS is enabled for API endpoints
- Import worker runs as a separate process to handle large files