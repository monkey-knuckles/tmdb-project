<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TV Series Search</title>
</head>
<body>
    <h1>Search for TV Series</h1>
    <form action="index.php" method="post">
        <label for="series_name">Series Name:</label>
        <input type="text" id="series_name" name="series_name" required>
        <button type="submit">Search</button>
    </form>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $series_name = htmlspecialchars($_POST['series_name']);
        $api_key = '4f3b2741447df1d94372ebd4bf2cf973';

        // Function to get the number of seasons
        function get_number_of_seasons($api_key, $series_name) {
            $search_url = "https://api.themoviedb.org/3/search/tv?api_key=$api_key&query=" . urlencode($series_name);
            $search_response = file_get_contents($search_url);
            $search_results = json_decode($search_response, true);

            if (!empty($search_results['results'])) {
                $series_id = $search_results['results'][0]['id'];
                $details_url = "https://api.themoviedb.org/3/tv/$series_id?api_key=$api_key";
                $details_response = file_get_contents($details_url);
                $series_details = json_decode($details_response, true);
                return $series_details['number_of_seasons'];
            } else {
                return null;
            }
        }

        $number_of_seasons = get_number_of_seasons($api_key, $series_name);

        if ($number_of_seasons !== null) {
            echo "<p>The series '$series_name' has $number_of_seasons seasons.</p>";
        } else {
            echo "<p>Series '$series_name' not found.</p>";
        }
    }
    ?>
</body>
</html>
