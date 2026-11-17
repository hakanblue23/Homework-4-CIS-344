<?php
    $page_title = "Concerts";
    include "datadata.php";

    // gets the concerts
    try {
        $result = $db->query("SELECT concert_id, artist, genre, venue, concert_date, ticket_price
                              FROM concerts WHERE concert_date > NOW() ORDER BY concert_id DESC");
    } catch (mysqli_sql_exception $e) {
        error_log($e->getMessage());
        http_response_code(503);
        exit("Concerts could not be loaded. Please try again later.");
    }

    // photos hopefull loads for others
    $photos = array(1 => "carti.jpg", 2 => "uzi.jpg", 3 => "lucki.jpg");

    include "header.php";
?>

<main>
    <section class="hero">
        <p>LIVE MUSIC / BLASSIC</p>
        <h1>Your next night out.</h1>
        <p>Find a concert and reserve your tickets.</p>
        <a class="button" href="#events">Explore concerts</a>
    </section>

    <section class="section" id="events">
        <h2>Upcoming concerts</h2>
        <?php if ($result->num_rows == 0) { ?>
            <p>More concerts coming soon.</p>
        <?php } ?>

        <?php while ($concert = $result->fetch_assoc()) { ?>
            <article class="concert">
                <?php if (isset($photos[$concert["concert_id"]])) { ?>
                    <img class="artist-photo<?php if ($concert["concert_id"] == 1) echo " tour-poster"; ?>" src="images/<?php echo $photos[$concert["concert_id"]]; ?>"
                         alt="<?php echo htmlspecialchars($concert["artist"]); ?>" loading="lazy">
                <?php } ?>
                <p class="details"><?php echo htmlspecialchars($concert["genre"]); ?></p>
                <h3><?php echo htmlspecialchars($concert["artist"]); ?></h3>
                <p><?php echo htmlspecialchars($concert["venue"]); ?></p>
                <p><?php echo date("F j, Y / g:i A", strtotime($concert["concert_date"])); ?></p>
                <p>$<?php echo number_format($concert["ticket_price"], 2); ?> per ticket</p>
                <a class="button" href="reserve.php?event=<?php echo $concert["concert_id"]; ?>">Reserve tickets</a>
            </article>
        <?php } ?>


    </section>
</main>

<?php
    $db->close();
    include "footc.php";
?>
