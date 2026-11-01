# RepairDesk — Electronics Repair Shop Job Tracker

A complete, modern, responsive frontend application for electronics repair shop management, built with pure **HTML5**, **CSS3**, and vanilla **JavaScript**.

---

## 📁 Project Folder Structure

```
RepairDesk/
├── index.html                   # Marketing Landing Page (Hero, Features, Workflow, Stats, CTA)
├── README.md                    # Project Documentation & Architecture
├── .gitignore                   # Standard Git Ignore Configuration
├── components/                  # Reusable Partial References
│   ├── navbar.html              # Public Navigation Header Snippet
│   └── footer.html              # Public Footer Snippet
├── css/
│   ├── main.css                 # CSS Reset, Design Tokens, Variables & Utility Classes
│   ├── navbar.css               # Public Navigation Bar Styles & Responsive Drawer
│   ├── footer.css               # Public Multi-Column Footer Styles
│   └── dashboard.css            # Workbench Dashboard Layout, Sidebar, Modals, Tables & Badges
├── js/
│   ├── main.js                  # Public Interactions: Mobile Nav, Accordions, Pricing Toggle, SPA Router
│   └── dashboard.js             # Dashboard Data Engine: LocalStorage Store, Role Switcher, Job Code Generator
├── pages/
│   ├── about.html               # About Us ("A service desk with people behind it")
│   ├── help.html                # Help Center (Search, Categories, Accordion FAQs, Support Form)
│   ├── pricing.html             # Transparent Pricing (Monthly/Annual Toggle, Tiers & Feature Matrix)
│   ├── login.html               # Multi-Role Sign In (Admin & Technician Quick-Fill Demo Chips)
│   ├── signup.html              # Workshop Registration & Data Storage Consent Form
│   └── track.html               # Customer Status Portal (6-Char Code / QR, Stepper, Quote Approval, Warranty)
└── dashboard/
    ├── index.html               # Workshop Overview (KPI Metrics, Active Jobs Table, Tech Workloads)
    ├── jobs.html                # Jobs Management (Pending / Completed / Cancelled Tabs, Create Job Modal)
    ├── job-detail.html          # Single Job Lifecycle (Stepping Rules, Diagnostics Quote, Notes, QR Sticker)
    ├── technicians.html         # Technician Roster (Workload Meters, Account Provisioning, Deactivation)
    ├── customers.html           # Customer Directory (Contact Records, Repair History, Consent Logs)
    ├── reports.html             # Analytics & Financials (Revenue, Turnaround Speed, CSS Bar Charts)
    └── settings.html            # Shop Settings (30-Day Unclaimed Terms, Resend Email Triggers, RBAC & 2FA)
```

---

## 🚀 Key Feature Highlights

### 1. Customer Tracking Experience (`pages/track.html`)
- **No account needed**: Customers access their repair status via a unique **6-character non-sequential code** (e.g., `B9X2K4`) or QR link (look-alike characters `0/O` and `1/I/L` excluded).
- **Interactive Quote Approval**: Customers review diagnostic itemization (diagnostic fee, replacement parts, labor) and click **Approve** or **Decline**.
- **Customer Progress Log**: Displays timestamped technician notes tagged as "Customer-Visible".
- **Contact Technician Modal**: Clean inquiry form that conceals technician email addresses and copies shop management.
- **Warranty Claim Activation**: Automatically unlocks once the repair reaches the `Collected` status.

### 2. Workshop Workbench & Role-Based Access (`dashboard/`)
- **Role Switcher**: Switch on-the-fly between **Shop Admin (Elena Corpuz)** and **Technician (Danilo Mendoza)** from the sidebar user menu to test both perspectives:
  - **Technician View**: Automatically filters job lists to assigned repairs; permits moving jobs backward by only 1 status step.
  - **Admin View**: Global visibility over all jobs, technician workload management, full lifecycle override, and collection release authorization.
- **3-Tab Lifecycle Architecture**:
  - `Pending`: All active items currently on the shop workbench.
  - `Completed`: Jobs marked `Collected` with active warranty countdowns.
  - `Cancelled`: `Declined`, `Cancelled`, and `Cannot Be Repaired` jobs.
- **Intake & QR Sticker**: Generates printable 3" x 2" item stickers with Job ID, Customer details, and intake timestamp.
- **Unclaimed Property Tracker**: Tracks 30-day ready collection window before daily storage fees or 1-year liquidation terms apply.

---

## 💻 Running the Application

Because this project uses vanilla HTML, CSS, and JS with zero external bundler dependencies, you can run it immediately using any local web server or directly in the browser:

```bash
# Option 1: Python HTTP Server (recommended for seamless relative paths)
cd RepairDesk
python -m http.server 8000

# Open in your browser:
# http://localhost:8000/
# http://localhost:8000/pages/track.html
# http://localhost:8000/dashboard/index.html
```
