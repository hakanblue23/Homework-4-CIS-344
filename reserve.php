<?php
    session_start();
    if (!isset($_SESSION["form_token"])) {
        $_SESSION["form_token"] = bin2hex(random_bytes(32));
    }

    $page_title = "Reserve Tickets";
    include "datadata.php";

    $customer_name = "";
    $email = "";
    $concert_id = 0;
    $quantity = 1;
    $message = "";
    $confirmation = "";

    if (isset($_SESSION["confirmation"])) {
        $confirmation = $_SESSION["confirmation"];
        unset($_SESSION["confirmation"]);
    }

 try {
        $result = $db->query("SELECT concert_id, artist, venue, concert_date, ticket_price
                              FROM concerts WHERE concert_date > NOW() ORDER BY concert_id DESC");
        $concerts = array();
        while ($row = $result->fetch_assoc()) {
            $concerts[$row["concert_id"]] = $row;
        }

        if (isset($_GET["event"]) && is_string($_GET["event"])) {
            $concert_id = (int) $_GET["event"];
        }

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            
            $customer_name = trim((string) filter_input(INPUT_POST, "customer_name"));
            $email = trim((string) filter_input(INPUT_POST, "email"));
            $concert_id = filter_input(INPUT_POST, "concert_id", FILTER_VALIDATE_INT);
            $quantity = filter_input(INPUT_POST, "quantity", FILTER_VALIDATE_INT);
            $form_token = filter_input(INPUT_POST, "form_token");

            // finally got this to work
            if (!is_string($form_token) || !hash_equals($_SESSION["form_token"], $form_token)) {
                $message = "Please reload the page and try again.";
            } elseif ($customer_name == "" || strlen($customer_name) > 100) {
                $message = "Please enter your name using 100 characters or fewer.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
                $message = "Please enter a valid email address.";
            } elseif (!isset($concerts[$concert_id])) {
                $message = "Please select an upcoming concert.";
            } elseif ($quantity === false || $quantity === null || $quantity < 1 || $quantity > 8) {
                $message = "Please choose between 1 and 8 tickets.";
            } else {
                
                $price = $concerts[$concert_id]["ticket_price"];
                $query = "INSERT INTO reservations (concert_id, customer_name, email, quantity, unit_price, submission_token)
                          VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                $stmt->bind_param("issids", $concert_id, $customer_name, $email, $quantity, $price, $form_token);
                $stmt->execute();
                $reservation_id = $stmt->insert_id;
                $stmt->close();

                $total = $quantity * $price;
                $_SESSION["confirmation"] = "Reservation #" . $reservation_id . " saved for " .
                    $concerts[$concert_id]["artist"] . ". Tickets: " . $quantity .
                    ". Total: $" . number_format($total, 2) . ".";

                // opens a fresh page so refreshing does not save the form again
                $_SESSION["form_token"] = bin2hex(random_bytes(32));
                $db->close();
                header("Location: reserve.php", true, 303);
                exit;
            }
        }
    } catch (mysqli_sql_exception $e) {
        error_log($e->getMessage());
        $message = "Your request could not be completed. Please try again.";
    }

    include "header.php";
?>

<main class="section reservation-page">
    <h1>Reserve your tickets</h1>
    <p>Choose a concert and enter your details.</p>

    <?php if ($confirmation != "") { ?>
        <p class="success" role="status"><?php echo htmlspecialchars($confirmation); ?></p>
    <?php } ?>

    <?php if ($message != "") { ?>
        <p class="error" role="alert"><?php echo htmlspecialchars($message); ?></p>
    <?php } ?>

    <?php if (!empty($concerts)) { ?>
        <form action="reserve.php" method="post">
            <input type="hidden" name="form_token" value="<?php echo htmlspecialchars($_SESSION["form_token"]); ?>">

            <label for="customer_name">Full name</label>
            <input type="text" id="customer_name" name="customer_name" maxlength="100"
                   value="<?php echo htmlspecialchars($customer_name); ?>" required>

            <label for="email">Email address</label>
            <input type="email" id="email" name="email" maxlength="254"
                   value="<?php echo htmlspecialchars($email); ?>" required>

            <label for="concert_id">Concert</label>
            <select id="concert_id" name="concert_id" required>
        <option value="">Choose a concert</option>
                <?php foreach ($concerts as $id => $concert) { ?>
        <option value="<?php echo $id; ?>" <?php if ($concert_id == $id) echo "selected"; ?>>
                        <?php echo htmlspecialchars($concert["artist"]) . " / " .
                            date("M j, g:i A", strtotime($concert["concert_date"])) .
                            " / $" . number_format($concert["ticket_price"], 2); ?>
                    </option>
                <?php } ?>
            </select>

            <label for="quantity">Number of tickets (1 to 8)</label>
            <input type="number" id="quantity" name="quantity" min="1" max="8"
                   value="<?php echo (int) $quantity; ?>" required>

            <button type="submit">Save reservation</button>
        </form>
    <?php } else { ?>
                    <p>No upcoming concerts are available to reserve.</p>
    <?php } ?>

    <p class="details">Thank you! </p>
</main>

<?php
    $db->close();
    include "footc.php";
?>
