<?php
$db = new SQLite3(__DIR__ . '/data.db');

// Rendera artikelkort
function renderArticles($db, $category, $limit = 3) {
    $limit = (int)$limit;
    $sql = "
        SELECT id, title, excerpt, image, date
        FROM articles
        WHERE TRIM(category) = :category
        ORDER BY date DESC
        LIMIT $limit
    ";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':category', $category, SQLITE3_TEXT);
    $res = $stmt->execute();

    echo '<div class="grid">';
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $img = trim($row['image'] ?? '') !== '' ? $row['image'] : 'assets/fallback.jpg';

        // Rensa bort fultaggar från excerpt
        $excerpt = strip_tags($row['excerpt']);
        $excerpt = htmlspecialchars($excerpt);
        if (mb_strlen($excerpt) > 80) {
            $excerpt = mb_strimwidth($excerpt, 0, 80, "…", "UTF-8");
        }

        echo '<div class="card">';
        echo '<img src="'.htmlspecialchars($img).'" alt="" class="article-cover" loading="lazy">';
        echo '<h3 style="margin-top:.25rem"><a href="article.php?id='.$row['id'].'" style="text-decoration:none;color:inherit">'.htmlspecialchars($row['title']).'</a></h3>';
        echo '<p class="badge">'.htmlspecialchars($row['date']).'</p>';
        echo '<p>'.$excerpt.'</p>';
        echo '</div>';
    }
    echo '</div>';
}

// Hämta senaste tematiska artikeln
$latestThematic = $db->querySingle("SELECT * FROM articles WHERE category='Tematisk läsning' ORDER BY date DESC LIMIT 1", true);
?>

<!doctype html>
<html lang="ps" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>د سولې او روحانیت کتابتون – د مولانا وحیدالدین خان لیکنې</title>
<link rel="stylesheet" href="styles.css">

<style>
/* 👉 endast stil för tematisk sektion */
.featured-thematic {
  display: flex;
  flex-direction: row-reverse; /* bilden till höger */
  align-items: stretch;
  background: #f0f4ff;
  border-radius: .5rem;
  padding: 1rem;
  margin-bottom: 2rem;
  box-shadow: var(--shadow);
  gap: 1rem;
}
.featured-thematic img {
  width: 60%;   /* större bild */
  object-fit: cover;
  border-radius:.8rem;
}
.featured-thematic .info {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
}
</style>
</head>
<body>

<?php include 'nav.php'; ?>

<main class="container">

  <?php if ($latestThematic): ?>
  <section class="featured-thematic">
    <img src="<?php echo htmlspecialchars($latestThematic['image'] ?: 'assets/fallback.jpg'); ?>" alt="">
    <div class="info">
      <h2><a href="thematicarticles.php?id=<?php echo $latestThematic['id']; ?>">🌟 <?php echo htmlspecialchars($latestThematic['title']); ?></a></h2>
      <p style="color:var(--muted);font-size:.9rem;margin:.25rem 0">
        <?php echo htmlspecialchars($latestThematic['date']); ?>
      </p>
      <p>
        <?php 
          $excerpt = strip_tags($latestThematic['excerpt']);
          echo htmlspecialchars(mb_strimwidth($excerpt, 0, 120, "…", "UTF-8")); 
        ?>
      </p>
    </div>
  </section>
  <?php endif; ?>

  <section>
    <h2 style="margin:0 0 .2rem">📖 تازه مطالب</h2>
    <?php renderArticles($db, 'Dagens läsning', 3); ?>
  </section>

  <section>
    <h2 style="margin:0 0.5rem">📚 ځانګړي مطالب</h2>
    <?php renderArticles($db, 'Veckans läsning', 3); ?>
  </section>

  <section>
    <h2 style="margin:0 0 .5rem">الهامي ویناوې</h2>
    <?php renderArticles($db, 'Dagens inspiration', 3); ?>
  </section>

</main>

<p>
<center>
  <img class="padded" src="assets/logo.svg" width="36" height="36" style="border-radius:10px"> 
  <img class="padded" src="assets/logo.svg" width="36" height="36" style="border-radius:10px"> 
  <img class="padded" src="assets/logo.svg" width="36" height="36" style="border-radius:10px"> 
</center>
</p>

<?php include 'footer.php'; renderFooter(); ?>

</body>
</html>
