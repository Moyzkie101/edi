<?php

session_start();

include '../config/connection.php';

echo '<script src="../sweetalert2/dist/sweetalert2.all.min.js"></script>';
echo '<link rel="stylesheet" href="../sweetalert2/dist/sweetalert2.min.css">';
echo '<script src="../assets/login/js/jquery-3.7.1.js"></script>';

if (!isset($_SESSION['admin_name'])) {
   header('location: ../login.php');
}

if (isset($_POST['submit'])) {
   // Escape user input to prevent SQL injection
   $codenum = mysqli_real_escape_string($conn, $_POST['idNum']);
   $description = mysqli_real_escape_string($conn, $_POST['desc']);
   $normaltype = mysqli_real_escape_string($conn, $_POST['normal-type']);
   $codetype = mysqli_real_escape_string($conn, $_POST['code-type']);

   // Get admin name from session
   $name = mysqli_real_escape_string($conn, $_SESSION['admin_name'] ?? 'Unknown');

   date_default_timezone_set('Asia/Manila');
   $date = date('Y-m-d');

   // Check if GL Code already exists
   $select = "SELECT * FROM " . $database[0] . ".gl_code_account WHERE gl_code = '$codenum'";
   $result = mysqli_query($conn, $select);

   if (mysqli_num_rows($result) > 0) {
       echo '<script>
           Swal.fire({
               title: "Error!",
               text: "GL Code already exists!",
               icon: "error",
               confirmButtonText: "OK"
           }).then(() => {
               window.location.href = "gl-codes.php";
           });
       </script>';
   } else {
       // Use prepared statement for security
       $insert = $conn->prepare("INSERT INTO " . $database[0] . ".gl_code_account (
                                       gl_code,
                                       gl_description,
                                       gl_normal_balance,
                                       gl_type,
                                       gl_created_by,
                                       gl_date_created
                                   ) VALUES (?, ?, ?, ?, ?, ?)");

       // Bind parameters
       $insert->bind_param("ssssss", $codenum, $description, $normaltype, $codetype, $name, $date);

       // Execute and check for success
       if ($insert->execute()) {
           echo '<script>
               Swal.fire({
                   title: "Success!",
                   text: "GL Code added successfully!",
                   icon: "success",
                   confirmButtonText: "OK"
               }).then(() => {
                   window.location.href = "gl-codes.php";
               });
           </script>';
       } else {
           echo '<script>
               Swal.fire({
                   title: "Error!",
                   text: "GL Code not added! ' . $conn->error . '",
                   icon: "error",
                   confirmButtonText: "OK"
               }).then(() => {
                   window.location.href = "gl-codes.php";
               });
           </script>';
       }

       // Close the prepared statement
       $insert->close();
   }

   // Fetch stored session data (if available)
   $results = $_SESSION['result'] ?? [];

}

