<div>
<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/shared_functions.php';

$mission_id = (int)$_GET['mission_id'];

$mission = new \dbObject\Mission();
$m = false;
$quizCount = 0;

if ($mission->load($mission_id)) {
    $m = [
        'title' => (string)$mission->get('title'),
        'resume' => (string)$mission->get('resume'),
        'html' => (string)$mission->get('html'),
        'video' => (string)$mission->get('video'),
    ];
    $quizCount = $mission->getQuizCount();
}

function vimeoEmbedUrl($url) {
    $url = trim((string)$url);

    if ($url === '') {
        return null;
    }

    if (preg_match('#videos/(\d+)/([a-zA-Z0-9]+)#', $url, $matches)) {
        $videoId = $matches[1];
        $hash = $matches[2];

        return "https://player.vimeo.com/video/$videoId?h=$hash";
    }

    if (preg_match('#vimeo\.com/(?:video/)?(\d+)(?:$|[?/])#', $url, $matches)) {
        $videoId = $matches[1];

        return "https://player.vimeo.com/video/$videoId";
    }

    return null;
}


if ($m) {
    $embedVideoUrl = vimeoEmbedUrl($m['video']);

    echo "<h2>" . htmlspecialchars($m['title']) . "</h2>";
    echo "<p><em>" . htmlspecialchars($m['resume']) . "</em></p>";
    
    echo "<div>";
?>
    <style>
      button:disabled {
          background: #ccc;
          cursor: not-allowed;
          opacity: 0.7;
      }
    </style>
<?
    if ($embedVideoUrl) {
?>

    <style>


        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }

        * {
  box-sizing: border-box;
}

.video-portal {
    position: relative;
    max-width: 800px;
    font-family: sans-serif;
    overflow: hidden;
    margin: auto;
    max-height: 50dvh;
    aspect-ratio: 1.8;
}

.video-inner {
  padding-top: 56.25%;
  position: relative;
}

.video-inner iframe {
  position: absolute;
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
}

/* Overlay branding */
.branding-overlay {
  position: absolute;
  inset: 0;
  pointer-events: none;
  background: url("/lms/branding-client.png") center/cover no-repeat;
}

/* Custom controls */
.video-portal {
  position: relative;
  max-width: 960px;
  font-family: sans-serif;
  overflow: hidden;
}

.custom-controls {
  position: absolute;
  left: 0;
  bottom: 0;
  width: 100%;
  padding: 12px;

  background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);

  display: flex;
  align-items: center;
  gap: 10px;

  opacity: 0;
  transform: translateY(10px);
  transition: opacity 0.25s ease, transform 0.25s ease;
}

.video-portal:hover .custom-controls {
  opacity: 1;
  transform: translateY(0);
}

.custom-controls button {
  border: none;
  padding: 5px 10px;
  cursor: pointer;
}

.progressvideo {
  flex: 1;
  height: 6px;
  background: #444;
  cursor: pointer;
  position: relative;
}

.progressvideo-bar {
  height: 100%;
  width: 0%;
  background: #fff;
}
    </style>
    

  <div class="video-portal">

  <div class="video-inner">
    <iframe 
      id="vimeoPlayer"
      src="<?php echo htmlspecialchars($embedVideoUrl . (strpos($embedVideoUrl, '?') === false ? '?' : '&') . 'controls=0', ENT_QUOTES, 'UTF-8'); ?>"
      frameborder="0"
      allow="autoplay; fullscreen; picture-in-picture"
      allowfullscreen>
    </iframe>
  </div>

  <div class="branding-overlay"></div>

  <div class="custom-controls">
    <button id="playBtn">Lire</button>
    <div class="progressvideo">
      <div class="progressvideo-bar"></div>
    </div>
    <span id="time">0:00</span>
  </div>
</div>
</div>

<?
    } elseif ($m['video']) {
?>
    <p>La video de cette mission n'est pas disponible pour le moment.</p>
<?
    }
    echo $m['html'];
    echo "</div>";
    echo "<div data-quiz-count='$quizCount' id='quiz-info'></div>";
} else {
    echo "Mission introuvable";
}
?>
<script>
    if (typeof initVideoPlayer === 'function') {
        initVideoPlayer();
    }
 


