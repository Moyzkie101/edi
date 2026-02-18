document.addEventListener('DOMContentLoaded', function() {
    const scrollContainer = document.getElementById('scroll-content');
    const scrollLeftBtn = document.getElementById('scroll-left');
    const scrollRightBtn = document.getElementById('scroll-right');

    scrollLeftBtn.addEventListener('click', function() {
        scrollContainer.scrollBy({
            left: -200, // Adjust the value based on the item width
            behavior: 'smooth'
        });
    });

    scrollRightBtn.addEventListener('click', function() {
        scrollContainer.scrollBy({
            left: 200, // Adjust the value based on the item width
            behavior: 'smooth'
        });
    });

    function checkScrollButtons() {
        scrollLeftBtn.disabled = scrollContainer.scrollLeft === 0;
        scrollRightBtn.disabled = scrollContainer.scrollWidth - scrollContainer.scrollLeft === scrollContainer.clientWidth;
    }

    scrollContainer.addEventListener('scroll', checkScrollButtons);
    window.addEventListener('resize', checkScrollButtons);
    checkScrollButtons(); // Initial check
});
