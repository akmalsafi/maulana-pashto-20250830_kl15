<?php
$db = new SQLite3(__DIR__ . '/data.db');

function clean_msoword($html) {
  // ta bort MS Word-attribut och Mso-klasser
  $html = preg_replace('/\s*style="[^"]*mso-[^"]*"/i', '', $html);
  $html = preg_replace('/\s*class="Mso[^"]*"/i', '', $html);
  // ta bort Google Translate-block (<pre id="tw-target-text"...>)
  $html = preg_replace('/<pre[^>]*id="tw-target-text"[^>]*>.*?<\/pre>/is', '', $html);
  // tillåt bara enkla taggar (resten, inkl. span, försvinner)
  $html = strip_tags($html, '<p><br><strong><b><em><i><a><ul><ol><li><blockquote>');
  // normalisera whitespace
  $html = preg_replace('/\s+/u', ' ', $html);
  return trim($html);
}
function make_excerpt($html, $len = 220) {
  $text = clean_msoword($html);
  $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  if (mb_strlen($text,'UTF-8') > $len) {
    $text = mb_substr($text, 0, $len, 'UTF-8') . '…';
  }
  return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

$page  = max(1, intval($_GET['page'] ?? 1));
$limit = 9;
$offset = ($page - 1) * $limit;

// Hämta senaste "Tematisk läsning"
$latestStmt = $db->prepare("SELECT * FROM articles WHERE category='Tematisk läsning' ORDER BY date DESC LIMIT 1");
$latestRes  = $latestStmt->execute();
$latestThematic = $latestRes ? $latestRes->fetchArray(SQLITE3_ASSOC) : null;

// Lista för kort (exkludera senaste om den finns)
if ($latestThematic) {
  $listStmt = $db->prepare("SELECT id,title,excerpt,content,image,date FROM articles 
                            WHERE category='Tematisk läsning' AND id != :latest
                            ORDER BY date DESC LIMIT :limit OFFSET :offset");
  $listStmt->bindValue(':latest', $latestThematic['id'], SQLITE3_INTEGER);
  $countStmt = $db->prepare("SELECT COUNT(*) FROM articles WHERE category='Tematisk läsning' AND id != :latest");
  $countStmt->bindValue(':latest', $latestThematic['id'], SQLITE3_INTEGER);
} else {
  $listStmt = $db->prepare("SELECT id,title,excerpt,content,image,date FROM articles 
                            WHERE category='Tematisk läsning'
                            ORDER BY date DESC LIMIT :limit OFFSET :offset");
  $countStmt = $db->prepare("SELECT COUNT(*) FROM articles WHERE category='Tematisk läsning'");
}
$listStmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
$listStmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
$listRes = $listStmt->execute();

$countRes = $countStmt->execute();
$total = $countRes ? (int)$countRes->fetchArray(SQLITE3_NUM)[0] : 0;
$pages = max(1, (int)ceil($total / $limit));
?>
<!doctype html>
<html lang="ps" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tematisk läsning</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    /* Endast sid-specifik layout, inga stiländringar i övrigt */
    .thematic-hero{
      display:grid;grid-template-columns: 1fr 1.2fr;gap:1rem;
      background: #f6f7ff;border:1px solid var(--border);border-radius:1rem;padding:1rem;box-shadow:var(--shadow)
    }
    .thematic-hero .img-wrap img{width:100%;height:100%;object-fit:cover;border-radius:1rem;border:1px solid var(--border)}
    .thematic-hero h2{margin:.25rem 0 .35rem;font-size:1.6rem}
    .thematic-hero .meta{margin:.2rem 0 .6rem}
    .thematic-hero .meta .badge{font-size:.9rem;padding:.25rem .6rem}
    .thematic-grid{display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(260px,1fr))}
    .thematic-card .article-cover{aspect-ratio:16/9}
    @media (max-width: 820px){ .thematic-hero{grid-template-columns:1fr} }
  </style>
</head>
<body>
<?php include 'nav.php'; ?>

<main class="container">

  <h2 style="margin-top:.5rem">تماتیک لیکنې</h2>

  <?php if ($latestThematic): ?>
  <!-- Senaste Tematisk läsning (stor hero) -->
  <section class="thematic-hero" style="margin: .75rem 0 1.25rem">
    <div class="img-wrap">
      <a href="thematicarticles.php?id=<?php echo (int)$latestThematic['id']; ?>">
        <img src="<?php echo htmlspecialchars($latestThematic['image'] ?: 'assets/fallback.jpg'); ?>" alt="">
      </a>
    </div>
    <div class="text-wrap">
      <h2>
        <a href="thematicarticles.php?id=<?php echo (int)$latestThematic['id']; ?>" style="text-decoration:none;color:inherit">
          <?php echo htmlspecialchars($latestThematic['title']); ?>
        </a>
      </h2>
      <div class="meta">
        <span class="badge"><?php echo htmlspecialchars($latestThematic['date']); ?></span>
      </div>
      <p style="margin:.25rem 0 0;color:var(--muted)">
        <?php
          $lead = $latestThematic['excerpt'] ?: $latestThematic['content'];
          echo make_excerpt($lead, 260);
        ?>
      </p>
      <p style="margin-top:.6rem">
        <a class="btn" href="thematicarticles.php?id=<?php echo (int)$latestThematic['id']; ?>">لوستل</a>
      </p>
    </div>
  </section>
  <?php else: ?>
    <p class="notice">Ingen artikel i “Tematisk läsning” ännu.</p>
  <?php endif; ?>

  <!-- Rutnät med kort (senaste 9 med paginering) -->
  <section>
    <div class="thematic-grid">
      <?php while ($row = $listRes->fetchArray(SQLITE3_ASSOC)): ?>
        <article class="card thematic-card">
          <?php if (!empty($row['image'])): ?>
            <img class="article-cover" src="<?php echo htmlspecialchars($row['image']); ?>" alt="">
          <?php endif; ?>
          <h3 style="margin:.5rem 0 .25rem">
            <a href="thematicarticles.php?id=<?php echo (int)$row['id']; ?>" style="text-decoration:none;color:inherit">
              <?php echo htmlspecialchars($row['title']); ?>
            </a>
          </h3>
          <p class="badge" style="margin:0 0 .4rem"><?php echo htmlspecialchars($row['date']); ?></p>




         
        </article>
      <?php endwhile; ?>
    </div>

    <!-- Paginering -->
    <div class="pagination" style="margin-top:1rem;display:flex;align-items:center;gap:.75rem;justify-content:center">
      <a class="btn <?php echo ($page <= 1 ? 'disabled' : ''); ?>" href="?page=<?php echo max(1, $page-1); ?>">« مخکنی</a>
      <span>پاڼه <?php echo $page; ?> / <?php echo $pages; ?></span>
      <a class="btn <?php echo ($page >= $pages ? 'disabled' : ''); ?>" href="?page=<?php echo min($pages, $page+1); ?>">بل »</a>
    </div>
  </section>

</main>

<?php include 'footer.php'; renderFooter(); ?>
</body>
</html>
