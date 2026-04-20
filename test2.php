<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Profil Projet & Relation</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />  
  <style>
  .progress-item {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid #ccc;
    cursor: pointer;
  }

  .answered {
    background-color: #4CAF50;
    border-color: #4CAF50;
  }

  .current {
    border: 2px solid #000;
  }
  @keyframes flip-hourglass {
    0%   { transform: rotate(0deg); }
    10%  { transform: rotate(180deg); }
    50%  { transform: rotate(180deg); }
    60%  { transform: rotate(360deg); }
    100% { transform: rotate(360deg); }
  }

  .flip {
    display: inline-block;
    animation: flip-hourglass 10s infinite ease-in-out;
    transition: transform 0.5s;
  }
</style>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center text-lg sm:text-base">
<div id="resultModal" class="fixed inset-0 bg-black/50 flex items-center justify-center hidden z-50">
  <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full p-6 overflow-auto max-h-[80vh]">
    <h2 class="text-xl font-bold mb-4">Résultat</h2>
    <div id="modalContent" class="text-sm"></div>
    <button onclick="closeModal()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
      Fermer
    </button>
  </div>
</div>
 
  <div class="px-4 md:px-8 lg:px-16">
  <h1 class="text-3xl md:text-4xl font-bold mb-6 text-center text-gray-800">🧭 Ton profil Projet & Relation</h1>
  <div id="progress-bar" class="flex gap-2 flex-wrap justify-center my-6 px-4"></div>

  <div id="question-container" class="bg-white shadow rounded-xl p-6">
    <div id="question-text" class="text-xl font-semibold mb-1"></div>
    <div id="question-description" class="text-sm text-gray-500 mb-2"></div>
    <div class="flex justify-between text-sm mb-2 px-1">
  <span>Pas du tout moi</span>
  <span>Complètement moi</span>
</div>
    <div id="options" class="flex justify-center gap-1"></div>

    <div class="flex justify-between items-center mt-6">
      <button onclick="goBack()" class="text-sm text-gray-500 hover:underline">⬅ Précédent</button>
      <button id="submit-button" class="hidden bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
        ✅ Valider mes réponses
      </button>
    </div>
  </div>
</div>

  <script>
	  
	  function showModal(content) {
  document.getElementById("modalContent").innerHTML = content;
  document.getElementById("resultModal").classList.remove("hidden");
}

function closeModal() {
  document.getElementById("resultModal").classList.add("hidden");
}

