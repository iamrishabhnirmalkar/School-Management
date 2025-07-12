# Fee Management System

This directory contains the complete fee management system for the School ERP. The system provides comprehensive functionality for managing student fees, collecting payments, generating reports, and configuring fee settings.

## Files Overview

### Core Files

1. **`index.php`** - Main fee dashboard with statistics and overview
2. **`collect.php`** - Fee collection interface with search and filtering
3. **`structure.php`** - Fee structure management (create, edit, delete fee types)
4. **`details.php`** - Detailed view of specific fee types for classes
5. **`reports.php`** - Comprehensive reporting system with export functionality
6. **`settings.php`** - System configuration and settings management

## Features

### 1. Fee Overview Dashboard (`index.php`)

- **Statistics Cards**: Total fees, paid fees, unpaid fees, overdue fees
- **Amount Statistics**: Total amount, collected amount, pending amount
- **Recent Transactions**: Latest fee payments and status updates
- **Overdue Fees**: List of fees past due date
- **Auto-refresh**: Dashboard updates every 5 minutes

### 2. Fee Collection (`collect.php`)

- **Search & Filter**: Search by student name, roll number, fee type
- **Class & Status Filters**: Filter by class and payment status
- **Payment Modal**: Collect fees with payment method, date, and remarks
- **Overdue Tracking**: Visual indicators for overdue fees
- **Bulk Actions**: Mark multiple fees as paid/unpaid

### 3. Fee Structure Management (`structure.php`)

- **Create Fee Structures**: Add new fee types with amounts and due dates
- **Class Assignment**: Apply fee structures to specific classes
- **Fee Types**: Tuition, Admission, Exam, Transport, Library, etc.
- **Statistics**: View paid/unpaid/overdue counts for each fee type
- **Delete Structures**: Remove fee structures from classes

### 4. Fee Details (`details.php`)

- **Class-specific Views**: Detailed breakdown by class and fee type
- **Student Lists**: Complete list of students with payment status
- **Bulk Operations**: Select and modify multiple student fees
- **Statistics**: Comprehensive statistics for the selected fee type
- **Print Functionality**: Export fee details for printing

### 5. Reports (`reports.php`)

- **Collection Reports**: Fee payments within date ranges
- **Overdue Reports**: Fees past due date
- **Summary Reports**: Class-wise fee summaries
- **Export to CSV**: Download reports in CSV format
- **Filtering**: Filter by class, fee type, date range

### 6. Settings (`settings.php`)

- **General Settings**: Currency symbol, default due days, late fee percentage
- **Fee Types**: Manage available fee types
- **Payment Methods**: Configure accepted payment methods
- **Reminder Settings**: Auto-reminder configuration

## Database Structure

### Main Tables

- **`fees`**: Core fee records with student, amount, due date, status
- **`fee_payments`**: Payment history and collection records
- **`classes`**: Class information for fee structure assignment
- **`students`**: Student information linked to fees
- **`users`**: User accounts for fee collection tracking

### Key Fields

- `student_id`: Links to student record
- `class_id`: Links to class for fee structure
- `fee_type`: Type of fee (Tuition, Admission, etc.)
- `amount`: Fee amount
- `due_date`: Payment due date
- `status`: paid/unpaid/partial
- `paid_date`: When payment was received

## Usage Instructions

### For Administrators

1. **Setup Fee Structure**:

   - Go to `structure.php`
   - Click "Add Fee Structure"
   - Select fee type, amount, due date
   - Choose classes to apply the structure
   - Save the structure

2. **Collect Fees**:

   - Go to `collect.php`
   - Search for students with unpaid fees
   - Click "Collect" button
   - Enter payment details
   - Confirm payment

3. **Generate Reports**:

   - Go to `reports.php`
   - Select report type (Collection/Overdue/Summary)
   - Set date range and filters
   - Generate and export reports

4. **Configure Settings**:
   - Go to `settings.php`
   - Update general settings
   - Manage fee types and payment methods
   - Save configurations

### For Students/Parents

Students can view their fee status through the student portal, which shows:

- Outstanding fees
- Payment history
- Due dates
- Payment status

## Security Features

- **Authentication**: All pages require admin login
- **Input Validation**: All form inputs are validated
- **SQL Injection Protection**: Prepared statements used
- **XSS Protection**: Output escaping implemented
- **CSRF Protection**: Form tokens for security

## Technical Details

### Technologies Used

- **Backend**: PHP 8.x
- **Database**: MySQL 8.x
- **Frontend**: HTML5, CSS3 (Tailwind CSS)
- **JavaScript**: Vanilla JS for interactivity
- **Icons**: Font Awesome 6.x

### File Structure

```
admin/fees/
├── index.php          # Dashboard
├── collect.php        # Fee collection
├── structure.php      # Fee structure management
├── details.php        # Fee details view
├── reports.php        # Reporting system
├── settings.php       # System settings
└── README.md         # This documentation
```

### Database Migrations

Run the migration file `migrations/002_add_fee_payments_table.sql` to create the required database tables.

## Customization

### Adding New Fee Types

1. Go to `settings.php`
2. Navigate to "Fee Types" tab
3. Add new fee type
4. Save changes

### Modifying Payment Methods

1. Go to `settings.php`
2. Navigate to "Payment Methods" tab
3. Add/remove payment methods
4. Save changes

### Changing Currency

1. Go to `settings.php`
2. Navigate to "General Settings" tab
3. Update currency symbol
4. Save changes

## Support

For technical support or feature requests, please contact the system administrator.

## Version History

- **v1.0**: Initial release with basic fee management
- **v1.1**: Added reporting and export functionality
- **v1.2**: Enhanced settings and customization options
