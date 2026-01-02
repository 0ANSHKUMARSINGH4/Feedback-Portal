# Feedback Portal System

A role-based web application built using PHP and MySQL that allows users to submit feedback and administrators to manage users, trainees, and generate reports.

---

## 🔹 Features

### 👤 User Module
- User registration & login
- Submit feedback
- View profile and update details
- Change password
- Secure logout

### 🛠 Admin Module
- Admin dashboard
- View all users & trainees
- View and manage feedbacks
- Generate and download reports (CSV/PDF)
- View signup statistics

---

## 🔹 Tech Stack

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL
- **Server:** XAMPP / Apache
- **Version Control:** Git & GitHub

---

## 🔹 Project Structure

```text
Feedback-Portal/
│── admin_dashboard.php
│── user_dashboard.php
│── login.php
│── signup.php
│── profile.php
│── settings.php
│── feedback_form.php
│── navbar.php
│── style.css
│── main.js
│── uploads/
│── README.md
│── .gitignore
```

---

## 🔹 Installation & Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/0ANSHKUMARSINGH4/Feedback-Portal.git
    ```

2. **Move the project to the XAMPP htdocs folder.**

3. **Create a MySQL database**
    - **Database name:** feedback_system
    - Import the required tables using phpMyAdmin.

4. **Configure database connection**

    ```bash
    // config.php
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "feedback_system";
    ```

5. **Run the project in browser**
    ```bash
    http://localhost/Feedback-Portal
    ```
---

## Live : https://feedbackportal.great-site.net/

---


## 👨‍💻 Author

Ansh Kumar Singh
Final Year B.Tech Student
GitHub: https://github.com/0ANSHKUMARSINGH4
