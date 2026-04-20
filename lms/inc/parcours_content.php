<div class="content lms-parcours-content<?= $isEmbedded ? ' lms-parcours-content--embed' : '' ?>">
<?php if ($isEmbedded): ?>
    <div class="lms-parcours-embed-header">
        <h1><?php echo htmlspecialchars($parcours['title']); ?></h1>
        <?php if ($parcours['description'] !== ''): ?>
            <p><?php echo htmlspecialchars($parcours['description']); ?></p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="view-switch">
    <button onclick="setView('todo')" id="btnTodo" class='active'>Mes missions</button>
    <button onclick="setView('done')" id="btnDone">Terminees</button>
    <button onclick="setView('next')" id="btnNext">A venir</button>
</div>
<div class="progress-container">
    <div class="progress-bar" id="progressBar"></div>
</div>
<div id="missions" class="missions"></div>

</div>

<?php
include __DIR__ . '/video.php';
include __DIR__ . '/drawer.php';
?>

<script>
    let currentView = 'todo';
    const parcoursId = <?php echo $parcours_id; ?>;

    let branchState = {};

    function setView(view) {
        currentView = view;

        document.getElementById('btnTodo').classList.remove('active');
        document.getElementById('btnDone').classList.remove('active');
        document.getElementById('btnNext').classList.remove('active');

        if (view === 'todo') {
            document.getElementById('btnTodo').classList.add('active');
        } else if (view === 'done') {
            document.getElementById('btnDone').classList.add('active');
        } else if (view === 'next') {
            document.getElementById('btnNext').classList.add('active');
        }

        loadMissions();
    }

    function loadMissions() {
        let url = '/lms/getmissions.php';

        if (currentView === 'done') url = '/lms/getmissions_done.php';
        if (currentView === 'next') url = '/lms/getmissions_next.php';

        fetch(`${url}?parcours_id=${parcoursId}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('missions').innerHTML = data.html;

                if (data.progress !== undefined) {
                    document.getElementById('progressBar').style.width = data.progress + '%';
                }

                restoreBranches();
            });
    }

    function markDone(missionId) {
        fetch('action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `mission_id=${missionId}&parcours_id=${parcoursId}`
        })
        .then(() => loadMissions());
    }

    function toggleBranch(branchId) {
        const el = document.querySelector(`[data-branch-id="${branchId}"]`);

        if (!el) return;

        const isClosed = el.classList.toggle('closed');
        branchState[branchId] = isClosed;
    }

    function restoreBranches() {
        document.querySelectorAll('.branch').forEach(el => {
            const id = el.dataset.branchId;

            if (branchState[id]) {
                el.classList.add('closed');
            }
        });
    }

    loadMissions();
</script>

<script>
let currentMissionId = null;

function viewMission(missionId) {
    currentMissionId = missionId;

    fetch(`/lms/getMissionDetail.php?mission_id=${missionId}`)
        .then(res => res.text())
        .then(html => {
            openDrawer(html);
            initMissionUI();
        });
}
</script>
