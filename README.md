# StudentHub Portal - University Academic & Community Web Portal

> **A comprehensive, responsive, accessible, and full-stack semester portal built across Practical Modules 1 through 10.**

---

## 1. Project Overview & Problem Definition

### 1.1 Problem Scope
University students typically navigate fragmented digital tools: examination timetables are posted on static noticeboards, event registrations are handled via disparate spreadsheets, course notes are scattered across private drives, and peer discussions lack a structured, moderated channel.

**StudentHub** provides a centralized, mobile-first, and accessible academic companion that brings together:
- Course syllabus, study notes, and lecture timetables.
- Campus technical workshops, seminars, and hackathons with real-time seat tracking.
- Student discussion forums and peer collaboration.
- Administrative directory management and secure student enrollment.

### 1.2 User Roles & Permissions
1. **Student (Regular User):**
   - Self-registration with client-side & server-side validation.
   - Authentication, profile customization, and skill badges.
   - Course tracking, timetable viewing, and event enrollment with immediate confirmation.
   - Community forum participation, question posting, and portal feedback submission.
2. **Administrator:**
   - Command center metrics (total students, active events, registrations).
   - Student directory searching, filtering by department, and CSV data export.
   - Event creation, schedule management, and capacity moderation.
   - Submissions archive inspection (`data/registrations.csv`, `data/contacts.csv`, `data/feedback.json`).
3. **Faculty / Guest:**
   - Public syllabus browsing, event timetable review, and campus contact access.

---

## 2. Practical 1: Key Questions & Theoretical Analysis

### Question 1: What is a URL and what are its constituent parts?
A **Uniform Resource Locator (URL)** is a standardized string of characters used to identify and locate a specific resource (such as an HTML document, image, or API endpoint) on the World Wide Web.

```
       https://   portal.studentshub.edu   :443   /pages/events.html   ?category=Workshops&sort=date   #registration
       \_____/    \____________________/   \__/   \________________/   \___________________________/   \___________/
      Protocol          Host/Domain        Port          Path                   Query String             Fragment
```

1. **Protocol / Scheme (`https://`):** Declares the communication protocol (HTTPS with TLS encryption on port 443; HTTP on port 80).
2. **Subdomain (`portal.`):** Logical subdivision directing traffic to the portal cluster.
3. **Domain Name / Hostname (`studentshub.edu`):** Human-readable name mapped via DNS to the server's IP address.
4. **Port Number (`:443`):** Specifies the transmission port on the server. Usually omitted if standard (80 for HTTP, 443 for HTTPS).
5. **Path (`/pages/events.html`):** Hierarchical route specifying the physical file or virtual route resource on the web server.
6. **Query String (`?category=Workshops&sort=date`):** Key-value parameter pairs preceded by `?` and separated by `&`, used to pass dynamic filter states to the page or backend script.
7. **Fragment / Anchor Identifier (`#registration`):** Points to a specific element `id` within the document for direct in-page scrolling, without re-requesting the server.

---

### Question 2: How is an HTML file processed by a web browser?
When a browser receives the raw byte stream of an HTML document, the browser rendering engine (e.g., Blink, Gecko, WebKit) executes the following sequence:

```
  HTML Bytes  ──>  Characters  ──>  Tokens  ──>  DOM Nodes  ──>  DOM Tree  ──┐
                                                                            ├──>  Render Tree  ──>  Layout  ──>  Paint  ──>  Composite
  CSS Bytes   ──>  Characters  ──>  Tokens  ──>  CSSOM Nodes ──>  CSSOM Tree ─┘
```

