<?php
    // connects the pages to the concert database
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $db = new mysqli("localhost", "concert_events_app", "66BBE617FC4E131AF377C75F8B90E49379E69C39ECCB9D60", "concert_events");
        $db->set_charset("utf8mb4");
    } catch (mysqli_sql_exception $e) {
        error_log($e->getMessage());
        http_response_code(503);
        exit("The concert database is unavailable. Please try again later.");
    }
?>
