<div align="center">
  <img src="https://img.icons8.com/external-flaticons-flat-flat-icons/96/000000/external-freelance-web-development-flaticons-flat-flat-icons.png" width="120" height="120"/>
  
  # 🚀 Freedly - Freelance Marketplace Platform

  ### Connect Freelancers and Employers Seamlessly

  [![GitHub license](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/hirunhansaka/freedlyproject/blob/main/LICENSE)
  [![GitHub stars](https://img.shields.io/github/stars/hirunhansaka/freedlyproject)](https://github.com/hirunhansaka/freedlyproject/stargazers)
  [![GitHub forks](https://img.shields.io/github/forks/hirunhansaka/freedlyproject)](https://github.com/hirunhansaka/freedlyproject/network)
  [![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat&logo=php&logoColor=white)](https://www.php.net)
  [![MySQL](https://img.shields.io/badge/MySQL-00000F?style=flat&logo=mysql&logoColor=white)](https://www.mysql.com)
  [![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=flat&logo=css3&logoColor=white)](https://www.w3.org/Style/CSS/)

  <br>

  <!-- Add your actual app screenshots here -->
  <!-- Replace these placeholder links with your actual screenshot paths -->
  <table>
    <tr>
      <td><img src="https://via.placeholder.com/400x250.png?text=Homepage" width="250" alt="Homepage"/></td>
      <td><img src="https://via.placeholder.com/400x250.png?text=Freelancer+Dashboard" width="250" alt="Freelancer Dashboard"/></td>
      <td><img src="https://via.placeholder.com/400x250.png?text=Employer+Post+Job" width="250" alt="Post Job"/></td>
      <td><img src="https://via.placeholder.com/400x250.png?text=Admin+Panel" width="250" alt="Admin Panel"/></td>
    </tr>
  </table>
</div>

---

## 📖 Overview

**Freedly** is a comprehensive freelance marketplace platform built with PHP and MySQL. It supports three distinct user roles—**Freelancers**, **Employers**, and **Admins**—each with a tailored dashboard and set of capabilities. The platform facilitates job posting, service creation, proposals, secure messaging, reviews, and full administrative oversight.

---

## ✨ Key Features at a Glance

<div align="center">
  <table>
    <tr>
      <td align="center" width="200">
        <img src="https://img.icons8.com/fluency/48/000000/user-group-man-man.png" width="40"/><br>
        <b>👥 3 User Roles</b>
      </td>
      <td align="center" width="200">
        <img src="https://img.icons8.com/fluency/48/000000/post-job.png" width="40"/><br>
        <b>📌 Job Posting</b>
      </td>
      <td align="center" width="200">
        <img src="https://img.icons8.com/fluency/48/000000/proposal.png" width="40"/><br>
        <b>📝 Proposals</b>
      </td>
    </tr>
    <tr>
      <td align="center" width="200">
        <img src="https://img.icons8.com/fluency/48/000000/star--v1.png" width="40"/><br>
        <b>⭐ Reviews & Ratings</b>
      </td>
      <td align="center" width="200">
        <img src="https://img.icons8.com/fluency/48/000000/administrator-male.png" width="40"/><br>
        <b>⚙️ Admin Dashboard</b>
      </td>
      <td align="center" width="200">
        <img src="https://img.icons8.com/fluency/48/000000/chat.png" width="40"/><br>
        <b>💬 Messaging</b>
      </td>
    </tr>
  </table>
</div>

---

## 🎯 Detailed Feature Breakdown

### 👤 **User Roles & Authentication**
- **Separate Dashboards**: Customized experiences for Freelancers, Employers, and Admins.
- **Secure Login/Registration**: `user_login.php`, `user_register.php`, and session management (`user_logout.php`).
- **Profile Management**: Each role can manage their profile (`employer_manage_profile.php`, `freelancer_manage_profile.php`, `user_profile.php`).

### 💼 **For Employers**
- **Job Posting**: Create and manage job listings (`employer_post_job.php`, `employer_manage_jobs.php`).
- **Proposal Management**: View and manage proposals from freelancers (`employer_view_proposals.php`).
- **Project Completion**: Mark jobs as complete (`employer_complete_job.php`).

### 🎨 **For Freelancers**
- **Service Creation**: Offer services to employers (`freelancer_create_service.php`, `freelancer_manage_services.php`).
- **Job Applications**: Browse and apply for jobs (`apply_to_job.php`, `freelancer_view_applications.php`).
- **Project Tracking**: View ongoing and past projects (`freelancer_my_projects.php`).
- **Public Profile**: A public-facing profile to showcase skills (`freelancer_public_profile.php`).

### ⚙️ **For Administrators**
- **Centralized Dashboard**: Overview of platform activity (`admin_dashboard.php`).
- **User Management**: View, edit, or disable user accounts (`admin_manage_users.php`).
- **Job Oversight**: Monitor and manage all posted jobs (`admin_manage_jobs.php`).
- **Report Handling**: Manage user-generated reports (`admin_manage_reports.php`).

### 🤝 **Communication & Community**
- **Messaging System**: Direct communication between users (`messaging_page.php`, `create_message.php`, `initiate_contact.php`).
- **Reviews & Ratings**: Freelancers and employers can leave feedback after project completion.
- **Reporting**: Users can report issues to admins (`user_report_admin.php`).

### 🛠️ **Additional Features**
- **Browse Services**: Explore services offered by freelancers (`browse_services.php`).
- **View Service Details**: Detailed view of a specific service (`view_service.php`).
- **Database Integration**: Centralized connection management (`database_connection.php`).

---

## 🛠️ **Technology Stack**

<div align="center">
  <table>
    <tr>
      <td align="center">
        <img src="https://img.icons8.com/officexs/48/000000/php-logo.png"/><br>
        <b>PHP</b> (Backend)
      </td>
      <td align="center">
        <img src="https://img.icons8.com/ios-filled/50/000000/mysql-logo.png"/><br>
        <b>MySQL</b> (Database)
      </td>
      <td align="center">
        <img src="https://img.icons8.com/fluency/48/000000/css3.png"/><br>
        <b>CSS3</b> (Styling)
      </td>
      <td align="center">
        <img src="https://img.icons8.com/fluency/48/000000/html-5.png"/><br>
        <b>HTML5</b> (Structure)
      </td>
    </tr>
  </table>
</div>

---

## 📁 **Project Structure**
