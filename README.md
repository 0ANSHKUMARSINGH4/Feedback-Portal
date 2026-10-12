# SNTI Feedback Portal System

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://www.php.net/)
[![Database](https://img.shields.io/badge/Database-MySQL%20%7C%20SQLite-orange.svg)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Deploy to Render](https://render.com/images/deploy-to-render-button.svg)](https://render.com)

A modern, role-based web application developed for **Shavak Nanavati Technical Institute (SNTI, Tata Steel)**. The portal facilitates seamless trainee academic verification, confidential structured training feedback, and comprehensive administrator analytics with multi-section CSV reporting.

---

## 🌟 Key Features

### 👤 Trainee Module
* **Account Registration & Authentication:** Secure registration and login with bcrypt-hashed passwords and session hardening.
* **Academic Verification (Step 1):** Submit engineering discipline (CSE, EE, Mechanical, etc.), university affiliation, contact info, and trainee ID verification.
* **Structured Training Evaluation (Step 2):** Interactive 5-star rating widget evaluating session quality, instructor engagement, lab equipment access, industry relevance, and schedule organization.
* **Personal Profile & Settings:** Manage profile avatars, email preferences, password updates, and account lifecycle.

### 🛠️ Administrator Module
* **Analytics Dashboard:** Live metrics tracking total feedback responses, verified trainees, signup accounts, and overall average satisfaction scores.
* **Audit & Management:** Detailed inspection tables for all trainee accounts with role-based filtering.
* **Multi-Section CSV Reporting:** 1-click export of consolidated training audit logs, trainee directories, and evaluation responses.

### 🛡️ Security & Reliability Architecture
* **Cross-Site Request Forgery (CSRF) Protection:** Cryptographically secure token validation across every POST request.
* **SQL Injection Prevention:** 100% prepared PDO statements with parameter binding.
* **Cross-Site Scripting (XSS) Mitigation:** Output sanitization using `htmlspecialchars()`.
* **Zero-Configuration Hybrid Database:** Native support for MySQL with automatic SQLite fallback for serverless/containerized deployments (e.g. Render).

---

## 🏗️ Tech Stack

* **Frontend:** HTML5, Modern Vanilla CSS (Glassmorphism, CSS Custom Properties, Responsive Grids), JavaScript
* **Backend:** PHP 8.2+ (PDO MySQL / PDO SQLite)
* **Web Server:** Apache / XAMPP / Docker
* **Deployment Platform:** Render (Docker containerized)

---

## 🚀 Quick Start (Local Setup via XAMPP)

1. **Clone the Repository:**
   ```bash
   git clone https://github.com/0ANSHKUMARSINGH4/Feedback-Portal.git
   cd Feedback-Portal
   ```

2. **Move to XAMPP htdocs:**
   Place the project folder inside your `C:\xampp\htdocs\` directory.

3. **Database Setup (Optional - MySQL):**
   * Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
   * Create a database named `feedback_system`.
   * Import `schema.sql`.
   *(Note: If MySQL is not running, the application will automatically initialize an SQLite database so it works out-of-the-box!)*

4. **Launch the Portal:**
   Visit `http://localhost/feedback_portal/` in your browser.

---

## 🌐 Deploy to Render

Deploying this portal to Render is fully automated using the included `Dockerfile` and `render.yaml`:

1. Fork or push your changes to your GitHub repository.
2. Log in to [Render](https://dashboard.render.com/).
3. Click **New +** > **Web Service**.
4. Connect your GitHub repository `Feedback-Portal`.
5. Render will automatically detect the `Dockerfile`:
   * **Runtime:** Docker
   * **Instance Type:** Free
   * **Region:** Any (e.g., Oregon or Frankfurt)
6. Click **Deploy Web Service**!

Your live site will be ready at `https://<your-service-name>.onrender.com` without risk of sudden inactivity suspensions.

---

## 🔑 Default Administrator Credentials

For immediate testing and grading:
* **Email:** `admin@feedbackportal.local`
* **Password:** `Admin@12345`

---

## 👨‍💻 Author

**Ansh Kumar Singh**  
GitHub: [@0ANSHKUMARSINGH4](https://github.com/0ANSHKUMARSINGH4)
