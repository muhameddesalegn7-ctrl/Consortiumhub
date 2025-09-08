<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Include database configuration
define('INCLUDED_SETUP', true);
include 'setup_database.php';

// Get document ID
$document_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($document_id <= 0) {
    echo "<p>Invalid document ID</p>";
    exit();
}

// Fetch document details
$sql = "SELECT * FROM project_documents WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $document_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p>Document not found</p>";
    exit();
}

$document = $result->fetch_assoc();

// Check if this is an AJAX request for modal content
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Check if download all request
if (isset($_GET['download_all'])) {
    // Check if ZipArchive is available
    if (!class_exists('ZipArchive')) {
        echo "<p>ZipArchive is not available on this server</p>";
        exit();
    }
    
    $docFiles = json_decode($document['document_file_names'], true);
    $docPaths = json_decode($document['document_file_paths'], true);
    $imageFiles = json_decode($document['image_file_names'], true);
    $imagePaths = json_decode($document['image_file_paths'], true);
    
    // Filter out any null or empty values
    if (is_array($docFiles)) {
        $docFiles = array_filter($docFiles, function($file) { return !empty($file); });
    }
    if (is_array($docPaths)) {
        $docPaths = array_filter($docPaths, function($path) { return !empty($path) && file_exists($path); });
    }
    if (is_array($imageFiles)) {
        $imageFiles = array_filter($imageFiles, function($file) { return !empty($file); });
    }
    if (is_array($imagePaths)) {
        $imagePaths = array_filter($imagePaths, function($path) { return !empty($path) && file_exists($path); });
    }
    
    // Check what types of files we have
    $hasDocuments = is_array($docFiles) && count($docFiles) > 0;
    $hasImages = is_array($imageFiles) && count($imageFiles) > 0;
    
    // Handle single file type downloads
    if ($hasDocuments && !$hasImages) {
        // Only documents - create zip with documents only
        $zipName = tempnam(sys_get_temp_dir(), 'doc_files_') . '.zip';
        $zip = new ZipArchive();
        
        if ($zip->open($zipName, ZipArchive::CREATE) === TRUE) {
            // Add document files
            if (is_array($docFiles) && is_array($docPaths) && count($docFiles) === count($docPaths)) {
                for ($i = 0; $i < count($docFiles); $i++) {
                    if (file_exists($docPaths[$i])) {
                        $zip->addFile($docPaths[$i], 'documents/' . basename($docFiles[$i]));
                    }
                }
            }
            
            $zip->close();
            
            // Send the zip file to the browser
            if (file_exists($zipName)) {
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="document_files.zip"');
                header('Content-Length: ' . filesize($zipName));
                readfile($zipName);
                unlink($zipName); // Delete the temporary zip file
                exit();
            }
        }
    } elseif ($hasImages && !$hasDocuments) {
        // Only images - create zip with images only
        $zipName = tempnam(sys_get_temp_dir(), 'image_files_') . '.zip';
        $zip = new ZipArchive();
        
        if ($zip->open($zipName, ZipArchive::CREATE) === TRUE) {
            // Add image files
            if (is_array($imageFiles) && is_array($imagePaths) && count($imageFiles) === count($imagePaths)) {
                for ($i = 0; $i < count($imageFiles); $i++) {
                    if (file_exists($imagePaths[$i])) {
                        $zip->addFile($imagePaths[$i], 'images/' . basename($imageFiles[$i]));
                    }
                }
            }
            
            $zip->close();
            
            // Send the zip file to the browser
            if (file_exists($zipName)) {
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="image_files.zip"');
                header('Content-Length: ' . filesize($zipName));
                readfile($zipName);
                unlink($zipName); // Delete the temporary zip file
                exit();
            }
        }
    } elseif ($hasDocuments && $hasImages) {
        // Both documents and images - create combined zip
        $zipName = tempnam(sys_get_temp_dir(), 'all_files_') . '.zip';
        $zip = new ZipArchive();
        
        if ($zip->open($zipName, ZipArchive::CREATE) === TRUE) {
            // Add document files
            if (is_array($docFiles) && is_array($docPaths) && count($docFiles) === count($docPaths)) {
                for ($i = 0; $i < count($docFiles); $i++) {
                    if (file_exists($docPaths[$i])) {
                        $zip->addFile($docPaths[$i], 'documents/' . basename($docFiles[$i]));
                    }
                }
            }
            
            // Add image files
            if (is_array($imageFiles) && is_array($imagePaths) && count($imageFiles) === count($imagePaths)) {
                for ($i = 0; $i < count($imageFiles); $i++) {
                    if (file_exists($imagePaths[$i])) {
                        $zip->addFile($imagePaths[$i], 'images/' . basename($imageFiles[$i]));
                    }
                }
            }
            
            $zip->close();
            
            // Send the zip file to the browser
            if (file_exists($zipName)) {
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="all_files.zip"');
                header('Content-Length: ' . filesize($zipName));
                readfile($zipName);
                unlink($zipName); // Delete the temporary zip file
                exit();
            }
        }
    }
    
    echo "<p>No files available for download</p>";
    exit();
}

