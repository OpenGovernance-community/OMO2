<?php
require_once dirname(__DIR__) . '/bootstrap.php';
usleep(1000000);
?>
<div class="kanban">

    <div class="kanban-column">
        <div class="kanban-header">Prêt</div>
        <div class="kanban-cards" data-status="ready"></div>
    </div>

    <div class="kanban-column">
        <div class="kanban-header">En cours</div>
        <div class="kanban-cards" data-status="progress"></div>
    </div>

    <div class="kanban-column">
        <div class="kanban-header">Bloqué</div>
        <div class="kanban-cards" data-status="blocked"></div>
    </div>

    <div class="kanban-column">
        <div class="kanban-header">À vérifier</div>
        <div class="kanban-cards" data-status="review"></div>
    </div>

    <div class="kanban-column">
        <div class="kanban-header">Terminé</div>
        <div class="kanban-cards" data-status="done"></div>
    </div>

    <div class="kanban-column">
        <div class="kanban-header">Un jour peut-être</div>
        <div class="kanban-cards" data-status="someday"></div>
    </div>

</div>
<style>
.kanban {
    display: flex;
    gap: 16px;
    padding: 16px;
    min-height: 100%;
    overflow-x: auto;
}

.kanban-column {
    min-width: 260px;
    background: var(--color-surface);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-sm);
    display: flex;
    flex-direction: column;
}

.kanban-header {
    position: sticky;
    top: 0;
    z-index: 2;

    background: var(--color-surface);
    border-bottom: 1px solid var(--color-border);

    padding: 12px;
    font-weight: 600;
}

.kanban-cards {
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    flex: 1;
}

/* CARD */
.kanban-card {
    background: var(--color-surface-alt);
    border-radius: var(--radius-sm);
    padding: 10px;
    box-shadow: var(--shadow-sm);
    cursor: grab;
    transition: transform 0.1s ease;
}

.kanban-card:hover {
    transform: scale(1.02);
}

/* CONTENU */
.card-title {
    font-weight: 600;
    margin-bottom: 6px;
}

.card-meta {
    font-size: 12px;
    color: var(--color-text-light);
}
.kanban-placeholder {
    height: 50px;
    background: rgba(79, 70, 229, 0.1);
    border: 2px dashed var(--color-primary);
    border-radius: var(--radius-sm);
}
</style>
<script>
    const projects = [
    {
        id: 1,
        title: "Refonte UI",
        owner: "Alice",
        deadline: "2026-05-01",
        status: "progress"
    },
    {
        id: 2,
        title: "API gouvernance",
        owner: "Bob",
        deadline: "2026-06-10",
        status: "ready"
    },
    {
        id: 3,
        title: "Module stats",
        owner: "Claire",
        deadline: "2026-05-20",
        status: "blocked"
    },
    {
        id: 4,
        title: "Module stats",
        owner: "Claire",
        deadline: "2026-05-20",
        status: "blocked"
    },
    {
        id: 5,
        title: "Module stats",
        owner: "Claire",
        deadline: "2026-05-20",
        status: "blocked"
    },
    {
        id: 6,
        title: "Module stats",
        owner: "Claire",
        deadline: "2026-05-20",
        status: "blocked"
    },
    {
        id: 7,
        title: "Module stats",
        owner: "Claire",
        deadline: "2026-05-20",
        status: "blocked"
    },
    {
        id: 8,
        title: "Module stats",
        owner: "Claire",
        deadline: "2026-05-20",
        status: "blocked"
    },
    {
        id: 9,
        title: "Module stats",
        owner: "Claire",
        deadline: "2026-05-20",
        status: "blocked"
    },
    {
        id: 10,
        title: "Module stats",
        owner: "Claire",
        deadline: "2026-05-20",
        status: "blocked"
    },
    {
        id: 11,
        title: "Module stats",
        owner: "Claire",
        deadline: "2026-05-20",
        status: "blocked"
    },
    {
        id: 12,
        title: "Module stats",
        owner: "Claire",
        deadline: "2026-05-20",
        status: "blocked"
    },
    {
        id: 13,
        title: "Module stats",
        owner: "Claire",
        deadline: "2026-05-20",
        status: "blocked"
    }
];
function renderKanban(data) {

    $('.kanban-cards').empty();

    data.forEach(project => {

        const card = `
            <div class="kanban-card" data-id="${project.id}">
                <div class="card-title">${project.title}</div>
                <div class="card-meta">👤 ${project.owner}</div>
                <div class="card-meta">📅 ${project.deadline}</div>
            </div>
        `;

        $(`.kanban-cards[data-status="${project.status}"]`).append(card);
    });
}
$(document).ready(function () {
    renderKanban(projects);
});

$(document).on('dragstart', '.kanban-card', function (e) {
    $(this).addClass('dragging');
    e.originalEvent.dataTransfer.setData("id", $(this).data('id'));
});

$(document).on('dragend', '.kanban-card', function () {
    $(this).removeClass('dragging');
    $('.kanban-placeholder').remove();
});

$(document).on('dragover', '.kanban-cards', function (e) {
    e.preventDefault();

    const container = this;

    // 🔥 supprimer tous les placeholders ailleurs
    $('.kanban-placeholder').not($(container).find('.kanban-placeholder')).remove();

    const afterElement = getDragAfterElement(container, e.clientY);

    let placeholder = container.querySelector('.kanban-placeholder');

    if (!placeholder) {
        placeholder = document.createElement('div');
        placeholder.classList.add('kanban-placeholder');
    }

    if (afterElement == null) {
        container.appendChild(placeholder);
    } else {
        container.insertBefore(placeholder, afterElement);
    }
});

$(document).on('drop', '.kanban-cards', function (e) {
    e.preventDefault();

    const id = e.originalEvent.dataTransfer.getData("id");
    const newStatus = $(this).data('status');

    const placeholder = this.querySelector('.kanban-placeholder');
    const dragged = $(`.kanban-card[data-id="${id}"]`);

    if (placeholder) {
        placeholder.replaceWith(dragged[0]);
    }

    // update data
    const project = projects.find(p => p.id == id);
    if (project) {
        project.status = newStatus;
    }

    updateOrder($(this));

});

function updateOrder(column) {

    column.find('.kanban-card').each(function (index) {

        const id = $(this).data('id');
        const project = projects.find(p => p.id == id);

        if (project) {
            project.order = index;
        }

    });

    console.log(projects);
}

function getDragAfterElement(container, y) {

    const elements = [...container.querySelectorAll('.kanban-card:not(.dragging)')];

    let closest = null;
    let closestOffset = Number.NEGATIVE_INFINITY;

    elements.forEach(child => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;

        if (offset < 0 && offset > closestOffset) {
            closestOffset = offset;
            closest = child;
        }
    });

    return closest;
}

$(document).on('mouseenter', '.kanban-card', function () {
    $(this).attr('draggable', true);
});
</script>
