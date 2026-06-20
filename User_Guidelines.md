# Thesis Management System - User Guidelines

Welcome to the **Thesis Management System** User Guidelines. This document explains all the features available to the different types of users within the system: **Administrators**, **Students**, and **Supervisors**.

---

## 1. Student Features
Students use the system to manage their thesis journey from proposal submission to final review. 

### Key Features:
*   **Dashboard (`student/dashboard.php`)**: The main landing page for students, providing an overview of their thesis status and notifications.
*   **Submit Proposal (`student/submit_proposal.php`)**: Allows students to submit new thesis proposals for supervisor review and approval.
*   **My Thesis (`student/my_thesis.php`)**: The core interface for managing an ongoing thesis. Students can upload chapters, view feedback, and track progress.
*   **Assigned Projects (`student/assigned_projects.php`)**: View specific thesis projects or topics that have been assigned to the student by a supervisor or department.
*   **Messages (`student/messages.php`)**: A communication hub for messaging supervisors or other relevant users regarding thesis work.

---

## 2. Supervisor Features
Supervisors use the system to oversee student progress, review submissions, and manage their assigned classes or department students.

### Key Features:
*   **Dashboard (`supervisor/dashboard.php`)**: An overview of all supervised students, pending reviews, and general system notifications.
*   **Pending Requests (`supervisor/pending_requests.php`)**: View and approve/reject thesis proposals submitted by students.
*   **My Students (`supervisor/my_students.php`)**: A list of all students currently under the supervisor's guidance, with quick links to their thesis progress.
*   **Review Thesis (`supervisor/review_thesis.php`)**: The interface for supervisors to read student submissions, add comments, provide feedback, and grade/approve chapters.
*   **Assign New Project (`supervisor/assign_new_project.php`)**: Allows supervisors to propose new projects and assign them directly to specific students.
*   **Department Students (`supervisor/department_students.php`)**: View all students within the supervisor's department for broader oversight.
*   **Manage Classes & View Class (`supervisor/manage_classes.php`, `supervisor/view_class.php`)**: Tools for supervisors who manage entire classes or cohorts, allowing them to track group progress.

---

## 3. Administrator Features
Administrators have full control over the system, managing users, departments, deadlines, and overall system configuration.

### Key Features:
*   **Dashboard (`admin/dashboard.php`)**: High-level overview of system activity, total users, and active theses.
*   **Manage Users (`admin/manage_users.php`)**: Create, edit, delete, and manage roles for all users (Students, Supervisors, and other Admins).
*   **Manage Departments & View Department (`admin/manage_departments.php`, `admin/view_department.php`)**: Add or modify academic departments and view their associated personnel and theses.
*   **Manage Deadlines (`admin/manage_deadlines.php`)**: Set critical dates for proposal submissions, chapter uploads, and final thesis defense.
*   **All Theses & History (`admin/all_theses.php`, `admin/history.php`)**: View all ongoing and past theses across the entire institution, providing a complete historical record.
*   **Assign Thesis & Assign New Project (`admin/assign_thesis.php`, `admin/assign_new_project.php`)**: Administrative override to assign specific theses or projects to students and supervisors.
*   **Manage Examiners (`admin/manage_examiners.php`)**: Assign internal or external examiners to specific thesis defenses or final reviews.
*   **Manage Resources (`admin/manage_resources.php`)**: Upload and manage shared resources, guidelines, or templates that students and supervisors can download.
*   **Permissions (`admin/permissions.php`)**: Fine-tune access control and feature visibility for different user roles.
*   **Reports (`admin/reports.php`)**: Generate detailed statistical reports on thesis completions, department performance, and system usage.

---

### General Navigation
All users must log in through the central **Authentication** portal. Depending on your assigned role (Student, Supervisor, or Admin), you will be redirected to the appropriate dashboard and have access to the features outlined above.
