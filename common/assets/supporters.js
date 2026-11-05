document.querySelectorAll('.supporters-image img').forEach((image) => {
    const revealInitials = () => image.remove();
    image.addEventListener('error', revealInitials, {once: true});
    if (image.complete && image.naturalWidth === 0) revealInitials();
});
