# Truck Maintenance Manager - Installation Instructions

## 📋 **System Requirements**

Before installing the Truck Maintenance Manager plugin, ensure your WordPress site meets these requirements:

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher  
- **MySQL**: 5.6 or higher
- **Web Server**: Apache or Nginx
- **Browser Support**: Chrome, Firefox, Safari, Edge (latest versions)

## 🚀 **Installation Methods**

### **Method 1: WordPress Admin Upload (Recommended)**

1. **Download the Plugin**
   - Download the `truck-maintenance-manager` folder as a ZIP file
   - Or create a ZIP file containing the entire plugin folder

2. **Upload via WordPress Admin**
   - Log in to your WordPress admin dashboard
   - Navigate to **Plugins > Add New**
   - Click **Upload Plugin** button
   - Choose the ZIP file and click **Install Now**
   - Click **Activate Plugin** when installation completes

### **Method 2: FTP/File Manager Upload**

1. **Upload Plugin Files**
   - Upload the entire `truck-maintenance-manager` folder to `/wp-content/plugins/`
   - Ensure the folder structure is: `/wp-content/plugins/truck-maintenance-manager/`

2. **Activate Plugin**
   - Go to **Plugins** in WordPress admin
   - Find "Truck Maintenance Manager" in the list
   - Click **Activate**

### **Method 3: WordPress CLI (Advanced Users)**

```bash
# Navigate to WordPress root directory
cd /path/to/wordpress

# Copy plugin to plugins directory
cp -r truck-maintenance-manager wp-content/plugins/

# Activate plugin
wp plugin activate truck-maintenance-manager
```

## ⚙️ **Post-Installation Setup**

### **1. Automatic Setup (Happens on Activation)**

When you activate the plugin, it automatically:
- ✅ Creates the `wp_truck_maintenance` database table
- ✅ Adds the "Truck Maintenance User" role
- ✅ Grants capabilities to Administrator and Editor roles
- ✅ Sets up all necessary WordPress hooks

### **2. Verify Installation**

1. **Check Admin Menu**
   - Look for **Truck Maintenance** in the WordPress admin sidebar
   - You should see: Dashboard, All Trucks, Add Truck, Import Trucks, User Management

2. **Test Database Table**
   - Go to **Truck Maintenance > Add Truck**
   - Try adding a test truck to verify database functionality

3. **Check User Roles**
   - Go to **Users > Add New**
   - Verify "Truck Maintenance User" appears in the Role dropdown

## 👥 **User Setup & Permissions**

### **Administrator Access**
Administrators automatically get full access to:
- All admin panels and truck management
- Add, edit, delete trucks
- Assign users to trucks
- Import/export functionality
- User management

### **Setting Up Regular Users**

1. **Create New Users**
   - Go to **Users > Add New**
   - Set Role to "Truck Maintenance User"
   - Or edit existing users and change their role

2. **Assign Users to Trucks**
   - Go to **Truck Maintenance > Add Truck** (or edit existing)
   - In the "User Access" section, select specific users
   - Leave empty to allow all users access

3. **Grant Editor Access (Optional)**
   - Editors automatically get view and update permissions
   - They cannot add/delete trucks or manage users

## 🎨 **Frontend Setup**

### **Adding Frontend Pages**

1. **Create a Maintenance Dashboard Page**
   ```
   Page Title: Fleet Maintenance
   Content: [truck_maintenance_dashboard]
   ```

2. **Create a Truck List Page**
   ```
   Page Title: Our Trucks
   Content: [truck_maintenance_list user_trucks_only="true"]
   ```

3. **Create Add Truck Page (Admin Only)**
   ```
   Page Title: Add New Truck
   Content: [truck_maintenance_form]
   ```

### **Shortcode Options**

**Dashboard Shortcode:**
```
[truck_maintenance_dashboard]
[truck_maintenance_dashboard user_trucks_only="true"]
```

**Truck List Shortcode:**
```
[truck_maintenance_list]
[truck_maintenance_list user_trucks_only="true"]
[truck_maintenance_list show_add_button="true"]
[truck_maintenance_list user_trucks_only="true" show_add_button="true"]
```

**Add Truck Form:**
```
[truck_maintenance_form]
```

## 📊 **Initial Data Setup**

### **Option 1: Add Trucks Manually**

1. Go to **Truck Maintenance > Add Truck**
2. Fill in required fields:
   - VIN (17 characters)
   - Year, Make, Model
   - Current mileage
   - Maintenance intervals

### **Option 2: Import from CSV**

1. **Prepare CSV File**
   - Download sample from **Truck Maintenance > Import Trucks**
   - Required columns: `vin`, `year`, `make`, `model`
   - Optional: `unit_number`, `current_mileage`, `distance_unit`

2. **Import Process**
   - Go to **Truck Maintenance > Import Trucks**
   - Upload your CSV file
   - Review and confirm import

