<?php
session_start();

// Skydda admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$db = new SQLite3(__DIR__ . '/data.db');

// Säkerställ att tabellen existerar
$db->exec("CREATE TABLE IF NOT EXISTS articles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT,
    excerpt TEXT,
    content TEXT,
    category TEXT,
    image TEXT,
    date TEXT DEFAULT CURRENT_TIMESTAMP
)");

// ---- LÄGG TILL ----
if (isset($_POST['add'])) {
    $title   = $_POST['title'] ?? '';
    $excerpt = $_POST['excerpt'] ?? '';
    $content = $_POST['content'] ?? '';
    $category= $_POST['category'] ?? '';

    // Hantera bilduppladdning
    $image = null;
    if (!empty($_FILES['image']['name'])) {
        $targetDir = __DIR__ . "/uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fileName   = time() . "_" . basename($_FILES['image']['name']);
        $targetFile = $targetDir . $fileName;
        if (is_uploaded_file($_FILES['image']['tmp_name'])) {
            move_uploaded_file($_FILES['image']['tmp_name'], $targetFile);
            $image = "uploads/" . $fileName;
        }
    }

    $stmt = $db->prepare("INSERT INTO articles (title, excerpt, content, category, image) 
                          VALUES (:title, :excerpt, :content, :category, :image)");
    $stmt->bindValue(':title',   $title,   SQLITE3_TEXT);
    $stmt->bindValue(':excerpt', $excerpt, SQLITE3_TEXT);
    $stmt->bindValue(':content', $content, SQLITE3_TEXT);
    $stmt->bindValue(':category',$category,SQLITE3_TEXT);
    $stmt->bindValue(':image',   $image,   SQLITE3_TEXT);
    $stmt->execute();
}

// ---- TA BORT ----
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->exec("DELETE FROM articles WHERE id=$id");
    header("Location: admin.php");
    exit;
}

