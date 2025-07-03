# Truck Maintenance Manager WordPress Plugin

A comprehensive WordPress plugin for managing truck maintenance schedules, tracking service history, and monitoring maintenance urgency with visual indicators.

## Features

### Admin Features
- **Dashboard Overview**: Visual dashboard with maintenance statistics and urgency indicators
- **Truck Management**: Add, edit, and delete trucks with detailed information
- **Maintenance Tracking**: Track oil changes, filter replacements, DPF cleaning, and safety inspections
- **Urgency Scoring**: Automatic calculation of maintenance urgency (0-100 scale) with color-coded visual indicators
- **QR Code Generation**: Generate QR codes for quick truck access
- **CSV Import**: Bulk import trucks from CSV files
- **User Management**: Assign specific users to trucks for access control

### Frontend Features
- **User Dashboard**: Frontend dashboard for non-admin users
- **Truck List View**: Browse and search assigned trucks
- **Maintenance Updates**: Update maintenance records from the frontend
- **Mobile Responsive**: Fully responsive design for mobile and tablet use
- **Interactive Cards**: Click maintenance cards to update records

### Technical Features
- **WordPress Integration**: Full WordPress plugin with proper hooks and filters
- **User Roles**: Custom user role "Truck Maintenance User" with specific capabilities
- **AJAX Interface**: Smooth user experience with AJAX-powered interactions
- **Security**: Proper nonce verification and capability checks
- **Database**: Custom database table for truck maintenance data

## Installation

1. **Upload Plugin**:
   - Upload the `truck-maintenance-manager` folder to `/wp-content/plugins/`
   - Or install via WordPress admin: Plugins > Add New > Upload Plugin

2. **Activate Plugin**:
   - Go to Plugins in WordPress admin
   - Find "Truck Maintenance Manager" and click Activate

3. **Setup**:
   - The plugin will automatically create the necessary database table
   - User roles and capabilities will be added automatically

## Usage

### Admin Usage

#### Adding Trucks
1. Go to **Truck Maintenance > Add Truck**
2. Fill in required information (VIN, Year, Make, Model)
3. Set maintenance intervals for different service types
4. Assign specific users (optional - leave empty for all users)
5. Click "Add Truck"

#### Managing Maintenance
1. Go to **Truck Maintenance > All Trucks**
2. Click on maintenance cards to update service records
3. View urgency scores and color-coded indicators
4. Generate QR codes for quick access

#### Importing Trucks
1. Go to **Truck Maintenance > Import Trucks**
2. Download the sample CSV format
3. Prepare your CSV file with truck data
4. Upload and import

### Frontend Usage

#### Shortcodes

**Dashboard Shortcode**:
```
[truck_maintenance_dashboard]
```
- Shows overview with statistics and recent activity
- Add `user_trucks_only="true"` to show only assigned trucks

**Truck List Shortcode**:
```
[truck_maintenance_list]
```
- Shows all trucks with maintenance cards
- Add `user_trucks_only="true"` to show only assigned trucks
- Add `show_add_button="true"` to show add truck button (admin only)

**Add Truck Form Shortcode**:
```
[truck_maintenance_form]
```
- Shows add truck form (admin only)

#### User Access
- Users need "Truck Maintenance User" role or equivalent capabilities
- Administrators can assign specific trucks to users
- Users can update maintenance records for their assigned trucks

## Urgency Scoring System

The plugin uses a sophisticated urgency scoring system (0-100):

### Scoring Factors
- **Maintenance Overdue**: High urgency (80-100)
- **Maintenance Due Soon**: Medium urgency (40-80)
- **Safety Inspection Expired**: Critical urgency (90-100)
- **No Maintenance History**: High urgency (90-100)

### Visual Indicators
- **Green (0-30)**: Good condition
- **Yellow (31-50)**: Attention needed
- **Orange (51-85)**: Service due soon
- **Red (86-100)**: Critical - service overdue

### Calculation Logic
- Considers mileage-based and date-based maintenance schedules
- Weights different maintenance types by importance
- Accounts for approaching thresholds (500-1000 miles/800-1600 km)
- Factors in safety inspection expiry dates

## Database Schema

### Table: `wp_truck_maintenance`
- `id`: Primary key
- `vin`: Vehicle Identification Number (unique)
- `unit_number`: Optional unit identifier
- `year`, `make`, `model`: Vehicle information
- `current_mileage`: Current odometer reading
- `distance_unit`: Miles or kilometers
- `oil_change_interval`: Service interval
- `air_filter_interval`: Service interval
- `fuel_filter_interval`: Service interval
- `dpf_cleaning_interval`: Service interval
- `maintenance_history`: JSON data of service records
- `safety_inspection_date`: Last inspection date
- `safety_inspection_expiry`: Inspection expiry date
- `assigned_users`: JSON array of user IDs
- `created_at`, `updated_at`: Timestamps

## User Roles and Capabilities

### Custom Role: Truck Maintenance User
- `read`: Basic WordPress capability
- `tmm_view_trucks`: View truck information
- `tmm_update_maintenance`: Update maintenance records

### Administrator Capabilities
- All truck maintenance capabilities
- `tmm_manage_trucks`: Add, edit, delete trucks
- Access to admin interface and user management

## File Structure

```
truck-maintenance-manager/
├── truck-maintenance-manager.php    # Main plugin file
├── assets/
│   ├── admin.css                   # Admin styles
│   ├── admin.js                    # Admin JavaScript
│   ├── frontend.css                # Frontend styles
│   └── frontend.js                 # Frontend JavaScript
├── templates/
│   ├── admin/                      # Admin templates
│   │   ├── dashboard.php
│   │   ├── truck-list.php
│   │   ├── add-truck.php
│   │   ├── import-trucks.php
│   │   └── user-management.php
│   └── frontend/                   # Frontend templates
│       ├── dashboard.php
│       ├── truck-list.php
│       └── add-truck.php
└── README.md                       # This file
```

## Customization

### Styling
- Modify CSS files in `/assets/` directory
- Use CSS classes with `tmm-` prefix for targeting
- Responsive design with mobile-first approach

### Functionality
- Hook into WordPress actions and filters
- Extend with custom maintenance types
- Add custom fields to truck records

### Maintenance Types
Current maintenance types:
- Oil Change
- Air Filter
- Fuel Filter
- DPF Cleaning
- Safety Inspection

## Security Features

- **Nonce Verification**: All AJAX requests verified
- **Capability Checks**: User permissions enforced
- **Data Sanitization**: All input sanitized and validated
- **SQL Injection Prevention**: Prepared statements used
- **Access Control**: User-specific truck assignments

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## Support

For support and customization:
1. Check the WordPress admin for error messages
2. Enable WordPress debug mode for detailed error logs
3. Verify user roles and capabilities are set correctly
4. Ensure database table was created successfully

## License

GPL v2 or later - same as WordPress

## Changelog

### Version 1.0.0
- Initial release
- Complete admin interface
- Frontend shortcodes and templates
- User role management
- Urgency scoring system
- QR code generation
- CSV import functionality
- Mobile responsive design