1. **Conversion & Tokenization:** Raw bytes received over HTTP are converted into characters based on the specified encoding (`UTF-8`). The tokenizer parses tags, attributes, and text into discrete tokens (`TagOpen`, `TagClose`, `Attribute`).
2. **DOM Tree Construction:** Tokens are converted into Node objects with relationships, forming the hierarchical **Document Object Model (DOM)** tree.
3. **CSSOM Construction:** Concurrently, linked `<link rel="stylesheet">` and `<style>` rules are parsed into the **CSS Object Model (CSSOM)** tree representing style inheritance.
4. **Render Tree Generation:** Combining visible DOM nodes with their corresponding CSSOM styles. Nodes with `display: none` or `<head>` tags are omitted from the render tree.
5. **Layout (Reflow):** The browser computes precise pixel geometry, dimensions, and coordinates for each render tree element relative to the device viewport.
6. **Paint:** The visual representation (colors, text, borders, box-shadows) is translated into actual pixels across multiple GPU layers.
7. **Compositing:** Layer textures are composited in proper stacking order (z-index) and presented onto the screen.

---

### Question 3: How is page navigation flow managed among all HTML pages?
To ensure consistent navigation, accessibility, and zero broken links across both root and subdirectory contexts:
1. **Hierarchical Directory Linking:** All inner pages are organized within the `pages/` directory (`pages/about.html`, `pages/events.html`, etc.). Links between sibling pages use direct file references (`href="events.html"`).
2. **Root Entry Symmetry:** Both root `index.html` and `pages/index.html` exist, ensuring seamless navigation whether a user accesses the portal from the workspace root or the pages folder.
3. **Breadcrumb Trail:** Every inner page includes an accessible breadcrumb trail (`.breadcrumbs`) informing the student of their current location within the hierarchy (`Home / Events / Details`).
4. **Active State Highlighting:** `js/app.js` runs on `DOMContentLoaded`, inspecting `window.location.pathname` to automatically append `.active` classes to the current header and sidebar navigation links.
5. **Mobile Navigation Drawer:** Responsive hamburger button (`.mobile-menu-toggle`) toggles a slide-out drawer on viewports under 768px.

---

### Question 4: How will GitHub commits be maintained after each practical?
Commits are managed using a structured Git workflow following the **Conventional Commits** specification:

```bash
# Workflow example for each practical:
git checkout -b practical-1-setup
git add .
git commit -m "feat(p1): complete project initiation, sitemap, wireframes, and documentation"
git push origin practical-1-setup

# Merge into main via Pull Request or direct merge:
git checkout main
git merge practical-1-setup
git tag -a v1.0.0-practical-1 -m "Practical 1 Completed: Project Initiation & Setup"
git push origin main --tags
```

#### Commit Convention Matrix:
- `feat(p1): ...` — Project structure, sitemap, wireframes, README setup.
- `feat(p2): ...` — Semantic HTML5 skeleton, accessibility landmarks, skip-link.
- `feat(p3): ...` — Responsive layouts using CSS Grid, Flexbox, and light cream palette.
- `feat(p4): ...` — DOM manipulation: accordion, modal, slider, notification banner, theme switcher.
- `feat(p5): ...` — Registration form regex validation, password strength meter, canvas CAPTCHA.
- `feat(p6): ...` — Fetch API data rendering from external JSON, search, filter, pagination.
- `feat(p7): ...` — PHP form processing, server-side sanitization, CSV and JSON file writing.
- `feat(p8): ...` — MySQL normalized schema (3NF), ER diagram, PDO prepared statements.
- `feat(p9): ...` — Secure user registration with duplicate checks, password_hash, and audit logging.

---

## 3. Architecture & Folder Structure

