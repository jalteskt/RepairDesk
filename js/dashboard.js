/**
 * RepairDesk - Interactive Dashboard Data Store & UI Controller (Philippine Localization)
 */

const STORE_KEY_VERSION = "repairdesk_ph_v1";

// Initial Seed Data - Philippine Electronics & Appliances
const DEFAULT_JOBS = [
  {
    id: "JOB-101",
    code: "B9X2K4",
    appliance: "Vacuum Cleaner",
    brand: "Dyson",
    model: "V11 Torque Drive",
    serial: "DY-9812-74",
    customer: { name: "Christine Reyes", email: "creyes@gmail.com", phone: "0917 234 8901" },
    technician: "Danilo \"Dan\" Mendoza",
    status: "Awaiting Quote Approval",
    tab: "pending",
    dateStarted: "2026-10-28",
    estCompletion: "2026-11-02",
    reportedIssue: "Pulsing on boost mode, shuts down after 10 seconds.",
    accessories: "Torque drive head, aluminum wand, wall charger dock",
    condition: "Minor cosmetic scratches on clear bin canister.",
    billing: { diagnosticFee: 500, parts: 3850, labor: 1200, total: 5550 },
    payment: "Unpaid",
    notes: [
      { id: 1, time: "2026-10-29 14:15", author: "Danilo Mendoza (Technician)", text: "Diagnostics finished. Battery pack cell voltage drops under high discharge. Needs OEM 7-cell battery pack replacement.", customerVisible: true },
      { id: 2, time: "2026-10-28 09:30", author: "Front Desk Intake", text: "Unit checked in at Gilmore counter. Customer signed 30-day storage consent.", customerVisible: false }
    ],
    timeline: [
      { time: "2026-10-28 09:30", action: "Job Created & Intake Slip Issued", user: "Elena Corpuz (Admin)" },
      { time: "2026-10-28 10:00", action: "Assigned to Danilo Mendoza", user: "Elena Corpuz (Admin)" },
      { time: "2026-10-29 14:15", action: "Status updated to Awaiting Quote Approval", user: "Danilo Mendoza" }
    ]
  },
  {
    id: "JOB-102",
    code: "F7W3H9",
    appliance: "Laptop",
    brand: "Apple",
    model: "MacBook Pro 16\" M2 Max",
    serial: "C02G901KMD6T",
    customer: { name: "Roberto \"Robert\" De Silva", email: "rdesilva@techno.ph", phone: "0920 892 1133" },
    technician: "Sarah Dizon",
    status: "Repairing",
    tab: "pending",
    dateStarted: "2026-10-27",
    estCompletion: "2026-10-31",
    reportedIssue: "No display backlight after accidental keyboard lid pressure.",
    accessories: "140W MagSafe Charger, neoprene sleeve",
    condition: "Minor dent on top aluminum lid edge.",
    billing: { diagnosticFee: 600, parts: 14500, labor: 3500, total: 18600 },
    payment: "Deposit",
    notes: [
      { id: 1, time: "2026-10-29 11:00", author: "Sarah Dizon", text: "Liquid Retina XDR panel installed, calibrating True Tone display profiles.", customerVisible: true }
    ],
    timeline: [
      { time: "2026-10-27 11:20", action: "Job Created", user: "Admin" },
      { time: "2026-10-28 16:00", action: "Quote Approved by Customer via SMS Link", user: "System" },
      { time: "2026-10-29 09:00", action: "Status moved to Repairing (₱5,000 Downpayment received via GCash)", user: "Sarah Dizon" }
    ]
  },
  {
    id: "JOB-103",
    code: "K2P8R5",
    appliance: "Espresso Machine",
    brand: "Breville",
    model: "Barista Touch BES880",
    serial: "BV-4410-09",
    customer: { name: "Sofia Martinez", email: "smartinez@cafe.ph", phone: "0918 441 9022" },
    technician: "Liam Bernardo",
    status: "Waiting for Parts",
    tab: "pending",
    dateStarted: "2026-10-25",
    estCompletion: "2026-11-04",
    reportedIssue: "Steam wand not generating pressure; error code 03 on touchscreen.",
    accessories: "Portafilter, 54mm tamper, stainless milk pitcher",
    condition: "Good condition, slight water scale marks on drip tray.",
    billing: { diagnosticFee: 500, parts: 2800, labor: 1500, total: 4800 },
    payment: "Unpaid",
    notes: [
      { id: 1, time: "2026-10-27 16:30", author: "Liam Bernardo", text: "Thermal fuse blown and solenoid valve corroded due to hard water scale. PO #8812 ordered from Breville Philippines distributor.", customerVisible: true }
    ],
    timeline: [
      { time: "2026-10-25 14:00", action: "Intake registered", user: "Elena Corpuz" },
      { time: "2026-10-27 16:30", action: "Status updated to Waiting for Parts", user: "Liam Bernardo" }
    ]
  },
  {
    id: "JOB-104",
    code: "M4K7P2",
    appliance: "Laptop",
    brand: "Apple",
    model: "MacBook Pro 14\" M1 Pro",
    serial: "C02FK319MD6R",
    customer: { name: "Marcus Chen", email: "mchen@designhub.ph", phone: "0919 312 7788" },
    technician: "Danilo \"Dan\" Mendoza",
    status: "Collected",
    tab: "completed",
    dateStarted: "2026-10-15",
    estCompletion: "2026-10-20",
    reportedIssue: "Sticky keys from milk tea spill; erratic trackpad haptics.",
    accessories: "Original box, 67W power adapter, USB-C cable",
    condition: "Clean casing.",
    billing: { diagnosticFee: 600, parts: 6800, labor: 2500, total: 9900 },
    payment: "Paid",
    warrantyUntil: "2027-01-26",
    notes: [
      { id: 1, time: "2026-10-20 17:00", author: "Elena Corpuz", text: "Customer picked up unit at Gilmore counter. Full payment of ₱9,900 settled via Maya QR. 90-day parts warranty active.", customerVisible: true }
    ],
    timeline: [
      { time: "2026-10-15 10:00", action: "Job Created", user: "Admin" },
      { time: "2026-10-19 15:30", action: "Status updated to Ready", user: "Danilo Mendoza" },
      { time: "2026-10-20 17:00", action: "Marked Collected by Admin (Paid via Maya)", user: "Elena Corpuz" }
    ]
  },
  {
    id: "JOB-105",
    code: "T9V4N6",
    appliance: "Smart TV 65\"",
    brand: "Samsung",
    model: "QLED Q80B 65\" 4K",
    serial: "SAM-65Q80-991",
    customer: { name: "David Lim", email: "dlim@media.ph", phone: "0917 782 9011" },
    technician: "Liam Bernardo",
    status: "Cannot Be Repaired",
    tab: "cancelled",
    dateStarted: "2026-10-20",
    estCompletion: "2026-10-23",
    reportedIssue: "Horizontal colored bands and internal OLED panel impact crack.",
    accessories: "Remote control, power cord",
    condition: "Internal matrix impact crack visible when powered on.",
    billing: { diagnosticFee: 500, parts: 0, labor: 0, total: 500 },
    payment: "Paid",
    notes: [
      { id: 1, time: "2026-10-22 13:00", author: "Liam Bernardo", text: "Samsung panel replacement cost exceeds unit market value. Customer advised to pull out. Diagnostic fee of ₱500 paid.", customerVisible: true }
    ],
    timeline: [
      { time: "2026-10-20 11:00", action: "Intake", user: "Admin" },
      { time: "2026-10-22 13:00", action: "Status: Cannot Be Repaired", user: "Liam Bernardo" }
    ]
  },
  {
    id: "JOB-106",
    code: "W3X8Y2",
    appliance: "Audio Receiver",
    brand: "Denon",
    model: "AVR-X2700H 7.2ch",
    serial: "DEN-2700-11",
    customer: { name: "Jessica Tolentino", email: "jtolentino@sound.ph", phone: "0922 431 8890" },
    technician: "Sarah Dizon",
    status: "Ready",
    tab: "pending",
    dateStarted: "2026-10-22",
    estCompletion: "2026-10-28",
    reportedIssue: "Protection mode triggers on power-up (red blinking standby LED).",
    accessories: "Power cord, Audyssey setup microphone",
    condition: "Good cosmetic condition.",
    billing: { diagnosticFee: 500, parts: 1850, labor: 1500, total: 3850 },
    payment: "Unpaid",
    notes: [
      { id: 1, time: "2026-10-28 15:45", author: "Sarah Dizon", text: "Replaced 2 shorted Sanken power transistors in front right channel. Passed 6-hour thermal burn-in stress test.", customerVisible: true }
    ],
    timeline: [
      { time: "2026-10-22 14:00", action: "Created", user: "Admin" },
      { time: "2026-10-28 16:00", action: "Marked Ready. Automated 30-day collection email sent.", user: "Sarah Dizon" }
    ]
  }
];

