# Level Life - Gamified Productivity System ⚔️

<img width="1892" height="954" alt="Screenshot 2026-01-17 100207" src="https://github.com/user-attachments/assets/9011f54c-4c54-49d3-b282-a387c41a8880" />


**Level Life** is a web-based productivity application that transforms daily tasks, habits, and schedules into an engaging RPG-like experience. Built with **Laravel 11** and **Vue 3 (Inertia.js)**, this project demonstrates a monolithic architecture focused on handling business logic for gamification, virtual economy, and time management.

## 🚀 Overview

The core problem this application solves is "productivity friction." By adding immediate feedback loops (XP, Gold, Level Ups) to mundane tasks, it encourages user consistency.

As a **Backend-focused project**, the highlight lies in the logic governing the economy (earning/spending), streak calculations, and state management for repeatable quests versus one-time missions.

## 🛠️ Tech Stack

* **Backend Framework:** Laravel 11 (PHP 8.2)
* **Frontend:** Vue.js 3, Inertia.js (Monolith)
* **Database:** MySQL / TiDB (Compatible)
* **Styling:** Tailwind CSS
* **Build Tool:** Vite
* **Authentication:** Laravel Breeze

## 🌟 Key Features & Architecture

### 1. Gamification Engine (Backend Logic)
* **Leveling System:** Automatic level calculation based on accumulated XP thresholds (exponential curve).
* **Economy System:** A transaction-based logic where users earn coins via Quests and spend them in the Treasury.
* **State Management:** Quests support complex states (`todo`, `in_progress`, `locked`, `done`) and `repeatable` logic (resetting daily).

### 2. Habit Tracking & Consistency
* **Streak Algorithm:** Backend logic to calculate current and longest streaks based on daily completion logs.
* **History Logs:** Tracks every completion by date (`habit_entries`), allowing for historical data visualization.
* **Active Scope:** Efficient querying to only show habits relevant to the current date.

### 3. Time Management
* **Time Blocks:** A dedicated module for scheduling day-to-day activities with specific start/end times (`time_blocks` table), distinct from the gamified quest system.

### 4. Data Integrity & Logging
* **Audit Trails:** Dedicated `quest_completions` and `treasury_purchases` tables ensure that every XP gained and Coin spent is permanently recorded for history and balancing analysis.

## 🗄️ Database Schema

The application relies on a robust relational database structure designed for scalability and data integrity.

### Core Tables
* **`users`**: Core authentication (Standard Laravel).
* **`profiles`**: Gamification stats linked 1:1 to User (`xp_total`, `coin_balance`, `current_streak`, `last_quest_completed_at`).

### Quest System
* **`quests`**: Task definitions. Attributes: `status`, `type` (Daily, Boss, etc), `xp_reward`, `coin_reward`, `is_repeatable`.
* **`quest_completions`**: Ledger table for completed quests. Tracks `xp_awarded`, `coin_awarded`, and `completed_at` for history.

### Habit System
* **`habits`**: Habit definitions (`start_date`, `end_date`).
* **`habit_entries`**: Daily logs. Records `date` and `note` for each habit completion to calculate streaks.

### Economy System
* **`treasury_rewards`**: Items available for purchase in the shop (`cost_coin`). Supports Soft Deletes.
* **`treasury_purchases`**: Transaction history. Records `qty`, `unit_cost_coin`, and total `cost_coin` at the time of purchase.

### Scheduling
* **`time_blocks`**: Daily schedule records (`start_time`, `end_time`, `title`).

## ⚙️ Installation & Setup

1.  **Clone the repository**
    ```bash
    git clone [https://github.com/yourusername/level-life.git](https://github.com/yourusername/level-life.git)
    cd level-life
    ```

2.  **Install PHP Dependencies**
    ```bash
    composer install
    ```

3.  **Install Node Dependencies**
    ```bash
    npm install
    ```

4.  **Environment Setup**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
    *Configure your database credentials in the `.env` file.*

5.  **Database Migration**
    ```bash
    php artisan migrate
    ```

6.  **Run the Application**
    ```bash
    # Terminal 1 (Backend)
    php artisan serve

    # Terminal 2 (Frontend Hot Reload)
    npm run dev
    ```

---

**Author:** [Arulzkash]
**Deployed App:** [https://levellife.my.id](https://levellife.my.id)
