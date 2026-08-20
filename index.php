<?php
declare(strict_types=1);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

require_once __DIR__ . '/includes/reviews.php';
$config = require __DIR__ . '/includes/config.php';
$reviews = load_reviews();
$stylesVersion = (string) (filemtime(__DIR__ . '/styles.css') ?: time());
$scriptVersion = (string) (filemtime(__DIR__ . '/script.js') ?: time());

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function social_item(string $url, string $label, string $iconClass): string
{
    $content = '<i class="bi ' . e($iconClass) . '" aria-hidden="true"></i><span>' . e($label) . '</span>';

    if ($url === '') {
        return '<span class="social-link social-link--pending" aria-label="' . e($label) . ' link coming soon">' . $content . '</span>';
    }

    return '<a class="social-link" href="' . e($url) . '" target="_blank" rel="noopener noreferrer" aria-label="Open Yorvis on ' . e($label) . '">' . $content . '</a>';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Read what customers say about Yorvis and the people behind every experience.">
  <meta name="theme-color" content="#131628">
  <title>What Our Customers Say | Yorvis</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: { yorvis: '#6f00ff', ink: '#131628', mist: '#f4f3f7' },
          fontFamily: { sans: ['Poppins', 'sans-serif'] }
        }
      }
    };
  </script>
  <link rel="stylesheet" href="styles.css?v=<?= e($stylesVersion) ?>">
</head>
<body class="bg-white text-ink antialiased">
  <header class="hero-section" id="top">
    <nav class="site-nav" aria-label="Primary navigation">
      <a class="logo-crop" href="#top" aria-label="Yorvis home">
        <span class="sr-only">Yorvis — Think Beyond</span>
      </a>
      <a class="nav-chip" href="#customer-stories">Customer stories <i class="bi bi-arrow-down" aria-hidden="true"></i></a>
    </nav>

    <div class="hero-overlay" aria-hidden="true"></div>
    <div class="hero-glow" aria-hidden="true"></div>

    <div class="hero-content">
      <p class="hero-kicker"><span></span> Real people. Real experiences.</p>
      <h1>WHAT OUR<br>CUSTOMERS<br><em>SAY</em></h1>
      <a class="hero-cta" href="#customer-stories">
        Explore their stories
        <span><i class="bi bi-arrow-down" aria-hidden="true"></i></span>
      </a>
    </div>

    <p class="hero-side-note" aria-hidden="true">YORVIS / CUSTOMER STORIES / 2026</p>
  </header>

  <main>
    <section class="stories-section" id="customer-stories" aria-labelledby="stories-heading">
      <div class="section-intro">
        <div>
          <p class="section-index">01 — CUSTOMER VOICES</p>
          <h2 id="stories-heading">Stories that<br><span>move us forward.</span></h2>
        </div>
        <p class="section-copy">Every experience shapes what we build next. Here are honest words from the people who chose to think beyond with Yorvis.</p>
      </div>

      <?php if ($reviews !== []): ?>
        <div class="reviews-grid" aria-live="polite">
          <?php foreach ($reviews as $index => $review): ?>
            <article class="review-card reveal-card <?= $index % 3 === 1 ? 'review-card--purple' : ($index % 3 === 2 ? 'review-card--dark' : '') ?>">
              <div class="quote-mark" aria-hidden="true">“</div>
              <blockquote><?= nl2br(e((string) $review['review'])) ?></blockquote>
              <footer>
                <span class="customer-line" aria-hidden="true"></span>
                <cite><?= e((string) $review['name']) ?></cite>
                <span class="verified-label"><i class="bi bi-patch-check-fill" aria-hidden="true"></i> Customer</span>
              </footer>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-stories reveal-card">
          <span class="quote-mark" aria-hidden="true">“</span>
          <p>Customer stories are being added. Check back soon to hear what people have to say.</p>
        </div>
      <?php endif; ?>
    </section>

    <section class="closing-banner" aria-labelledby="closing-title">
      <div class="closing-orbit closing-orbit--one" aria-hidden="true"></div>
      <div class="closing-orbit closing-orbit--two" aria-hidden="true"></div>
      <div class="closing-inner">
        <div class="closing-kicker">
          <span>02 — OUR MINDSET</span>
          <span>YORVIS / 2026</span>
        </div>
        <p>YOUR EXPERIENCE MATTERS.</p>
        <h2 id="closing-title">
          <span>THINK BEYOND.</span>
          <span>BUILD BETTER.</span>
        </h2>
        <div class="closing-signoff">
          <span class="closing-line" aria-hidden="true"></span>
          <span>Different thinking creates different results.</span>
        </div>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="footer-brand">
      <a class="logo-crop logo-crop--footer" href="#top" aria-label="Yorvis home"></a>
      <p>Different thinking creates<br>different results.</p>
    </div>

    <div class="footer-socials" aria-label="Yorvis social links">
      <p>FOLLOW THE JOURNEY</p>
      <div class="social-row">
        <?= social_item((string) $config['instagram_url'], 'Instagram', 'bi-instagram') ?>
        <?= social_item((string) $config['facebook_url'], 'Facebook', 'bi-facebook') ?>
      </div>
    </div>

    <div class="footer-whatsapp">
      <p>LET'S TALK</p>
      <?php if ($config['whatsapp_url'] !== ''): ?>
        <a class="whatsapp-link" href="<?= e((string) $config['whatsapp_url']) ?>" target="_blank" rel="noopener noreferrer">
          <i class="bi bi-whatsapp" aria-hidden="true"></i>
          <span>WhatsApp us</span>
          <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
        </a>
      <?php else: ?>
        <span class="whatsapp-link whatsapp-link--pending" aria-label="WhatsApp link coming soon">
          <i class="bi bi-whatsapp" aria-hidden="true"></i>
          <span>WhatsApp us</span>
          <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
        </span>
      <?php endif; ?>
    </div>

    <div class="footer-bottom">
      <span>© <?= date('Y') ?> YORVIS. ALL RIGHTS RESERVED.</span>
      <span>INNOVATE BEYOND<sup>™</sup></span>
    </div>
  </footer>

  <script src="script.js?v=<?= e($scriptVersion) ?>"></script>
</body>
</html>