const DEFAULT_TECHNICIANS = [
  { id: "TECH-1", name: "Danilo \"Dan\" Mendoza", email: "dan.mendoza@repairdesk.ph", phone: "0917 890 1201", activeJobs: 4, completedJobs: 48, status: "Active", specialty: "Small Appliances, Motors & Dyson Repairs" },
  { id: "TECH-2", name: "Sarah Dizon", email: "sarah.d@repairdesk.ph", phone: "0928 890 1202", activeJobs: 6, completedJobs: 62, status: "Active", specialty: "Micro-soldering, Logic Boards & Apple MacBooks" },
  { id: "TECH-3", name: "Liam Bernardo", email: "liam.b@repairdesk.ph", phone: "0918 890 1203", activeJobs: 3, completedJobs: 34, status: "Active", specialty: "AV Receivers, Amps & Commercial Espresso Machines" },
  { id: "TECH-4", name: "Maya Villanueva", email: "maya.v@repairdesk.ph", phone: "0922 890 1204", activeJobs: 5, completedJobs: 51, status: "Active", specialty: "Smart LED/OLED Displays & Game Consoles" },
  { id: "TECH-5", name: "Carlos Garcia", email: "carlos.g@repairdesk.ph", phone: "0939 890 1205", activeJobs: 0, completedJobs: 19, status: "Deactivated", specialty: "General Power Supply Diagnostics" }
];

