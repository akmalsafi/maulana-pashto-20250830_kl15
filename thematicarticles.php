<?php
$db = new SQLite3(__DIR__ . '/data.db');
$id = $_GET['id'] ?? 0;
$stmt = $db->prepare("SELECT * FROM articles WHERE id=:id");
$stmt->bindValue(':id',$id,SQLITE3_INTEGER);
$res = $stmt->execute();
$article = $res->fetchArray(SQLITE3_ASSOC);

if (!$article) {
    echo "❌ Artikel hittades inte.";
    exit;
}

// Hämta Unsplash slumpbild (ny varje gång)
$apiKey = "DIN_UNSPLASH_API_KEY"; // byt till din riktiga Unsplash-nyckel
$keywords = "nature,sea,religion";
$url = "https://api.unsplash.com/photos/random?query=$keywords&count=10&client_id=$apiKey&sig=" . rand(1,99999);

$response = @file_get_contents($url);
if ($response !== false) {
    $data = json_decode($response, true);
    if (isset($data[0])) {
        $random = $data[array_rand($data)];
        $unsplashImg = $random['urls']['regular'] ?? "assets/fallback.jpg";
    } else {
        $unsplashImg = $data['urls']['regular'] ?? "assets/fallback.jpg";
    }
} else {
    $unsplashImg = "assets/fallback.jpg";
}

// Hämta relaterade artiklar (andra från Tematisk läsning)
$related = $db->query("SELECT id,title,image FROM articles WHERE category='Tematisk läsning' AND id != $id ORDER BY RANDOM() LIMIT 4");


// Hämta tre slumpade artiklar från Dagens läsning
$relatedDaily = $db->query("SELECT id,title,image FROM articles WHERE category='Dagens läsning' AND id != $id ORDER BY RANDOM() LIMIT 4");
// Hämta tre slumpade artiklar från Veckans läsning
$relatedWeekly = $db->query("SELECT id,title,image FROM articles WHERE category='Veckans läsning' AND id != $id ORDER BY RANDOM() LIMIT 4");
?>
<!doctype html>
<html lang="ps" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($article['title']); ?></title>
<link rel="stylesheet" href="styles.css">
<style>
  .article-hero {
    width: 100%;
    height: 350px;
    background: url('<?php echo $unsplashImg; ?>') no-repeat center center;
    background-size: cover;
    border-radius: 12px;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    text-shadow: 0 2px 6px rgba(0,0,0,0.6);
    flex-direction: column;
    text-align: center;
    padding: 2rem;
  }
  .article-hero h1 {
    font-size: 2.2rem;
    margin: 0;
  }
  .article-hero p {
    font-size: 1rem;
    margin-top: .5rem;
    background: rgba(0,0,0,0.4);
    padding: .3rem .7rem;
    border-radius: 6px;
  }
  .article-container {
    background: rgba(8,90,255,0.02);
    border-radius: 12px;
    padding: 2rem;
  }
  .article-layout {
    display: flex;
    flex-direction: row;
    gap: 2rem;
    align-items: flex-start;
  }
  .article-text { flex: 2; }
  .article-image { flex: .9; }
  .article-image img {
    max-width: 100%;
    border-radius: 10px;
  }
  @media (max-width: 768px) {
    .article-layout { flex-direction: column; }
    .article-image { margin-top: 1rem; }
  }

  .related-section { margin-top: 3rem; text-align:center; }
  .related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill,minmax(220px,1fr));
    gap: 1.5rem;
    justify-items: center;
  }
  .related-card {
    background: rgba(8,90,255,0.02);
    padding: .75rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    transition: transform .5s;
    width: 100%;
    max-width: 250px;
    text-align: center;
  }
  .related-card:hover {
    transform: translateY(-3px);
  }
  .related-card img {
    width: 100%;
    height: 140px;
    object-fit: cover;
    border-radius: 6px;
  }
  .related-card h4 {
    margin: .5rem 0 .25rem;
    font-size: 1.1rem;
  }
</style>
</head>
<body>
<?php include 'nav.php'; ?>

<main class="container">

  <!-- Hero-bild från Unsplash -->
  <div class="article-hero">
    <h1><?php echo htmlspecialchars($article['title']); ?></h1>
    <p><?php echo htmlspecialchars($article['date']); ?></p>
  </div>

  <div class="article-container">
    <?php if (!empty($article['excerpt'])): ?>
      <div style="font-size:1.3rem;color:#555;margin:1rem 0;font-weight:bold;">
        <?php echo $article['excerpt']; ?>
      </div>
    <?php endif; ?>

    <div class="article-layout">
      <div class="article-text">
        <?php echo $article['content']; ?>
      </div>

      <?php if ($article['image']): ?>
      <div class="article-image">
        <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="">
      </div>
      <?php endif; ?>
    </div>
  </div>

  <p style="text-align:center;margin-top:1rem">
    <a class="btn" href="thematic.php">⬅️ بېرته کور ته</a>
  </p>

  <!-- Relaterade artiklar -->
  <div class="related-section">
    <h2>📖 ورته موضوعي مطالب</h2>
    <div class="related-grid">
      <?php while ($row = $related->fetchArray(SQLITE3_ASSOC)): ?>
        <div class="related-card">
          <img src="<?php echo htmlspecialchars($row['image'] ?: 'assets/fallback.jpg'); ?>" alt="">
          <h4><a href="thematicarticles.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['title']); ?></a></h4>
        </div>
      <?php endwhile; ?>
    </div>
  </div>



<div class="related-section">
    <h2>📖 ورته مطالب</h2>
    <div class="related-grid">
      <?php while ($row = $relatedDaily->fetchArray(SQLITE3_ASSOC)): ?>
        <div class="related-card">
          <img src="<?php echo htmlspecialchars($row['image'] ?: 'assets/fallback.jpg'); ?>" alt="">
          <h4><a href="article.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['title']); ?></a></h4>
        </div>
      <?php endwhile; ?>
    </div>
  </div>

  <div class="related-section">
    <h2>📚 ځانګړي مطالب</h2>
    <div class="related-grid">
      <?php while ($row = $relatedWeekly->fetchArray(SQLITE3_ASSOC)): ?>
        <div class="related-card">
          <img src="<?php echo htmlspecialchars($row['image'] ?: 'assets/fallback.jpg'); ?>" alt="">
          <h4><a href="article.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['title']); ?></a></h4>
        </div>
      <?php endwhile; ?>
    </div>
  </div>

</main>

<?php include 'footer.php'; renderFooter(); ?>

</body>
</html>
