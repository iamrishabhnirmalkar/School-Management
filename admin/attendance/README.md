# Attendance Management System

## Overview

The Attendance Management System is a comprehensive module for tracking and managing student and teacher attendance in the School ERP system. It provides real-time attendance tracking, detailed reports, and configurable settings.

## Features

### 1. Dashboard (`index.php`)

- **Real-time Statistics**: View total students, teachers, and today's attendance counts
- **Class-wise Summary**: See attendance percentages for each class
- **Recent Activity**: Display recent student and teacher attendance records
- **Quick Navigation**: Easy access to all attendance features

### 2. Student Attendance (`students.php`)

- **Class-based Filtering**: Select specific classes to manage attendance
- **Date Selection**: Choose any date to mark or view attendance
- **Bulk Attendance Marking**: Mark attendance for entire class at once
- **Individual Remarks**: Add specific remarks for each student
- **Monthly Summary**: View attendance statistics for selected month
- **Status Options**: Present, Absent, Late

### 3. Teacher Attendance (`teachers.php`)

- **Daily Attendance Marking**: Mark attendance for all teachers
- **Date Filtering**: Select specific dates to manage attendance
- **Monthly Reports**: View teacher attendance summaries
- **Status Options**: Present, Absent, Leave
- **Individual Remarks**: Add notes for each teacher

### 4. Reports (`reports.php`)

- **Multiple Report Types**:
  - Daily Attendance Report
  - Monthly Summary Report
  - Teacher Attendance Report
  - Individual Student Report
- **Advanced Filtering**: Filter by class, date, month, teacher, or student
- **CSV Export**: Export reports in CSV format for external analysis
- **Detailed Statistics**: Present, absent, late counts with percentages

### 5. Settings (`settings.php`)

- **Attendance Types**: Configure student and teacher attendance statuses
- **School Hours**: Set school start/end times and late thresholds
- **Notifications**: Enable/disable attendance notifications
- **Automation**: Configure auto-attendance and report frequency
- **Holiday Calendar**: Define school holidays

## Database Tables

### Student Attendance (`attendance`)

```sql
CREATE TABLE `attendance` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','late') NOT NULL,
  `remarks` text
);
```

### Teacher Attendance (`teacher_attendance`)

```sql
CREATE TABLE `teacher_attendance` (
  `id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','leave') NOT NULL,
  `remarks` text
);
```

## Usage Instructions

### For Administrators

1. **Access Attendance Module**:

   - Navigate to Admin Dashboard
   - Click on "Attendance" in the sidebar

2. **Mark Student Attendance**:

   - Go to "Student Attendance"
   - Select class and date
   - Mark attendance for each student
   - Add optional remarks
   - Click "Save Attendance"

3. **Mark Teacher Attendance**:

   - Go to "Teacher Attendance"
   - Select date
   - Mark attendance for each teacher
   - Add optional remarks
   - Click "Save Attendance"

4. **Generate Reports**:

   - Go to "Reports"
   - Select report type and filters
   - View results or export to CSV

5. **Configure Settings**:
   - Go to "Settings"
   - Modify attendance types, school hours, notifications
   - Save changes

### For Teachers

Teachers can mark attendance for their assigned classes through the teacher portal:

- Navigate to Teacher Dashboard
- Click on "Attendance"
- Select class and mark attendance

### For Students

Students can view their attendance records:

- Navigate to Student Dashboard
- Click on "Attendance"
- View attendance history and statistics

## Key Features

### Real-time Tracking

- Live attendance statistics
- Instant updates when attendance is marked
- Real-time dashboard with current day's data

### Comprehensive Reporting

- Multiple report formats (daily, monthly, individual)
- Export functionality for external analysis
- Detailed attendance percentages and trends

### Flexible Configuration

- Customizable attendance types
- Configurable school hours and late thresholds
- Holiday calendar management
- Notification settings

### User-friendly Interface

- Clean, modern UI with Tailwind CSS
- Responsive design for all devices
- Intuitive navigation and forms
- Clear status indicators and color coding

## Security Features

- **Authentication**: All pages require admin login
- **Input Validation**: Proper validation of all form inputs
- **SQL Injection Protection**: Prepared statements for all database queries
- **XSS Protection**: HTML escaping for all user inputs

## File Structure

```
admin/attendance/
├── index.php          # Dashboard with statistics
├── students.php       # Student attendance management
├── teachers.php       # Teacher attendance management
├── reports.php        # Comprehensive reporting system
├── settings.php       # Configuration settings
└── README.md         # This documentation
```

## Technical Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser with JavaScript enabled

## Integration

The attendance system integrates with:

- **User Management**: Uses existing user/student/teacher data
- **Class Management**: Links attendance to class assignments
- **Dashboard**: Provides statistics to main admin dashboard
- **Reports**: Integrates with the main reporting system

## Future Enhancements

- **Biometric Integration**: Support for fingerprint/face recognition
- **Mobile App**: Native mobile application for attendance
- **SMS Notifications**: Automated SMS alerts to parents
- **Advanced Analytics**: Machine learning for attendance patterns
- **API Integration**: REST API for third-party integrations

## Support

For technical support or feature requests, please contact the development team or refer to the main School ERP documentation.
