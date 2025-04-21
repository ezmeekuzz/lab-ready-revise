$(document).ready(function() {
    $('#edituser').submit(function(event) {
        // Prevent default form submission
        event.preventDefault();

        // Get form data
        let fullname = $('#fullname').val();
        let email = $('#email').val();
        let password = $('#password').val();
        let usertype = $('#usertype').val();

        // Perform client-side validation
        if (fullname.trim() === '' || email.trim() === '' || usertype.trim() === '') {
            // Show error using SweetAlert2
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Please fill in the required fields!',
            });
            return;
        }

        // Get checkbox value (convert to 1 or 0)
        let poAllow = $('input[name="po_allow"]').is(':checked') ? 1 : 0;

        // Create form data object
        let formData = {
            user_id: $('#user_id').val(),
            fullname: fullname,
            email: email,
            password: password,
            usertype: usertype,
            po_allow: poAllow
        };

        // Send AJAX request
        $.ajax({
            type: 'POST',
            url: '/edituser/update',
            data: formData,
            dataType: 'json',
            beforeSend: function() {
                // Show loading effect
                Swal.fire({
                    title: 'Updating...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            success: function(response) {
                if (response.success) {
                    // Redirect upon successful login
                    Swal.fire({
                        icon: 'success',
                        title: 'Data Updated',
                        text: response.message,
                    });
                } else {
                    // Show error message
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: response.message,
                    });
                }
            },
            error: function(xhr, status, error) {
                // Handle AJAX errors
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'An error occurred while updating user. Please try again later.',
                });
                console.error(xhr.responseText);
            }
        });
    });
});