<style>
/* Overlay */
#overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.4);
    display: none;
    z-index: 1000;
}

/* Drawer */
#drawer {
    position: fixed;
    top: 0;
    right: -1000px;
    width: 1000px;
    max-width: 100dvw;
    height: 100dvh;
    background: var(--bg-card);
    box-shadow: -3px 0 10px rgba(0,0,0,0.2);
    transition: right 0.3s ease;
    z-index: 1001;

    display: flex;
    flex-direction: column;
}

#drawer.open {
    right: 0;
}

#drawer-content {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
}

#drawer-footer {
    padding: 15px;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
}
#quiz-zone {
    padding: 15px;
    border-top: 1px solid #ddd;
    background: #fafafa;
}
</style>
<div id="overlay" onclick="closeDrawer()"></div>

<div id="drawer">
    <div id="drawer-content"></div>
    <div id="quiz-zone"></div>
    <div id="drawer-footer">
        <button onclick="closeDrawer()">Close</button>
        <button id="doneBtn">Done</button>
    </div>
</div>
<script>
function closeDrawer() {
    document.getElementById('drawer').classList.remove('open');
    document.getElementById('overlay').style.display = 'none';
}
function openDrawer(content) {
    const container = document.getElementById('drawer-content');
    container.innerHTML = content;

    container.querySelectorAll('script').forEach(s => {
        const script = document.createElement('script');
        script.textContent = s.textContent;
        [...s.attributes].forEach(attr => script.setAttribute(attr.name, attr.value));
        s.replaceWith(script);
    });
    document.getElementById('overlay').style.display = 'block';
    document.getElementById('drawer').classList.add('open');
}
</script>