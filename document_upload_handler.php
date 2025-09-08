<?php
// Handle document uploads
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Include database connection
include 'config.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Get form data
    $documentType = $_POST['documentType'] ?? '';
    $customDocumentName = $_POST['customDocumentName'] ?? null;
    $userCluster = $_SESSION['cluster_name'] ?? 'No Cluster Assigned';
    
    // Progress report fields (all optional now)
    $progressTitle = $_POST['progressTitle'] ?? null;
    $progressDate = $_POST['progressDate'] ?? null;
    
    // Challenge fields (all optional now)
    $challengeTitle = $_POST['challengeTitle'] ?? null;
    $challengeDescription = $_POST['challengeDescription'] ?? null;
    $challengeImpact = $_POST['challengeImpact'] ?? null;
    $proposedSolution = $_POST['proposedSolution'] ?? null;
    
    // Success story fields (all optional now)
    $successTitle = $_POST['successTitle'] ?? null;
    $successDescription = $_POST['successDescription'] ?? null;
    $beneficiaries = $_POST['beneficiaries'] ?? null;
    $successDate = $_POST['successDate'] ?? null;
    
    // Photo title
    $photoTitle = $_POST['photoTitle'] ?? null;
    
    // Validate required fields
    if (empty($documentType)) {
        throw new Exception('Document type is required. Please select a document type from the dropdown.');
    }
    
    // Create upload directories if they don't exist
    $documentUploadDir = 'uploads/documents/';
    $imageUploadDir = 'uploads/images/';
    
    if (!is_dir($documentUploadDir)) {
        if (!mkdir($documentUploadDir, 0755, true)) {
            throw new Exception('Failed to create documents upload directory');
        }
    }
    
    if (!is_dir($imageUploadDir)) {
        if (!mkdir($imageUploadDir, 0755, true)) {
            throw new Exception('Failed to create images upload directory');
        }
    }
    
    // Handle progress document file uploads
    $progressDocumentFileNames = [];
    $progressDocumentFilePaths = [];
    
    if (isset($_FILES['progressDocumentFiles']) && is_array($_FILES['progressDocumentFiles']['name']) && count($_FILES['progressDocumentFiles']['name']) > 0) {
        for ($i = 0; $i < count($_FILES['progressDocumentFiles']['name']); $i++) {
            if ($_FILES['progressDocumentFiles']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue; // Skip if no file was uploaded
            }
            
            $fileName = $_FILES['progressDocumentFiles']['name'][$i];
            $fileTmpName = $_FILES['progressDocumentFiles']['tmp_name'][$i];
            $fileSize = $_FILES['progressDocumentFiles']['size'][$i];
            $fileError = $_FILES['progressDocumentFiles']['error'][$i];
            $fileType = $_FILES['progressDocumentFiles']['type'][$i];
            
            // Check for upload errors
            if ($fileError !== UPLOAD_ERR_OK) {
                throw new Exception("Error uploading progress document file: $fileName (Error code: $fileError)");
            }
            
            // Validate file size (10MB max)
            if ($fileSize > 10 * 1024 * 1024) {
                throw new Exception("Progress document file too large: $fileName (max 10MB)");
            }
            
            // Validate file type
            $allowedTypes = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 
                             'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception("Invalid progress document file type for: $fileName. Allowed types: PDF, DOCX, XLSX");
            }
            
            // Generate unique file name
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = uniqid() . '_' . time() . '.' . $fileExtension;
            $filePath = $documentUploadDir . $newFileName;
            
            // Move uploaded file to destination
            if (!move_uploaded_file($fileTmpName, $filePath)) {
                throw new Exception("Failed to move uploaded progress document file: $fileName");
            }
            
            $progressDocumentFileNames[] = $fileName;
            $progressDocumentFilePaths[] = $filePath;
        }
    }
    
    // Handle image file uploads
    $imageFileNames = [];
    $imageFilePaths = [];
    $photoTitles = [];
    
    if (isset($_FILES['imageFiles']) && is_array($_FILES['imageFiles']['name']) && count($_FILES['imageFiles']['name']) > 0) {
        for ($i = 0; $i < count($_FILES['imageFiles']['name']); $i++) {
            if ($_FILES['imageFiles']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue; // Skip if no file was uploaded
            }
            
            $fileName = $_FILES['imageFiles']['name'][$i];
            $fileTmpName = $_FILES['imageFiles']['tmp_name'][$i];
            $fileSize = $_FILES['imageFiles']['size'][$i];
            $fileError = $_FILES['imageFiles']['error'][$i];
            $fileType = $_FILES['imageFiles']['type'][$i];
            
            // Check for upload errors
            if ($fileError !== UPLOAD_ERR_OK) {
                throw new Exception("Error uploading image file: $fileName (Error code: $fileError)");
            }
            
            // Validate file size (10MB max)
            if ($fileSize > 10 * 1024 * 1024) {
                throw new Exception("Image file too large: $fileName (max 10MB)");
            }
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception("Invalid image file type for: $fileName. Allowed types: JPG, JPEG, PNG");
            }
            
            // Generate unique file name
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = uniqid() . '_' . time() . '.' . $fileExtension;
            $filePath = $imageUploadDir . $newFileName;
            
            // Move uploaded file to destination
            if (!move_uploaded_file($fileTmpName, $filePath)) {
                throw new Exception("Failed to move uploaded image file: $fileName");
            }
            
            $imageFileNames[] = $fileName;
            $imageFilePaths[] = $filePath;
            $photoTitles[] = $photoTitle; // Store the photo title for each image
        }
    }
    
    // Check if at least one field or file is filled
    $hasContent = !empty($progressDocumentFileNames) || !empty($imageFileNames) ||
                  !empty($progressTitle) || !empty($progressDate) ||
                  !empty($challengeTitle) || !empty($challengeDescription) || !empty($challengeImpact) || !empty($proposedSolution) ||
                  !empty($successTitle) || !empty($successDescription) || !empty($beneficiaries) || !empty($successDate);
    
    if (!$hasContent) {
        throw new Exception('At least one field or file must be filled to submit the report. Please upload at least one file or fill in at least one form field.');
    }
    
    // Insert record into database
    $sql = "INSERT INTO project_documents (
                document_type, 
                custom_document_name, 
                cluster, 
                document_file_names, 
                document_file_paths, 
                image_file_names, 
                image_file_paths, 
                photo_titles,
                progress_title,
                progress_date,
                challenge_title,
                challenge_description,
                challenge_impact,
                proposed_solution,
                success_title,
                success_description,
                beneficiaries,
                success_date,
                uploaded_by
            ) VALUES (
                :document_type, 
                :custom_document_name, 
                :cluster, 
                :document_file_names, 
                :document_file_paths, 
                :image_file_names, 
                :image_file_paths, 
                :photo_titles,
                :progress_title,
                :progress_date,
                :challenge_title,
                :challenge_description,
                :challenge_impact,
                :proposed_solution,
                :success_title,
                :success_description,
                :beneficiaries,
                :success_date,
                :uploaded_by
            )";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':document_type', $documentType);
    $stmt->bindParam(':custom_document_name', $customDocumentName);
    $stmt->bindParam(':cluster', $userCluster);
    
    // Store JSON encoded values in variables to avoid "Only variables should be passed by reference" error
    $jsonProgressDocumentFileNames = json_encode($progressDocumentFileNames);
    $jsonProgressDocumentFilePaths = json_encode($progressDocumentFilePaths);
    $jsonImageFileNames = json_encode($imageFileNames);
    $jsonImageFilePaths = json_encode($imageFilePaths);
    $jsonPhotoTitles = json_encode($photoTitles);
    
    $stmt->bindParam(':document_file_names', $jsonProgressDocumentFileNames);
    $stmt->bindParam(':document_file_paths', $jsonProgressDocumentFilePaths);
    $stmt->bindParam(':image_file_names', $jsonImageFileNames);
    $stmt->bindParam(':image_file_paths', $jsonImageFilePaths);
    $stmt->bindParam(':photo_titles', $jsonPhotoTitles);
    
    $stmt->bindParam(':progress_title', $progressTitle);
    $stmt->bindParam(':progress_date', $progressDate);
    $stmt->bindParam(':challenge_title', $challengeTitle);
    $stmt->bindParam(':challenge_description', $challengeDescription);
    $stmt->bindParam(':challenge_impact', $challengeImpact);
    $stmt->bindParam(':proposed_solution', $proposedSolution);
    $stmt->bindParam(':success_title', $successTitle);
    $stmt->bindParam(':success_description', $successDescription);
    $stmt->bindParam(':beneficiaries', $beneficiaries);
    $stmt->bindParam(':success_date', $successDate);
    $stmt->bindParam(':uploaded_by', $_SESSION['username']);
    
    if ($stmt->execute()) {
        // Set the correct content type for successful response
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Project report uploaded successfully']);
    } else {
        throw new Exception('Failed to save document information to database');
    }
    
} catch (Exception $e) {
    error_log("Upload error: " . $e->getMessage()); // Log the error for debugging
    // Set the correct content type for error response
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
