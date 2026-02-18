<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script type="text/javascript" src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/2.9.2/semantic.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.semanticui.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/2.9.2/semantic.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
  </body>

<script>
    $(document).ready(function() {
        $('#myTable').DataTable();
    });
</script>

<script>
    $(document).ready(function(){
      $('.filter_status').change(function(){
        window.location.href = "http://localhost/edi/admin/region_profile.php?id="+$(this).val();
      });
    });
</script>
<script>
    function displayModal(rowIndex) {
    // Get the row data
    const rows = document.querySelectorAll(".selectable-row");
    const row = rows[rowIndex];
    const rowData = Array.from(row.cells).map(cell => cell.textContent.trim());
    
    // Set the modal content
    document.querySelector("#branch_code_update").textContent = rowData[0] || '';
    document.querySelector("#branch_id_update").textContent = rowData[1] || '';
    document.querySelector("#branch_name_update").textContent = rowData[2] || '';
    document.querySelector("#main_zone_update").textContent = rowData[3] || '';
    document.querySelector("#zone_code_update").textContent = rowData[4] || '';
    document.querySelector("#region_name_update").textContent = rowData[5] || '';
    document.querySelector("#region_code_update").textContent = rowData[6] || '';
    document.querySelector("#area_name_update").textContent = rowData[7] || '';
    document.querySelector("#area_code_update").textContent = rowData[8] || '';
    document.querySelector("#am_base_update").textContent = rowData[9] || '';
    document.querySelector("#rm_base_update").textContent = rowData[10] || '';
    // document.querySelector("#kp_code_update").textContent = rowData[11] || '';
    // document.querySelector("#kp_zone_update").textContent = rowData[12] || '';
    // document.querySelector("#kp_region_update").textContent = rowData[13] || '';
    // document.querySelector("#kp_branch_update").textContent = rowData[14] || '';
    // document.querySelector("#created_by_update").textContent = rowData[15] || '';
    // document.querySelector("#system_date_update").textContent = rowData[16] || '';
    // document.querySelector("#modified_by_update").textContent = rowData[17] || '';
    // document.querySelector("#modified_date_update").textContent = rowData[18] || '';
    document.querySelector("#status_update").textContent = rowData[19] || '';
    document.querySelector("#corporate_name_update").textContent = rowData[20] || '';
    document.querySelector("#globe_accnumber_update").textContent = rowData[21] || '';
    document.querySelector("#globe_accnumber2_update").textContent = rowData[22] || '';
    document.querySelector("#globe_accnumber3_update").textContent = rowData[23] || '';
    document.querySelector("#globe_accnumber4_update").textContent = rowData[24] || '';
    document.querySelector("#globe_accnumber5_update").textContent = rowData[25] || '';
    document.querySelector("#gmobile_accnumber_update").textContent = rowData[26] || '';
    document.querySelector("#gmobile_accnumber2_update").textContent = rowData[27] || '';
    document.querySelector("#gmobile_accnumber3_update").textContent = rowData[28] || '';
    document.querySelector("#gmobile_accnumber4_update").textContent = rowData[29] || '';
    document.querySelector("#gmobile_accnumber5_update").textContent = rowData[30] || '';
    document.querySelector("#smart_accnumber_update").textContent = rowData[31] || '';
    document.querySelector("#smart_accnumber2_update").textContent = rowData[32] || '';
    document.querySelector("#smart_accnumber3_update").textContent = rowData[33] || '';
    document.querySelector("#smart_accnumber4_update").textContent = rowData[34] || '';
    document.querySelector("#smart_accnumber5_update").textContent = rowData[35] || '';
    document.querySelector("#selected_id").value = rowData[36] || '';
    
    // Show the modal
    const modal = document.querySelector("#myModal");
    modal.style.display = "block";
}


    const updateCloseButton = document.querySelector(".update-close");
    updateCloseButton.addEventListener("click", function() {
        const modal = document.querySelector("#myModal");
        modal.style.display = "none";
    });

    function highlightRow(row) {
    // Remove highlight from previously selected row
    const previouslySelectedRow = document.querySelector(".selectable-row.highlight");
    if (previouslySelectedRow) {
        previouslySelectedRow.classList.remove("highlight");
    }
    // Add highlight to the clicked row
    row.classList.add("highlight");
}


    function updateSelectRegion() {
        // Get the selected region_code from the hidden input field
        var regionCode = document.getElementById('region-select').value;
        // Display the region_code in the region_code input field
        document.getElementById('region_code_display').value = regionCode;
    }

    function updateSelectArea() {
        // Get the selected region_code from the hidden input field
        var areaCode = document.getElementById('area-select').value;
        // Display the region_code in the region_code input field
        document.getElementById('area_code_display').value = areaCode;
    }

</script>
<script>
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
</script>