```
StudentHub-main/
│
├── index.html                   # Root Home Page (Accessible entry point)
├── README.md                    # Practical 1-9 Master Engineering Documentation
│
├── css/
│   └── style.css                # Master Design System (Warm Light Cream Theme, Dark Mode, Responsive Grid)
│
├── js/
│   ├── app.js                   # Practical 4: Hamburger, Theme Switcher, Slider, Modals, Accordion
│   ├── validation.js            # Practical 5: Form Regex, Password Meter, Canvas CAPTCHA
│   ├── events-loader.js         # Practical 6: Fetch API, Search, Filter, Sort, Pagination, Cache
│   ├── faq-loader.js            # Practical 6: FAQ Fetch API & Accordion
│   └── admin-loader.js          # Admin Directory Loader & CSV Data Export
│
├── data/
│   ├── events.json              # 16 Comprehensive Campus Events (Practical 6)
│   ├── students.json            # 16 Student Directory Records (Practical 6)
│   ├── faqs.json                # 16 Categorized Knowledgebase FAQs (Practical 6)
│   ├── registrations.csv        # Practical 7: Form Submissions File Storage (CSV)
│   ├── registrations.json       # Practical 7: Form Submissions File Storage (JSON)
│   ├── contacts.csv             # Practical 7: Contact Form Submissions (CSV)
│   └── feedback.json            # Feedback Form Submissions (JSON)
│
├── php/
│   ├── db.php                   # Practical 8: Secure PDO Database Connectivity & Helper
│   ├── process_register.php     # Practical 7: Server Validation & Safe CSV/JSON File Writing
│   ├── process_contact.php      # Practical 7: Contact Inquiry Handler & CSV Writer
│   ├── process_feedback.php     # Feedback Processor (JSON Storage)
│   ├── view_submissions.php    # Practical 7 Extension: Submissions Viewer Webpage
│   ├── register_secure.php      # Practical 9: PDO Insert, Duplicate Check, password_hash()
│   ├── register_mysqli.php      # Practical 9: MySQLi Prepared Statements Implementation
│   ├── auth.php                 # Practical 10: Secure Session Manager, RBAC, Timeout & Remember-Me
│   ├── login.php                # Practical 10: Unified Secure Authentication & Role Redirection
│   ├── logout.php               # Practical 10: Session Invalidation & Token Cleanup Handler
│   ├── dashboard.php            # Practical 10: Authenticated Student Dashboard & Session Inspector
│   ├── admin.php                # Practical 10: Admin Command Console with Strict RBAC Enforcement
│   └── session_status.php       # Practical 10: Live Session State & Inactivity Monitor API
│
├── database/
│   ├── studenthub.sql           # Practicals 8, 9 & 10: Normalized MySQL 3NF Schema & Seed Data
│   └── er_diagram.md            # Practical 8: Detailed Mermaid ER Model & Cardinality Analysis
│
├── pages/                       # 16 Complete StudentHub Portal Pages
│   ├── index.html               # Home (Campus Highlights Slider & Overview)
│   ├── about.html               # About Us (Mission, Leadership Team, Metrics)
│   ├── register.html            # Registration (Practical 5: Regex, Meter, Canvas CAPTCHA)
│   ├── login.html               # Authentication (Password reveal toggle, Demo credentials)
│   ├── dashboard.html           # Student Dashboard (Stats, Timetable, Join Class Modal)
│   ├── events.html              # Campus Events (Practical 6: Fetch, Filter, Sort, Modal)
│   ├── profile.html             # Student Profile (Edit Profile Modal, Academic Record)
│   ├── contact.html             # Helpdesk Contact Form & Campus Address
│   ├── admin.html               # Administrator Command Console & Student Directory
│   ├── faq.html                 # FAQ Accordion & Category Search
│   ├── feedback.html            # Portal Feedback with Star Rating & Submission
│   ├── courses.html             # Enrolled Modules, Progress Bars & Syllabi
│   ├── forum.html               # Community Discussion Forum & New Post Modal
│   ├── discussion.html          # Thread View, Comments & Upvote Interaction
│   ├── schedule.html            # Weekly Lecture Timetable & Exam Deadlines
│   └── tos.html                 # Terms of Service & Academic Policies
│
├── docs/                        # Practical Documentation & Design Artefacts
│   ├── practical_10_guide.md    # Practical 10: Master Lab Manual, Screenshots Checklist & Viva Guide
│   ├── sitemap.png              # Visual Site Navigation Hierarchy
│   └── wireframe/
│       ├── home-wireframe.png
│       ├── login-wireframe.png
│       ├── events-wireframe.png
│       └── dashboard-wireframe.png
│
└── images/                      # Campus Imagery & High-Resolution Assets
    ├── coding-workshop.jpg       # Modern CS computer laboratory coding session
    ├── campus-hackathon.jpg     # 24-Hour student hackathon event
    ├── tech-seminar.jpg         # Central auditorium keynote presentation
    ├── library-study.jpg        # Sunlit modern campus library study desks
    ├── student-teamwork.jpg     # Outdoor collaborative study group
    ├── robotics-lab.jpg         # IoT & embedded systems hardware workbench
    ├── campus-quad.jpg          # University institute quad architecture
    ├── tech-exhibition.jpg      # Annual project expo & tech showcase
    ├── student-lounge.jpg       # Student innovation & co-working lounge
    ├── campus-events.jpg        # Additional campus event banner
    ├── library-studyjpg.jpg     # Library study archive
    └── students-stairs.avif     # Campus outdoor steps
```

