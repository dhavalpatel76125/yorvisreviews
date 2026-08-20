<?php
declare(strict_types=1);

header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet', true);
header('Cache-Control: no-store, private');

$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
$sessionDirectory = __DIR__ . '/../storage/sessions';
if (!is_dir($sessionDirectory)) {
    mkdir($sessionDirectory, 0700, true);
}
session_save_path($sessionDirectory);
session_name('yorvis_review_manager');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

require_once __DIR__ . '/../includes/reviews.php';
$config = require __DIR__ . '/../includes/config.php';

const SESSION_TTL = 7200;
const MAX_LOGIN_ATTEMPTS = 5;
const LOGIN_LOCK_SECONDS = 60;

function admin_e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect_to_admin(): never
{
    $path = strtok($_SERVER['REQUEST_URI'] ?? '/manage-yorvis-reviews-7f3c9b/', '?');
    header('Location: ' . ($path ?: '/manage-yorvis-reviews-7f3c9b/'), true, 303);
    exit;
}

if (isset($_SESSION['authenticated_at']) && time() - (int) $_SESSION['authenticated_at'] > SESSION_TTL) {
    $_SESSION = [];
    session_regenerate_id(true);
}

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $lockedUntil = (int) ($_SESSION['locked_until'] ?? 0);

    if ($lockedUntil > time()) {
        $message = 'Too many attempts. Please wait ' . ($lockedUntil - time()) . ' seconds.';
        $messageType = 'error';
    } elseif (password_verify((string) ($_POST['password'] ?? ''), (string) $config['admin_password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['authenticated_at'] = time();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['login_attempts'] = 0;
        unset($_SESSION['locked_until']);
        redirect_to_admin();
    } else {
        $_SESSION['login_attempts'] = (int) ($_SESSION['login_attempts'] ?? 0) + 1;
        if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
            $_SESSION['locked_until'] = time() + LOGIN_LOCK_SECONDS;
            $_SESSION['login_attempts'] = 0;
        }
        $message = 'That password is not correct.';
        $messageType = 'error';
    }
}

if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    redirect_to_admin();
}

$authenticated = isset($_SESSION['authenticated_at']);

if ($authenticated && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== 'login') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
        http_response_code(403);
        $message = 'Your session check failed. Refresh the page and try again.';
        $messageType = 'error';
    } else {
        $reviews = load_reviews();
        $action = (string) ($_POST['action'] ?? '');
        $name = trim((string) ($_POST['name'] ?? ''));
        $reviewText = trim((string) ($_POST['review'] ?? ''));
        $reviewId = preg_replace('/[^a-f0-9]/', '', (string) ($_POST['id'] ?? ''));

        if ($action === 'add') {
            if ($name === '' || $reviewText === '') {
                $message = 'Customer name and review are both required.';
                $messageType = 'error';
            } elseif (mb_strlen($name) > 100 || mb_strlen($reviewText) > 1500) {
                $message = 'Please keep names under 100 characters and reviews under 1,500 characters.';
                $messageType = 'error';
            } else {
                array_unshift($reviews, [
                    'id' => bin2hex(random_bytes(8)),
                    'name' => $name,
                    'review' => $reviewText,
                    'created_at' => gmdate(DATE_ATOM),
                ]);
                $message = save_reviews($reviews) ? 'Review added successfully.' : 'The review could not be saved. Check storage permissions.';
                $messageType = str_contains($message, 'successfully') ? 'success' : 'error';
            }
        } elseif ($action === 'update') {
            if ($name === '' || $reviewText === '') {
                $message = 'Customer name and review are both required.';
                $messageType = 'error';
            } else {
                $found = false;
                foreach ($reviews as &$review) {
                    if (hash_equals((string) $review['id'], (string) $reviewId)) {
                        $review['name'] = mb_substr($name, 0, 100);
                        $review['review'] = mb_substr($reviewText, 0, 1500);
                        $review['updated_at'] = gmdate(DATE_ATOM);
                        $found = true;
                        break;
                    }
                }
                unset($review);
                $message = $found && save_reviews($reviews) ? 'Review updated.' : 'That review could not be updated.';
                $messageType = str_contains($message, 'updated.') ? 'success' : 'error';
            }
        } elseif ($action === 'delete') {
            $before = count($reviews);
            $reviews = array_values(array_filter($reviews, static fn(array $review): bool => !hash_equals((string) $review['id'], (string) $reviewId)));
            $message = count($reviews) < $before && save_reviews($reviews) ? 'Review deleted.' : 'That review could not be deleted.';
            $messageType = str_contains($message, 'deleted.') ? 'success' : 'error';
        }
    }
}

