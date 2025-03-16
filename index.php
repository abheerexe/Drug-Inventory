<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drug Inventory Home</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Funnel+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <!-- Hero Section -->
        <header>
            <div class="hero-content">
                <h1 class="animate-fade-in funnel-sans-font">
                    Drug Inventory <span class="gradient-text">Search</span>
                </h1>
                
                <!-- Auth Buttons -->
                <div id="authButtons">
                    <button onclick="window.location.href='login.php'" class="button-40">Login</button>
                    <button onclick="window.location.href='signup.php'"class="button-40">Sign Up</button>
                </div>

                <!-- Search Section -->
                <form id="searchForm" method="get" action="" class="search-container">
                    <input type="text" id="searchInput" name="q" placeholder="Search for drugs...">
                    <button type="submit" class="button-40">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        Search
                    </button>
                </form>

                <!-- Results Table -->
                <div id="searchResults" class="funnel-sans-font">
                    <!-- Search results will be dynamically inserted here -->
                </div>
            </div>
        </header>

        <!-- Footer -->
        <footer>
            <div class="footer-content">
                <p>© 2024 Drug Inventory System. All rights reserved.</p>
            </div>
        </footer>
    </div>

    <script>
        // JavaScript for handling search (AJAX)
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('searchForm').addEventListener('submit', function(event) {
                event.preventDefault(); // Prevent traditional form submission

                const searchTerm = document.getElementById('searchInput').value;

                // Make an AJAX request to search.php
                const xhr = new XMLHttpRequest();
                xhr.open('GET', 'search.php?q=' + encodeURIComponent(searchTerm), true);
                xhr.onload = function() {
                    if (xhr.status >= 200 && xhr.status < 400) {
                        document.getElementById('searchResults').innerHTML = xhr.responseText;
                    } else {
                        console.error('Error: ' + xhr.status);
                        document.getElementById('searchResults').innerHTML = '<p>An error occurred.</p>';
                    }
                };
                xhr.onerror = function() {
                    console.error('Network error');
                    document.getElementById('searchResults').innerHTML = '<p>A network error occurred.</p>';
                };
                xhr.send();
            });

            // Add hover effect to feature cards if they exist
            const featureCards = document.querySelectorAll('.feature-card');
            featureCards.forEach(card => {
                card.addEventListener('mouseenter', () => {
                    card.style.transform = 'translateY(-5px)';
                });
                
                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>