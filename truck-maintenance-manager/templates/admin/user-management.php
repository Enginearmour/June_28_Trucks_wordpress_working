<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get all users with truck maintenance capabilities
$truck_users = get_users(array(
    'meta_query' => array(
        'relation' => 'OR',
        array(
            'key' => 'wp_capabilities',
            'value' => 'truck_maintenance_user',
            'compare' => 'LIKE'
        ),
        array(
            'key' => 'wp_capabilities',
            'value' => 'administrator',
            'compare' => 'LIKE'
        ),
        array(
            'key' => 'wp_capabilities',
            'value' => 'editor',
            'compare' => 'LIKE'
        )
    )
));

// Get all trucks for assignment overview
global $wpdb;
$table_name = $wpdb->prefix . 'truck_maintenance';
$trucks = $wpdb->get_results("SELECT id, vin, unit_number, year, make, model, assigned_users FROM $table_name ORDER BY year DESC, make ASC");
?>

<div class="tmm-container">
    <h1>User Management</h1>
    
    <div class="tmm-card">
        <h3>Users with Truck Maintenance Access</h3>
        
        <?php if (empty($truck_users)): ?>
            <p>No users found with truck maintenance permissions.</p>
        <?php else: ?>
            <table class="tmm-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Assigned Trucks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($truck_users as $user): 
                        $user_trucks = array();
                        foreach ($trucks as $truck) {
                            $assigned_users = json_decode($truck->assigned_users, true) ?: array();
                            if (empty($assigned_users) || in_array($user->ID, $assigned_users)) {
                                $user_trucks[] = $truck;
                            }
                        }
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html($user->display_name); ?></strong></td>
                            <td><?php echo esc_html($user->user_email); ?></td>
                            <td>
                                <?php 
                                $roles = $user->roles;
                                echo esc_html(ucfirst($roles[0]));
                                ?>
                            </td>
                            <td>
                                <?php if (empty($user_trucks)): ?>
                                    <span style="color: #666;">No trucks assigned</span>
                                <?php else: ?>
                                    <span style="color: #1976d2; font-weight: 600;">
                                        <?php echo count($user_trucks); ?> truck(s)
                                    </span>
                                    <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                        <?php 
                                        $truck_names = array_slice(array_map(function($truck) {
                                            return $truck->year . ' ' . $truck->make . ' ' . $truck->model . 
                                                   ($truck->unit_number ? ' (' . $truck->unit_number . ')' : '');
                                        }, $user_trucks), 0, 3);
                                        
                                        echo esc_html(implode(', ', $truck_names));
                                        if (count($user_trucks) > 3) {
                                            echo ' and ' . (count($user_trucks) - 3) . ' more...';
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('user-edit.php?user_id=' . $user->ID); ?>" 
                                   class="tmm-btn small">Edit User</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <div class="tmm-card">
        <h3>Truck Assignments Overview</h3>
        
        <?php if (empty($trucks)): ?>
            <p>No trucks found. <a href="<?php echo admin_url('admin.php?page=truck-maintenance-add'); ?>">Add your first truck</a>.</p>
        <?php else: ?>
            <table class="tmm-table">
                <thead>
                    <tr>
                        <th>Truck</th>
                        <th>VIN</th>
                        <th>Unit Number</th>
                        <th>Assigned Users</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trucks as $truck): 
                        $assigned_users = json_decode($truck->assigned_users, true) ?: array();
                        $assigned_user_names = array();
                        
                        if (empty($assigned_users)) {
                            $assigned_user_names[] = 'All users';
                        } else {
                            foreach ($assigned_users as $user_id) {
                                $user = get_user_by('id', $user_id);
                                if ($user) {
                                    $assigned_user_names[] = $user->display_name;
                                }
                            }
                        }
                    ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($truck->year . ' ' . $truck->make . ' ' . $truck->model); ?></strong>
                            </td>
                            <td><?php echo esc_html($truck->vin); ?></td>
                            <td>
                                <?php echo $truck->unit_number ? esc_html($truck->unit_number) : '<span style="color: #666;">—</span>'; ?>
                            </td>
                            <td>
                                <?php if (empty($assigned_users)): ?>
                                    <span style="color: #1976d2; font-weight: 600;">All users</span>
                                <?php else: ?>
                                    <span style="color: #333;">
                                        <?php echo esc_html(implode(', ', array_slice($assigned_user_names, 0, 3))); ?>
                                        <?php if (count($assigned_user_names) > 3): ?>
                                            and <?php echo count($assigned_user_names) - 3; ?> more...
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=truck-maintenance-add&edit=' . $truck->id); ?>" 
                                   class="tmm-btn small">Edit Assignments</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <div class="tmm-card">
        <h3>Add New Truck Maintenance User</h3>
        <p>To give a user access to truck maintenance:</p>
        <ol>
            <li>Go to <a href="<?php echo admin_url('users.php'); ?>">Users</a> in the WordPress admin</li>
            <li>Edit the user you want to give access to</li>
            <li>Change their role to "Truck Maintenance User" or add the capability manually</li>
            <li>Assign them to specific trucks when adding/editing trucks</li>
        </ol>
        
        <p style="margin-top: 20px;">
            <a href="<?php echo admin_url('user-new.php'); ?>" class="tmm-btn">Add New User</a>
            <a href="<?php echo admin_url('users.php'); ?>" class="tmm-btn secondary">Manage Existing Users</a>
        </p>
    </div>
</div>