// Handle search form submission
if (isset($_POST['search'])) {
   $search = $_POST['search-input'];
   if (!empty($search)) { // Check if search input is not empty
       $query = "SELECT * FROM " . $database[0] . ".gl_code_account WHERE gl_code LIKE '%$search%' OR  gl_description LIKE '%$search%' OR gl_normal_balance LIKE '%$search%' OR gl_type LIKE '%$search%'";
       $result = mysqli_query($conn, $query);
       if ($result) {
           $searchResults = mysqli_fetch_all($result, MYSQLI_ASSOC);
       } else {
           $searchResults = array(); // Empty array if there is an error
           $errors[] = "Error: " . mysqli_error($conn); // Store the SQL error message
       }
   } else {
       $searchResults = array(); // Empty array if search input is empty
   }
} else {
   $searchResults = array(); // Empty array if no search is performed
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E D I</title>
    <!-- custom CSS file link  -->
    <link rel="stylesheet" href="../assets/css/admin/userLog/style.css?v=<?php echo time(); ?>">
    <link rel="icon" href="../assets/picture/MLW Logo.png" type="image/x-icon"/>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.22/css/jquery.dataTables.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<!-- Include SweetAlert CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.1.5/dist/sweetalert2.min.css">

<script>
  document.addEventListener("DOMContentLoaded", function () {
   // Get form elements
   const form = document.querySelector(".register");
   const idNum = document.getElementById("idNum");
   const desc = document.getElementById("desc");
   const normalType = document.getElementById("normal-type");
   const codeType = document.getElementById("code-type");
   const registerButton = document.getElementById("register");

   // Function to check if all fields are filled
   function validateForm() {
      const idNumValue = idNum.value.trim();
      const descValue = desc.value.trim();
      const normalTypeValue = normalType.value;
      const codeTypeValue = codeType.value;

      // Enable button only if all fields are filled
      if (idNumValue !== "" && descValue !== "" && normalTypeValue !== "" && codeTypeValue !== "") {
         registerButton.disabled = false;
      } else {
         registerButton.disabled = true;
      }
   }

   // Attach event listeners for real-time validation
   idNum.addEventListener("input", validateForm);
   desc.addEventListener("input", validateForm);
   normalType.addEventListener("change", validateForm);
   codeType.addEventListener("change", validateForm);

   // Prevent form submission if button is disabled
   form.addEventListener("submit", function (event) {
      if (registerButton.disabled) {
         event.preventDefault();
      }
   });
});
</script>
</head>
                     
<body>
   
   <div class="container">
      <div class="top-content">
         <?php include '../templates/sidebar.php' ?>
      </div>
      <div class="s-div">
         <div id="search-div">
            <form method="POST" class="form-group">
               <div class="left-div">
                  <input type="text" id="search-input" maxlength="40" name="search-input" value="<?php if (isset($_POST['search'])) echo $_POST['search']; ?>" placeholder="Search...">
                  <button type="submit" id="search" name="search" ><i class="fa-solid fa-magnifying-glass"></i></button>
               </div>
               <div class="right-div">
                  <button type="button" id="add" name="add" onclick="showModal('register-modal')"> <i style="margin-right: 8px;" class="fa-solid fa-circle-plus"></i>Add GL Code</button>
                  <button type="button" id="edit" name="edit" onclick="showEditModal()"><i style="margin-right: 8px;" class="fa-solid fa-user-pen"></i>Edit GL Code</button>
                  <!-- <button type="button" id="delete" name="delete" onclick="deleteRow()"><i style="margin-right: 8px;" class="fa-solid fa-user-xmark"></i>Delete</button>
                  <button type="button" id="update" name="update" onclick="updateStatus()">Change Status</button> -->
               </div>
            </form>
            <?php if (!empty($searchResults) && isset($_POST['search']) && !empty($search)) : ?>
            <div id="search-results">
               <h3 style="color: #29348e;">SEARCH RESULT</h3>
               <table>
                  <thead>
                     <tr>
                        <th style="display:none;"></th>
                        <th>GL Code Number</th>
                        <th>GL Code Description</th>
                        <th>Normal Balance</th>
                        <th>Category</th>
                     </tr>
                  </thead>
                  <tbody>
                        <?php foreach ($searchResults as $result) : ?>
                           <tr onclick="selectRow(this)">
                              <td style="text-align:center; padding-left:10px; width:fit-content;"> <?php echo htmlspecialchars($result['gl_code']); ?> </td>
                              <td style="text-align:center; padding-left:10px; width:fit-content;"> <?php echo htmlspecialchars($result['gl_description']); ?> </td>
                              <td style="text-align:center; padding-left:10px; width:fit-content;"> <?php echo htmlspecialchars($result['gl_normal_balance']); ?> </td>
                              <td style="text-align:center; padding-left:10px; width:fit-content;"> <?php echo htmlspecialchars($result['gl_type']); ?> </td>
                           </tr>
                        <?php endforeach; ?>
                  </tbody>
               </table>
            </div>
            <?php endif; ?>
         </div>
         <div class="table-wrap">
            <table id="users-table">
               <h3 style="color: #db120b;">GL Codes [Setup]</h3>
               <thead>
                  <tr>
                     <th>GL Code Number</th>
                     <th>GL Code Description</th>
                     <th>Normal Balance</th>
                     <th>Category</th>
                  </tr>
               </thead>
               <tbody>
                  <?php
                  $query = "SELECT * FROM " . $database[0] . ".gl_code_account ORDER BY gl_code ASC";
                  $result2 = mysqli_query($conn, $query);
                  if ($result2->num_rows > 0) {
                     while ($row = $result2->fetch_assoc()) {
                        ?>
                        <tr onclick="selectRow(this)">
                           <td style="text-align:center; padding-left:10px;"> <?php echo htmlspecialchars($row['gl_code']); ?> </td>
                           <td style="text-align:center; padding-left:10px;"> <?php echo htmlspecialchars($row['gl_description']); ?> </td>
                           <td style="text-align:center; padding-left:10px;"> <?php echo htmlspecialchars($row['gl_normal_balance']); ?> </td>
                           <td style="text-align:center; padding-left:10px;"> <?php echo htmlspecialchars($row['gl_type']); ?> </td>
                        </tr>
                     <?php
                     }
                  }else{
                     echo '<td colspan="4" style="text-align:center;">No records found.</td>';
                  }
                  ?>
               </tbody>
            </table>
         </div>
      </div>
   </div>

<!-- Register Modal -->
<div id="register-modal" class="modal">
   <div class="register_modal-content">
      <span class="close" onclick="hideModal('register-modal'); clearRegisterForm();">&times;</span>
      <form action="" method="post" class="register">
         <div class="logo">
            <img src="../assets/picture/MLW Logo.png" alt="logo">
         </div>
            <h3 style="color: #29348e;  margin-top:10px; padding:5px;">Add GL Code</h3>
            <div class="inputs">
               <div class="l-c-i">
                  <div class="input-container">
                     <label for="idNum">GL Code Number</label>
                     <input class="add_inp" type="text" name="idNum" id="idNum" required autocomplete="off" onkeypress="return (event.keyCode >= 48 && event.keyCode <= 57) || (event.keyCode == 46 && event.keyCode == 18 );">
                  </div>

                  <div class="input-container">
                     <label for="desc">GL Description</label>
                     <input class="add_inp" type="text" name="desc" id="desc" required autocomplete="off">
                  </div>
                  <div class="input-container">
                     <label for="normal-type">Normal Balance</label>
                     <select name="normal-type" id="normal-type" required>
                        <option value="" disabled selected></option>
                        <option value="DEBIT">Debit</option>
                        <option value="CREDIT">Credit</option>
                     </select>
                  </div>
                  <div class="input-container">
                     <label for="code-type" >Category</label>
                     <select name="code-type" id="code-type" required>
                        <option value="" disabled selected></option>
                        <option value="RECEIVABLE">Receivable</option>
                        <option value="PAYABLE">Payable</option>
                     </select>
                  </div>
               </div>
            </div>
         <center><button type="submit" id="register" name="submit" class="form-btn" onclick="validateForm()" disabled >ADD</button></center>
      </form>
   </div>
</div>

<!-- Edit User Modal -->
<div id="edit-modal" class="modal" >
   <div class="edit_modal-content">
      <span class="close" id="close" onclick="hideModal('edit-modal')">&times;</span>
      <form method="POST" action="">
         <div class="logo">
               <img src="../assets/picture/MLW Logo.png" alt="logo">
            </div>
               <h3 style="color: #29348e;  margin-top:10px; padding:5px;">Add GL Code</h3>
               <div class="inputs">
                  <div class="l-c-i">
                     <div class="input-container">
                        <label for="idNum">GL Code Number</label>
                        <input class="add_inp" type="text" name="idNum" id="idNum" required autocomplete="off" onkeypress="return (event.keyCode >= 48 && event.keyCode <= 57) || (event.keyCode == 46 && event.keyCode == 18 );">
                     </div>

                     <div class="input-container">
                        <label for="desc">GL Description</label>
                        <input class="add_inp" type="text" name="desc" id="desc" required autocomplete="off">
                     </div>
                     <div class="input-container">
                        <label for="normal-type">Normal Balance</label>
                        <select name="normal-type" id="normal-type">
                           <option value="" disabled selected></option>
                           <option value="DEBIT">Debit</option>
                           <option value="CREDIT">Credit</option>
                        </select>
                     </div>
                     <div class="input-container">
                        <label for="code-type">Category</label>
                        <select name="code-type" id="code-type">
                           <option value="" disabled selected></option>
                           <option value="RECEIVABLE">Receivable</option>
                           <option value="PAYABLE">Payable</option>
                        </select>
                     </div>
                  </div>
               </div>
               <center>
                  <button type="submit" id="update-user" name="update-user" onclick="edit_validateForm()">Update User</button>
                  <button type="submit" id="reset_pass" name="reset_pass">Reset Password</button>
               </center>
         </div>
      </form>
   </div>
</div>
<!-- Include SweetAlert JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.1.5/dist/sweetalert2.all.min.js"></script>



<script> 
 // Display error messages in a SweetAlert modal
 <?php if(!empty($errors)): ?>
      Swal.fire({
         title: "Error!",
         html: "<?php echo implode('<br>', $errors); ?>",
         icon: "error",
         allowOutsideClick: false
      });
   <?php endif; ?>
     // Display success messages in a SweetAlert modal

   <?php if(!empty($successMessages)): ?>
      Swal.fire({
         title: "Success!",
         html: "<?php echo implode('<br>', $successMessages); ?>",
         icon: "success",
         allowOutsideClick: false
      });
   <?php endif; ?>

   
    function clearRegisterForm() {
      document.getElementById('idNum').value = '';
      document.getElementById('desc').value = '';
      document.getElementById('mname').value = '';
      document.getElementById('normal-type').value = '';
      document.getElementById('code-type').value = '';

   }
   function showModal(modalId) {
      var modal = document.getElementById(modalId);
      modal.style.display = 'block';
   }

   function hideModal(modalId) {
      var modal = document.getElementById(modalId);
      modal.style.display = 'none';
   }

   function selectRow(row) {
    var selectedRow = document.querySelector('.selected');
    if (selectedRow) {
        selectedRow.classList.remove('selected');
        selectedRow.style.backgroundColor = '';
        selectedRow.style.color = '';
    }
    if (selectedRow !== row) {
        row.classList.add('selected');
        row.style.backgroundColor = 'skyblue';
        row.style.color = 'black';
    }
}
function populateModalInputs() {
   var selectedRow = document.querySelector('.selected');
   if (selectedRow) {
      var inputs = document.getElementById('edit-modal').getElementsByTagName('input');
      var select = document.getElementById('edit_user_type');
      inputs[0].value = selectedRow.cells[0].textContent;
      inputs[1].value = selectedRow.cells[1].textContent;
      inputs[2].value = selectedRow.cells[2].textContent;
      inputs[3].value = selectedRow.cells[3].textContent;
      inputs[4].value = selectedRow.cells[4].textContent;
      inputs[5].value = selectedRow.cells[5].textContent;
      inputs[6].value = selectedRow.cells[6].textContent;
      select.value = selectedRow.cells[7].textContent.toLowerCase();
      inputs[9].value = selectedRow.cells[9].textContent;
      
      // Check the checkbox based on the value of edit_role
      var editRole = selectedRow.cells[8].textContent.toLowerCase();
      var editRoles = editRole.split(',').map(function(role) {
         return role.trim();
      });
      
      var checkboxes = document.querySelectorAll('#edit_roles_list input[type="checkbox"]');
      checkboxes.forEach(function(checkbox) {
         if (editRoles.includes(checkbox.value.toLowerCase())) {
            checkbox.checked = true;
         } else {
            checkbox.checked = false;
         }
      });
      
      showModal('edit-modal');
   }
}

function showEditModal() {
   var selectedRow = document.querySelector('#users-table tr.selected');
   var selectedRow2 = document.querySelector('#search-results tr.selected');
   if (selectedRow || selectedRow2) {
      var cells = (selectedRow || selectedRow2).getElementsByTagName('td');
      document.getElementById('edit_user_id').value = cells[0].innerText;
      document.getElementById('edit_user_type').value = cells[1].innerText;
      document.getElementById('edit_idNum').value = cells[2].innerText;
      document.getElementById('edit_email').value = cells[3].innerText;
      document.getElementById('edit_first_name').value = cells[4].innerText;
      document.getElementById('edit_middle_name').value = cells[5].innerText;
      document.getElementById('edit_last_name').value = cells[6].innerText;
      document.getElementById('password').value = ''; // Leave password field blank
      document.getElementById('edit_role').value = cells[8].innerText;
      
      // Check the checkbox based on the value of edit_role
      var editRole = cells[8].innerText.toLowerCase();
      var editRoles = editRole.split(',').map(function(role) {
         return role.trim();
      });
      
      var checkboxes = document.querySelectorAll('#edit_roles_list input[type="checkbox"]');
      checkboxes.forEach(function(checkbox) {
         if (editRoles.includes(checkbox.value.toLowerCase())) {
            checkbox.checked = true;
         } else {
            checkbox.checked = false;
         }
      });
      
      showModal('edit-modal');
   } else {
      Swal.fire({
         title: 'Warning',
         text: 'Please select a user to edit.',
         icon: 'warning',
         showConfirmButton: true,
         allowOutsideClick: true,
      });
   }
}

function storeValues() {
   populateModalInputs();
   showModal('register-modal');
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
</body>
</html>
