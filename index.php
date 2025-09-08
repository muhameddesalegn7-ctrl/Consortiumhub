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

  
</body>

</html>