const questions = [
  {
    id: "1",
    text: "J’aime inspirer les autres avec une vision enthousiasmante.",
    espace: "Sens",
    disc: "I",
    description: "Tu aimes partager des idées motivantes qui donnent envie d’avancer ensemble vers un but commun."
  },
  {
    id: "2",
    text: "Je cherche à définir une mission claire et percutante.",
    espace: "Sens",
    disc: "D",
    description: "Tu veux que le projet ait une direction claire et affirmée, pour que chacun sache où aller."
  },
  {
    id: "3",
    text: "J’ai besoin que notre mission repose sur des faits solides.",
    espace: "Sens",
    disc: "C",
    description: "Tu es plus à l’aise si la mission est fondée sur des données concrètes, pas seulement sur l’intuition."
  },
  {
    id: "4",
    text: "Je m’investis quand je sens que la cause est humaine et utile.",
    espace: "Sens",
    disc: "S",
    description: "Tu cherches du sens humain dans ce que tu fais, et t’impliques si tu perçois un impact positif pour les autres."
  },

  {
    id: "5",
    text: "J’ai souvent des idées nouvelles ou créatives à proposer.",
    espace: "Rêve",
    disc: "I",
    description: "Tu es une source d’inspiration, avec des idées originales qui peuvent faire évoluer un projet."
  },
  {
    id: "6",
    text: "Je définis facilement des priorités stratégiques.",
    espace: "Rêve",
    disc: "D",
    description: "Tu sais transformer une vision globale en étapes logiques pour avancer efficacement."
  },
  {
    id: "7",
    text: "Je préfère des plans réalistes ancrés dans les contraintes du terrain.",
    espace: "Rêve",
    disc: "C",
    description: "Tu es pragmatique et préfères des idées concrètes plutôt que des visions idéales déconnectées du réel."
  },
  {
    id: "8",
    text: "J’anticipe les besoins pour construire une vision partagée.",
    espace: "Rêve",
    disc: "S",
    description: "Tu captes en amont les attentes implicites pour créer une vision commune qui rassemble."
  },

  {
    id: "9",
    text: "Je peux rendre un plan vivant si j’y vois du sens ou du fun.",
    espace: "Plan",
    disc: "I",
    description: "Tu rends les choses dynamiques et motivantes quand tu comprends leur utilité ou qu’elles te plaisent."
  },
  {
    id: "10",
    text: "J’organise les étapes pour atteindre les objectifs rapidement.",
    espace: "Plan",
    disc: "D",
    description: "Tu as une capacité à découper les tâches et planifier efficacement pour atteindre des résultats concrets."
  },
  {
    id: "11",
    text: "J’aime structurer les choses avec méthode et précision.",
    espace: "Plan",
    disc: "C",
    description: "Tu apportes de la rigueur et de la clarté dans l’organisation du travail, étape par étape."
  },
  {
    id: "12",
    text: "Je planifie pour garantir la stabilité et rassurer le groupe.",
    espace: "Plan",
    disc: "S",
    description: "Tu organises les choses pour que chacun se sente sécurisé et sache à quoi s’attendre."
  },

  {
    id: "13",
    text: "Je mobilise naturellement les autres dans l’action.",
    espace: "Action",
    disc: "I",
    description: "Tu entraînes les gens avec énergie et motivation à passer à l’action ensemble."
  },
  {
    id: "14",
    text: "Je passe rapidement à l’action et je pousse le groupe à avancer.",
    espace: "Action",
    disc: "D",
    description: "Tu es moteur dans le passage à l’action, et tu n’hésites pas à bousculer les choses pour avancer."
  },
  {
    id: "15",
    text: "J’agis si j’ai un cadre clair et des étapes définies.",
    espace: "Action",
    disc: "C",
    description: "Tu préfères intervenir dans un cadre structuré, avec une organisation rassurante."
  },
  {
    id: "16",
    text: "Je fais ma part avec constance, en soutenant les autres.",
    espace: "Action",
    disc: "S",
    description: "Tu es fiable, régulier, et tu participes avec engagement en veillant à l’équilibre collectif."
  },

  {
    id: "17",
    text: "J’aime créer des moments de reconnaissance collectifs.",
    espace: "Célébration",
    disc: "I",
    description: "Tu as le réflexe de valoriser ce qui a été accompli, pour que chacun se sente reconnu."
  },
  {
    id: "18",
    text: "J’intègre les feedbacks pour progresser rapidement.",
    espace: "Célébration",
    disc: "D",
    description: "Tu utilises les retours pour t’ajuster et faire avancer les choses sans perdre de temps."
  },
  {
    id: "19",
    text: "Je repère facilement ce qu’on pourrait améliorer.",
    espace: "Célébration",
    disc: "C",
    description: "Tu observes avec finesse les axes d’amélioration, pour renforcer la qualité ou l’efficacité."
  },
  {
    id: "20",
    text: "Je suis attentif à l’impact émotionnel des retours sur chacun.",
    espace: "Célébration",
    disc: "S",
    description: "Tu prends soin de la manière dont les feedbacks sont reçus, pour préserver la relation."
  },

  {
    id: "21",
    text: "J’aime créer une bonne ambiance et connecter les gens.",
    espace: "Relation",
    disc: "I",
    description: "Tu facilites les liens entre les personnes, en rendant les échanges chaleureux et conviviaux."
  },
  {
    id: "22",
    text: "Je suis direct, je dis les choses clairement, même si ça pique.",
    espace: "Relation",
    disc: "D",
    description: "Tu privilégies la franchise, même si cela peut être un peu brutal, pour éviter les non-dits."
  },
  {
    id: "23",
    text: "Je préfère les échanges cadrés et respectueux.",
    espace: "Relation",
    disc: "C",
    description: "Tu valorises des relations claires et équilibrées, avec des règles implicites ou explicites."
  },
  {
    id: "24",
    text: "Je veille à ce que chacun se sente bien dans le groupe.",
    espace: "Relation",
    disc: "S",
    description: "Tu es attentif au climat du groupe, et tu contribues à ce que tout le monde s’y sente bien intégré."
  }
];


    function shuffle(array) {
      for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]];
      }
    }

    shuffle(questions);

