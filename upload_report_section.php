<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if this file is being included or accessed directly
$included = defined('INCLUDED_FROM_INDEX');

if (!$included) {
    include 'header.php';
}
?>

<?php
// Get user cluster information
$userCluster = $_SESSION['cluster_name'] ?? 'No Cluster Assigned';

// Get current date for default value
$currentDate = date('Y-m-d');
?>

<?php if (!$included): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Project Reports | Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #ff6b6b;
            --accent: #06d6a0;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 15px 35px rgba(0, 0, 0, 0.12);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 16px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.18);
            transition: var(--transition);
        }

        .glass-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-5px);
        }

        .gradient-bg {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        }

        .text-gradient {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: 12px;
            padding: 14px 28px;
            font-weight: 600;
            color: white;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(67, 97, 238, 0.4);
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
        }

        .section-title {
            position: relative;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }

        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            border-radius: 2px;
        }

        .upload-zone {
            border: 2px dashed #dbe4ff;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            transition: var(--transition);
            background: rgba(219, 228, 255, 0.3);
        }

        .upload-zone:hover, .upload-zone.dragover {
            border-color: var(--primary);
            background: rgba(67, 97, 238, 0.05);
        }

        .form-control {
            border-radius: 10px;
            padding: 14px 18px;
            border: 1px solid #e2e8f0;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
        }

        .animated-border {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
        }

        .animated-border:before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            z-index: -1;
            background: linear-gradient(45deg, var(--primary), var(--accent), var(--secondary), var(--primary));
            background-size: 400% 400%;
            animation: gradientShift 8s ease infinite;
            border-radius: 18px;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .fade-in {
            animation: fadeIn 0.8s ease forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .progress-bar {
            height: 8px;
            border-radius: 4px;
            background: #e2e8f0;
            overflow: hidden;
        }

        .progress-value {
            height: 100%;
            background: linear-gradient(to right, var(--accent), var(--primary));
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        .floating-notification {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            animation: slideIn 0.5s ease forwards;
        }

        @keyframes slideIn {
            from { transform: translateX(100px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Main content area -->
    <div class="flex flex-col flex-1 min-w-0">
        <!-- Header -->
        <header class="flex items-center justify-between h-20 px-8 bg-white border-b border-gray-200 shadow-sm rounded-bl-xl">
            <div class="flex items-center">
                <!-- Hamburger menu for small screens -->
                <button id="sidebarToggleBtn"
                    class="text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-md p-2 lg:hidden">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                            d="M4 6h16M4 12h16M4 18h16">
                        </path>
                    </svg>
                </button>
                <h2 class="ml-4 text-2xl font-semibold text-gray-800">Upload Report</h2>
            </div>
        </header>

        <!-- Content Area -->
        <main class="flex-1 p-8 overflow-y-auto overflow-x-auto bg-gray-50">
            <div class="bg-white rounded-lg shadow-md p-6 max-w-4xl mx-auto">
                <div class="text-center mb-8">
                   
                    <h3 class="text-3xl font-bold text-gray-800 mb-2">Upload Project Documents</h3>
                    <p class="text-gray-500 text-lg">Share your progress and achievements with our team</p>
                </div>
                
                <!-- Display user's cluster -->
                <div class="mb-8 p-5 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-100 animated-border">
                    <div class="flex items-center">
                        <div class="bg-blue-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-users text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-blue-800 font-medium">
                                Uploading for Cluster: <span class="font-bold text-primary"><?php echo htmlspecialchars($userCluster); ?></span>
                            </p>
                            <p class="text-blue-600 text-sm mt-1">Your documents will be shared with cluster administrators</p>
                        </div>
                    </div>
                </div>
                
                <form id="uploadForm" class="space-y-8">
                    <div class="glass-card p-6 rounded-xl">
                        <h4 class="text-xl font-bold text-gray-800 mb-4 section-title">Document Information</h4>
                        <div>
                            <label for="documentTypeSelect" class="block text-gray-700 text-sm font-semibold mb-2">
                                <i class="fas fa-file-alt mr-2 text-primary"></i>Document Type:
                            </label>
                            <select id="documentTypeSelect"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition duration-200 shadow-sm form-control">
                                <option value="Progress Report">Progress Report</option>
                               
                                <option value="Other">Other Document Type</option>
                            </select>
                        </div>
                        
                        <div id="customDocumentNameContainer" class="hidden mt-4">
                            <label for="customDocumentNameInput" class="block text-gray-700 text-sm font-semibold mb-2">
                                <i class="fas fa-pen mr-2 text-primary"></i>Custom Document Name:
                            </label>
                            <input type="text" id="customDocumentNameInput"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition duration-200 shadow-sm form-control"
                                placeholder="e.g., Q3 Financial Report 2025">
                        </div>
                    </div>

                    <!-- Progress Report Section (dynamically named) -->
                    <div class="glass-card p-6 rounded-xl bg-gradient-to-br from-blue-50/50 to-white/50">
                        <div class="flex items-center mb-6">
                            <div class="bg-blue-100 p-3 rounded-lg mr-4">
                                <i class="fas fa-chart-line text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <h4 id="progressSectionTitle" class="text-xl font-bold text-gray-800">Progress Report</h4>
                                <p id="progressSectionDescription" class="text-gray-600">Share updates on your project's progress</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <div>
                                <label for="progressTitle" class="block text-gray-700 text-sm font-semibold mb-2">
                                    <i class="fas fa-heading mr-2 text-primary"></i>Report Title:
                                </label>
                                <input type="text" id="progressTitle"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition duration-200 shadow-sm form-control"
                                    placeholder="e.g., Monthly Progress Report - June 2025">
                            </div>
                            
                            <div>
                                <label for="progressDate" class="block text-gray-700 text-sm font-semibold mb-2">
                                    <i class="fas fa-calendar mr-2 text-primary"></i>Report Date:
                                </label>
                                <input type="date" id="progressDate"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition duration-200 shadow-sm form-control"
                                    value="<?php echo $currentDate; ?>">
                            </div>
                        </div>
                        
                        <!-- Document Upload within Progress Report Section -->
                        <div class="mt-6">
                            <label class="block text-gray-700 text-sm font-semibold mb-2">
                                <i class="fas fa-file-upload mr-2 text-primary"></i>Supporting Documents:
                            </label>
                            <div class="flex items-center justify-center w-full">
                                <label for="progressDocumentFiles" id="progressDocumentDropZone" class="flex flex-col items-center justify-center w-full h-40 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-primary-400 hover:bg-primary-50 transition duration-200 upload-zone">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                        <svg class="w-10 h-10 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                        </svg>
                                        <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                                        <p class="text-xs text-gray-500">PDF, DOCX, XLSX (Max. 10MB each)</p>
                                    </div>
                                    <input id="progressDocumentFiles" type="file" class="hidden" multiple accept=".pdf,.docx,.xlsx">
                                </label>
                            </div> 
                            <p id="selectedProgressDocumentNames" class="mt-2 text-sm text-primary-600 font-medium hidden"></p>
                            
                            <!-- Upload progress bar -->
                            <div id="progressDocumentProgress" class="mt-4 hidden">
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm font-medium text-primary-600">Uploading</span>
                                    <span id="progressDocumentPercent" class="text-sm font-medium text-primary-600">0%</span>
                                </div>
                                <div class="progress-bar">
                                    <div id="progressDocumentBar" class="progress-value" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Challenges Section -->
                    <div class="glass-card p-6 rounded-xl bg-gradient-to-br from-red-50/50 to-white/50">
                        <div class="flex items-center mb-6">
                            <div class="bg-red-100 p-3 rounded-lg mr-4">
                                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                            </div>
                            <div>
                                <h4 class="text-xl font-bold text-gray-800">Challenges</h4>
                                <p class="text-gray-600">Document any challenges encountered</p>
                            </div>
                        </div>
                        
                        <div class="space-y-6 mt-6">
                            <div>
                                <label for="challengeTitle" class="block text-gray-700 text-sm font-semibold mb-2">
                                    <i class="fas fa-heading mr-2 text-primary"></i>Challenge Title:
                                </label>
                                <input type="text" id="challengeTitle"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition duration-200 shadow-sm form-control"
                                    placeholder="Brief title of the challenge">
                            </div>
                            
                            <div>
                                <label for="challengeDescription" class="block text-gray-700 text-sm font-semibold mb-2">
                                    <i class="fas fa-align-left mr-2 text-primary"></i>Challenge Description:
                                </label>
                                <textarea id="challengeDescription" rows="4"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition duration-200 shadow-sm form-control"
                                    placeholder="Describe the challenge in detail..."></textarea>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="challengeImpact" class="block text-gray-700 text-sm font-semibold mb-2">
                                        <i class="fas fa-bolt mr-2 text-primary"></i>Impact:
                                    </label>
                                    <textarea id="challengeImpact" rows="3"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition duration-200 shadow-sm form-control"
                                        placeholder="How has this challenge impacted the project?"></textarea>
                                </div>
                                
                                <div>
                                    <label for="proposedSolution" class="block text-gray-700 text-sm font-semibold mb-2">
                                        <i class="fas fa-lightbulb mr-2 text-primary"></i>Proposed Solution:
                                    </label>
                                    <textarea id="proposedSolution" rows="3"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition duration-200 shadow-sm form-control"
                                        placeholder="What solution do you propose?"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Success Stories Section -->
                    <div class="glass-card p-6 rounded-xl bg-gradient-to-br from-green-50/50 to-white/50">
                        <div class="flex items-center mb-6">
                            <div class="bg-green-100 p-3 rounded-lg mr-4">
                                <i class="fas fa-trophy text-green-600 text-xl"></i>
                            </div>
                            <div>
                                <h4 class="text-xl font-bold text-gray-800">Success Stories</h4>
                                <p class="text-gray-600">Share your project's achievements and milestones</p>
                            </div>
                        </div>
                        
                        <div class="space-y-6 mt-6">
                            <div>
                                <label for="successTitle" class="block text-gray-700 text-sm font-semibold mb-2">
                                    <i class="fas fa-heading mr-2 text-primary"></i>Success Title:
                                </label>
                                <input type="text" id="successTitle"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition duration-200 shadow-sm form-control"
                                    placeholder="e.g., Community Workshop Successfully Completed">
                            </div>
                            
                            <div>
                                <label for="successDescription" class="block text-gray-700 text-sm font-semibold mb-2">
                                    <i class="fas fa-align-left mr-2 text-primary"></i>Success Description:
                                </label>
                                <textarea id="successDescription" rows="4"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition duration-200 shadow-sm form-control"
                                    placeholder="Describe the success story in detail..."></textarea>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="beneficiaries" class="block text-gray-700 text-sm font-semibold mb-2">
                                        <i class="fas fa-users mr-2 text-primary"></i>Number of Beneficiaries:
                                    </label>
                                    <input type="number" id="beneficiaries"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition duration-200 shadow-sm form-control"
                                        placeholder="e.g., 150">
                                </div>
                                
                                <div>
                                    <label for="successDate" class="block text-gray-700 text-sm font-semibold mb-2">
                                        <i class="fas fa-calendar mr-2 text-primary"></i>Date of Achievement:
                                    </label>
                                    <input type="date" id="successDate"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition duration-200 shadow-sm form-control">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Image Files Upload -->
                    <div class="glass-card p-6 rounded-xl bg-gradient-to-br from-purple-50/50 to-white/50">
                        <div class="flex items-center mb-6">
                            <div class="bg-purple-100 p-3 rounded-lg mr-4">
                                <i class="fas fa-images text-purple-600 text-xl"></i>
                            </div>
                            <div>
                                <h4 class="text-xl font-bold text-gray-800">Project Photos</h4>
                                <p class="text-gray-600">Upload images related to your project</p>
                            </div>
                        </div>
                        
                        <!-- Photo title input -->
                        <div class="mt-4">
                            <label for="photoTitle" class="block text-gray-700 text-sm font-semibold mb-2">
                                <i class="fas fa-heading mr-2 text-primary"></i>Photo Title/Description:
                            </label>
                            <input type="text" id="photoTitle"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition duration-200 shadow-sm form-control"
                                placeholder="e.g., Team meeting, Community event, etc.">
                        </div>
                        
                        <div class="mt-6">
                            <label class="block text-gray-700 text-sm font-semibold mb-2">
                                <i class="fas fa-camera mr-2 text-primary"></i>Select Images:
                            </label>
                            <div class="flex items-center justify-center w-full">
                                <label for="imageFiles" id="imageDropZone" class="flex flex-col items-center justify-center w-full h-40 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-primary-400 hover:bg-primary-50 transition duration-200 upload-zone">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                        <svg class="w-10 h-10 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                                        <p class="text-xs text-gray-500">JPG, PNG, JPEG (Max. 10MB each)</p>
                                    </div>
                                    <input id="imageFiles" type="file" class="hidden" multiple accept=".jpg,.jpeg,.png">
                                </label>
                            </div> 
                            <p id="selectedImageNames" class="mt-2 text-sm text-primary-600 font-medium hidden"></p>
                            
                            <!-- Upload progress bar -->
                            <div id="imageProgress" class="mt-4 hidden">
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm font-medium text-primary-600">Uploading</span>
                                    <span id="imagePercent" class="text-sm font-medium text-primary-600">0%</span>
                                </div>
                                <div class="progress-bar">
                                    <div id="imageBar" class="progress-value" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Preview for uploaded images -->
                        <div id="imagePreviewContainer" class="mt-6 hidden">
                            <h5 class="text-lg font-semibold text-gray-800 mb-3">Image Preview:</h5>
                            <div id="imagePreview" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4"></div>
                        </div>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-primary to-primary-dark text-white py-4 px-6 rounded-xl hover:from-primary-dark hover:to-primary focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-all duration-300 shadow-lg hover:shadow-xl font-bold text-lg btn-primary">
                        <i class="fas fa-cloud-upload-alt mr-2"></i>Upload Project Report
                    </button>
                </form>
                
                <!-- Success Message -->
                <div id="successMessage" class="hidden mt-8 p-6 bg-green-50 border border-green-200 text-green-700 rounded-xl">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 text-2xl mr-4"></i>
                        <div>
                            <h4 class="font-bold text-lg">Upload Successful!</h4>
                            <p id="successMessageText" class="mt-1">Your project report has been uploaded successfully.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Error Message -->
                <div id="errorMessage" class="hidden mt-8 p-6 bg-red-50 border border-red-200 text-red-700 rounded-xl">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-500 text-2xl mr-4"></i>
                        <div>
                            <h4 class="font-bold text-lg">Upload Failed!</h4>
                            <p id="errorMessageText" class="mt-1">There was an error uploading your report. Please try again.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Show/hide custom document name input based on selection and update section title
        document.getElementById('documentTypeSelect').addEventListener('change', function() {
            const customContainer = document.getElementById('customDocumentNameContainer');
            const progressSectionTitle = document.getElementById('progressSectionTitle');
            const progressSectionDescription = document.getElementById('progressSectionDescription');
            
            if (this.value === 'Other') {
                customContainer.classList.remove('hidden');
                progressSectionTitle.textContent = 'Other Document';
                progressSectionDescription.textContent = 'Provide details for your custom document';
            } else {
                customContainer.classList.add('hidden');
                progressSectionTitle.textContent = this.value;
                progressSectionDescription.textContent = 'Share updates on your project\'s progress';
            }
        });
        
        // Handle image file selection display and preview
        document.getElementById('imageFiles').addEventListener('change', function() {
            const fileNameElement = document.getElementById('selectedImageNames');
            const previewContainer = document.getElementById('imagePreviewContainer');
            const preview = document.getElementById('imagePreview');
            
            // Clear previous previews
            preview.innerHTML = '';
            
            if (this.files.length > 0) {
                const fileNames = Array.from(this.files).map(file => file.name).join(', ');
                fileNameElement.textContent = `Selected: ${fileNames}`;
                fileNameElement.classList.remove('hidden');
                
                // Show preview container
                previewContainer.classList.remove('hidden');
                
                // Generate previews for each image
                Array.from(this.files).forEach((file, index) => {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const imgContainer = document.createElement('div');
                            imgContainer.className = 'relative group';
                            
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.className = 'w-full h-32 object-cover rounded-lg border border-gray-200';
                            
                            // Get photo title if provided
                            const photoTitle = document.getElementById('photoTitle').value || 'Untitled Photo';
                            
                            const fileInfo = document.createElement('div');
                            fileInfo.className = 'absolute bottom-0 left-0 right-0 bg-black bg-opacity-70 text-white text-xs p-2 rounded-b-lg truncate';
                            fileInfo.textContent = photoTitle;
                            
                            imgContainer.appendChild(img);
                            imgContainer.appendChild(fileInfo);
                            preview.appendChild(imgContainer);
                        };
                        reader.readAsDataURL(file);
                    }
                });
            } else {
                fileNameElement.classList.add('hidden');
                previewContainer.classList.add('hidden');
            }
        });
        
        // Handle progress document file selection display
        document.getElementById('progressDocumentFiles').addEventListener('change', function() {
            const fileNameElement = document.getElementById('selectedProgressDocumentNames');
            if (this.files.length > 0) {
                const fileNames = Array.from(this.files).map(file => file.name).join(', ');
                fileNameElement.textContent = `Selected: ${fileNames}`;
                fileNameElement.classList.remove('hidden');
            } else {
                fileNameElement.classList.add('hidden');
            }
        });
        
        // Handle drag and drop for file uploads
        const dropZones = document.querySelectorAll('.upload-zone');
        
        dropZones.forEach(zone => {
            zone.addEventListener('dragover', (e) => {
                e.preventDefault();
                zone.classList.add('dragover');
            });
            
            zone.addEventListener('dragleave', () => {
                zone.classList.remove('dragover');
            });
            
            zone.addEventListener('drop', (e) => {
                e.preventDefault();
                zone.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    if (zone.id === 'imageDropZone') {
                        document.getElementById('imageFiles').files = files;
                        const event = new Event('change');
                        document.getElementById('imageFiles').dispatchEvent(event);
                    } else if (zone.id === 'progressDocumentDropZone') {
                        document.getElementById('progressDocumentFiles').files = files;
                        const event = new Event('change');
                        document.getElementById('progressDocumentFiles').dispatchEvent(event);
                    }
                }
            });
        });
        
        // Handle form submission
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Hide any previous messages
            document.getElementById('successMessage').classList.add('hidden');
            document.getElementById('errorMessage').classList.add('hidden');
            
            // Check if any files are selected
            const progressDocumentFiles = document.getElementById('progressDocumentFiles').files;
            const imageFiles = document.getElementById('imageFiles').files;
            
            // Validate document type only if files are selected
            const documentType = document.getElementById('documentTypeSelect').value;
            if ((progressDocumentFiles.length > 0 || imageFiles.length > 0) && !documentType) {
                showError('Please select a document type before uploading files.');
                return;
            }
            
            // Show progress bars
            document.getElementById('progressDocumentProgress').classList.remove('hidden');
            document.getElementById('imageProgress').classList.remove('hidden');
            
            // Simulate upload progress (in a real application, this would be handled by the actual upload)
            let progress = 0;
            const interval = setInterval(() => {
                progress += 5;
                document.getElementById('progressDocumentBar').style.width = `${progress}%`;
                document.getElementById('progressDocumentPercent').textContent = `${progress}%`;
                document.getElementById('imageBar').style.width = `${progress}%`;
                document.getElementById('imagePercent').textContent = `${progress}%`;
                
                if (progress >= 100) {
                    clearInterval(interval);
                    
                    // Create FormData object
                    const formData = new FormData();
                    
                    // Add form fields
                    formData.append('documentType', document.getElementById('documentTypeSelect').value);
                    
                    if (document.getElementById('documentTypeSelect').value === 'Other') {
                        formData.append('customDocumentName', document.getElementById('customDocumentNameInput').value);
                    }
                    
                    // Progress report fields (optional)
                    formData.append('progressTitle', document.getElementById('progressTitle').value);
                    formData.append('progressDate', document.getElementById('progressDate').value);
                    
                    // Challenge fields (optional)
                    formData.append('challengeTitle', document.getElementById('challengeTitle').value);
                    formData.append('challengeDescription', document.getElementById('challengeDescription').value);
                    formData.append('challengeImpact', document.getElementById('challengeImpact').value);
                    formData.append('proposedSolution', document.getElementById('proposedSolution').value);
                    
                    // Success story fields (optional)
                    formData.append('successTitle', document.getElementById('successTitle').value);
                    formData.append('successDescription', document.getElementById('successDescription').value);
                    formData.append('beneficiaries', document.getElementById('beneficiaries').value);
                    formData.append('successDate', document.getElementById('successDate').value);
                    
                    // Photo title
                    formData.append('photoTitle', document.getElementById('photoTitle').value);
                    
                    // Add progress document files
                    const progressDocumentFiles = document.getElementById('progressDocumentFiles').files;
                    for (let i = 0; i < progressDocumentFiles.length; i++) {
                        formData.append('progressDocumentFiles[]', progressDocumentFiles[i]);
                    }
                    
                    // Add image files
                    const imageFiles = document.getElementById('imageFiles').files;
                    for (let i = 0; i < imageFiles.length; i++) {
                        formData.append('imageFiles[]', imageFiles[i]);
                    }
                    
                    // Send AJAX request
                    fetch('document_upload_handler.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        // First check if the response is OK
                        if (!response.ok) {
                            throw new Error('Network response was not ok: ' + response.status + ' ' + response.statusText);
                        }
                        
                        // Get content type
                        const contentType = response.headers.get('content-type');
                        
                        // Check if response is JSON
                        if (contentType && contentType.indexOf('application/json') !== -1) {
                            return response.json();
                        } else {
                            // If response is not JSON, get text and throw error
                            return response.text().then(text => {
                                throw new Error('Server returned invalid response format: ' + text.substring(0, 200));
                            });
                        }
                    })
                    .then(data => {
                        // Hide progress bars
                        document.getElementById('progressDocumentProgress').classList.add('hidden');
                        document.getElementById('imageProgress').classList.add('hidden');
                        
                        // Check if data has the expected structure
                        if (data && typeof data === 'object' && 'success' in data) {
                            if (data.success) {
                                // Show success message
                                document.getElementById('successMessageText').textContent = data.message;
                                document.getElementById('successMessage').classList.remove('hidden');
                                
                                // Create floating notification
                                createFloatingNotification('Upload successful! Your documents have been submitted.', 'success');
                                
                                // Reset form
                                document.getElementById('uploadForm').reset();
                                document.getElementById('selectedProgressDocumentNames').classList.add('hidden');
                                document.getElementById('selectedImageNames').classList.add('hidden');
                                document.getElementById('customDocumentNameContainer').classList.add('hidden');
                                document.getElementById('imagePreviewContainer').classList.add('hidden');
                                document.getElementById('imagePreview').innerHTML = '';
                                
                                // Reset section title to default
                                document.getElementById('progressSectionTitle').textContent = 'Progress Report';
                                document.getElementById('progressSectionDescription').textContent = 'Share updates on your project\'s progress';
                                
                                // Set default date again
                                document.getElementById('progressDate').value = '<?php echo $currentDate; ?>';
                            } else {
                                // Show error message
                                document.getElementById('errorMessageText').textContent = data.message;
                                document.getElementById('errorMessage').classList.remove('hidden');
                                
                                // Create floating notification
                                createFloatingNotification(data.message, 'error');
                            }
                        } else {
                            // Handle unexpected response format
                            throw new Error('Unexpected response format from server');
                        }
                    })
                    .catch(error => {
                        // Hide progress bars
                        document.getElementById('progressDocumentProgress').classList.add('hidden');
                        document.getElementById('imageProgress').classList.add('hidden');
                        
                        // Show error message with more detailed information
                        let errorMessage = 'Upload failed! Please try again.';
                        // If it's a specific error, show more details
                        if (error.message) {
                            errorMessage = 'Upload failed! ' + error.message;
                        }
                        
                        document.getElementById('errorMessageText').textContent = errorMessage;
                        document.getElementById('errorMessage').classList.remove('hidden');
                        
                        // Create floating notification
                        createFloatingNotification(errorMessage, 'error');
                        
                        console.error('Upload error:', error);
                    });
                }
            }, 50);
        });
        
        // Function to show error message
        function showError(message) {
            document.getElementById('errorMessageText').textContent = message;
            document.getElementById('errorMessage').classList.remove('hidden');
            createFloatingNotification(message, 'error');
        }
        
        // Function to create floating notifications
        function createFloatingNotification(message, type) {
            // Remove any existing notifications
            const existingNotifications = document.querySelectorAll('.floating-notification');
            existingNotifications.forEach(notification => notification.remove());
            
            const notification = document.createElement('div');
            notification.className = `floating-notification p-4 rounded-lg shadow-lg flex items-center ${
                type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 
                'bg-red-100 text-red-800 border border-red-200'
            }`;
            
            const icon = document.createElement('i');
            icon.className = `mr-3 ${
                type === 'success' ? 'fas fa-check-circle text-green-500' : 
                'fas fa-exclamation-circle text-red-500'
            }`;
            
            const text = document.createElement('span');
            text.textContent = message;
            
            notification.appendChild(icon);
            notification.appendChild(text);
            
            document.body.appendChild(notification);
            
            // Remove notification after 5 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.animation = 'slideIn 0.5s ease reverse forwards';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            document.body.removeChild(notification);
                        }
                    }, 500);
                }
            }, 5000);
        }
    </script>
<?php endif; ?>
