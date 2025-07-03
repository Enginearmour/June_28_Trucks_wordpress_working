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
        
        <ul style="margin: 20px 0; padding-left: 30px;">
            <li><strong>vin</strong> - Vehicle Identification Number (required)</li>
            <li><strong>unit_number</strong> - Unit number (optional)</li>
            <li><strong>year</strong> - Year (required)</li>
            <li><strong>make</strong> - Make (required)</li>
            <li><strong>model</strong> - Model (required)</li>
            <li><strong>current_mileage</strong> - Current mileage (optional, defaults to 0)</li>
            <li><strong>distance_unit</strong> - Distance unit (optional, defaults to 'miles')</li>
        </ul>
        
        <form id="tmm-import-form" enctype="multipart/form-data">
            <div class="tmm-form-group">
                <label for="csv_file">CSV File *</label>
                <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
            </div>
            
            <button type="submit" class="tmm-btn">Import Trucks</button>
        </form>
    </div>
    
    <div class="tmm-card">
        <h3>Sample CSV Format</h3>
        <p>Download a sample CSV file to see the correct format:</p>
        
        <div style="background: #f5f5f5; padding: 15px; border-radius: 4px; font-family: monospace; margin: 15px 0;">
vin,unit_number,year,make,model,current_mileage,distance_unit<br>
1HGBH41JXMN109186,101,2020,Honda,Civic,25000,miles<br>
2HGBH41JXMN109187,102,2019,Toyota,Camry,30000,miles<br>
3HGBH41JXMN109188,103,2021,Ford,F-150,15000,miles
        </div>
        
        <a href="data:text/csv;charset=utf-8,vin%2Cunit_number%2Cyear%2Cmake%2Cmodel%2Ccurrent_mileage%2Cdistance_unit%0A1HGBH41JXMN109186%2C101%2C2020%2CHonda%2CCivic%2C25000%2Cmiles%0A2HGBH41JXMN109187%2C102%2C2019%2CToyota%2CCamry%2C30000%2Cmiles%0A3HGBH41JXMN109188%2C103%2C2021%2CFord%2CF-150%2C15000%2Cmiles" 
           download="sample-trucks.csv" class="tmm-btn secondary">Download Sample CSV</a>
    </div>
</div>
