<div align="center">

# 🎟️ EventHub

### Event Management & Registration System

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge\&logo=php\&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge\&logo=mysql\&logoColor=white)
![XAMPP](https://img.shields.io/badge/XAMPP-FB7A24?style=for-the-badge\&logo=xampp\&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge\&logo=html5\&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge\&logo=css3\&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge\&logo=javascript\&logoColor=black)

A full-stack **Event Management System** built using **PHP, MySQL, HTML, CSS, and JavaScript**.

</div>

---

# 📌 Overview

**EventHub** is a web-based event management application developed as a **college project**.

It demonstrates core full-stack development concepts including:

* CRUD operations
* User registration systems
* Admin dashboards
* Database integration
* Secure authentication

Users can **browse and register for events**, while administrators manage the events and view registrations through a dedicated admin dashboard.

---

# ✨ Features

## 👤 User Side

* Browse available events
* Register for events
* View booking confirmation
* Simple event listing interface

## 🛠️ Admin Panel

* Secure admin login
* Create events
* Edit events
* Delete events
* View all user bookings
* Dark themed dashboard

## 🔒 Security

* CSRF protection for forms
* Input validation and sanitization
* Session-based authentication

---

# 🖼️ Screenshots

| Page               | Preview                               |
| ------------------ | ------------------------------------- |
| Home / Events Page | ![Home](screenshots/home.png)         |
| Event Registration | ![Register](screenshots/register.png) |
| Admin Dashboard    | ![Admin](screenshots/admin.png)       |

*(Place these images inside a folder called `screenshots` in the repository)*

---

# 🧰 Tech Stack

| Layer    | Technology              |
| -------- | ----------------------- |
| Frontend | HTML5, CSS3, JavaScript |
| Backend  | PHP                     |
| Database | MySQL                   |
| Server   | Apache (XAMPP)          |

---

# ⚙️ Installation & Setup

## 1️⃣ Clone the repository

```
git clone https://github.com/ragnarabhi/Repo1.git
```

---

## 2️⃣ Move the project to XAMPP web directory

Windows:

```
move Repo1 C:\xampp\htdocs\eventhub
```

---

## 3️⃣ Import the database

1. Start **Apache** and **MySQL** from XAMPP
2. Open:

```
http://localhost/phpmyadmin
```

3. Create a database named:

```
eventhub
```

4. Import:

```
eventhub.sql
```

---

## 4️⃣ Configure database connection

Open:

```
config/db.php
```

Example configuration:

```php
<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "eventhub";
?>
```

---

## 5️⃣ Run the project

Open your browser and visit:

```
http://localhost/eventhub
```

Admin panel:

```
http://localhost/eventhub/admin
```

---

# 📁 Project Structure

```
eventhub/
│
├── admin/
│   ├── index.php
│   ├── events.php
│   └── bookings.php
│
├── config/
│   └── db.php
│
├── includes/
│
├── css/
├── js/
│
├── screenshots/
│   ├── home.png
│   ├── register.png
│   └── admin.png
│
├── index.php
├── register.php
├── eventhub.sql
└── README.md
```

---

# 🗄️ Database

Main tables used in the system:

| Table    | Description                    |
| -------- | ------------------------------ |
| events   | Stores event information       |
| bookings | Stores user registrations      |
| admin    | Stores admin login credentials |

---

# 👨‍💻 Author

**Abhishek Ningaraju (Abhi)**

BCA Student | Developer | Tech Enthusiast

GitHub:
https://github.com/ragnarabhi

---

# 📄 License

This project was developed as part of a **college academic project** and is shared for educational and learning purposes.

---

<div align="center">

⭐ If you like this project, consider giving the repository a star.

</div>
