<?php
// Function to fetch series count from TMDB
function get_series_count_from_tmdb($series_name, $api_key) {
    $search_url = "https://api.themoviedb.org/3/search/tv?api_key=$api_key&query=" . urlencode($series_name);
    $search_response = file_get_contents($search_url);
    $search_results = json_decode($search_response, true);

    if (!empty($search_results['results'])) {
        $series_id = $search_results['results'][0]['id'];
        $details_url = "https://api.themoviedb.org/3/tv/$series_id?api_key=$api_key";
        $details_response = file_get_contents($details_url);
        $series_details = json_decode($details_response, true);
        return $series_details['number_of_seasons']; // Return relevant data from TMDB
    } else {
        return null; // Handle series not found
    }
}

// Function to list folders and their subfolder counts
function list_folders($directory, $api_key, $offset, $limit) {
    $result = [];
    $counter = 0;

    // Check if directory exists and is readable
    if (is_dir($directory) && is_readable($directory)) {
        $folders = scandir($directory);

        foreach ($folders as $folder) {
            // Ignore . and .. directories and only consider actual folders
            if ($folder !== '.' && $folder !== '..' && is_dir($directory . '/' . $folder)) {
                $counter++;

                // Skip folders until we reach the offset
                if ($counter <= $offset) {
                    continue;
                }

                // Replace underscore with space for TMDB search
                $series_name = str_replace('_', ' ', $folder);

                // Fetch series count from TMDB
                $tmdb_series_count = get_series_count_from_tmdb($series_name, $api_key);

                $subfolders = scandir($directory . '/' . $folder);
                $subfolder_count = count(array_filter($subfolders, function ($item) use ($directory, $folder) {
                    return $item !== '.' && $item !== '..' && is_dir($directory . '/' . $folder . '/' . $item);
                }));

                // Determine status
                if ($tmdb_series_count === $subfolder_count) {
                    $status = 'Complete';
                    $status_class = 'complete';
                } elseif ($tmdb_series_count > $subfolder_count) {
                    $status = 'Incomplete';
                    $status_class = 'incomplete';
                } else {
                    $status = 'Unknown';
                    $status_class = 'unknown';
                }

                $result[] = [
                    'name' => $folder, // Original folder name with underscores
                    'subfolder_count' => $subfolder_count,
                    'tmdb_series_count' => $tmdb_series_count,
                    'status' => $status,
                    'status_class' => $status_class
                ];

                // Break the loop when we have reached the limit
                if (count($result) >= $limit) {
                    break;
                }
            }
        }
    } else {
        echo "Directory '$directory' does not exist or is not readable.";
    }

    return $result;
}

// Directory containing folders to scan
$base_directory = 'W:\Media\Video\TV Shows';

// TMDB API key (replace with your actual API key)
$tmdb_api_key = '4f3b2741447df1d94372ebd4bf2cf973';

// Parameters for pagination
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = 10;

// List folders based on offset and limit
$folders = list_folders($base_directory, $tmdb_api_key, $offset, $limit);

// Display results in a table with dynamic cell highlighting
if (!empty($folders)) {
    echo "<style>
            .complete { background-color: #a6f7a6; } /* Light green */
            .incomplete { background-color: #f7aaaa; } /* Light red */
            .unknown { background-color: #ffffff; } /* White */
          </style>";

    echo "<div id='folder-table'>";
    echo "<table border='1'>
            <tr>
                <th>Folder Name</th>
                <th>Subfolder Count</th>
                <th>TMDB Series Count</th>
                <th>Status</th>
            </tr>";
    
    foreach ($folders as $folder) {
        echo "<tr>";
        echo "<td>" . $folder['name'] . "</td>";
        echo "<td>" . $folder['subfolder_count'] . "</td>";
        echo "<td>" . $folder['tmdb_series_count'] . "</td>";
        echo "<td class='" . $folder['status_class'] . "'>" . $folder['status'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    echo "</div>";

    // Display load more button if there are more folders to load
    if (count($folders) >= $limit) {
        $next_offset = $offset + $limit;
        echo "<button id='load-more' data-offset='$next_offset' data-limit='$limit'>Load 10 More</button>";
    }

} else {
    echo "No folders found or unable to access directory.";
}
?>

<script>
    // JavaScript for handling load more functionality
    document.getElementById('load-more').addEventListener('click', function() {
        var offset = this.getAttribute('data-offset');
        var limit = this.getAttribute('data-limit');
        var url = window.location.href.split('?')[0] + '?offset=' + offset;

        // Make AJAX request to fetch more data
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);

        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 400) {
                var response = xhr.responseText;
                var newTable = document.createElement('div');
                newTable.innerHTML = response;

                // Append new table content to existing table
                var currentTable = document.getElementById('folder-table');
                currentTable.innerHTML += newTable.querySelector('#folder-table').innerHTML;

                // Update offset for next load
                document.getElementById('load-more').setAttribute('data-offset', parseInt(offset) + parseInt(limit));

                // Remove load more button if no more data to load
                if (newTable.querySelector('#load-more') === null) {
                    document.getElementById('load-more').remove();
                }
            } else {
                console.error('Error loading more folders');
            }
        };

        xhr.onerror = function() {
            console.error('Request failed');
        };

        xhr.send();
    });
</script>
