// Attach click event listeners to group buttons
document.querySelectorAll('.group-btn').forEach(button => {
    button.addEventListener('click', () => {
        const group = button.parentElement;

        // Toggle visibility of this group
        group.classList.toggle('show');

        // Close other groups in the dropdown
        document.querySelectorAll('.dropdown-group').forEach(otherGroup => {
            if (otherGroup !== group) {
                otherGroup.classList.remove('show');
            }
        });
    });
});

// Close all groups when clicking outside the dropdown
document.addEventListener('click', event => {
    if (!event.target.closest('.dropdown-content')) {
        document.querySelectorAll('.dropdown-group').forEach(group => {
            group.classList.remove('show');
        });
    }
});