currentQuestions = [];
currentIndex = 0;
currentMission = null;

quizMode = false;

function initMissionUI() {

    const quizZone = document.getElementById('quiz-zone');
    const doneBtn = document.getElementById('doneBtn');

    const quizCount = parseInt(document.getElementById('quiz-info').dataset.quizCount || 0);

    quizMode = false;

    if (quizCount > 0) {

        quizZone.innerHTML = `
            <div class="quiz-info">
                Cette mission sera validee par ${quizCount} question${quizCount > 1 ? 's' : ''}
            </div>
        `;

        doneBtn.textContent = "Commencer le quiz";

    } else {

        quizZone.innerHTML = '';
        doneBtn.textContent = "Marquer comme lu";
    }
}

function startValidation(missionId) {
    currentMission = missionId;

    fetch(`getMissionQuestions.php?mission_id=${missionId}`)
        .then(res => res.json())
        .then(data => {

            if (!data || data.length === 0) {
                completeMission();
                return;
            }

            currentQuestions = data;
            currentIndex = 0;

            showQuestion();
        });
}

function showQuestion() {

    let q = currentQuestions[currentIndex];

    let html = `
        <div class="quiz">
            <strong>Question ${currentIndex + 1}/${currentQuestions.length}</strong>
            <p>${q.question}</p>
    `;

    if (q.multiple) {
        html += `<small>Plusieurs reponses possibles</small>`;
    }

    q.choices.forEach(c => {

        html += `
            <label>
                <input type="${q.multiple ? 'checkbox' : 'radio'}" name="qcm" value="${c.id}">
                ${c.label}
            </label><br>
        `;
    });

    html += `</div>`;

    document.getElementById('quiz-zone').innerHTML = html;

    const doneBtn = document.getElementById('doneBtn');
    doneBtn.disabled = true;

    setTimeout(() => {
        document.querySelectorAll('input[name="qcm"]').forEach(i => {
            i.addEventListener('change', () => {
                document.getElementById('doneBtn').disabled = false;
            });
        });
    }, 0);

    if (currentIndex === currentQuestions.length - 1) {
        doneBtn.textContent = "Terminer";
    } else {
        doneBtn.textContent = "Valider la reponse";
    }
}

function submitAnswer() {

    let inputs = document.querySelectorAll('input[name="qcm"]:checked');
    let selected = Array.from(inputs).map(i => i.value);

    if (selected.length === 0) {
        alert("Veuillez selectionner une reponse");
        return;
    }

    fetch('checkAnswer.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ choices: selected })
    })
    .then(res => res.json())
    .then(data => {

        if (data.correct) {

            currentIndex++;

            if (currentIndex >= currentQuestions.length) {
                completeMission();
            } else {
                showQuestion();
            }

        } else {
            alert("Mauvaise reponse");
        }
    });
}

function answer(choiceId) {

    fetch('checkAnswer.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'choice_id=' + choiceId
    })
    .then(res => res.json())
    .then(data => {

        if (data.correct) {

            currentIndex++;

            if (currentIndex >= currentQuestions.length) {
                completeMission();
            } else {
                showQuestion();
            }

        } else {
            alert("Mauvaise reponse");
        }
    });
}

function completeMission() {
    const missionId = currentMission || currentMissionId;

    if (!missionId) {
        alert("Mission introuvable");
        return;
    }

    fetch('action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `mission_id=${missionId}&parcours_id=${parcoursId}`
    })
    .then(() => {
        document.getElementById('quiz-zone').innerHTML = '';
        closeDrawer();
        loadMissions();
    });
}

document.getElementById('doneBtn').onclick = () => {

    if (!currentMissionId) return;

    const quizZone = document.getElementById('quiz-zone');
    const quizCount = parseInt(document.getElementById('quiz-info').dataset.quizCount || 0);

    if (quizCount === 0) {
        completeMission();
        return;
    }

    if (!quizMode) {
        quizMode = true;
        startValidation(currentMissionId);
        return;
    }

    submitAnswer();
};

   
</script>
