//for fetching zone
function updateZone() {
    var mainzone = document.getElementById("mainzone").value;
    var selectedZone = document.getElementById("zone").value; // Get the currently selected zone, if any
    
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "../../../fetch/get_zone.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            document.getElementById("zone").innerHTML = xhr.responseText;
        }
    };
    // Pass the current zone as well to preserve the selection
    xhr.send("mainzone=" + mainzone + "&selected_zone=" + selectedZone);
}

// Ensure the zones are updated automatically on page load based on the current mainzone
window.onload = function() {
    var mainzone = document.getElementById("mainzone").value;
    if (mainzone !== "") {
        updateZone(); // Fetch and set the zones automatically if a mainzone is already selected
    }
};
 
// Function to fetch regions based on the selected zone
function updateRegions() {
    var zone = document.getElementById("zone").value;
    var selectedRegion = document.getElementById("region").value; // Get the currently selected region, if any

    var xhr = new XMLHttpRequest();
    xhr.open("POST", "../../../fetch/get_regions.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            document.getElementById("region").innerHTML = xhr.responseText;
        }
    };
    // Pass the current region as well to preserve the selection
    xhr.send("zone=" + zone + "&selected_region=" + selectedRegion);
}

// Ensure the regions are updated automatically when a zone is selected or when the page reloads
document.getElementById("zone").addEventListener('change', updateRegions);

window.onload = function() {
    var zone = document.getElementById("zone").value;
    if (zone !== "") {
        updateRegions(); // Fetch and set the regions automatically if a zone is already selected
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