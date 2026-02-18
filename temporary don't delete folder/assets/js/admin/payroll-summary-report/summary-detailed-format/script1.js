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

// Function to fetch branches based on the selected region
function updateBranches() {
    var region = document.getElementById("region").value;
    var selectedBranch = document.getElementById("branch").value; // Get the currently selected branch, if any

    var xhr = new XMLHttpRequest();
    xhr.open("POST", "../../../fetch/get_branches.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            document.getElementById("branch").innerHTML = xhr.responseText;
        }
    };
    // Pass the current branch as well to preserve the selection
    xhr.send("region=" + region + "&selected_branch=" + selectedBranch); // Changed 'selected_branches' to 'selected_branch'
}

// Ensure the branches are updated automatically when a region is selected
document.getElementById("region").addEventListener('change', updateBranches);

// Automatically update branches when the page loads
window.onload = function() {
    var region = document.getElementById("region").value;
    if (region !== "") {
        updateBranches(); // Fetch and set the branches automatically if a region is already selected
    }
};
 
//for fetching region
// function updateBranches() {
//     var region = document.getElementById("region").value;
//     var xhr = new XMLHttpRequest();
//     xhr.open("POST", "get_branches.php", true);
//     xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
//     xhr.onreadystatechange = function () {
//         if (xhr.readyState === 4) {
//             if (xhr.status === 200) {
//                 document.getElementById("branch").innerHTML = xhr.responseText;
//             } else {
//                 console.error("Failed to fetch regions. Status: " + xhr.status);
//             }
//         }
//     };
//     xhr.send("region=" + encodeURIComponent(region));
// }

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
document.getElementById('restricted-date1').addEventListener('change', function(event) {
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