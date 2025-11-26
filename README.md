# TARUMT Voting System

A comprehensive web-based voting system for **TAR UMT** student elections.  
Supports multiple roles (**Admin, Student, Nominee**), manages election events, voting sessions, nominee applications, campaign materials, and generates official election results and reports.

> 🗳️ Built with PHP, MySQL/MariaDB, and a custom MVC-style structure.

---

## Table of Contents

- [Project Overview](#project-overview)
- [Tech Stack & Requirements](#tech-stack--requirements)
- [Installation](#installation)
- [Project Structure](#project-structure)
- [Key Features](#key-features)
- [Main Routes](#main-routes)
- [Database Schema Overview](#database-schema-overview)
- [File Uploads](#file-uploads)
- [Configuration](#configuration)
- [Security Features](#security-features)
- [Usage by Role](#usage-by-role)
- [Troubleshooting](#troubleshooting)
- [Dependencies](#dependencies)
- [Support & Maintenance](#support--maintenance)
- [Version Info](#version-info)

---

## Project Overview

The **TARUMT Voting System** is a web-based application designed to manage and conduct student elections:

- Handles **Admin**, **Student**, and **Nominee** roles.
- Manages **election events**, **voting sessions**, **nominee applications**, **campaign materials**, and **campaign schedules**.
- Generates **official election results**, **statistics**, and **reports** for administrators.

---

## Tech Stack & Requirements

**Backend**

- PHP 7.4 or higher
- MySQL / MariaDB
- Apache Web Server (with `mod_rewrite` enabled)

**Tools**

- Composer (PHP package manager)

**Client**

- Modern web browser (Chrome, Firefox, Edge, Safari)

---

## Installation

1. **Clone or extract** this repository into your web server directory (e.g. `htdocs` or `www`):

   ```bash
   git clone https://github.com/<your-username>/tarumt-voting-system.git
