//for fetching zone
function updateZone() {
    var mainzone = document.getElementById("mainzone").value;
    var selectedRegion = document.getElementById("region").value;

    var xhr = new XMLHttpRequest();
    xhr.open("POST", "../../fetch/rfp-payroll-get-regions.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            document.getElementById("region").innerHTML = xhr.responseText;
        }
    };
    xhr.send("mainzone=" + encodeURIComponent(mainzone) + "&selected_region=" + encodeURIComponent(selectedRegion));
}

// Ensure the zones are updated automatically on page load based on the current mainzone
window.onload = function() {
    var mainzone = document.getElementById("mainzone").value;
    if (mainzone !== "") {
        updateZone(); // Correct function call
    }
};

// only 15 and last day of the month
function isLastDayOfMonth(date) {
    const nextDay = new Date(date.getFullYear(), date.getMonth(), date.getDate() + 1);
    return nextDay.getDate() === 1;
}

document.getElementById('restricted-date').addEventListener('change', function(event) {
    const input = event.target;
    const date = new Date(input.value);
    const day = date.getDate();

    // Allow only the 15th and the last day of the month
    if (day !== 15 && !isLastDayOfMonth(date)) {
        // Reset the value if it's not a valid day
        input.value = '';
        alert('Please select only the 15th or the last day of the month.');
    }
});