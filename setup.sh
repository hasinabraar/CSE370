#!/bin/bash

echo "🚀 Setting up Accident Detection and Hospital Alert System"
echo "=================================================="

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "❌ PHP is not installed. Please install PHP 8.0+ first."
    exit 1
fi

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "❌ Node.js is not installed. Please install Node.js 16+ first."
    exit 1
fi

# Check if MySQL is installed
if ! command -v mysql &> /dev/null; then
    echo "❌ MySQL is not installed. Please install MySQL 8.0+ first."
    exit 1
fi

echo "✅ Prerequisites check passed!"

# Setup Backend
echo ""
echo "📦 Setting up PHP Backend..."
cd backend

# Install Composer dependencies
if [ -f "composer.json" ]; then
    echo "Installing PHP dependencies..."
    composer install
else
    echo "❌ composer.json not found in backend directory"
    exit 1
fi

# Create .env file from example
if [ ! -f ".env" ] && [ -f "env.example" ]; then
    echo "Creating .env file from template..."
    cp env.example .env
    echo "⚠️  Please update the .env file with your database credentials"
fi

cd ..

# Setup Frontend
echo ""
echo "📦 Setting up React Frontend..."
cd frontend

# Install Node.js dependencies
if [ -f "package.json" ]; then
    echo "Installing Node.js dependencies..."
    npm install
else
    echo "❌ package.json not found in frontend directory"
    exit 1
fi

cd ..

# Setup Database
echo ""
echo "🗄️  Setting up Database..."
echo "⚠️  Make sure MySQL is running and you have created a database"
echo "⚠️  Update the .env file with your database credentials before proceeding"

read -p "Do you want to run the database setup script? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Running database setup..."
    php database/setup.php
fi

echo ""
echo "🎉 Setup completed!"
echo ""
echo "📋 Next steps:"
echo "1. Update backend/.env with your database credentials"
echo "2. Start the PHP backend: cd backend && php -S localhost:8000"
echo "3. Start the React frontend: cd frontend && npm start"
echo ""
echo "🌐 The application will be available at:"
echo "   Frontend: http://localhost:3000"
echo "   Backend API: http://localhost:8000"
echo ""
echo "🔑 Demo credentials:"
echo "   User: john@example.com / password"
echo "   Hospital: hospital@citygeneral.com / password"
echo "   Admin: admin@system.com / password"
