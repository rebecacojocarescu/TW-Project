<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Pet News - Pow</title>
  <link rel="stylesheet" href="../stiluri/news.css" />
  <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&display=swap" rel="stylesheet">
</head>
<body>
  <header class="navbar">
    <a href="homepage.php" class="back-button">
      <span class="back-icon">←</span>
      <span>Back</span>
    </a>
    <div class="logo">Pow</div>
    <a href="profile.php" class="profile-icon">
      <img src="../stiluri/imagini/profileicon.png" alt="Profile">
    </a>
  </header>

  <section class="news-header">
    <h1 class="news-title">Latest Pet News</h1>
  </section>

  <section class="rss-section">
    <div class="rss-header-flex">
      <h2 class="rss-title">Ultimele animale propuse spre adopție</h2>
      <a href="../public/rss.php" target="_blank" class="rss-link">
        <img src="../stiluri/imagini/rss-icon.png" alt="RSS" style="width:24px;vertical-align:middle;"> Abonează-te la fluxul RSS!
      </a>
    </div>
    <div id="rss-feed" class="rss-container"></div>
  </section>

  <script>
    async function fetchRssFeed() {
      try {
        const response = await fetch('../public/api/rss-feed.php');
        const items = await response.json();
        
        const feedContainer = document.getElementById('rss-feed');
        feedContainer.innerHTML = '';
        if (items.length === 0) {
          feedContainer.innerHTML = '<p style="text-align:center;">Nu există animale disponibile momentan.</p>';
          return;
        }
        items.forEach(item => {
          const itemElement = document.createElement('div');
          itemElement.className = 'rss-item';
          itemElement.innerHTML = `
            ${item.image ? `<img src="${item.image}" alt="${item.title}" class="rss-pet-image">` : ''}
            <h3>${item.title}</h3>
            <div class="meta">
              ${item.date ? new Date(item.date).toLocaleDateString() : ''}
            </div>
            <div class="description">${item.description}</div>
            <a href="${item.link}" class="details-btn">Vezi detalii</a>
          `;
          feedContainer.appendChild(itemElement);
        });
      } catch (error) {
        console.error('Error fetching RSS feed:', error);
      }
    }

    document.addEventListener('DOMContentLoaded', fetchRssFeed);
  </script>
</body>
</html> 