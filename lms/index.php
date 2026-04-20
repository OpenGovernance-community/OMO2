<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/shared_functions.php';
require_once BASE_PATH . '/common/auth.php';

commonRestoreRememberedUser();
include 'inc/org.php';

if (!isset($_SESSION['currentUser'])) {
    commonRenderMagicLoginPage([
        'title' => $org['name'] . ' - LMS',
        'appName' => 'LMS',
        'intro' => 'Connectez-vous pour accéder à vos parcours de formation.',
        'returnTo' => '/lms/',
    ]);
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($org['name']); ?></title>
    <link rel="stylesheet" href="/lms/css/std.css">
    
    <style>
        h1 {
            text-align: center;
            margin-bottom: 30px;
        }

        .container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 350px));
            justify-content: center;
            gap: 20px;
        }

.card {
    border: 1px solid #ddd;
    border-radius: 10px;
    overflow: hidden; /* important */
    background: white;
    cursor: pointer;
    transition: 0.2s;
  display: flex;
  flex-direction: column;

}
    /* bloc du bas */
.card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-top:auto;
}

/* image 16:9 */
.card-image {
    width: 100%;
    aspect-ratio: 16/6;
    overflow: hidden;
}

.card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.card-content {
    padding: 15px;
    display: flex;
    flex-direction: column;
    flex: 1; /* IMPORTANT */
}

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }



.banner-bg {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    opacity: 0.4;
}

/* contenu au-dessus */
.banner-content {
    position: relative;
    z-index: 2;
}

.logo-wrapper {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: white;
    padding: 5px;
    margin: 0 auto 15px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.logo-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}


.login-box {
    margin: 100px auto;
    text-align: center;
}



.email-group {
    display: flex;
    max-width: 350px;
    margin: 0 auto 15px;
    border: 1px solid #ccc;
    border-radius: 8px;
    overflow: hidden;
    background: white;
}

/* champ texte */
.email-group input {
    flex: 1;
    border: none;
    padding: 12px;
    font-size: 16px;
    outline: none;
    text-align: right;
}

/* partie domaine */
.email-domain {
    background: #f0f0f0;
    padding: 12px;
    color: #555;
    font-size: 16px;
    border-left: 1px solid #ccc;
    display: flex;
    align-items: center;
    white-space: nowrap;
}


.progress-circle {
  position: relative;
  width: 60px;
  height: 60px;
  display: inline-block;
  margin: 5px;
}

.progress-circle svg {
  transform: rotate(-90deg);
}

circle {
  fill: none;
  stroke-width: 5;
}

.bg {
  stroke: #ddd;
}

.progress {
  stroke: #4caf50;
  stroke-linecap: round;
  transition: stroke-dashoffset 0.6s ease;
}

.label {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 18px;
  font-weight: bold;
}

    </style>
    
</head>
<body>
<?include 'inc/menu.php'; ?>
<div class="org-banner" style="background-color: <?php echo htmlspecialchars($org['color']); ?>">

    <?php if (!empty($org['banner'])): ?>
        <div class="banner-bg" style="background-image: url('<?php echo htmlspecialchars($org['banner']); ?>')"></div>
    <?php endif; ?>

    <div class="banner-content">

        <?php if (!empty($org['logo'])): ?>
            <div class="logo-wrapper">
                <img src="<?=$org['logo'] ?>" alt="logo">
            </div>
        <?php endif; ?>

        <h1><?php echo htmlspecialchars($org['name']); ?></h1>

    </div>
</div>
<?php

$user_id = (int)($_SESSION['currentUser'] ?? 0);
$parcours = \dbObject\Parcours::fetchForOrganizationWithProgress($org['id'], $user_id);

?>

<h1>Parcours de formation</h1>

<div class="container">
<?php foreach ($parcours as $p): 
    
    $total = $p['total_missions'];
$done = $p['done_missions'];

$percent = $total > 0 ? round(($done / $total) * 100) : 0;

if ($done == 0) {
    $status = "Pas commence";
} elseif ($done == $total) {
    $status = "Termine";
} else {
    $status = "En cours";
}

    ?>
<div class="card" onclick="goToParcours(<?php echo $p['id']; ?>)">

    <?php if (!empty($p['image'])): ?>
        <div class="card-image">
            <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="">
        </div>
    <?php endif; ?>

    <div class="card-content">
        <h3><?php echo htmlspecialchars($p['title']); ?></h3>
        <div><?php echo htmlspecialchars($p['description']); ?></div>

            <div class="card-footer">
        <div class="progress-circle" data-percent="<?php echo $percent; ?>"></div>
        

        <button class="open-btn">Ouvrir</button>
    </div>
     </div>


</div>
<?php endforeach; ?>
</div>

<script>
document.querySelectorAll('.progress-circle').forEach(el => {
  const percent = el.getAttribute('data-percent');

  const radius = 25;
  const circumference = 2 * Math.PI * radius;

  el.innerHTML = `
    <svg width="60" height="60">
      <circle class="bg" cx="30" cy="30" r="${radius}"></circle>
      <circle class="progress" cx="30" cy="30" r="${radius}"></circle>
    </svg>
    <div class="label">${percent}%</div>
  `;

  const progressCircle = el.querySelector('.progress');

  progressCircle.style.strokeDasharray = circumference;
  progressCircle.style.strokeDashoffset =
    circumference * (1 - percent / 100);
});

function goToParcours(id) {
    window.location.href = "/lms/parcours.php?idp=" + id;
}
</script>
<?  
    include 'inc/drawer.php';
?>
</body>
</html>
