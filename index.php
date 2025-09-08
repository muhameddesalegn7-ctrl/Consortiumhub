<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

    <!-- Main content area -->
    <!-- Added min-w-0 to allow this flex item to shrink correctly -->
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
                <h2 id="mainContentTitle" class="ml-4 text-2xl font-semibold text-gray-800">Upload Project Reports</h2>
            </div>
            <div class="flex items-center space-x-4">
                <!-- Notification bell -->
                <button class="relative p-2 text-gray-500 hover:text-primary-600 rounded-full focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <span class="absolute top-0 right-0 flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-primary-600"></span>
                    </span>
                </button>
                
                <!-- Help button -->
                <button class="p-2 text-gray-500 hover:text-primary-600 rounded-full focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </button>
            </div>
        </header>

        <!-- Content Area -->
            <!-- Content Area -->
        <main id="mainContentArea" class="flex-1 p-8 overflow-y-auto overflow-x-auto bg-gray-50">
            <?php include 'upload_report_section.php'; ?>
            <?php include 'report_times_section.php'; ?>
            <?php include 'financial_report_section.php'; ?>
            <?php include 'forecast_budget_table.php'; ?>
            
            <!--
                When you add new sections, simply include their PHP files here.
                Example: <?php // include 'your_new_section.php'; ?>
                Make sure the main div in 'your_new_section.php' has a unique ID (e.g., 'yourNewSectionContent')
                and the sidebar button has data-target-section="yourNewSectionContent".
            -->
        </main>
    </div>

    <!-- Floating Action Button for Messages -->
    <div class="fixed right-6 bottom-6 z-40">
        <button id="messageBarToggle"
            class="flex items-center justify-center w-14 h-14 bg-primary-600 text-white rounded-full shadow-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-all duration-300 hover:shadow-xl">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
            </svg>
        </button>
    </div>

    <!-- Message Panel (slides in from right) -->
    <div id="messagePanel"
        class="fixed inset-y-0 right-0 z-50 w-96 bg-white shadow-xl transform translate-x-full transition-transform duration-300 ease-in-out p-6 flex flex-col rounded-l-xl border-l border-gray-200">
        <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-200">
            <h3 class="text-xl font-bold text-gray-800">Compose Message</h3>
            <button id="closeMessagePanelBtn"
                class="text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-300 rounded-full p-1 transition duration-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">To:</label>
            <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition duration-200 shadow-sm">
                <option>Project Managers</option>
                <option>All Members</option>
                <option>Administrators</option>
                <option>Select Individuals...</option>
            </select>
        </div>
        
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">Subject:</label>
            <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition duration-200 shadow-sm" placeholder="Message subject">
        </div>
        
        <div class="mb-4 flex-1">
            <label class="block text-gray-700 text-sm font-semibold mb-2">Message:</label>
            <textarea id="messageTextarea"
                class="w-full h-48 p-4 border border-gray-300 rounded-lg resize-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition duration-200 shadow-sm"
                placeholder="Write your message here..."></textarea>
        </div>
        
        <div class="flex space-x-3">
            <button class="flex-1 border border-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-50 transition duration-200 focus:outline-none focus:ring-2 focus:ring-2 focus:ring-primary-500 shadow-sm">
                Save Draft
            </button>
            <button id="sendMessageBtn"
                class="flex-1 bg-primary-600 text-white py-2 px-4 rounded-lg hover:bg-primary-700 transition duration-300 focus:outline-none focus:ring-2 focus:ring-primary-500 shadow-md font-medium">
                Send Message
            </button>
        </div>
    </div>

    <!-- Custom Message Box for alerts -->
    <div id="customMessageBox" class="fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-white p-6 rounded-lg shadow-xl max-w-sm w-full text-center">
            <h4 id="messageBoxTitle" class="text-xl font-semibold text-gray-800 mb-4"></h4>
            <p id="messageBoxContent" class="text-gray-700 mb-6"></p>
            <button id="messageBoxCloseBtn" class="bg-primary-600 text-white py-2 px-5 rounded-lg hover:bg-primary-700 transition duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                OK
            </button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // DOM Elements
            const sidebar = document.getElementById('sidebar');
            const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
            const mainContentTitle = document.getElementById('mainContentTitle');
            const mainContentArea = document.getElementById('mainContentArea'); // Get the main content area

            // Custom Message Box elements
            const customMessageBox = document.getElementById('customMessageBox');
            const messageBoxTitle = document.getElementById('messageBoxTitle');
            const messageBoxContent = document.getElementById('messageBoxContent');
            const messageBoxCloseBtn = document.getElementById('messageBoxCloseBtn');

            // Message Panel Elements
            const messageBarToggle = document.getElementById('messageBarToggle');
            const messagePanel = document.getElementById('messagePanel');
            const closeMessagePanelBtn = document.getElementById('closeMessagePanelBtn');
            const messageTextarea = document.getElementById('messageTextarea');
            const sendMessageBtn = document.getElementById('sendMessageBtn');

            // Upload Form Elements
            const uploadForm = document.getElementById('uploadForm');
            const documentNameSelect = document.getElementById('documentNameSelect');
            const customDocumentNameContainer = document.getElementById('customDocumentNameContainer');
            const customDocumentNameInput = document.getElementById('customDocumentNameInput');
            const reportFileInput = document.getElementById('reportFile');
            const selectedFileNameSpan = document.getElementById('selectedFileName');


            /**
             * Displays a custom message box instead of the native alert.
             * @param {string} title - The title of the message box.
             * @param {string} message - The content message.
             */
            function showCustomMessageBox(title, message) {
                messageBoxTitle.textContent = title;
                messageBoxContent.textContent = message;
                customMessageBox.classList.remove('hidden');
            }

            // Event listener for closing the custom message box
            messageBoxCloseBtn.addEventListener('click', () => {
                customMessageBox.classList.add('hidden');
            });

            /**
             * Hides all content sections within the main content area.
             * It looks for direct child divs of the mainContentArea that have an ID.
             */
            function hideAllSections() {
                if (mainContentArea) {
                    const sections = mainContentArea.querySelectorAll(':scope > div[id]'); // Selects direct child divs with an ID
                    sections.forEach(section => {
                        section.classList.add('hidden');
                    });
                }
            }

            // Initial state: show upload section
            hideAllSections(); // Hide all first
            // You'll need to know the ID of your initial section (e.g., 'uploadReportSection')
            const initialSection = document.getElementById('uploadReportSection');
            if (initialSection) initialSection.classList.remove('hidden');
            if (mainContentTitle) mainContentTitle.textContent = 'Upload Project Reports';

            // Universal Sidebar Navigation Logic
            // This is the core change: It handles all sidebar buttons with 'data-target-section'
            const sidebarButtons = document.querySelectorAll('#sidebar button[data-target-section]');

            sidebarButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const targetSectionId = button.dataset.targetSection;
                    let titleText = button.querySelector('span').textContent; // Get title from button text

                    hideAllSections(); // Hides all existing sections

                    // Special handling for the main Financial Report button (if it combines sections)
                    // The ID 'financialReportSections' is a custom one you defined for this button.
                    if (targetSectionId === 'financialReportSections') {
                        // Directly reference the IDs of the sections it should show
                        const transactionListingSection = document.getElementById('transactionListingSection');
                        const forecastBudgetTableSection = document.getElementById('forecastBudgetTableSection');
                        
                        if (transactionListingSection) transactionListingSection.classList.remove('hidden');
                        if (forecastBudgetTableSection) forecastBudgetTableSection.classList.remove('hidden');
                        titleText = 'Financial Report Overview'; // Specific title for this combined view
                    } else {
                        // For all other singular sections, directly get the element by ID
                        const targetSection = document.getElementById(targetSectionId);
                        if (targetSection) {
                            targetSection.classList.remove('hidden');
                        }
                    }

                    if (mainContentTitle) mainContentTitle.textContent = titleText;

                    // Close sidebar on mobile after selection
                    if (window.innerWidth < 1024 && sidebar) {
                        sidebar.classList.add('-translate-x-full');
                    }
                });
            });

            // Toggle sidebar on hamburger menu click (mobile)
            if (sidebarToggleBtn) {
                sidebarToggleBtn.addEventListener('click', () => {
                    if (sidebar) sidebar.classList.toggle('-translate-x-full');
                });
            }

            // Hide sidebar when clicking outside on mobile (optional, but good UX)
            document.addEventListener('click', (event) => {
                if (window.innerWidth < 1024 && sidebar && !sidebar.contains(event.target) && sidebarToggleBtn && !sidebarToggleBtn.contains(event.target) && !sidebar.classList.contains('-translate-x-full')) {
                    sidebar.classList.add('-translate-x-full');
                }
            });
            
            // Handle file input change to display selected file name
            if (reportFileInput) {
                reportFileInput.addEventListener('change', () => {
                    if (reportFileInput.files.length > 0) {
                        if (selectedFileNameSpan) {
                            selectedFileNameSpan.textContent = `Selected file: ${reportFileInput.files[0].name}`;
                            selectedFileNameSpan.classList.remove('hidden');
                        }
                    } else {
                        if (selectedFileNameSpan) {
                            selectedFileNameSpan.textContent = '';
                            selectedFileNameSpan.classList.add('hidden');
                        }
                    }
                });
            }

            // --- Document Name Dropdown Logic ---
            if (documentNameSelect) {
                documentNameSelect.addEventListener('change', () => {
                    if (documentNameSelect.value === 'Other') {
                        if (customDocumentNameContainer) customDocumentNameContainer.classList.remove('hidden');
                        if (customDocumentNameInput) customDocumentNameInput.setAttribute('required', 'true'); 
                    } else {
                        if (customDocumentNameContainer) customDocumentNameContainer.classList.add('hidden');
                        if (customDocumentNameInput) {
                            customDocumentNameInput.removeAttribute('required'); 
                            customDocumentNameInput.value = ''; 
                        }
                    }
                });
            }
            // --- End Document Name Dropdown Logic ---

            // --- Drag and Drop Functionality ---
            const dropZone = document.getElementById('dropZoneLabel');

            if (dropZone) {
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, preventDefaults, false);
                    document.body.addEventListener(eventName, preventDefaults, false); 
                });

                function preventDefaults(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }

                ['dragenter', 'dragover'].forEach(eventName => {
                    dropZone.addEventListener(eventName, () => {
                        dropZone.classList.add('border-primary-500', 'bg-primary-100'); 
                    }, false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, () => {
                        dropZone.classList.remove('border-primary-500', 'bg-primary-100'); 
                    }, false);
                });

                dropZone.addEventListener('drop', (e) => {
                    const files = e.dataTransfer.files;
                    if (reportFileInput) reportFileInput.files = files;
                    const event = new Event('change');
                    if (reportFileInput) reportFileInput.dispatchEvent(event);
                }, false);
            }
            // --- End Drag and Drop Functionality ---

            // Handle Report Upload Form Submission
            if (uploadForm) {
                uploadForm.addEventListener('submit', (e) => {
                    e.preventDefault(); 
                    let documentName = documentNameSelect ? documentNameSelect.value : '';
                    if (documentName === 'Other' && customDocumentNameInput) {
                        documentName = customDocumentNameInput.value.trim();
                    }
                    const selectedFile = reportFileInput && reportFileInput.files.length > 0 ? reportFileInput.files[0] : null;

                    if (documentName && selectedFile) {
                        const submitBtn = uploadForm.querySelector('button[type="submit"]');
                        const originalText = submitBtn ? submitBtn.textContent : '';
                        if (submitBtn) {
                            submitBtn.innerHTML = '<span class="flex items-center justify-center"><svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Uploading...</span>';
                            submitBtn.disabled = true;
                        }
                        
                        setTimeout(() => {
                            showCustomMessageBox('Upload Successful!', `Successfully uploaded "${documentName}" with file: ${selectedFile.name}`);
                            if (documentNameSelect) documentNameSelect.value = 'Financial Report'; 
                            if (customDocumentNameInput) customDocumentNameInput.value = '';
                            if (customDocumentNameContainer) customDocumentNameContainer.classList.add('hidden'); 
                            if (reportFileInput) reportFileInput.value = '';
                            if (selectedFileNameSpan) selectedFileNameSpan.classList.add('hidden');
                            if (submitBtn) {
                                submitBtn.textContent = originalText;
                                submitBtn.disabled = false;
                            }
                        }, 1500);
                    } else if (!documentName) {
                        showCustomMessageBox('Error', 'Please enter a document name.');
                    } else if (!selectedFile) {
                        showCustomMessageBox('Error', 'Please select a file to upload.');
                    }
                });
            }

            // Toggle Message Panel
            if (messageBarToggle) {
                messageBarToggle.addEventListener('click', () => {
                    if (messagePanel) messagePanel.classList.toggle('translate-x-full');
                });
            }

            if (closeMessagePanelBtn) {
                closeMessagePanelBtn.addEventListener('click', () => {
                    if (messagePanel) messagePanel.classList.add('translate-x-full');
                });
            }

            // Handle Send Message
            if (sendMessageBtn) {
                sendMessageBtn.addEventListener('click', () => {
                    const message = messageTextarea ? messageTextarea.value.trim() : '';
                    if (message) {
                        const originalText = sendMessageBtn.textContent;
                        sendMessageBtn.innerHTML = '<span class="flex items-center justify-center"><svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Sending...</span>';
                        sendMessageBtn.disabled = true;
                        
                        setTimeout(() => {
                            showCustomMessageBox('Message Sent!', 'Message sent successfully!');
                            if (messageTextarea) messageTextarea.value = '';
                            if (messagePanel) messagePanel.classList.add('translate-x-full');
                            sendMessageBtn.textContent = originalText;
                            sendMessageBtn.disabled = false;
                        }, 1000);
                    } else {
                        showCustomMessageBox('Error', 'Message cannot be empty.');
                    }
                });
            }
        });
    </script>
</body>
</html>