# Accident Detection and Nearest Hospital Alert System

A full-stack web application for detecting accidents and automatically alerting the nearest hospitals with ambulance availability.

## 🚀 Features

- **Authentication System**: JWT-based login/signup with role-based access (User, Hospital, Admin)
- **Accident Detection**: Log accidents with location, time, and severity
- **Nearest Hospital Alert**: Automatically find and alert the nearest hospital using Haversine formula
- **Real-time Notifications**: Send alerts to hospitals and users involved
- **Dashboard Analytics**: Filter, sort, and view accident statistics with charts
- **Hospital Management**: Track ambulance availability and hospital locations
- **Car Registration**: Link cars to users with sensor status monitoring

## 🛠️ Tech Stack

- **Backend**: PHP (RESTful API, MVC structure, JWT authentication)
- **Frontend**: React (functional components, hooks, Tailwind CSS)
- **Database**: MySQL (optimized with indexes for performance)
- **Authentication**: JWT tokens

## 📁 Project Structure

```
accident-detection-system/
├── backend/                 # PHP Backend
│   ├── api/                # REST API endpoints
│   ├── config/             # Database and JWT configuration
│   ├── controllers/        # Business logic controllers
│   ├── models/            # Database models
│   ├── middleware/        # Authentication middleware
│   └── index.php          # Main entry point
├── frontend/              # React Frontend
│   ├── public/
│   ├── src/
│   │   ├── components/    # Reusable components
│   │   ├── pages/         # Page components
│   │   ├── services/      # API services
│   │   ├── hooks/         # Custom hooks
│   │   └── utils/         # Utility functions
│   └── package.json
├── database/              # Database schema and sample data
└── README.md
```

## 🚀 Quick Start

### Prerequisites
- PHP 8.0+
- Node.js 16+
- MySQL 8.0+
- Composer (for PHP dependencies)

### Backend Setup

1. **Install PHP dependencies:**
   ```bash
   cd backend
   composer install
   ```

2. **Configure database:**
   - Copy `config/database.example.php` to `config/database.php`
   - Update database credentials

3. **Run database migrations:**
   ```bash
   php database/setup.php
   ```

4. **Start PHP server:**
   ```bash
   php -S localhost:8000
   ```

### Frontend Setup

1. **Install Node.js dependencies:**
   ```bash
   cd frontend
   npm install
   ```

2. **Start React development server:**
   ```bash
   npm start
   ```

3. **Access the application:**
   - Frontend: http://localhost:3000
   - Backend API: http://localhost:8000

## 🔐 Authentication

The system uses JWT tokens for authentication. Users can register with different roles:

- **User**: Can register cars and view their accident reports
- **Hospital**: Can view and update accident status, manage ambulance availability
- **Admin**: Full access to all features and system management

## 📊 API Endpoints

### Authentication
- `POST /api/auth/register` - User registration
- `POST /api/auth/login` - User login
- `GET /api/auth/profile` - Get user profile

### Accidents
- `POST /api/accidents` - Log new accident
- `GET /api/accidents` - Get accidents (with filters)
- `PUT /api/accidents/{id}` - Update accident status
- `GET /api/accidents/statistics` - Get accident statistics

### Hospitals
- `GET /api/hospitals` - Get all hospitals
- `PUT /api/hospitals/{id}/ambulance` - Update ambulance availability

### Cars
- `POST /api/cars` - Register new car
- `GET /api/cars` - Get user's cars

## 🗄️ Database Schema

### Core Tables
- `users` - User accounts and authentication
- `cars` - Registered vehicles with sensor status
- `hospitals` - Hospital locations and ambulance availability
- `accidents` - Accident records with location and severity
- `notifications` - Alert notifications sent to hospitals

### Indexes for Performance
- Location-based queries (latitude, longitude)
- Time-based queries (accident_time)
- Status-based queries (accident_status)
- User-based queries (owner_id, car_id)

## 🎯 Key Features

### Accident Detection
- Log accidents with GPS coordinates
- Automatic nearest hospital calculation
- Real-time ambulance availability check
- Multi-hospital notification system

### Dashboard Analytics
- Daily accident statistics
- Filtering by time, location, severity
- Sorting by various criteria
- Visual charts and trends

### Hospital Management
- Location-based hospital search
- Ambulance availability tracking
- Accident status updates
- Notification management

## 🔧 Configuration

### Environment Variables
- Database connection settings
- JWT secret key
- API endpoints
- Notification service settings

### Performance Optimization
- Database indexes for fast queries
- Pagination for large datasets
- Caching for frequently accessed data
- Optimized distance calculations

## 📝 License

This project is licensed under the MIT License.