// Initialize Data Store in localStorage with Version Guard
function getJobs() {
  const version = localStorage.getItem("repairdesk_version");
  if (version !== STORE_KEY_VERSION) {
    localStorage.setItem("repairdesk_version", STORE_KEY_VERSION);
    localStorage.setItem("repairdesk_jobs", JSON.stringify(DEFAULT_JOBS));
    localStorage.setItem("repairdesk_technicians", JSON.stringify(DEFAULT_TECHNICIANS));
    localStorage.setItem("repairdesk_user", "Elena Corpuz (Shop Admin)");
    localStorage.setItem("repairdesk_role", "admin");
    return DEFAULT_JOBS;
  }
  const stored = localStorage.getItem("repairdesk_jobs");
  if (!stored) {
    localStorage.setItem("repairdesk_jobs", JSON.stringify(DEFAULT_JOBS));
    return DEFAULT_JOBS;
  }
  return JSON.parse(stored);
}

function saveJobs(jobs) {
  localStorage.setItem("repairdesk_jobs", JSON.stringify(jobs));
}

function getTechnicians() {
  const version = localStorage.getItem("repairdesk_version");
  if (version !== STORE_KEY_VERSION) {
    getJobs(); // Will reset both
  }
  const stored = localStorage.getItem("repairdesk_technicians");
  if (!stored) {
    localStorage.setItem("repairdesk_technicians", JSON.stringify(DEFAULT_TECHNICIANS));
    return DEFAULT_TECHNICIANS;
  }
  return JSON.parse(stored);
}

