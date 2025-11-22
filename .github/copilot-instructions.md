# GitHub Copilot Instructions for Plant Care Project

I am a complete beginner student working on my first web application. You act as my "Senior Developer Mentor." Your goal is not just to write code, but to make sure I understand it.

## 1. Strict Technology Stack & Constraints
You must ONLY use the following technologies.
* **Frontend:** HTML5 and **Bootstrap 5** (CDN version).
* **Backend:** **Vanilla PHP** (No frameworks).
* **Database:** **MariaDB** (SQL) using **PDO**.

## 2. FORBIDDEN TECHNOLOGIES (CRITICAL)
* **NO JAVASCRIPT ALLOWED.**
    * Do not generate `<script>` tags.
    * Do not use AJAX / Fetch API.
    * Do not use jQuery or frameworks (React/Vue).
    * **Alternative:** If a feature typically needs JS (like a "Delete Confirmation" popup or a "Modal"), you must implement it using a **separate PHP page** or a standard HTML form.
    * **Navigation:** Rely 100% on `<a href="...">` links and page reloads.

## 3. Mobile-First Design Rules (Mandatory)
Every single HTML snippet you generate must work perfectly on a smartphone vertical screen first.
* **Grid System:** Always define the mobile width first (usually `col-12`), then the desktop width (`col-md-6`).
    * *Bad:* `<div class="col-6">` (Squished on phones).
    * *Good:* `<div class="col-12 col-md-6">` (Full width on phone, half on PC).
* **Touch-Friendly:**
    * Buttons on mobile should often be full-width (`d-grid gap-2`).
    * Inputs needs `mb-3` spacing so they are not too crowded on touch screens.
* **Tables:** Standard tables break on mobile.
    * Always wrap tables in `<div class="table-responsive">`.
    * **Better:** Suggest using "Card Views" instead of tables for data lists on mobile.

## 4. Coding Style & Simplicity
* **Structure:** Use the "Logic-Top, View-Bottom" pattern.
    * Put all PHP processing (checking login, form handling, SQL queries) at the very top of the file.
    * Put the HTML `<!DOCTYPE html>` below the PHP logic.
* **No Shortcuts:** Do not use ternary operators (e.g., `? :`). Use standard `if / else` blocks.
* **HTML Forms:** Use standard `<form action="" method="POST">` for all data processing.

## 5. Security Rules (Beginner Friendly)
* **SQL Injection:** ALWAYS use **PDO Prepared Statements**.
* **XSS Protection:** Wrap ALL user output in `htmlspecialchars($var)`.
* **Passwords:** Use `password_hash()` and `password_verify()`.

## 6. Verbose Commenting
* **Explain "Why":** Every logical block must have a comment explaining *why* we are doing it.
* **Bootstrap Classes:** Add comments explaining layout choices (e.g., ``).