let currentQuestion = 0;
let lastAnsweredIndex = -1;
const responses = new Array(questions.length).fill(null);

function renderProgressBar() {
  const bar = document.getElementById("progress-bar");
  bar.innerHTML = "";

  questions.forEach((_, i) => {
    const isAnswered = responses[i] !== null;
    const isCurrent = i === currentQuestion;

    const span = document.createElement("span");
    span.className =
      "w-5 h-5 rounded-full cursor-pointer border-2 transition-all " +
      (isAnswered ? "bg-green-500 border-green-500" : "bg-white border-gray-300") +
      (isCurrent ? " ring-2 ring-black" : "");

    span.title = `Question ${i + 1}`;
    span.onclick = () => {
      currentQuestion = i;
      renderQuestion(currentQuestion);
    };
    bar.appendChild(span);
  });
}

function renderQuestion(index) {
  const q = questions[index];
  document.getElementById("question-text").innerText = `${index + 1}. ${q.text}`;
  document.getElementById("question-description").innerText = `${q.description}`;

  const optionsContainer = document.getElementById("options");
  optionsContainer.innerHTML = "";

  for (let i = 1; i <= 7; i++) {
    const wrapper = document.createElement("div");
    wrapper.className = "inline-block";

    const input = document.createElement("input");
    input.type = "radio";
    input.name = "response";
    input.id = `q${index}-opt${i}`;
    input.value = i;
    input.className = "hidden peer";
    if (responses[index] === i) input.checked = true;

    const label = document.createElement("label");
    label.htmlFor = input.id;
    label.innerText = i;
    label.className =
      "peer-checked:bg-blue-600 peer-checked:text-white text-lg sm:text-base " +
      "text-blue-600 border border-blue-600 px-3 py-2 m-1 rounded-full cursor-pointer transition";

    input.addEventListener("change", () => {
      responses[index] = i;
      if (currentQuestion >= lastAnsweredIndex) lastAnsweredIndex = currentQuestion + 1;
      updateSubmitButton();
      if (currentQuestion + 1 < questions.length) {
        currentQuestion++;
        renderQuestion(currentQuestion);
      } else {
		  renderProgressBar();
	  }
    });

    wrapper.appendChild(input);
    wrapper.appendChild(label);
    optionsContainer.appendChild(wrapper);
  }

  renderProgressBar();
}


  
  function updateDisc(D,I,S,C) {
	// Normalisation
  const maxScore = 36;
  const normalizedData = [D, I, S, C].map(v => (v-1) / maxScore * 5);

    const ctx = document.getElementById('discRadarChart').getContext('2d');

    // ✅ Valeurs d'exemple — à remplacer dynamiquement avec PHP/JS
    const discData = {
      labels: ['D – Directif', 'I – Spontané', 'S – Coopératif', 'C – Méthodique'],
      datasets: [{
        label: 'Votre profil DISC',
        data: normalizedData, 
        fill: true,
        backgroundColor: 'rgba(54, 162, 235, 0.2)',
        borderColor: 'rgba(54, 162, 235, 1)',
        pointBackgroundColor: 'rgba(54, 162, 235, 1)',
        pointBorderColor: '#fff',
        pointHoverBackgroundColor: '#fff',
        pointHoverBorderColor: 'rgba(54, 162, 235, 1)'
      }]
    };

    new Chart(ctx, {
      type: 'radar',
      data: discData,
      options: {
        responsive: true,
        scales: {
          r: {
            suggestedMin: 0,
            suggestedMax: 5,
            ticks: {
              stepSize: 1,
              backdropColor: 'transparent'
            },
            pointLabels: {
              font: {
                size: 14
              }
            }
          }
        },
        plugins: {
          legend: {
            display: false
          },
          title: {
            display: false
          }
        }
      }
    });
   }


