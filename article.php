<?php
$db = new SQLite3(__DIR__ . '/data.db');

/**
 * Hämtar relaterade artiklar säkert och snabbt.
 * - category och currentId binds med prepared statements (skydd mot SQL injection)
 * - Väljer ett slumpat OFFSET i sorterad lista (snabbare än ORDER BY RANDOM())
 */
function getRelatedArticles(SQLite3 $db, string $category, int $currentId, int $limit = 4): SQLite3Result {
    // 1) Räkna hur många kandidater som finns
    $countStmt = $db->prepare("
        SELECT COUNT(*) AS c
        FROM articles
        WHERE category = :category AND id <> :currentId
    ");
    $countStmt->bindValue(':category',  $category,  SQLITE3_TEXT);
    $countStmt->bindValue(':currentId', $currentId, SQLITE3_INTEGER);
    $countRes  = $countStmt->execute();
    $countRow  = $countRes ? $countRes->fetchArray(SQLITE3_ASSOC) : ['c' => 0];
    $count     = (int)($countRow['c'] ?? 0);

    // 2) Slumpa ett offset-fönster i den sorterade listan
    $offsetMax = max(0, $count - $limit);
    $offset    = $offsetMax > 0 ? random_int(0, $offsetMax) : 0;

    // 3) Hämta ett “fönster” av artiklar (stabil sortering, snabbt)
    $stmt = $db->prepare("
        SELECT id, title, image
        FROM articles
        WHERE category = :category AND id <> :currentId
        ORDER BY id DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':category',  $category,  SQLITE3_TEXT);
    $stmt->bindValue(':currentId', $currentId, SQLITE3_INTEGER);
    $stmt->bindValue(':limit',     $limit,     SQLITE3_INTEGER);
    $stmt->bindValue(':offset',    $offset,    SQLITE3_INTEGER);

    return $stmt->execute();
}

// --- Artikel-id (säker cast till int) ---
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// --- Hämta artikel säkert ---
$stmt = $db->prepare("SELECT * FROM articles WHERE id = :id");
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$res = $stmt->execute();
$article = $res->fetchArray(SQLITE3_ASSOC);

if (!$article) {
    echo "❌ Artikel hittades inte.";
    exit;
}

// Hämta Unsplash slumpbild (ny varje gång)
$apiKey   = "XfEuQS4hVf0ddoryv5uaMnzNC7WLmdyedl5UsHj2C0w"; // byt till din riktiga nyckel
$keywords = "nature,sea,religion,sunset,mosque,spirituality,peace,universe,stars,sad,birds";
$url      = "https://api.unsplash.com/photos/random?query=$keywords&count=10&client_id=$apiKey&sig=" . rand(1,99999);

$response = @file_get_contents($url);
if ($response !== false) {
    $data = json_decode($response, true);

    // Om vi fick flera bilder, välj en slumpad
    if (isset($data[0])) {
        $random      = $data[array_rand($data)];
        $unsplashImg = $random['urls']['regular'] ?? "assets/fallback.jpg";
    } else {
        $unsplashImg = $data['urls']['regular'] ?? "assets/fallback.jpg";
    }
} else {
    $unsplashImg = "assets/fallback.jpg";
}

// Relaterade artiklar (säker & snabb funktion)
$relatedDaily  = getRelatedArticles($db, 'Dagens läsning',  $id, 4);
$relatedWeekly = getRelatedArticles($db, 'Veckans läsning', $id, 4);
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
    margin-top: 0.01rem;
  }

  .article-layout { display: block; } /* Ingen flex — behövs för float-flow */

  .article-image {
    float: left;               /* OBS: din nuvarande inställning (vänster) */
    margin: 0 0 1rem 1rem;     /* Luft under & vänster */
    max-width: 40%;
  }
  .article-image img {
    width: 100%;
    height: auto;
    border-radius: 10px;
  }

  @media (max-width: 768px) {
    .article-image { float: none; max-width: 100%; margin: 0 0 1rem 0; }
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
  .related-card:hover { transform: translateY(-3px); }
  .related-card img {
    width: 100%;
    height: 140px;
    object-fit: cover;
    border-radius: 6px;
  }
  .related-card h4 { margin: .5rem 0 .25rem; font-size: 1.1rem; }
  .related-card p { font-size: .85rem; color: #333; }
</style>
</head>
<body>
<?php include 'nav.php'; ?>

<main class="container">

  <!-- Stor Unsplash-bild + rubrik -->
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
      <?php if ($article['image']): ?>
        <div class="article-image">
          <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="">
        </div>
      <?php endif; ?>

      <div class="article-text">
        <?php echo $article['content']; ?>
      </div>
    </div>
  </div>

  <p style="text-align:center;margin-top:1rem">
    <a class="btn" href="index.php">⬅️ بېرته کور ته</a>
  </p>

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