---

## 4. Practical Implementation Matrix

| Practical | Module Description | Key Components Implemented | Verified Files |
|---|---|---|---|
| **Practical 1** | Requirement Analysis, Sitemap, Wireframe & Setup | Scope, 3 Roles, 10+ Pages, 4 Key Questions answered, Sitemap & Wireframes linked. | `README.md`, `docs/sitemap.png`, `docs/wireframe/` |
| **Practical 2** | Semantic HTML5 & Accessibility-Ready Structure | `<header>`, `<nav>`, `<main id="main-content">`, `<section>`, `<aside>`, `<footer>`, `.skip-link`, `.breadcrumbs`, ARIA labels. | All 16 HTML pages in `pages/` |
| **Practical 3** | Responsive UI (CSS Grid, Flexbox, Light Cream Theme) | Mobile-first CSS Grid/Flexbox, Warm white cream palette (`#FAF8F5`, `#FFFFFF`), custom breakpoints (`1024px`, `768px`, `480px`). | `css/style.css` |
| **Practical 4** | JavaScript DOM Manipulation & Interactivity | Accordion FAQ, Modal Popups, Hero Content Slider with Dots/Autoplay, Notification Banner, Hamburger Menu, Theme Switcher with `localStorage`. | `js/app.js`, `css/style.css` |
| **Practical 5** | Registration Form Validation & UX Error Handling | Regex for Name, Email, Mobile (10 digits), Password rules, Confirm Password, Course, Year, Gender, Terms. Password strength meter, HTML5 Canvas CAPTCHA. | `pages/register.html`, `js/validation.js` |
| **Practical 6** | Rendering External JSON using Fetch API | Fetching `events.json`, `students.json`, `faqs.json` (16+ records each), search, category filters, sorting, pagination, offline `localStorage` cache. | `js/events-loader.js`, `js/faq-loader.js`, `data/*.json` |
| **Practical 7** | PHP Form Processing & CSV/JSON File Storage | POST handling, server-side validation & sanitization, file writing with `LOCK_EX` to CSV and JSON, Submissions viewer table. | `php/process_register.php`, `php/process_contact.php`, `php/view_submissions.php` |
| **Practical 8** | MySQL Schema Design, ER Model & PDO Connectivity | 3NF Schema (`students`, `events`, `registrations`, `feedback`, `audit_logs`), Foreign Keys, Seed data, Mermaid ER diagram, secure PDO connection. | `database/studenthub.sql`, `database/er_diagram.md`, `php/db.php` |
| **Practical 9** | Secure Registration, Duplicate Check & Password Hashing | Duplicate check via Prepared Statements, `password_hash($pwd, PASSWORD_BCRYPT)`, audit logging, both PDO and MySQLi implementations. | `php/register_secure.php`, `php/register_mysqli.php` |
| **Practical 10** | Secure Login/Logout, Sessions, RBAC, Timeout & Remember-Me | Secure sessions (`HttpOnly`, `SameSite=Lax`, `Secure`), `password_verify()`, `session_regenerate_id()`, RBAC role redirection, 15m inactivity timeout with live counter, `last_login` timestamp, SHA-256 Remember-Me token in DB, live audit logs. | `php/auth.php`, `php/login.php`, `php/logout.php`, `php/dashboard.php`, `php/admin.php`, `php/session_status.php`, `docs/practical_10_guide.md` |