$reviews = $authenticated ? load_reviews() : [];
$csrfToken = (string) ($_SESSION['csrf_token'] ?? '');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
  <title>Yorvis Review Manager</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = { theme: { extend: { colors: { yorvis: '#6f00ff', ink: '#131628', mist: '#f4f3f7' }, fontFamily: { sans: ['Poppins', 'sans-serif'] } } } };
  </script>
</head>
<body class="min-h-screen bg-mist font-sans text-ink antialiased">
<?php if (!$authenticated): ?>
  <main class="min-h-screen grid place-items-center p-5">
    <section class="w-full max-w-md rounded-3xl bg-white p-8 md:p-10 shadow-[0_30px_100px_rgba(19,22,40,.14)]" aria-labelledby="login-title">
      <div class="mb-8 flex h-14 w-14 items-center justify-center rounded-2xl bg-yorvis text-2xl text-white"><i class="bi bi-shield-lock-fill" aria-hidden="true"></i></div>
      <p class="mb-3 text-xs font-bold tracking-[.18em] text-yorvis">PRIVATE ACCESS</p>
      <h1 id="login-title" class="text-3xl font-extrabold tracking-[-.05em]">Review Manager</h1>
      <p class="mt-3 text-sm leading-7 text-slate-500">Enter your private password to manage Yorvis customer stories.</p>
      <?php if ($message !== ''): ?>
        <p class="mt-6 rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-700" role="alert"><?= admin_e($message) ?></p>
      <?php endif; ?>
      <form class="mt-8 space-y-5" method="post">
        <input type="hidden" name="action" value="login">
        <div>
          <label class="mb-2 block text-xs font-bold uppercase tracking-wider" for="password">Access password</label>
          <input class="w-full rounded-xl border border-slate-200 px-4 py-3.5 outline-none transition focus:border-yorvis focus:ring-4 focus:ring-violet-100" id="password" name="password" type="password" autocomplete="current-password" required autofocus>
        </div>
        <button class="w-full rounded-full bg-yorvis px-6 py-3.5 font-bold text-white transition hover:-translate-y-0.5 hover:bg-violet-800" type="submit">Open manager <i class="bi bi-arrow-right ml-2" aria-hidden="true"></i></button>
      </form>
    </section>
  </main>
