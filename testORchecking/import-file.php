<?php
session_start();
include '../config/connecion.php';
if (isset($_POST['upload'])) {
    
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Retrieve form data
    $zone = $_POST['zone'];
    $restrictedDate = $_POST['restricted-date'];
    
    // Handle file upload
    $targetDir = "temp_uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $fileName = basename($_FILES["excelFile"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

    if(move_uploaded_file($_FILES["excelFile"]["tmp_name"], $targetFilePath)){
        // Load the PHPExcel library (ensure it's installed via Composer)
        require 'vendor/autoload.php';
        
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($targetFilePath);
        $worksheet = $spreadsheet->getActiveSheet();
        
        // Example: Extract data from the first row (adjust as needed)
        $dataToCheck = [];
        foreach ($worksheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            
            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getValue();
            }
            $dataToCheck[] = $rowData; // Assuming the first row is sufficient to identify uniqueness
        }
        
        // Check if the data already exists in the database
        $isDuplicate = false;
        foreach ($dataToCheck as $data) {
            $sql = "SELECT COUNT(*) as count FROM your_table_name WHERE column1 = '{$data[0]}' AND column2 = '{$data[1]}' AND column3 = '{$data[2]}'";
            $result = $conn->query($sql);
            if ($result) {
                $row = $result->fetch_assoc();
                if ($row['count'] > 0) {
                    $isDuplicate = true;
                    break;
                }
            }
        }

        // Delete the temporary file
        unlink($targetFilePath);
        
        if ($isDuplicate) {
            $_SESSION['error'] = "The file's data already exists in the database. Please upload a different file.";
            header("Location: form-page.php"); // Replace with your form page URL
            exit();
        } else {
            // Proceed with actual file processing and data insertion
            $targetDir = "uploads/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            $targetFilePath = $targetDir . $fileName;
            move_uploaded_file($_FILES["excelFile"]["tmp_name"], $targetFilePath);

            foreach ($dataToCheck as $data) {
                $sql = "INSERT INTO your_table_name (column1, column2, column3) VALUES ('{$data[0]}', '{$data[1]}', '{$data[2]}')";
                
                if ($conn->query($sql) !== TRUE) {
                    echo "Error: " . $sql . "<br>" . $conn->error;
                }
            }
            echo "File has been uploaded and data has been inserted successfully.";
        }
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
    
    $conn->close();
}
?>
