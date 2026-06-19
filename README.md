# ProCook

ProCook is a PHP-based recipe management and sharing platform that allows users to explore, save, and manage recipes. The platform features user authentication (including Google OAuth), recipe interactions, and much more.

## Features

* **User Authentication:** Secure sign-up, login, and password recovery.
* **Google OAuth Integration:** Quick and easy sign-in via Google.
* **Recipe Management:** Create, view, update, and delete recipes.
* **User Dashboard:** Personalized space for users to manage their saved recipes and settings.
* **Admin Panel:** Administrative tools for managing platform content and users.

---

## Prerequisites

Before you begin, ensure you have the following installed on your local machine:

* **PHP** (Version 7.4 or higher recommended)
* **MySQL / MariaDB**
* **Composer** (PHP dependency manager)
* A local server environment like **XAMPP**

---

## Getting Started & Installation

Follow these steps to set up the project locally:

### 1. Clone the Repository
```bash
git clone https://github.com/jnbrv/procooknew.git
cd procooknew
```

### 2. Install Dependencies
Run Composer to install the required backend packages (e.g., PHPMailer, Google API Client):

```bash
composer install
```

### 3. Database Setup
Open your database management tool (like phpMyAdmin).

Create a new database named procook.

Import the database schema file (look for a .sql file inside the project, typically in admin1/ or an includes/ directory if available).

### 4. Environment Configuration
Create a configuration file (or update your existing database connection file inside includes/) with your environment credentials:

* **Database Credentials:** Host, username, password, and database name.
* **SMTP Settings:** Set your mail server details for the password reset and sign-up verification features.
* **Google OAuth Credentials:** Add your Google Client ID and Secret to login.php and google-callback.php.

### 5. Run the Application
Move the project folder to your local server's root directory (e.g., htdocs for XAMPP) and access it via your browser:

```
http://localhost/procooknew/index.php
```

---

## Project Structure

* `/admin1` - Admin dashboard views and management scripts.
* `/css` & `/js` - Frontend stylesheets and JavaScript interactions.
* `/includes` - Core backend utilities, database connections, and helper functions.
* `index.php` - The main landing page of the application.
* `login.php` & `sign-up.php` - User authentication handling.

---