function saveTechnicians(techs) {
  localStorage.setItem("repairdesk_technicians", JSON.stringify(techs));
}

// Generate 6-Character Non-Sequential Code without look-alike chars (0/O, 1/I/L)
function generateJobCode() {
  const chars = "23456789ABCDEFGHJKMNPQRSTUVWXYZ";
  let code = "";
  for (let i = 0; i < 6; i++) {
    code += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return code;
}

// Status Badge Styling Helper
function getStatusBadge(status) {
  let badgeClass = "badge-gray";
  if (status === "Diagnosing") badgeClass = "badge-blue";
  else if (status === "Awaiting Quote Approval") badgeClass = "badge-amber";
  else if (status === "Repairing") badgeClass = "badge-teal";
  else if (status === "Waiting for Parts") badgeClass = "badge-yellow";
  else if (status === "Ready") badgeClass = "badge-green";
  else if (status === "Collected") badgeClass = "badge-emerald";
  else if (status === "Cannot Be Repaired" || status === "Declined") badgeClass = "badge-red";
  else if (status === "Cancelled") badgeClass = "badge-gray";

  return `<span class="badge ${badgeClass}">${status}</span>`;
}

// Payment Badge Helper (Philippine Peso)
function getPaymentBadge(status) {
  if (status === "Paid") return `<span class="badge badge-green">Paid</span>`;
  if (status === "Deposit") return `<span class="badge badge-blue">Deposit</span>`;
  return `<span class="badge badge-amber">Unpaid</span>`;
}

// Format Currency to Philippine Peso
function formatPHP(amount) {
  return "₱" + Number(amount).toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Role Switcher Toggle
function initRoleSwitcher() {
  const roleSelect = document.getElementById("role-selector");
  if (!roleSelect) return;

  const currentRole = localStorage.getItem("repairdesk_role") || "admin";
  roleSelect.value = currentRole;

  roleSelect.addEventListener("change", (e) => {
    const newRole = e.target.value;
    localStorage.setItem("repairdesk_role", newRole);
    if (newRole === "admin") {
      localStorage.setItem("repairdesk_user", "Elena Corpuz (Shop Admin)");
    } else {
      localStorage.setItem("repairdesk_user", "Danilo \"Dan\" Mendoza (Technician)");
    }
    window.location.reload();
  });

  // Display user badge
  const userDisplay = document.getElementById("header-user-name");
  if (userDisplay) {
    userDisplay.innerText = localStorage.getItem("repairdesk_user") || "Elena Corpuz (Shop Admin)";
  }
}

// Mobile Sidebar Toggle
function initSidebarToggle() {
  const toggleBtn = document.getElementById("sidebar-toggle-btn");
  const sidebar = document.querySelector(".sidebar");
  if (!sidebar) return;

  // Create overlay if not present
  let overlay = document.querySelector(".sidebar-overlay");
  if (!overlay) {
    overlay = document.createElement("div");
    overlay.className = "sidebar-overlay";
    document.body.appendChild(overlay);
  }

  if (toggleBtn) {
    toggleBtn.addEventListener("click", () => {
      sidebar.classList.toggle("open");
      overlay.classList.toggle("show");
    });
  }

  overlay.addEventListener("click", () => {
    sidebar.classList.remove("open");
    overlay.classList.remove("show");
  });
}

// Export functions to global scope
window.RepairDeskStore = {
  getJobs,
  saveJobs,
  getTechnicians,
  saveTechnicians,
  generateJobCode,
  getStatusBadge,
  getPaymentBadge,
  formatPHP,
  initRoleSwitcher,
  initSidebarToggle
};

document.addEventListener("DOMContentLoaded", () => {
  getJobs(); // triggers version check
  initRoleSwitcher();
  initSidebarToggle();
});