// ---- UPPDATERA (REDIGERA) ----
if (isset($_POST['update'])) {
    $id      = (int)($_POST['id'] ?? 0);
    $title   = $_POST['title'] ?? '';
    $excerpt = $_POST['excerpt'] ?? '';
    $content = $_POST['content'] ?? '';
    $category= $_POST['category'] ?? '';

    // Behåll befintlig bild om ingen ny laddas upp
    $image = $_POST['current_image'] ?? null;
    if (!empty($_FILES['image']['name'])) {
        $targetDir = __DIR__ . "/uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fileName   = time() . "_" . basename($_FILES['image']['name']);
        $targetFile = $targetDir . $fileName;
        if (is_uploaded_file($_FILES['image']['tmp_name'])) {
            move_uploaded_file($_FILES['image']['tmp_name'], $targetFile);
            $image = "uploads/" . $fileName;
        }
    }

    $stmt = $db->prepare("UPDATE articles 
                          SET title=:title, excerpt=:excerpt, content=:content, category=:category, image=:image 
                          WHERE id=:id");
    $stmt->bindValue(':title',    $title,    SQLITE3_TEXT);
    $stmt->bindValue(':excerpt',  $excerpt,  SQLITE3_TEXT);
    $stmt->bindValue(':content',  $content,  SQLITE3_TEXT);
    $stmt->bindValue(':category', $category, SQLITE3_TEXT);
    $stmt->bindValue(':image',    $image,    SQLITE3_TEXT);
    $stmt->bindValue(':id',       $id,       SQLITE3_INTEGER);
    $stmt->execute();

    header("Location: admin.php");
    exit;
}
?>
<!doctype html>
<html lang="sv">
<!doctype html>
<html lang="sv">
<!doctype html>
<html lang="sv">
<head>
  <meta charset="utf-8">
  <title>Adminpanel</title>
  <link rel="stylesheet" href="styles.css">

  <!-- TinyMCE -->
  <script src="https://cdn.tiny.cloud/1/autczrpyzfk1nlrgo7n3f4g3buxhojq9wwl77qpj7h5tgoym/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  // Editor för sammanfattning
  tinymce.init({
    selector: 'textarea#excerpt',
    menubar: false,
    plugins: 'link lists code',
    toolbar: 'undo redo | bold italic underline | bullist numlist | link | code',
    branding: false,
    min_height: 200,
    content_style: "body { font-size: 18px; font-weight: bold; line-height:1.6; }",
    forced_root_block: 'p',       // 👈 skapar riktiga <p> vid radbrytning
    force_br_newlines: true,      // 👈 Shift+Enter = <br>
    force_p_newlines: false,      // 👈 Enter = <p>
    setup: function (editor) {
      editor.on('change', function () {
        editor.save();
      });
    }
  });

  // Editor för artikeltext
 tinymce.init({
  selector: 'textarea#content, textarea#excerpt',
  plugins: 'link lists code paste',
  toolbar: 'undo redo | bold italic underline | bullist numlist | link | code',
  menubar: false,
  branding: false,
  paste_as_text: true,  // 👈 ALLT klistrat in blir ren text
  content_style: "body { font-size:16px; line-height:1.7; }",
  setup: function (editor) {
    editor.on('change', function () {
      editor.save();
    });
  }
});

</script>

</head>
<body class="container">

<h1>Adminpanel</h1>

<?php
// ===== REDIGERINGS-LÄGE =====
if (isset($_GET['edit'])):
    $editId = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM articles WHERE id=:id");
    $stmt->bindValue(':id', $editId, SQLITE3_INTEGER);
    $res = $stmt->execute();
    $article = $res ? $res->fetchArray(SQLITE3_ASSOC) : null;

    if ($article):
?>
  <h2>✏️ Redigera artikel</h2>
  <form method="post" enctype="multipart/form-data" style="max-width:800px;margin:auto;display:flex;flex-direction:column;gap:1rem">
    <input type="hidden" name="id" value="<?php echo (int)$article['id']; ?>">
    <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($article['image'] ?? ''); ?>">

    <label for="title">Titel</label>
    <input type="text" id="title" name="title" required
           value="<?php echo htmlspecialchars($article['title']); ?>"
           style="padding:.75rem;font-size:1.1rem;width:100%;border:1px solid #ccc;border-radius:8px">

    <label for="excerpt">Sammanfattning</label>
    <textarea id="excerpt" name="excerpt" rows="4" required
              style="padding:.75rem;font-size:1.1rem;width:100%;border:1px solid #ccc;border-radius:8px"><?php echo htmlspecialchars($article['excerpt']); ?></textarea>

    <label for="content">Artikeltext</label>
    <textarea id="content" name="content" rows="10" required
              style="padding:.75rem;font-size:1.1rem;width:100%;border:1px solid #ccc;border-radius:8px"><?php echo htmlspecialchars($article['content']); ?></textarea>

    <label for="category">Kategori</label>
    <select id="category" name="category" required
        style="padding:.75rem;font-size:1.1rem;width:100%;border:1px solid #ccc;border-radius:8px">
      <option value="Dagens läsning"    <?php if(($article['category']??'')==="Dagens läsning")    echo "selected"; ?>>Dagens läsning</option>
      <option value="Veckans läsning"   <?php if(($article['category']??'')==="Veckans läsning")   echo "selected"; ?>>Veckans läsning</option>
      <option value="Dagens inspiration"<?php if(($article['category']??'')==="Dagens inspiration")echo "selected"; ?>>Dagens inspiration</option>
      <option value="Tematisk läsning"  <?php if(($article['category']??'')==="Tematisk läsning")  echo "selected"; ?>>Tematisk läsning</option>
    </select>

    <label for="image">Byt bild (valfritt)</label>
    <input type="file" id="image" name="image" accept="image/*"
           style="padding:.75rem;font-size:1rem;width:100%;border:1px solid #ccc;border-radius:8px">
    <?php if (!empty($article['image'])): ?>
      <p>Nuvarande bild:<br><img src="<?php echo htmlspecialchars($article['image']); ?>" style="max-width:200px;border-radius:6px;margin-top:5px"></p>
    <?php endif; ?>

    <button type="submit" name="update" class="btn" style="padding:1rem;font-size:1.2rem;width:100%">Uppdatera artikel</button>
  </form>

  <p style="margin-top:1rem"><a href="admin.php">⬅️ Tillbaka</a></p>

<?php
    else:
        echo "<p>❌ Artikel hittades inte.</p><p><a href='admin.php'>Tillbaka</a></p>";
    endif;

// ===== STANDARD-LÄGE (Lägg till + Lista) =====
else:
?>

<h2>➕ Lägg till ny artikel</h2>
<form method="post" enctype="multipart/form-data" style="max-width:800px;margin:auto;display:flex;flex-direction:column;gap:1rem">
  <label for="title">Titel</label>
  <input type="text" id="title" name="title" required 
         style="padding:.75rem;font-size:1.1rem;width:100%;border:1px solid #ccc;border-radius:8px">

  <label for="excerpt">Sammanfattning</label>
  <textarea id="excerpt" name="excerpt" rows="4" required 
            style="padding:.75rem;font-size:1.1rem;width:100%;border:1px solid #ccc;border-radius:8px"></textarea>

  <label for="content">Artikeltext</label>
  <textarea id="content" name="content" rows="10" required 
            style="padding:.75rem;font-size:1.1rem;width:100%;border:1px solid #ccc;border-radius:8px"></textarea>

  <label for="category">Kategori</label>
  <select id="category" name="category" required
        style="padding:.75rem;font-size:1.1rem;width:100%;border:1px solid #ccc;border-radius:8px">
    <option value="Dagens läsning">Dagens läsning</option>
    <option value="Veckans läsning">Veckans läsning</option>
    <option value="Dagens inspiration">Dagens inspiration</option>
    <option value="Tematisk läsning">Tematisk läsning</option>
  </select>

  <label for="image">Ladda upp bild</label>
  <input type="file" id="image" name="image" accept="image/*"
         style="padding:.75rem;font-size:1rem;width:100%;border:1px solid #ccc;border-radius:8px">

  <button type="submit" name="add" class="btn" style="padding:1rem;font-size:1.2rem;width:100%">Spara artikel</button>
</form>

<hr>

<h2>📑 Befintliga artiklar</h2>

<?php
// Bestäm sorteringsfält och riktning
$allowedSort = ['id','category','date'];
$sort = $_GET['sort'] ?? 'date';
if (!in_array($sort, $allowedSort)) $sort = 'date';

$dir = $_GET['dir'] ?? 'DESC';
$dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

// Förbereda nästa riktning (växla vid klick)
$nextDir = $dir === 'ASC' ? 'DESC' : 'ASC';

$results = $db->query("SELECT * FROM articles ORDER BY $sort $dir");
?>

<table border="1" cellpadding="5" cellspacing="0" style="width:100%;margin-top:1rem;border-collapse:collapse">
  <tr>
    <th><a href="?sort=id&dir=<?php echo ($sort==='id' ? $nextDir : 'ASC'); ?>">ID</a></th>
    <th>Titel</th>
    <th><a href="?sort=category&dir=<?php echo ($sort==='category' ? $nextDir : 'ASC'); ?>">Kategori</a></th>
    <th><a href="?sort=date&dir=<?php echo ($sort==='date' ? $nextDir : 'ASC'); ?>">Datum</a></th>
    <th>Bild</th>
    <th>Åtgärder</th>
  </tr>
<?php
while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
    echo "<tr>";
    echo "<td>".$row['id']."</td>";
    echo "<td>".htmlspecialchars($row['title'])."</td>";
    echo "<td>".htmlspecialchars($row['category'])."</td>";
    echo "<td>".htmlspecialchars($row['date'])."</td>";
    echo "<td>".(!empty($row['image']) ? "<img src='".htmlspecialchars($row['image'])."' width='80'>" : "-")."</td>";
    echo "<td>
            <a href='admin.php?edit=".$row['id']."'>✏️ Redigera</a> | 
            <a href='admin.php?delete=".$row['id']."' onclick=\"return confirm('Är du säker?')\">🗑️ Ta bort</a>
          </td>";
    echo "</tr>";
}
?>
</table>

<p><a href="logout.php">Logga ut</a></p>

<?php endif; // slut på redigerings-/standard-läge ?>
</body>
</html>
