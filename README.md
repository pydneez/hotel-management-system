

# **RoyalStay Hotel Management System (HMS)**

## 🚀 **Overview**

**RoyalStay HMS** centralizes and streamlines hotel operations through a fully web-based, highly optimized management platform. Designed beyond basic CRUD, this system is built for:

* **High-volume transactions** (millions of records)
* **Sub-second financial analytics**
* **Strict data integrity** via stored procedures & triggers
* **Modern operational workflows** for hotels & accommodations

---

## 🛠️ **Tech Stack**

| Layer           | Technology                                             |
| --------------- | ------------------------------------------------------ |
| **Backend**     | PHP 8.x (Native)                                       |
| **Database**    | MySQL 8.0 (InnoDB, triggers, views, stored procedures) |
| **Frontend**    | HTML5, CSS3, JavaScript (AJAX)                         |
| **Environment** | XAMPP, MAMP, WAMP                                      |


---

## 📸 **UI Preview**

<details>
<summary><strong> Index Page</strong></summary>
<br>
<img src="https://github.com/user-attachments/assets/f7edbc4a-affe-43d8-a97e-6964b862898e" width="700"/>
</details>

<details>
<summary><strong> Guest Dashboard</strong></summary>
<br>
<img src="https://github.com/user-attachments/assets/5dd613e5-7e98-468d-b5bb-3213126f641d" width="700"/>
</details>

<details>
<summary><strong> Admin Panel</strong></summary>
<br>
<img src="https://github.com/user-attachments/assets/2ef9a76f-9e77-4f90-8b82-663d87304801" width="700"/>
</details>

<details>
<summary><strong> Check-In</strong></summary>
<br>
<img src="https://github.com/user-attachments/assets/17b3dc92-ccc8-4e8f-b62a-a1f4fc66ccd1" width="700"/>
</details>

<details>
<summary><strong> Admin Analytics</strong></summary>
<br>
<img src="https://github.com/user-attachments/assets/9054a0ed-1752-4682-bf97-f4deb4ae79e5" width="700"/>
</details>

---

## 📚 **Table of Contents**

* [🚀 Overview](#-overview)
* [🛠️ Tech Stack](#️-tech-stack)
* [✨ Features](#-features)
* [💾 Database Architecture](#-database-architecture)
* [🔮 Future Improvements](#-future-improvements)
* [⚙️ Installation](#️-installation)
* [📜 License](#-license)

---

## ✨ **Features**

<summary><strong>🏨 Guest Portal</strong></summary>

* Real-time booking engine
* Guest dashboard with booking history
* Reservation Cancellation (24-hour rule enforced)
* Secure registration + login (hashed passwords)


<summary><strong>🛡️ Admin & Operations</strong></summary>

* Role-Based Access Control (Admin / Staff)
* Real-time operations dashboard
* Room availability management
* Arrival/departure overview
* Atomic check-in/check-out via MySQL transactions
* High-performance financial analytics


<summary><strong>📈 Analytics & Reporting</strong></summary>

* Aggregates millions of invoice records
* Covering indexes for zero full-table scans
* Financial summaries
* Occupancy trends
* Revenue breakdowns

---

## 💾 **Database Architecture**

<summary><strong>🧩 Stored Procedures</strong></summary>

* `sp_CreateGuestReservation`
* `sp_CheckInGuest`
* `sp_CheckOutGuest`
* `sp_GenerateFinancialReport`


<summary><strong>⛓️ Triggers</strong></summary>

* Auto-calculate total stay cost before reservation insert
* Auto-update room status on check-in/out
* Audit logging


<summary><strong>🔍 Views</strong></summary>

* `v_ReservationDetails`
* `v_GuestBookings`
* `v_FinancialSummary`



## 🔮 **Future Improvements**


* <summary><strong>📱 Responsive UI</strong> : Mobile-first redesign for staff & guests.</summary> 
* <summary><strong>💳 Payment Integration</strong> : Stripe / PayPal real-time transactions.</summary> 
* <summary><strong>📨 Email Notifications</strong> :  Booking confirmations, cancellation notices, invoices.</summary> 
* <summary><strong>🧹 Housekeeping Module</strong> : Live updates for cleaning staff via mobile.</summary> 

---

## ⚙️ **Installation**

```bash
git clone https://github.com/pydneez/hotel-management-system.git
```

1. Import **hotel-management-system.sql** into MySQL with database called "hotel-management-system"
2. Update `connect.php` with your database credentials
3. Ensure `/uploads` directory is writable
4. Run via Apache (XAMPP/MAMP/WAMP)

---

## 📜 **License**

This project is developed as a **CSS326 Database Programmng Laboratory Capstone Project**

