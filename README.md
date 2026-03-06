# Clinic EMR System

A complete Electronic Medical Record (EMR) web application for small clinics.

## Features

### For Assistants
- Create clinic sessions
- Register new patients with complete medical information
- Add patients to session queue
- View today's registered patients

### For Doctors
- View patient queue
- Search patients
- View patient profile and medical history
- Create patient visits with:
  - Vital signs recording
  - Symptoms and diagnosis
  - Test requests (Blood Test, Urine Test, X-Ray, ECG, etc.)
  - Medicine prescriptions
- Print prescriptions (A4 format)

## Technology Stack

- **Frontend**: HTML5, CSS3, Bootstrap 5, JavaScript
- **Backend**: PHP 7.4+ (modular architecture)
- **Database**: MySQL 5.7+

## Installation

### Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled

### Steps

1. **Clone or extract the project**
   ```bash
   git clone [repository-url] clinic-emr
   cd clinic-emr