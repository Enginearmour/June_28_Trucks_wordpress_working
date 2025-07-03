<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="tmm-container">
    <h1>Import Trucks</h1>
    
    <div class="tmm-card">
        <h3>CSV Import</h3>
        <p>Upload a CSV file to import multiple trucks at once. The CSV file should have the following columns:</p>
        
        <div style="background: #f9f9f9; padding: 15px; border-radius: 4px; margin: 20px 0;">
            <strong>Required columns:</strong> vin, year, make, model<br>
            <strong>Optional columns:</strong> unit_number, current_mileage, distance_unit
        </div>
        
        <form id="tmm-import-form" enctype="multipart/form-data">
            <div class="tmm-form-group">
                <label for="csv_file">CSV File *</label>
                <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
            </div>
            
            <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 20px;">
                <a href="<?php echo admin_url('admin.php?page=truck-maintenance-list'); ?>" 
                   class="tmm-btn secondary">Cancel</a>
                <button type="submit" class="tmm-btn">Import Trucks</button>
            </div>
        </form>
    </div>
    
    <div class="tmm-card">
        <h3>Sample CSV Format</h3>
        <p>Download this sample CSV file to see the correct format:</p>
        
        <div style="background: #f9f9f9; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 12px; overflow-x: auto;">
vin,unit_number,year,make,model,current_mileage,distance_unit<br>
1HGBH41JXMN109186,TRUCK001,2020,Freightliner,Cascadia,125000,miles<br>
1HGBH41JXMN109187,TRUCK002,2019,Peterbilt,579,98000,miles<br>
1HGBH41JXMN109188,,2021,Volvo,VNL,75000,miles
        </div>
        
        <p style="margin-top: 15px;">
            <a href="data:text/csv;charset=utf-8,vin%2Cunit_number%2Cyear%2Cmake%2Cmodel%2Ccurrent_mileage%2Cdistance_unit%0A1HGBH41JXMN109186%2CTRUCK001%2C2020%2CFreightliner%2CCascadia%2C125000%2Cmiles%0A1HGBH41JXMN109187%2CTRUCK002%2C2019%2CPeterbilt%2C579%2C98000%2Cmiles%0A1HGBH41JXMN109188%2C%2C2021%2CVolvo%2CVNL%2C75000%2Cmiles" 
               download="sample_trucks.csv" class="tmm-btn secondary">Download Sample CSV</a>
        </p>
    </div>
</div>
