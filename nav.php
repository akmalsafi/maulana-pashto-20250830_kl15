<?php
// nav.php
?>
<style>
  .banner {
    width: 100%;
    height: 220px; /* höjden på banner */
    background: url('images/icons/mosque2.png') no-repeat center center;
    background-size: cover;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 1rem;
    position: relative;
    margin-bottom: 1rem;
  }

  .banner-content {
    display: flex;
    align-items: center;
    gap: 2rem;
    background: rgba(255,255,255,0.5);
    border-radius: 16px;
    padding: 1rem 2rem;
    box-shadow: 0 4px 10px rgba(0,0,0,0.25);
  }

  .banner-content img {
    max-height: 140px;
    border-radius: 12px;
  }

  .nav {
    display: flex;
    gap: 1rem;
  }

  .nav a {
    text-decoration: none;
    font-size: 1.1rem;
    color: #333;
    font-weight: bold;
    padding: .4rem .8rem;
    border-radius: 6px;
    transition: background 0.3s;
  }

  .nav a:hover {
    background: rgba(8,90,255,0.2);
  }
</style>

<div class="banner">
  <div class="banner-content">
    <!-- Meny till vänster -->
    <div class="nav">
       <a href="index.php" title="Home"> کورپاڼه</a>
  <a href="weekly.php" title="Veckans läsning"> ځانګړي مطالب</a>
  <a href="daily.php" title="Dagens läsning">لنډ مطالب</a>
  <a href="inspiration.php" title="Dagens inspiration">سوال او ځواب</a>
  <a href="thematic.php" title="Tematisk läsning">تماتیک لیکنې</a>
  <a href="admin.php" title="Admin">اداره</a>
</b></div>

    <!-- Bild till höger -->
    <img src="images/icons/khan6.png" alt="Khan">
  </div>
</div>