---

## 5. How to Run & Test

### Option A: Testing Frontend & Client Features (Practicals 1 to 6)
Open any HTML file directly in modern browsers (Chrome, Edge, Firefox), or run a lightweight local web server:
```powershell
# Using Python built-in HTTP server:
python -m http.server 8000
# Then visit: http://localhost:8000/
```

### Option B: Testing Full-Stack PHP, MySQL & Authentication (Practicals 7 to 10)
1. **Start Apache & MySQL:** Launch XAMPP / WAMP / LAMP and start the **Apache** and **MySQL** services.
2. **Move/Symlink Codebase:** Place `StudentHub-main` in your server root (e.g. `C:\xampp\htdocs\StudentHub-main`).
3. **Import Database Schema:**
   - Open **phpMyAdmin** (`http://localhost/phpmyadmin/`).
   - Create database `studenthub` or import `database/studenthub.sql` directly:
     ```powershell
     mysql -u root -p studenthub < database/studenthub.sql
     ```
4. **Test Database Connection:**
   - Open: `http://localhost/StudentHub-main/php/db.php`
   - Response: `{"status": "success", "message": "Database connection established successfully via PDO."}`
5. **Test Form Submissions & File Storage (Practical 7):**
   - Submit registration: `http://localhost/StudentHub-main/pages/register.html`
   - View stored CSV/JSON records: `http://localhost/StudentHub-main/php/view_submissions.php`
6. **Test Secure Authentication & Session Management (Practical 10):**
   - **Login Portal:** `http://localhost/StudentHub-main/php/login.php`
   - **Student Login:** User: `25IT053` | Password: `Password@123` (Redirects to Student Dashboard `php/dashboard.php`).
   - **Admin Login:** User: `admin@studentshub.edu` | Password: `Password@123` (Redirects to Admin Console `php/admin.php`).
   - **Inactivity Timeout Test:** On the Student Dashboard, click **⏳ Test 10-Second Timeout** to watch the countdown tick to 0 and trigger auto-logout redirect to `login.php?msg=timeout`.
   - **Unauthorized Access Test:** In an Incognito window, open `php/dashboard.php` or `php/admin.php` directly without logging in; verify immediate redirection to `login.php?msg=unauthorized`.
   - **RBAC Privilege Test:** While logged in as student `25IT053`, attempt visiting `php/admin.php`; verify access is blocked and redirected to `dashboard.php?msg=admin_denied` with an alert banner.
   - **Logout:** Click **🚪 Sign Out**; verify session destruction and redirection to `login.php?msg=logout`.
   - **Lab Guide & Viva Reference:** View [docs/practical_10_guide.md](docs/practical_10_guide.md) for full screenshots checklist and viva questions.

---

## 6. Design Principles & Palette

- **Background (Main):** `#FAF8F5` (Soft warm light cream / ivory)
- **Cards & Elevators:** `#FFFFFF` (Crisp white for readability and focus)
- **Secondary Surfaces:** `#F3EFEA` (Warm subtle beige)
- **Primary Text:** `#1E293B` (High-contrast slate / dark charcoal)
- **Secondary Text:** `#64748B` (Muted slate for metadata)
- **Borders & Dividers:** `#E8E2D8` (Warm gentle borders)
- **Accent Primary:** `#0D9488` (Refined scholarly teal / emerald)
- **Accent Warning/Prizes:** `#D97706` (Warm amber)
- **Dark Mode Option:** Switchable via the header button (`[data-theme="dark"]`).

---

## 7. License & Academic Attribution
**Student Developer:** Krishna Mangukiya (ID: `25IT053`)  
Created for academic evaluation at Chandubhai S Patel Institute of Technology (CSPIT). All source code, design assets, and practical implementations adhere to educational web engineering standards.
