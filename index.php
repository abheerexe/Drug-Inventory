<!DOCTYPE html>
<html>
<head >
    <title>Drug Inventory Home</title>
    <link rel="stylesheet" href="style.css"> 
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Funnel+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
</head>
<body>

    <h1 class="funnel-sans-font">Drug Inventory Search</h1>
    <div id="authButtons">
      <button onclick="window.location.href='login.php'">Login</button>
      <button onclick="window.location.href='signup.php'">Sign Up</button>

    </div>
    <form id="searchForm" method="get" action="">
        <input type="text" id="searchInput" name="q" placeholder="Search for drugs...">
        <button type="submit">Search</button>
    </form>

    <div id="searchResults">
        </div>  </div>


    <script>
        // JavaScript for handling search (AJAX)
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
    </script>

</body>
</html>