// Handle individual file download
if (isset($_GET['download_file'])) {
    $fileType = $_GET['file_type']; // 'document' or 'image'
    $fileIndex = intval($_GET['file_index']);
    
    if ($fileType === 'document') {
        $docFiles = json_decode($document['document_file_names'], true);
        $docPaths = json_decode($document['document_file_paths'], true);
        
        if (is_array($docFiles) && is_array($docPaths) && 
            isset($docFiles[$fileIndex]) && isset($docPaths[$fileIndex]) && 
            file_exists($docPaths[$fileIndex])) {
            
            $filePath = $docPaths[$fileIndex];
            $fileName = basename($docFiles[$fileIndex]);
            
            // Set appropriate headers for download
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit();
        }
    } elseif ($fileType === 'image') {
        $imageFiles = json_decode($document['image_file_names'], true);
        $imagePaths = json_decode($document['image_file_paths'], true);
        
        if (is_array($imageFiles) && is_array($imagePaths) && 
            isset($imageFiles[$fileIndex]) && isset($imagePaths[$fileIndex]) && 
            file_exists($imagePaths[$fileIndex])) {
            
            $filePath = $imagePaths[$fileIndex];
            $fileName = basename($imageFiles[$fileIndex]);
            
            // Set appropriate headers for download
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit();
        }
    }
    
    echo "<p>File not found</p>";
    exit();
}

