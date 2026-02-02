# Level Life ⚔️🛡️

**Level Life** is a gamified productivity platform that transforms daily tasks, habits, and schedules into an engaging RPG experience. 

Built with **Laravel 11** and **Vue 3 (Inertia.js)**, this project demonstrates a **modern monolithic architecture** that combines the SEO and routing simplicity of a backend framework with the reactivity of a client-side SPA.

![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?logo=laravel)
![Vue](https://img.shields.io/badge/Vue.js-3-4FC08D?logo=vue.js)
![Inertia](https://img.shields.io/badge/Inertia.js-Monolith-purple)
![Tailwind](https://img.shields.io/badge/Tailwind-CSS-38B2AC?logo=tailwind-css)
![Status](https://img.shields.io/badge/status-active-success)

![Dashboard Screenshot]<img width="1893" height="958" alt="Screenshot 2026-02-02 113011" src="https://github.com/user-attachments/assets/24eaa598-43c6-4840-83d4-de0a8519a2b1" />
![Leaderboard Screenshot]<img width="1876" height="952" alt="Screenshot 2026-02-02 114258" src="https://github.com/user-attachments/assets/34431eee-8ef0-434e-aa50-b04d625229ad" />

## 💡 Philosophy & The Problem

Productivity often fails due to a lack of **consistency** and the feeling of being overwhelmed. Level Life tackles this by enforcing a **"Start Small"** philosophy:

1.  **Immediate Feedback:** Every small task completed gives instant XP and Gold, hacking the brain's reward system.
2.  **Daily Awareness:** The system encourages users to focus on *today's* target via Time Blocks and limited Quests, preventing burnout.
3.  **Frictionless Consistency:** Gamified streaks and "freeze" items allow users to be human (miss a day) without losing their momentum entirely.

## 🏗️ Technical Highlights

This project focuses on **High-Performance Monolith** patterns, minimizing database costs while ensuring a snappy frontend experience.

### 1. Modern Monolithic Architecture (Inertia.js)
Unlike traditional API-based stacks (which require separate routing and state syncing), Level Life uses **Inertia.js** to create a tightly coupled Fullstack environment:
- **Shared Validation:** Backend validation logic is automatically reflected in the Frontend UI without duplicating code.
- **Server-Driven Routing:** Retains the security and simplicity of Laravel routes while delivering a seamless SPA (Single Page Application) user experience.
- **Zero API Overhead:** Eliminates the need for REST/GraphQL serialization for internal views, speeding up development and response times.

### 2. High-Efficiency Shared Caching (Backend Optimization)
Calculating the leaderboard involves heavy aggregation. To minimize Database Read Units (RU):
- **Shared Cache Strategy:** The global leaderboard is computed once and cached with a 24-hour TTL.
- **Derived State:** The dashboard does **not** query the database for "My Rank." Instead, it performs an efficient in-memory lookup within the cached global roster.
- **Event-Driven Invalidation:** The cache is strictly rebuilt only when a relevant write event (e.g., Quest Completion) occurs.

### 3. Robust Streak & "Freeze" Logic
The application implements a forgiving streak algorithm:
- **Gap Walking:** The backend calculates streaks by traversing usage history backwards.
- **Automated Recovery:** It intelligently detects gaps in daily activity and automatically consumes "Freeze" items to repair broken streaks, handling complex edge cases like weekly boundaries.

### 4. Data Integrity & Audit Trails
- **Immutable Ledger:** `quest_completions` and `treasury_purchases` act as permanent history logs. Even if a Quest is deleted, the historical XP/Coin data remains accurate.
- **Atomic Transactions:** Critical actions (XP gain, Item purchase) are wrapped in database transactions to prevent race conditions.

## 🛠️ Tech Stack

**Backend**
- **Framework:** Laravel 11 (PHP 8.2)
- **Database:** MySQL / TiDB Compatible
- **Auth:** Laravel Breeze
- **Caching:** File Driver (Tag-based invalidation)

**Frontend**
- **Framework:** Vue.js 3 (Composition API)
- **Glue:** Inertia.js (Server-driven SPA)
- **Styling:** Tailwind CSS

## 🌟 Key Features

### 🎮 Gamification Engine
- **Quests:** Todo, In-Progress, and Done states. Supports daily repeatable tasks.
- **Economy:** Earn coins from productivity, spend them on real-life rewards.
- **Leveling:** Exponential XP curve ($level^{1.5} \times 100$).

### 📅 Productivity Tools
- **Habit Tracker:** Visual heatmaps and streak counting.
- **Time Blocking:** Strict scheduling validation to manage the day efficiently.
- **Journaling:** Markdown-supported entries with templates and mood tracking.

### 🏆 Social System
- **Rivalry:** Automatically identifies the user directly above you in the leaderboard.
- **Badges:** System-awarded achievements for milestones (Consistency, Recovery, etc.).

## 🗄️ Database Schema & Architecture

The database is designed with **Strict Normalization** for core entities and **Snapshot Logic** for historical logs to ensure auditability.

### 1. Core & Gamification State
* **`users`**: Standard authentication table (Laravel default).
* **`profiles`**: A dedicated 1:1 extension table holding volatile gamification state (`xp_total`, `coin_balance`, `current_streak`, `freeze_stats`).
    * *Why separate?* Keeps the auth table lean and isolates high-write game logic from read-heavy auth logic.

### 2. The Quest Engine (Ledger Pattern)
* **`quests`**: Definitions of tasks, including rewards (`xp_reward`, `coin_reward`) and types (Daily, Boss).
* **`quest_completions`**: **(Immutable Ledger)** Records *when* a quest was done.
    * *Key Feature:* It snapshots the `xp_awarded` and `coin_awarded` at the moment of completion. This ensures historical data remains accurate even if the original Quest is edited or rebalanced later.

### 3. Virtual Economy
* **`treasury_rewards`**: Shop items defined by the user (e.g., "1 Hour Gaming").
* **`treasury_purchases`**: **(Immutable Ledger)** Transaction history for spending coins.
    * *Key Feature:* Preserves `unit_cost_coin` at the time of purchase to calculate accurate spending history.

### 4. Consistency Tracking
* **`habits`** & **`habit_entries`**: Tracks daily binary completions. Logic handles streak calculation dynamically based on entry gaps.
* **`time_blocks`**: Stores daily schedules with strict `start_time` vs `end_time` validation.

### 5. Achievement System
* **`badges`**: System-defined milestones (e.g., "7 Day Streak", "Comeback Kid").
* **`user_badges`**: Many-to-Many pivot table tracking when a user earns a specific badge.

## ⚡ Installation

1.  **Clone the repository**
    ```bash
    git clone [https://github.com/arulzkash/level-life.git](https://github.com/arulzkash/level-life.git)
    cd level-life
    ```

2.  **Install Dependencies**
    ```bash
    composer install
    npm install
    ```

3.  **Environment Setup**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4.  **Database Migration & Seeding**
    Run migration and populate the default badge system:
    ```bash
    php artisan migrate
    php artisan badges:seed
    ```

5.  **Run Development Server**
    ```bash
    # Terminal 1 (Laravel)
    php artisan serve

    # Terminal 2 (Vite)
    npm run dev
    ```

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

**Author:** [Arulzkash]
**Deployed App:** [https://levellife.my.id](https://levellife.my.id)