### **Sample CSV Format:**
```csv
vin,unit_number,year,make,model,current_mileage,distance_unit
1HGBH41JXMN109186,TRUCK001,2020,Freightliner,Cascadia,125000,miles
1HGBH41JXMN109187,TRUCK002,2019,Peterbilt,579,98000,miles
1HGBH41JXMN109188,,2021,Volvo,VNL,75000,miles
```

## 🔧 **Configuration Options**

### **Maintenance Intervals (Default Values)**
- **Oil Change**: 5,000 miles/8,000 km
- **Air Filter**: 15,000 miles/24,000 km  
- **Fuel Filter**: 25,000 miles/40,000 km
- **DPF Cleaning**: 100,000 miles/160,000 km

### **Distance Units**
- Miles (default)
- Kilometers

### **Urgency Scoring**
The plugin automatically calculates urgency scores (0-100) based on:
- Overdue maintenance
- Approaching maintenance dates
- Safety inspection expiry
- Mileage thresholds

## 🎯 **Testing Your Installation**

### **Admin Testing Checklist**
- [ ] Can access Truck Maintenance menu
- [ ] Can add a new truck successfully
- [ ] Can view truck list with urgency indicators
- [ ] Can update maintenance records
- [ ] Can generate QR codes
- [ ] Can import CSV data

### **Frontend Testing Checklist**
- [ ] Shortcodes display correctly on pages
- [ ] Users can view assigned trucks
- [ ] Maintenance cards are clickable
- [ ] Modal forms work for updates
- [ ] Search and sort functionality works
- [ ] Mobile responsive design displays properly

### **User Permission Testing**
- [ ] Regular users see only assigned trucks
- [ ] Users can update maintenance on their trucks
- [ ] Admins can access all features
- [ ] Unauthorized users are properly blocked

## 🚨 **Troubleshooting**

### **Common Issues & Solutions**

**1. Plugin Not Appearing in Admin**
- Verify files are in `/wp-content/plugins/truck-maintenance-manager/`
- Check file permissions (755 for folders, 644 for files)
- Ensure main plugin file exists: `truck-maintenance-manager.php`

**2. Database Table Not Created**
- Check WordPress database user has CREATE TABLE permissions
- Manually activate/deactivate plugin to trigger table creation
- Verify PHP error logs for database errors

**3. Shortcodes Not Working**
- Ensure plugin is activated
- Check user has proper permissions
- Verify shortcode syntax is correct

**4. AJAX Errors**
- Check browser console for JavaScript errors
- Verify WordPress AJAX URL is accessible
- Ensure nonce verification is working

**5. Permission Denied Errors**
- Verify user roles are set correctly
- Check truck assignments for specific users
- Ensure capabilities were added during activation

### **Debug Mode**
Enable WordPress debug mode to see detailed error messages:

```php
// Add to wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### **Manual Database Table Creation**
If the table wasn't created automatically, run this SQL:

```sql
CREATE TABLE wp_truck_maintenance (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    vin varchar(17) NOT NULL UNIQUE,
    unit_number varchar(50) DEFAULT '',
    year int(4) NOT NULL,
    make varchar(100) NOT NULL,
    model varchar(100) NOT NULL,
    current_mileage int(11) DEFAULT 0,
    distance_unit varchar(10) DEFAULT 'miles',
    oil_change_interval int(11) DEFAULT 5000,
    air_filter_interval int(11) DEFAULT 15000,
    fuel_filter_interval int(11) DEFAULT 25000,
    dpf_cleaning_interval int(11) DEFAULT 100000,
    maintenance_history longtext DEFAULT '',
    safety_inspection_date date DEFAULT NULL,
    safety_inspection_expiry date DEFAULT NULL,
    assigned_users longtext DEFAULT '',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);
```

## 🔄 **Updates & Maintenance**

### **Plugin Updates**
- Always backup your database before updating
- Deactivate plugin, replace files, reactivate
- Test functionality after updates

### **Data Backup**
Regular backup recommendations:
- Export truck data via CSV before major changes
- Backup WordPress database regularly
- Keep maintenance history records safe

## 📞 **Support & Resources**

### **Getting Help**
1. Check this installation guide first
2. Review the main README.md for usage instructions
3. Enable debug mode to identify specific errors
4. Check WordPress and PHP error logs

### **File Locations**
- **Plugin Files**: `/wp-content/plugins/truck-maintenance-manager/`
- **Database Table**: `wp_truck_maintenance`
- **Error Logs**: `/wp-content/debug.log` (if debug enabled)

### **Useful WordPress Commands**
```bash
# Check plugin status
wp plugin status truck-maintenance-manager

# Activate plugin
wp plugin activate truck-maintenance-manager

# Check database tables
wp db query "SHOW TABLES LIKE '%truck_maintenance%'"
```

---

## ✅ **Installation Complete!**

Once installed and configured, your Truck Maintenance Manager plugin will provide:

- **Complete fleet management** with urgency-based visual indicators
- **Frontend user access** via shortcodes
- **Mobile-responsive design** for field use
- **QR code generation** for quick truck access
- **Comprehensive maintenance tracking** with history
- **User role management** and truck assignments

Your fleet maintenance system is now ready to use! 🚛✨
