function isLastDayOfMonth(date) {
    const nextDay = new Date(date.getFullYear(), date.getMonth(), date.getDate() + 1);
    return nextDay.getDate() === 1;
}

document.getElementById('restricted-date').addEventListener('change', function(event) {
    const input = event.target;
    const date = new Date(input.value);
    const day = date.getDate();

    // Allow only the 15th and the last day of the month
    if (!isLastDayOfMonth(date)) {
        // Reset the value if it's not a valid day
        input.value = '';
        alert('Please select only the last day of the month.');
    }
});

// script.js or within <script> tags in <head> or before </body>
document.getElementById('uploadForm').addEventListener('submit', function() {
    // Show loading overlay when form is submitted
    document.getElementById('loading-overlay').style.display = 'block';
});

