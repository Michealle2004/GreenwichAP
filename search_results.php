<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

$search_query = isset($_GET['query']) ? trim($_GET['query']) : '';

$conn = connectToDatabase();

$search_results = [];

if (!empty($search_query)) {
    $safe_query = '%' . strtolower(pg_escape_string($conn, $search_query)) . '%';
    
    $sql = "
        SELECT
            'course' AS type,
            course_name AS title,
            'Course information for ' || course_name AS description,
            '#' AS link
        FROM
            courses
        WHERE
            LOWER(course_name) LIKE $1 OR LOWER(course_id) LIKE $1
        UNION ALL
        SELECT
            'report' AS type,
            'Attendance Report' AS title,
            'View your attendance records.' AS description,
            'attendance_report.php' AS link
        WHERE
            LOWER('Attendance Report') LIKE $1
        UNION ALL
        SELECT
            'report' AS type,
            'Curriculum' AS title,
            'View your major''s curriculum.' AS description,
            'curriculum.php' AS link
        WHERE
            LOWER('Curriculum') LIKE $1
        UNION ALL
        SELECT
            'report' AS type,
            'Course Fee' AS title,
            'View course fees for your major.' AS description,
            'course_fee.php' AS link
        WHERE
            LOWER('Course Fee') LIKE $1
        UNION ALL
        SELECT
            'info' AS type,
            'Change Password' AS title,
            'Change your account password.' AS description,
            'change_password.php' AS link
        WHERE
            LOWER('Change Password') LIKE $1
        UNION ALL
        SELECT
            'info' AS type,
            'Feedback Quality' AS title,
            'Submit feedback about a subject or lecturer.' AS description,
            'feedback_quality.php' AS link
        WHERE
            LOWER('Feedback Quality') LIKE $1
        ORDER BY
            type, title
    ";

    $prepare_result = pg_prepare($conn, "search_query", $sql);
    
    if ($prepare_result) {
        $result = pg_execute($conn, "search_query", array($safe_query));
        
        if ($result) {
            while ($row = pg_fetch_assoc($result)) {
                $search_results[] = $row;
            }
        } else {
            echo "<p class='message error'>Failed to execute search query: " . pg_last_error($conn) . "</p>";
        }
    } else {
        echo "<p class='message error'>Failed to prepare search query: " . pg_last_error($conn) . "</p>";
    }
}

pg_close($conn);
?>

<title>Search Results - Greenwich AP</title>

<div class="page-container">
    <h1>Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h1>
    
    <?php if (empty($search_results)): ?>
        <p>No results found. Please try a different search.</p>
    <?php else: ?>
        <div class="search-results-list">
            <?php foreach ($search_results as $result): ?>
                <div class="search-result-item">
                    <h4 class="search-result-title">
                        <?php echo htmlspecialchars($result['title']); ?>
                    </h4>
                    <p class="search-result-description">
                        <?php echo htmlspecialchars($result['description']); ?>
                    </p>
                    <a href="<?php echo htmlspecialchars($result['link']); ?>" class="search-result-link">View Details</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php 
require_once 'includes/footer.php'; 
?>