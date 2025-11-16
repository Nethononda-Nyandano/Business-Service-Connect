Business-Service-Connect

Connecting small business owners with people who need their services — fast, simple, and community-driven.

A web-based marketplace for service request posting and service provider discovery.
Built with PHP, JavaScript, HTML, and Tailwind CSS CDN.

<p align="center">










</p>
📚 Table of Contents

About the Project

Features

Technology Stack

Screenshots

Folder Structure

Installation

API Overview

Roadmap

Contributing

License

Author

⭐ About the Project

Business-Service-Connect is a platform designed to help small entrepreneurs showcase their services, while allowing users to post service requests such as:

Home repairs

Tailoring

Cleaning

Electrical work

Local gigs

And more

The goal is to make service discovery easy and bring opportunities to small businesses.

🚀 Features
🔍 For Requestors

Post service needs

Search for providers

View provider details

Track sent requests

🛠️ For Service Providers

Create business/service profiles

Respond to job requests

Manage accepted tasks

Showcase skills, pricing, and availability

🧩 Platform-wide Features

Tailwind CSS responsive UI

Backend powered by PHP & PDO

Fast vanilla JS interactions (AJAX/fetch)

Secure login & registration

Modular code design

🧰 Technology Stack
Category	Technology
Frontend	HTML, Tailwind CSS (CDN), JavaScript
Backend	PHP + PDO
Database	MySQL
Environment	XAMPP / Apache
API Style	REST-like PHP endpoints
📸 Screenshots

(Add your actual images inside /screenshots)

/screenshots/home.png
/screenshots/provider-profile.png
/screenshots/request-form.png

Example placeholder:

📁 Folder Structure
Business-Service-Connect/
│
├── assets/
│   ├── logo.png
│   └── images/
│
├── config/
│   └── db.php
│
├── public/
│   ├── index.php
│   ├── login.php
│   ├── register.php
│   ├── request.php
│   └── provider.php
│
├── api/
│   ├── create_request.php
│   ├── get_providers.php
│   ├── auth.php
│   └── update_profile.php
│
├── css/
│   └── tailwind.css (if used locally)
│
├── js/
│   └── app.js
│
└── README.md

🌐 API Overview

A simple REST-like structure using PHP endpoints.

GET /api/get_providers.php

Returns a list of service providers.

POST /api/create_request.php

Creates a new service request.

POST /api/auth.php

Handles login & registration.

PUT /api/update_profile.php

Updates provider information.

(You can ask me to generate full API documentation.)

📦 Installation
1. Clone the Repository
git clone https://github.com/yourusername/Business-Service-Connect.git
cd Business-Service-Connect

2. Import the Database

Open phpMyAdmin

Create database: business_service_connect

Import /database/database.sql

3. Configure Database

Edit /config/db.php:

$host = "localhost";
$db_name = "business_service_connect";
$username = "root";
$password = "";

4. Run the Project

Place the project inside:

xampp/htdocs/


Start Apache & MySQL, then visit:

http://localhost/Business-Service-Connect/public

🧭 Roadmap

 Add messaging/chat

 Add notifications

 Provider verification (ID upload)

 Ratings & reviews

 Admin dashboard

 Mobile app (React Native)

🤝 Contributing

You are welcome to submit:

Bug fixes

UI improvements

Feature enhancements

Open a pull request or issue anytime.

📄 License

This project is under the MIT License — free to use, modify, and distribute.

👤 Author

Nethononda Nyandano
Creator & Full-Stack Developer
📧 (Add your email if you want)