<?php else: ?>
  <header class="border-b border-slate-200 bg-white">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-5 px-5 py-5 md:px-8">
      <div>
        <p class="text-[10px] font-bold tracking-[.2em] text-yorvis">YORVIS PRIVATE AREA</p>
        <h1 class="mt-1 text-xl font-extrabold tracking-[-.04em]">Review Manager</h1>
      </div>
      <div class="flex items-center gap-3">
        <a class="rounded-full border border-slate-200 px-4 py-2 text-xs font-bold hover:border-yorvis hover:text-yorvis" href="../" target="_blank" rel="noopener">View website <i class="bi bi-arrow-up-right ml-1" aria-hidden="true"></i></a>
        <a class="grid h-10 w-10 place-items-center rounded-full bg-ink text-white" href="?logout=1" aria-label="Log out" title="Log out"><i class="bi bi-box-arrow-right" aria-hidden="true"></i></a>
      </div>
    </div>
  </header>

  <main class="mx-auto max-w-7xl px-5 py-8 md:px-8 md:py-12">
    <?php if ($message !== ''): ?>
      <div class="mb-7 rounded-2xl <?= $messageType === 'error' ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800' ?> px-5 py-4 text-sm font-semibold" role="status"><?= admin_e($message) ?></div>
    <?php endif; ?>

    <section class="grid gap-8 lg:grid-cols-[390px_1fr] lg:items-start">
      <aside class="rounded-3xl bg-ink p-7 text-white shadow-xl lg:sticky lg:top-8">
        <p class="text-xs font-bold tracking-[.18em] text-violet-300">ADD A STORY</p>
        <h2 class="mt-3 text-3xl font-extrabold tracking-[-.05em]">New review</h2>
        <form class="mt-7 space-y-5" method="post">
          <input type="hidden" name="csrf_token" value="<?= admin_e($csrfToken) ?>">
          <input type="hidden" name="action" value="add">
          <div>
            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-300" for="new-name">Customer name</label>
            <input class="w-full rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-white outline-none placeholder:text-slate-500 focus:border-violet-400" id="new-name" name="name" type="text" maxlength="100" required>
          </div>
          <div>
            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-300" for="new-review">Review</label>
            <textarea class="min-h-44 w-full resize-y rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-white outline-none placeholder:text-slate-500 focus:border-violet-400" id="new-review" name="review" maxlength="1500" required></textarea>
          </div>
          <button class="w-full rounded-full bg-yorvis px-5 py-3.5 font-bold text-white transition hover:-translate-y-0.5 hover:bg-violet-700" type="submit"><i class="bi bi-plus-lg mr-2" aria-hidden="true"></i>Add review</button>
        </form>
      </aside>

      <div>
        <div class="mb-5 flex items-end justify-between gap-4">
          <div>
            <p class="text-xs font-bold tracking-[.18em] text-yorvis">PUBLISHED STORIES</p>
            <h2 class="mt-2 text-3xl font-extrabold tracking-[-.05em]"><?= count($reviews) ?> review<?= count($reviews) === 1 ? '' : 's' ?></h2>
          </div>
        </div>

        <?php if ($reviews === []): ?>
          <div class="rounded-3xl border-2 border-dashed border-slate-300 bg-white p-10 text-center">
            <div class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-violet-100 text-xl text-yorvis"><i class="bi bi-chat-square-quote" aria-hidden="true"></i></div>
            <h3 class="mt-5 text-xl font-extrabold">No reviews yet</h3>
            <p class="mt-2 text-sm text-slate-500">Add your first customer story using the form.</p>
          </div>
        <?php else: ?>
          <div class="space-y-5">
            <?php foreach ($reviews as $review): ?>
              <article class="rounded-3xl bg-white p-6 shadow-[0_15px_50px_rgba(19,22,40,.07)]">
                <form class="space-y-4" method="post">
                  <input type="hidden" name="csrf_token" value="<?= admin_e($csrfToken) ?>">
                  <input type="hidden" name="id" value="<?= admin_e((string) $review['id']) ?>">
                  <div class="grid gap-4 md:grid-cols-[220px_1fr]">
                    <div>
                      <label class="mb-2 block text-[10px] font-bold uppercase tracking-[.14em] text-slate-400">Customer name</label>
                      <input class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold outline-none focus:border-yorvis" name="name" value="<?= admin_e((string) $review['name']) ?>" maxlength="100" required>
                    </div>
                    <div>
                      <label class="mb-2 block text-[10px] font-bold uppercase tracking-[.14em] text-slate-400">Review</label>
                      <textarea class="min-h-28 w-full resize-y rounded-xl border border-slate-200 px-4 py-3 text-sm leading-6 outline-none focus:border-yorvis" name="review" maxlength="1500" required><?= admin_e((string) $review['review']) ?></textarea>
                    </div>
                  </div>
                  <div class="flex flex-wrap justify-end gap-3 border-t border-slate-100 pt-4">
                    <button class="rounded-full border border-slate-200 px-5 py-2.5 text-xs font-bold text-red-600 transition hover:border-red-600 hover:bg-red-50" name="action" value="delete" type="submit" data-delete-review><i class="bi bi-trash3 mr-1" aria-hidden="true"></i>Delete</button>
                    <button class="rounded-full bg-yorvis px-5 py-2.5 text-xs font-bold text-white transition hover:bg-violet-800" name="action" value="update" type="submit"><i class="bi bi-check2 mr-1" aria-hidden="true"></i>Save changes</button>
                  </div>
                </form>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <script>
    document.querySelectorAll('[data-delete-review]').forEach((button) => {
      button.addEventListener('click', (event) => {
        if (!window.confirm('Delete this review permanently?')) event.preventDefault();
      });
    });
  </script>
<?php endif; ?>
</body>
</html>