function goBack() {
  if (currentQuestion > 0) {
    currentQuestion--;
    renderQuestion(currentQuestion);
  }
}

function updateSubmitButton() {
  const btn = document.getElementById("submit-button");
  const allAnswered = responses.every(r => r !== null);
  btn.style.display = allAnswered ? "inline-block" : "none";
  
}

document.getElementById("submit-button").onclick = () => {
  const data = questions.map((q, i) => ({
    id: q.id,
    question: q.text,
    espace: q.espace,
    disc: q.disc,
    response: responses[i]
  }));

  // 🔄 ENVOI À UNE API (exemple POST vers ton serveur ou une fonction backend)
  showModal("<span class='flip text-2xl'>⏳</span> Chargement du profil... Veuillez patienter, cela peut prendre quelques instants.");
  fetch("/test.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ responses: data })
  })
    .then(res => res.json())
    .then(result => {
		
function afficherProfilDepuisJSON(data) {
  const output = document.getElementById("profilOutput");
  console.log(data);
  monProfil = `
     <h1 class="text-2xl font-bold mb-4">🧭 Mon Profil Projet et Relationnel</h1>

    <div class="section space-y-2">
      <h2 class="text-xl font-semibold">Synthèse</h2>
      <p>${escapeHTML(data.synthese)}</p>
      <hr>
      <p>${escapeHTML(data.description)}</p>
    </div>
   <div class="section space-y-2">
      <h2 class="text-xl font-semibold">✅ Ce qui est facile pour moi</h2>
      <ul class="list-disc ml-5">
        ${data.ce_qui_est_facile.map(item => `<li>${escapeHTML(item)}</li>`).join('')}
      </ul>
    </div>

    <div class="section space-y-2">
      <h2 class="text-xl font-semibold">⚠️ Ce qui est plus difficile</h2>
      <ul class="list-disc ml-5">
        ${data.ce_qui_est_difficile.map(item => `<li>${escapeHTML(item)}</li>`).join('')}
      </ul>
    </div>

    <div class="section space-y-2">
      <h2 class="text-xl font-semibold">🔄 Style relationnel</h2>
        <canvas id="discRadarChart" width="300" height="300"></canvas>
      <ul class="list-disc ml-5">
        <li><span class="font-semibold">Dominant :</span> ${data.style_relationnel.dominant.join(', ')}</li>
        <li><span class="font-semibold">Teinte secondaire :</span> ${data.style_relationnel.teinte.join(', ')}</li>
        <li><span class="font-semibold">Moins présent :</span> ${data.style_relationnel.moins_present.join(', ')}</li>
      </ul>
    </div>

    <div class="section space-y-2">
      <h2 class="text-xl font-semibold">🎯 Recommandations collaboratives</h2>
      <ul class="list-disc ml-5">
        ${data.recommandations_collaboratives.map(item => `<li>${escapeHTML(item)}</li>`).join('')}
      </ul>
    </div>
 
  `;
  return monProfil;
}

// Fonction pour échapper le HTML injecté
function escapeHTML(str) {
  return str?.replace(/[&<>'"]/g, function (tag) {
    const chars = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      "'": '&#39;',
      '"': '&quot;'
    };
    return chars[tag] || tag;
  });
}
	  	
	  console.log(result);
	  formatedResult=afficherProfilDepuisJSON(result);
	  modalContent= formatedResult+"<input type='hidden' name='content' value='"+formatedResult+"'>Votre e-mail: <input type='text'><input type='button' value='Envoyer'>";
	  showModal(modalContent);
	  
	  updateDisc(result.total_disc.D,result.total_disc.I,result.total_disc.S,result.total_disc.C);
	  
	  
	  
      //alert("Analyse envoyée ! Résultat : " + JSON.stringify(result));
      

      
      
      
    })
    .catch(err => {
	  showModal(`❌ Erreur : ${err.message}`);
      console.error("Erreur d’envoi :", err);
      //alert("Une erreur est survenue.");
    });
};

// Initialisation
renderQuestion(currentQuestion);
</script>

</body>
</html>
