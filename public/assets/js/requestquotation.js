document.getElementById("addQuotationBtn").addEventListener("click", function () {
    Swal.fire({
        title: "Quote Name",
        input: "text",
        inputPlaceholder: "Use a name that is relevant so you can find it later",
        showCancelButton: true,
        confirmButtonText: "OK",
        cancelButtonText: "Cancel",
        inputValidator: (value) => {
            if (!value) {
                return "Quotation Name is required!";
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            let quotationName = result.value;

            Swal.fire({
                title: "Submitting...",
                text: "Please wait while we save your quotation.",
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: "/requestquotation/addQuotation",
                type: "POST",
                data: { quotation_name: quotationName },
                dataType: "json",
                success: function (response) {
                    if (response.success) {
                        Swal.fire({
                            icon: "success",
                            title: "Success",
                            text: response.message,
                            confirmButtonText: "Go to Quotation"
                        }).then(() => {
                            window.location.href = response.redirect_url; // Redirect to process quotation page
                        });
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: response.message
                        });
                    }
                },
                error: function (xhr, status, error) {
                    console.error("AJAX Error:", error);
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "An error occurred. Please try again."
                    });
                }
            });
        }
    });
});
