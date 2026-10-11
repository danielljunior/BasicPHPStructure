<?php
function renderHeadSection(array $extraStyles = [])
{
  ob_start(); ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com" rel="preconnect" />
  <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&amp;family=Noto+Serif:ital,wght@0,400;0,700;1,400&amp;family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
  <link rel="shortcut icon" href="/favicon.png" type="image/png">
  <title>A Game for Idling</title>
  <link rel="stylesheet" href="/css/general.css" />
  <?php foreach ($extraStyles as $style): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($style) ?>" />
  <?php endforeach; ?>

  <?php if (filter_var(getenv('ENABLE_LIVE_RELOAD'), FILTER_VALIDATE_BOOLEAN)): ?>
    <script src="/js/hot-reload.js?v=<?= time() ?>"></script>
  <?php endif; ?>
  <?php if (\utils\user\userHasEditClearance()): ?>
    <!--<meta name="csrf-token" content="<?= htmlspecialchars(\utils\csrfToken()) ?>">
    <link rel="stylesheet" href="/editor.css" />
    <script src="/js/editor.js" defer></script>-->
  <?php endif; ?>
<?php return ob_get_clean();
}