// If this is an AJAX request, only output the content without full HTML structure
if ($is_ajax_request):
?>
<div class="space-y-6">
    <!-- Document Header -->
    <div class="document-header">
        <div>
            <h2>
                <?php 
                if (!empty($document['progress_title'])) {
                    echo htmlspecialchars($document['progress_title']);
                } elseif (!empty($document['challenge_title'])) {
                    echo htmlspecialchars($document['challenge_title']);
                } elseif (!empty($document['success_title'])) {
                    echo htmlspecialchars($document['success_title']);
                } else {
                    echo "Document #" . $document['id'];
                }
                ?>
            </h2>
            <p>
                Uploaded by <?php echo htmlspecialchars($document['uploaded_by']); ?> on 
                <?php echo date('M j, Y \a\t g:i A', strtotime($document['uploaded_at'])); ?>
            </p>
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
            <span class="badge badge-primary">
                <?php echo htmlspecialchars($document['cluster']); ?>
            </span>
            <span class="badge badge-<?php 
                if (!empty($document['document_file_names']) && $document['document_file_names'] !== '[]' && $document['document_file_names'] !== '') {
                    echo 'warning';
                } elseif (!empty($document['image_file_names']) && $document['image_file_names'] !== '[]' && $document['image_file_names'] !== '') {
                    echo 'success';
                } elseif (!empty($document['success_title'])) {
                    echo 'success';
                } elseif (!empty($document['challenge_title'])) {
                    echo 'danger';
                } elseif (!empty($document['progress_title'])) {
                    echo 'primary';
                } else {
                    echo 'secondary';
                }
            ?>">
                <?php 
                if (!empty($document['document_file_names']) && $document['document_file_names'] !== '[]' && $document['document_file_names'] !== '') {
                    echo 'Document';
                } elseif (!empty($document['image_file_names']) && $document['image_file_names'] !== '[]' && $document['image_file_names'] !== '') {
                    echo 'Image';
                } elseif (!empty($document['success_title'])) {
                    echo 'Success Story';
                } elseif (!empty($document['challenge_title'])) {
                    echo 'Challenge';
                } elseif (!empty($document['progress_title'])) {
                    echo 'Progress Report';
                } else {
                    echo 'Other';
                }
                ?>
            </span>
            <?php 
            // Show download all button if there are files
            $docFiles = !empty($document['document_file_names']) ? json_decode($document['document_file_names'], true) : [];
            $imageFiles = !empty($document['image_file_names']) ? json_decode($document['image_file_names'], true) : [];
            
            // Filter out empty values
            if (is_array($docFiles)) {
                $docFiles = array_filter($docFiles, function($file) { return !empty($file); });
            }
            if (is_array($imageFiles)) {
                $imageFiles = array_filter($imageFiles, function($file) { return !empty($file); });
            }
            
            $hasDocuments = is_array($docFiles) && count($docFiles) > 0;
            $hasImages = is_array($imageFiles) && count($imageFiles) > 0;
            
            if ($hasDocuments || $hasImages): ?>
                <a href="admin_document_detail.php?id=<?php echo $document_id; ?>&download_all=1" class="btn-primary" target="_blank" download>
                    <i class="fas fa-download mr-2"></i> Download All Files
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Document Content -->
    <div class="grid grid-cols-1 lg:grid-cols-2">
        <!-- Left Column -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Progress Report Section -->
            <?php if (!empty($document['progress_title']) || !empty($document['progress_date'])): ?>
                <div class="glass-card" style="padding: 1.25rem;">
                    <h3 style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-chart-line" style="color: #4361ee;"></i>
                        Progress Report
                    </h3>
                    <?php if (!empty($document['progress_title'])): ?>
                        <div style="margin-bottom: 0.75rem;">
                            <p style="font-size: 0.875rem; color: #64748b;">Title</p>
                            <p style="font-weight: 500;"><?php echo htmlspecialchars($document['progress_title']); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($document['progress_date'])): ?>
                        <div style="margin-bottom: 0.75rem;">
                            <p style="font-size: 0.875rem; color: #64748b;">Date</p>
                            <p style="font-weight: 500;"><?php echo date('M j, Y', strtotime($document['progress_date'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- Challenge Section -->
            <?php if (!empty($document['challenge_title']) || !empty($document['challenge_description'])): ?>
                <div class="glass-card" style="padding: 1.25rem;">
                    <h3 style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i>
                        Challenge
                    </h3>
                    <?php if (!empty($document['challenge_title'])): ?>
                        <div style="margin-bottom: 0.75rem;">
                            <p style="font-size: 0.875rem; color: #64748b;">Title</p>
                            <p style="font-weight: 500;"><?php echo htmlspecialchars($document['challenge_title']); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($document['challenge_description'])): ?>
                        <div style="margin-bottom: 0.75rem;">
                            <p style="font-size: 0.875rem; color: #64748b;">Description</p>
                            <p style="font-weight: 500;"><?php echo nl2br(htmlspecialchars($document['challenge_description'])); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($document['challenge_impact'])): ?>
                        <div style="margin-bottom: 0.75rem;">
                            <p style="font-size: 0.875rem; color: #64748b;">Impact</p>
                            <p style="font-weight: 500;"><?php echo nl2br(htmlspecialchars($document['challenge_impact'])); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($document['proposed_solution'])): ?>
                        <div>
                            <p style="font-size: 0.875rem; color: #64748b;">Proposed Solution</p>
                            <p style="font-weight: 500;"><?php echo nl2br(htmlspecialchars($document['proposed_solution'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- Success Story Section -->
            <?php if (!empty($document['success_title']) || !empty($document['success_description'])): ?>
                <div class="glass-card" style="padding: 1.25rem;">
                    <h3 style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-trophy" style="color: #4cc9f0;"></i>
                        Success Story
                    </h3>
                    <?php if (!empty($document['success_title'])): ?>
                        <div style="margin-bottom: 0.75rem;">
                            <p style="font-size: 0.875rem; color: #64748b;">Title</p>
                            <p style="font-weight: 500;"><?php echo htmlspecialchars($document['success_title']); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($document['success_description'])): ?>
                        <div style="margin-bottom: 0.75rem;">
                            <p style="font-size: 0.875rem; color: #64748b;">Description</p>
                            <p style="font-weight: 500;"><?php echo nl2br(htmlspecialchars($document['success_description'])); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($document['beneficiaries'])): ?>
                        <div style="margin-bottom: 0.75rem;">
                            <p style="font-size: 0.875rem; color: #64748b;">Beneficiaries</p>
                            <p style="font-weight: 500;"><?php echo number_format($document['beneficiaries']); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($document['success_date'])): ?>
                        <div>
                            <p style="font-size: 0.875rem; color: #64748b;">Date</p>
                            <p style="font-weight: 500;"><?php echo date('M j, Y', strtotime($document['success_date'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Right Column -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Document Files Section -->
            <?php if (!empty($document['document_file_names']) && $document['document_file_names'] !== '[]' && $document['document_file_names'] !== 'null'): ?>
                <div class="glass-card" style="padding: 1.25rem;">
                    <h3 style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-file-alt" style="color: #f8961e;"></i>
                        Document Files
                    </h3>
                    <?php
                    $docFiles = json_decode($document['document_file_names'], true);
                    $docPaths = json_decode($document['document_file_paths'], true);
                    
                    if (is_array($docFiles) && is_array($docPaths) && count($docFiles) === count($docPaths)):
                    ?>
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <?php for ($i = 0; $i < count($docFiles); $i++): ?>
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem; background: rgba(248, 250, 252, 0.8); border-radius: 12px;">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div style="width: 2.5rem; height: 2.5rem; border-radius: 12px; background: rgba(248, 150, 30, 0.1); display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-file" style="color: #f8961e;"></i>
                                        </div>
                                        <div>
                                            <p style="font-weight: 500; font-size: 0.875rem;"><?php echo htmlspecialchars($docFiles[$i]); ?></p>
                                            <p style="font-size: 0.75rem; color: #64748b;">
                                                <?php 
                                                $fileExt = pathinfo($docFiles[$i], PATHINFO_EXTENSION);
                                                echo strtoupper($fileExt) . ' File';
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                    <a href="admin_document_detail.php?id=<?php echo $document_id; ?>&download_file=1&file_type=document&file_index=<?php echo $i; ?>" class="btn-primary" target="_blank" download>
                                        <i class="fas fa-download" style="margin-right: 0.25rem;"></i> Download
                                    </a>
                                </div>
                            <?php endfor; ?>
                        </div>
                    <?php else: ?>
                        <p style="color: #64748b;">No document files available</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- Image Files Section -->
            <?php if (!empty($document['image_file_names']) && $document['image_file_names'] !== '[]' && $document['image_file_names'] !== 'null'): ?>
                <div class="glass-card" style="padding: 1.25rem;">
                    <h3 style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-images" style="color: #4cc9f0;"></i>
                        Images
                    </h3>
                    <?php
                    $imageFiles = json_decode($document['image_file_names'], true);
                    $imagePaths = json_decode($document['image_file_paths'], true);
                    $photoTitles = !empty($document['photo_titles']) ? json_decode($document['photo_titles'], true) : [];
                    
                    if (is_array($imageFiles) && is_array($imagePaths) && count($imageFiles) === count($imagePaths)):
                    ?>
                        <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem;">
                            <?php for ($i = 0; $i < count($imageFiles); $i++): ?>
                                <div>
                                    <div style="position: relative; overflow: hidden; border-radius: 12px; background: #f1f5f9; border: 1px solid rgba(0, 0, 0, 0.1);">
                                        <?php if (file_exists($imagePaths[$i])): ?>
                                            <img src="<?php echo htmlspecialchars($imagePaths[$i]); ?>" alt="<?php echo !empty($photoTitles[$i]) ? htmlspecialchars($photoTitles[$i]) : 'Project Image'; ?>" style="width: 100%; height: 8rem; object-fit: cover;">
                                        <?php else: ?>
                                            <div style="width: 100%; height: 8rem; display: flex; align-items: center; justify-content: center; background: #f1f5f9;">
                                                <i class="fas fa-image" style="color: #94a3b8; font-size: 1.5rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div style="margin-top: 0.5rem;">
                                        <p style="font-size: 0.875rem; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($imageFiles[$i]); ?></p>
                                        <?php if (!empty($photoTitles[$i])): ?>
                                            <p style="font-size: 0.75rem; color: #64748b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($photoTitles[$i]); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div style="margin-top: 0.5rem;">
                                        <a href="admin_document_detail.php?id=<?php echo $document_id; ?>&download_file=1&file_type=image&file_index=<?php echo $i; ?>" class="btn-primary" style="display: inline-block; width: 100%; text-align: center;" target="_blank" download>
                                            <i class="fas fa-download" style="margin-right: 0.25rem;"></i> Download
                                        </a>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    <?php else: ?>
                        <p style="color: #64748b;">No images available</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
// If this is a direct request, show the full page
else:
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Viewer | Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --background: #f0f2f5;
            --card-bg: rgba(255, 255, 255, 0.8);
            --text-primary: #2d3748;
            --text-secondary: #718096;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: var(--text-primary);
            line-height: 1.6;
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .space-y-6 > * + * {
            margin-top: 1.5rem;
        }

        /* Header Styles */
        .document-header {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding: 1.5rem;
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.1);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        @media (min-width: 640px) {
            .document-header {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
        }

        .document-header h2 {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .document-header p {
            color: var(--text-secondary);
            margin-top: 0.5rem;
        }

        /* Badge Styles */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 500;
            color: white;
        }

        .badge-primary {
            background: var(--primary);
        }

        .badge-secondary {
            background: var(--secondary);
        }

        .badge-success {
            background: var(--success);
        }

        .badge-danger {
            background: var(--danger);
        }

        .badge-warning {
            background: var(--warning);
        }

        .badge-info {
            background: var(--info);
        }

        /* Grid Layout */
        .grid {
            display: grid;
            gap: 1.5rem;
        }

        @media (min-width: 1024px) {
            .grid-cols-1 {
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }
            
            .lg\:grid-cols-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        /* Card Styles */
        .glass-card {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.1);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(31, 38, 135, 0.2);
        }

        .glass-card h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .glass-card > div {
            margin-bottom: 1rem;
        }

        .glass-card > div:last-child {
            margin-bottom: 0;
        }

        .glass-card p.text-sm {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .glass-card p.font-medium {
            font-weight: 500;
            color: var(--text-primary);
        }

        /* Button Styles */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            background: var(--primary);
            color: white;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.3s ease;
        }

        .btn-primary:hover {
            background: var(--primary-light);
        }

        /* File and Image Styles */
        .bg-gray-50 {
            background: rgba(248, 250, 252, 0.8);
        }

        .rounded-lg {
            border-radius: 12px;
        }

        .group:hover .group-hover\:scale-105 {
            transform: scale(1.05);
        }

        .transition-transform {
            transition: transform 0.3s ease;
        }

        .duration-300 {
            transition-duration: 300ms;
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .glass-card {
            animation: fadeIn 0.5s ease-out;
        }

        .document-header {
            animation: fadeIn 0.4s ease-out;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-light);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="space-y-6">
            <!-- Document Header -->
            <div class="document-header">
                <div>
                    <h2>
                        <?php 
                        if (!empty($document['progress_title'])) {
                            echo htmlspecialchars($document['progress_title']);
                        } elseif (!empty($document['challenge_title'])) {
                            echo htmlspecialchars($document['challenge_title']);
                        } elseif (!empty($document['success_title'])) {
                            echo htmlspecialchars($document['success_title']);
                        } else {
                            echo "Document #" . $document['id'];
                        }
                        ?>
                    </h2>
                    <p>
                        Uploaded by <?php echo htmlspecialchars($document['uploaded_by']); ?> on 
                        <?php echo date('M j, Y \a\t g:i A', strtotime($document['uploaded_at'])); ?>
                    </p>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    <span class="badge badge-primary">
                        <?php echo htmlspecialchars($document['cluster']); ?>
                    </span>
                    <span class="badge badge-<?php 
                        if (!empty($document['document_file_names']) && $document['document_file_names'] !== '[]' && $document['document_file_names'] !== '') {
                            echo 'warning';
                        } elseif (!empty($document['image_file_names']) && $document['image_file_names'] !== '[]' && $document['image_file_names'] !== '') {
                            echo 'success';
                        } elseif (!empty($document['success_title'])) {
                            echo 'success';
                        } elseif (!empty($document['challenge_title'])) {
                            echo 'danger';
                        } elseif (!empty($document['progress_title'])) {
                            echo 'primary';
                        } else {
                            echo 'secondary';
                        }
                    ?>">
                        <?php 
                        if (!empty($document['document_file_names']) && $document['document_file_names'] !== '[]' && $document['document_file_names'] !== '') {
                            echo 'Document';
                        } elseif (!empty($document['image_file_names']) && $document['image_file_names'] !== '[]' && $document['image_file_names'] !== '') {
                            echo 'Image';
                        } elseif (!empty($document['success_title'])) {
                            echo 'Success Story';
                        } elseif (!empty($document['challenge_title'])) {
                            echo 'Challenge';
                        } elseif (!empty($document['progress_title'])) {
                            echo 'Progress Report';
                        } else {
                            echo 'Other';
                        }
                        ?>
                    </span>
                    <?php 
                    // Show download all button if there are files
                    $docFiles = !empty($document['document_file_names']) ? json_decode($document['document_file_names'], true) : [];
                    $imageFiles = !empty($document['image_file_names']) ? json_decode($document['image_file_names'], true) : [];
                    
                    // Filter out empty values
                    if (is_array($docFiles)) {
                        $docFiles = array_filter($docFiles, function($file) { return !empty($file); });
                    }
                    if (is_array($imageFiles)) {
                        $imageFiles = array_filter($imageFiles, function($file) { return !empty($file); });
                    }
                    
                    $hasDocuments = is_array($docFiles) && count($docFiles) > 0;
                    $hasImages = is_array($imageFiles) && count($imageFiles) > 0;
                    
                    if ($hasDocuments || $hasImages): ?>
                        <a href="admin_document_detail.php?id=<?php echo $document_id; ?>&download_all=1" class="btn-primary" target="_blank" download>
                            <i class="fas fa-download mr-2"></i> Download All Files
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Document Content -->
            <div class="grid grid-cols-1 lg:grid-cols-2">
                <!-- Left Column -->
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <!-- Progress Report Section -->
                    <?php if (!empty($document['progress_title']) || !empty($document['progress_date'])): ?>
                        <div class="glass-card" style="padding: 1.25rem;">
                            <h3 style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-chart-line" style="color: var(--primary);"></i>
                                Progress Report
                            </h3>
                            <?php if (!empty($document['progress_title'])): ?>
                                <div style="margin-bottom: 0.75rem;">
                                    <p class="text-sm">Title</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($document['progress_title']); ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($document['progress_date'])): ?>
                                <div style="margin-bottom: 0.75rem;">
                                    <p class="text-sm">Date</p>
                                    <p class="font-medium"><?php echo date('M j, Y', strtotime($document['progress_date'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Challenge Section -->
                    <?php if (!empty($document['challenge_title']) || !empty($document['challenge_description'])): ?>
                        <div class="glass-card" style="padding: 1.25rem;">
                            <h3 style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-exclamation-triangle" style="color: var(--danger);"></i>
                                Challenge
                            </h3>
                            <?php if (!empty($document['challenge_title'])): ?>
                                <div style="margin-bottom: 0.75rem;">
                                    <p class="text-sm">Title</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($document['challenge_title']); ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($document['challenge_description'])): ?>
                                <div style="margin-bottom: 0.75rem;">
                                    <p class="text-sm">Description</p>
                                    <p class="font-medium"><?php echo nl2br(htmlspecialchars($document['challenge_description'])); ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($document['challenge_impact'])): ?>
                                <div style="margin-bottom: 0.75rem;">
                                    <p class="text-sm">Impact</p>
                                    <p class="font-medium"><?php echo nl2br(htmlspecialchars($document['challenge_impact'])); ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($document['proposed_solution'])): ?>
                                <div>
                                    <p class="text-sm">Proposed Solution</p>
                                    <p class="font-medium"><?php echo nl2br(htmlspecialchars($document['proposed_solution'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Success Story Section -->
                    <?php if (!empty($document['success_title']) || !empty($document['success_description'])): ?>
                        <div class="glass-card" style="padding: 1.25rem;">
                            <h3 style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-trophy" style="color: var(--success);"></i>
                                Success Story
                            </h3>
                            <?php if (!empty($document['success_title'])): ?>
                                <div style="margin-bottom: 0.75rem;">
                                    <p class="text-sm">Title</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($document['success_title']); ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($document['success_description'])): ?>
                                <div style="margin-bottom: 0.75rem;">
                                    <p class="text-sm">Description</p>
                                    <p class="font-medium"><?php echo nl2br(htmlspecialchars($document['success_description'])); ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($document['beneficiaries'])): ?>
                                <div style="margin-bottom: 0.75rem;">
                                    <p class="text-sm">Beneficiaries</p>
                                    <p class="font-medium"><?php echo number_format($document['beneficiaries']); ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($document['success_date'])): ?>
                                <div>
                                    <p class="text-sm">Date</p>
                                    <p class="font-medium"><?php echo date('M j, Y', strtotime($document['success_date'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Right Column -->
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <!-- Document Files Section -->
                    <?php if (!empty($document['document_file_names']) && $document['document_file_names'] !== '[]' && $document['document_file_names'] !== 'null'): ?>
                        <div class="glass-card" style="padding: 1.25rem;">
                            <h3 style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-file-alt" style="color: var(--warning);"></i>
                                Document Files
                            </h3>
                            <?php
                            $docFiles = json_decode($document['document_file_names'], true);
                            $docPaths = json_decode($document['document_file_paths'], true);
                            
                            if (is_array($docFiles) && is_array($docPaths) && count($docFiles) === count($docPaths)):
                            ?>
                                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                    <?php for ($i = 0; $i < count($docFiles); $i++): ?>
                                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem; background: rgba(248, 250, 252, 0.8); border-radius: 12px;">
                                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                <div style="width: 2.5rem; height: 2.5rem; border-radius: 12px; background: rgba(248, 150, 30, 0.1); display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-file" style="color: var(--warning);"></i>
                                                </div>
                                                <div>
                                                    <p style="font-weight: 500; font-size: 0.875rem;"><?php echo htmlspecialchars($docFiles[$i]); ?></p>
                                                    <p style="font-size: 0.75rem; color: var(--text-secondary);">
                                                        <?php 
                                                        $fileExt = pathinfo($docFiles[$i], PATHINFO_EXTENSION);
                                                        echo strtoupper($fileExt) . ' File';
                                                        ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <a href="admin_document_detail.php?id=<?php echo $document_id; ?>&download_file=1&file_type=document&file_index=<?php echo $i; ?>" class="btn-primary" target="_blank" download>
                                                <i class="fas fa-download" style="margin-right: 0.25rem;"></i> Download
                                            </a>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            <?php else: ?>
                                <p style="color: var(--text-secondary);">No document files available</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Image Files Section -->
                    <?php if (!empty($document['image_file_names']) && $document['image_file_names'] !== '[]' && $document['image_file_names'] !== 'null'): ?>
                        <div class="glass-card" style="padding: 1.25rem;">
                            <h3 style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-images" style="color: var(--success);"></i>
                                Images
                            </h3>
                            <?php
                            $imageFiles = json_decode($document['image_file_names'], true);
                            $imagePaths = json_decode($document['image_file_paths'], true);
                            $photoTitles = !empty($document['photo_titles']) ? json_decode($document['photo_titles'], true) : [];
                            
                            if (is_array($imageFiles) && is_array($imagePaths) && count($imageFiles) === count($imagePaths)):
                            ?>
                                <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem;">
                                    <?php for ($i = 0; $i < count($imageFiles); $i++): ?>
                                        <div class="group">
                                            <div style="position: relative; overflow: hidden; border-radius: 12px; background: #f1f5f9; border: 1px solid rgba(0, 0, 0, 0.1);">
                                                <?php if (file_exists($imagePaths[$i])): ?>
                                                    <img src="<?php echo htmlspecialchars($imagePaths[$i]); ?>" alt="<?php echo !empty($photoTitles[$i]) ? htmlspecialchars($photoTitles[$i]) : 'Project Image'; ?>" style="width: 100%; height: 8rem; object-fit: cover; transition: transform 0.3s ease;" class="group-hover:scale-105">
                                                <?php else: ?>
                                                    <div style="width: 100%; height: 8rem; display: flex; align-items: center; justify-content: center; background: #f1f5f9;">
                                                        <i class="fas fa-image" style="color: #94a3b8; font-size: 1.5rem;"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div style="margin-top: 0.5rem;">
                                                <p style="font-size: 0.875rem; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($imageFiles[$i]); ?></p>
                                                <?php if (!empty($photoTitles[$i])): ?>
                                                    <p style="font-size: 0.75rem; color: var(--text-secondary); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($photoTitles[$i]); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div style="margin-top: 0.5rem;">
                                                <a href="admin_document_detail.php?id=<?php echo $document_id; ?>&download_file=1&file_type=image&file_index=<?php echo $i; ?>" class="btn-primary" style="display: inline-block; width: 100%; text-align: center;" target="_blank" download>
                                                    <i class="fas fa-download" style="margin-right: 0.25rem;"></i> Download
                                                </a>
                                            </div>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            <?php else: ?>
                                <p style="color: var(--text-secondary);">No images available</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add some subtle animations to elements as they come into view
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.glass-card');
            
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>
<?php
endif;
?>