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

// Function to list folders and their subfolder counts (limit to first 10 folders)
function list_folders($directory, $api_key) {
    $result = [];
    $counter = 0; // Counter to limit to first 10 folders

    // Check if directory exists and is readable
    if (is_dir($directory) && is_readable($directory)) {
        $folders = scandir($directory);

        foreach ($folders as $folder) {
            // Ignore . and .. directories and only consider actual folders
            if ($folder !== '.' && $folder !== '..' && is_dir($directory . '/' . $folder)) {
                // Replace underscore with space for TMDB search
                $series_name = str_replace('_', ' ', $folder);

                $counter++;

                // Fetch series count from TMDB
                $tmdb_series_count = get_series_count_from_tmdb($series_name, $api_key);

                $subfolders = scandir($directory . '/' . $folder);
                $subfolder_count = count(array_filter($subfolders, function ($item) use ($directory, $folder) {
                    return $item !== '.' && $item !== '..' && is_dir($directory . '/' . $folder . '/' . $item);
                }));

                $result[] = [
                    'name' => $folder, // Original folder name with underscores
                    'subfolder_count' => $subfolder_count,
                    'tmdb_series_count' => $tmdb_series_count
                ];

                // Limit to first 10 folders
                if ($counter >= 10) {
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

// Step 1: List folders and their subfolder counts (limited to first 10)
$folders = list_folders($base_directory, $tmdb_api_key);

// Display results
if (!empty($folders)) {
    foreach ($folders as $folder) {
        echo "Folder: " . $folder['name'] . "<br>";
        echo "Subfolder Count: " . $folder['subfolder_count'] . "<br>";
        echo "TMDB Series Count: " . $folder['tmdb_series_count'] . "<br>";

        // Compare subfolder count with TMDB series count
        if ($folder['tmdb_series_count'] > $folder['subfolder_count']) {
            echo "Echoing series name: " . $folder['name'] . "<br><br>";
        } else {
            echo "<br>";
        }
    }
} else {
    echo "No folders found or unable to access directory.";
}
?>
