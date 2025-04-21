$(document).ready(function () {
    $(document).on('click', '.quotationDetails', function () {
        let quotationReponseId = $(this).data("id");
        let quotationId = $(this).data("quotation-id");
        let productAmount = $(this).data('amount');

        $.ajax({
            type: "GET",
            url: "/quotations/quotationDetails",
            data: { quotationReponseId: quotationReponseId },
            success: function (response) {
                // Access specific properties from the response object
                let quotationDate = moment(response.created_at).format('MMMM DD, YYYY');
                let invoiceFile = response.invoice_file_location;
                let productName = response.quotation_name;
                let productPrice = response.price;
                let shipmentLink = response.shipment_link;
                let poAllow = response.po_allow; // Get the PO allow status

                // Format the content as HTML
                let htmlContent = '<div class="book-layout">';
                htmlContent += '<div id="pdfViewer" class="pdf-viewer" style="width:100%; border:1px solid #ccc;"></div>';
                htmlContent += '<a href="' + invoiceFile + '" class="btn btn-primary mt-3" download="'+productName+'.pdf" class="btn-download-pdf">Download PDF</a>';
                htmlContent += '<div class="book-details mt-3">';
                htmlContent += '<div class="date mt-3"><strong>DATE:</strong> ' + quotationDate + '</div>';
                htmlContent += '<div class="date mt-3"><strong>Amount:</strong> ' + productPrice + '</div>';
                if (shipmentLink) {
                    htmlContent += '<div class="date mt-3"><strong>Track Order:</strong> <a href="' + shipmentLink + '" target="_blank">Track Order</a></div>';
                }
                if (response.payment_status === 'Unpaid') {
                    htmlContent += '<div class="row">';
                    htmlContent += '<div class="mb-3 mt-3 col-lg-12">';
                    
                    // Only show PayPal button if PO is allowed
                    if (poAllow == 1) {
                        htmlContent += '<div id="paypalButton" class="form-group"></div>';
                    }
                    
                    // Credit Card Button
                    htmlContent += '<button type="button" class="btn btn-info p-3 w-100 mb-2" id="chargeCreditCard">';
                    htmlContent += '<img src="https://static.vecteezy.com/system/resources/previews/019/879/184/original/credit-cards-payment-icon-on-transparent-background-free-png.png" class="w-25" /> Credit Card Payment';
                    htmlContent += '</button>';
                    
                    // eCheck Button
                    htmlContent += '<button type="button" class="btn btn-success p-3 w-100" id="chargeECheck">';
                    htmlContent += '<img src="https://cdn-icons-png.flaticon.com/512/4002/4002020.png" class="w-20" /> ACH Payment';
                    htmlContent += '</button>';
                    
                    htmlContent += '</div>';
                    htmlContent += '</div>';
                }
                
                htmlContent += '</div>'; // Close book-details div
                htmlContent += '</div>'; // Close book-layout div

                // Display the formatted content in the #displayDetails div
                $("#displayDetails").html(htmlContent);
                const pdfUrl = invoiceFile;
                const loadingTask = pdfjsLib.getDocument(pdfUrl);
                loadingTask.promise.then(function(pdf) {
                    // Fetch the first page
                    pdf.getPage(1).then(function(page) {
                        const scale = 1.5;
                        const viewport = page.getViewport({ scale: scale });
            
                        // Prepare canvas using PDF page dimensions
                        const canvas = document.createElement('canvas');
                        const context = canvas.getContext('2d');
                        canvas.height = viewport.height;
                        canvas.width = viewport.width;
                        $("#pdfViewer").html(canvas);
            
                        // Render PDF page into canvas context
                        const renderContext = {
                            canvasContext: context,
                            viewport: viewport
                        };
                        page.render(renderContext);
                    });
                });
                $("#productName").html('<h3><i class="fa fa-flag"></i> ' + productName + '</h3>');

                // Show the modal
                $("#quotationDetails").modal("show");

                // Render PayPal button only if PO is allowed
                if (poAllow == 1) {
                    paypal.Buttons({
                        createOrder: (data, actions) => {
                            if (quotationReponseId !== '' && productAmount !== '') {
                                return actions.order.create({
                                    purchase_units: [{
                                        amount: {
                                            value: productAmount,
                                            currency_code: 'USD'
                                        }
                                    }]
                                });
                            } else {
                                Swal.fire({
                                    title: 'Warning!',
                                    text: 'Please fill up all of the required form!',
                                    icon: 'warning',
                                });
                            }
                        },
                        onApprove: (data, actions) => {
                            return actions.order.capture().then(function (orderData) {
                                const formData = new FormData();
                                formData.append('quotationId', quotationId);
                                formData.append('quotationReponseId', quotationReponseId);
                    
                                $.ajax({
                                    type: "POST",
                                    url: '/quotations/pay',
                                    processData: false,
                                    contentType: false,
                                    data: formData,
                                    success: function (data) {
                                        if (data.success) {
                                            Swal.fire({
                                                title: 'Success!',
                                                text: data.message,
                                                icon: 'success',
                                                willClose: () => {
                                                    window.location.href = "/quotations";
                                                }
                                            });
                                        } else {
                                            Swal.fire({
                                                title: 'Failed!',
                                                text: data.message,
                                                icon: 'error',
                                                willClose: () => {
                                                    window.location.href = "/quotations";
                                                }
                                            });
                                        }
                                    }
                                });
                            });
                        },
                        onCancel: function (data) {
                            Swal.fire({
                                title: 'Warning!',
                                text: 'You cancelled your payment!',
                                icon: 'warning',
                            });
                        }
                    }).render('#paypalButton');
                }
                
                // Add event listener for the "chargeCreditCard" button
                $(document).on('click', '#chargeCreditCard', function () {
                    // Step 1: Show Shipping Address
                    Swal.fire({
                        title: 'Shipping Address',
                        html: `
                            <label>Address:</label><input type="text" id="address" name="address" value="${address}" class="form-control" required><br>
                            <label>City:</label><input type="text" id="city" name="city" value="${city}" class="form-control" required><br>
                            <label>State:</label><input type="text" id="state" name="state" value="${state}" class="form-control" required><br>
                            <label>Zip Code:</label><input type="text" id="zipcode" name="zipcode" value="${zipcode}" class="form-control" required><br>
                            <label>Phone Number:</label><input type="text" id="phonenumber" name="phonenumber" value="${phonenumber}" class="form-control" required><br>`,
                        focusConfirm: false,
                        showCancelButton: true,
                        confirmButtonText: 'Next',
                        preConfirm: () => {
                            const address = Swal.getPopup().querySelector('#address').value.trim();
                            const city = Swal.getPopup().querySelector('#city').value.trim();
                            const state = Swal.getPopup().querySelector('#state').value.trim();
                            const zipcode = Swal.getPopup().querySelector('#zipcode').value.trim();
                            const phoneNumber = Swal.getPopup().querySelector('#phonenumber').value.trim();
                
                            if (!address || !city || !state || !zipcode || !phoneNumber) {
                                Swal.showValidationMessage(`Please fill out all required fields.`);
                                return false;
                            }
                
                            return {
                                address,
                                city,
                                state,
                                zipcode,
                                phoneNumber,
                            };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const shippingData = result.value;
                            
                            // Step 2: Show Payment Details
                            Swal.fire({
                                title: 'Credit Card Payment',
                                html: `
                                    <label>Amount:</label><input type="text" id="amount" name="amount" class="form-control" value="${productAmount}" readonly><br>
                                    <label>Card Number:</label><input type="text" id="card_number" name="card_number" class="form-control" required><br>
                                    <label>Expiration Date (MMYY):</label><input type="text" id="expiration_date" name="expiration_date" class="form-control" required><br>
                                    <label>CVV:</label><input type="text" id="cvv" name="cvv" class="form-control" required><br>`,
                                focusConfirm: false,
                                preConfirm: () => {
                                    const cardNumber = Swal.getPopup().querySelector('#card_number').value.trim();
                                    const expirationDate = Swal.getPopup().querySelector('#expiration_date').value.trim();
                                    const cvv = Swal.getPopup().querySelector('#cvv').value.trim();
                
                                    if (!cardNumber || !expirationDate || !cvv) {
                                        Swal.showValidationMessage(`Please fill out all required fields.`);
                                        return false;
                                    }
                
                                    return {
                                        cardNumber,
                                        expirationDate,
                                        cvv,
                                    };
                                }
                            }).then((paymentResult) => {
                                if (paymentResult.isConfirmed) {
                                    const paymentData = paymentResult.value;
                
                                    // Combine both shipping and payment data
                                    const formData = {
                                        ...shippingData,
                                        ...paymentData
                                    };
                
                                    // Send the data via AJAX
                                    $.ajax({
                                        type: 'POST',
                                        url: '/quotations/chargeCreditCard',
                                        data: {
                                            amount: $('#amount').val(),
                                            cardNumber: formData.cardNumber,
                                            expirationDate: formData.expirationDate,
                                            cvv: formData.cvv,
                                            address: formData.address,
                                            city: formData.city,
                                            state: formData.state,
                                            zipcode: formData.zipcode,
                                            phoneNumber: formData.phoneNumber,
                                            quotationId: quotationId,
                                            quotationReponseId: quotationReponseId,
                                        },
                                        success: function (response) {
                                            const { success, message } = response;
                
                                            Swal.fire({
                                                title: success ? 'Payment Successful!' : 'Payment Failed!',
                                                text: message,
                                                icon: success ? 'success' : 'error',
                                                willClose: () => {
                                                    window.location.href = "/quotations";
                                                }
                                            });
                                        },
                                        error: function (xhr) {
                                            const errorMessage = xhr.responseJSON?.message || 'An error occurred during the payment process. Please try again later.';
                                            
                                            Swal.fire({
                                                title: 'Payment Error!',
                                                text: errorMessage,
                                                icon: 'error',
                                            });
                                        }
                                    });
                                }
                            });
                        }
                    });
                });                
                // Add event listener for the "chargeECheck" button
                $(document).on('click', '#chargeECheck', function () {
                    // Step 1: Shipping Address
                    Swal.fire({
                        title: 'Shipping Address',
                        html: `
                            <label>Address:</label><input type="text" id="address" name="address" value="${address}" class="form-control" required><br>
                            <label>City:</label><input type="text" id="city" name="city" value="${city}" class="form-control" required><br>
                            <label>State:</label><input type="text" id="state" name="state" value="${state}" class="form-control" required><br>
                            <label>Zip Code:</label><input type="text" id="zipcode" name="zipcode" value="${zipcode}" class="form-control" required><br>
                            <label>Phone Number:</label><input type="text" id="phonenumber" name="phonenumber" value="${phonenumber}" class="form-control" required><br>`,
                        showCancelButton: true,
                        confirmButtonText: 'Next',
                        preConfirm: () => {
                            const address = Swal.getPopup().querySelector('#address').value.trim();
                            const city = Swal.getPopup().querySelector('#city').value.trim();
                            const state = Swal.getPopup().querySelector('#state').value.trim();
                            const zipcode = Swal.getPopup().querySelector('#zipcode').value.trim();
                            const phoneNumber = Swal.getPopup().querySelector('#phonenumber').value.trim();
                
                            if (!address || !city || !state || !zipcode || !phoneNumber) {
                                Swal.showValidationMessage(`Please fill out all required fields.`);
                                return false;
                            }
                
                            return { address, city, state, zipcode, phoneNumber };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const shippingData = result.value;
                
                            // Step 2: Payment Details
                            Swal.fire({
                                title: 'eCheck Payment',
                                html: `
                                    <label>Amount:</label><input type="text" id="amount" name="amount" class="form-control" value="${productAmount}" readonly><br>
                                    <label>Bank Account Number:</label><input type="text" id="account_number" name="account_number" class="form-control" required><br>
                                    <label>Routing Number:</label><input type="text" id="routing_number" name="routing_number" class="form-control" required><br>
                                    <label>Account Type:</label>
                                    <select id="account_type" name="account_type" class="form-control" required>
                                        <option value="">Select Account Type</option>
                                        <option value="checking">Checking</option>
                                        <option value="savings">Savings</option>
                                    </select><br>
                                    <label>Account Holder Name:</label><input type="text" id="account_holder" name="account_holder" class="form-control" required><br>`,
                                focusConfirm: false,
                                showCancelButton: true,
                                confirmButtonText: 'Submit',
                                preConfirm: () => {
                                    const accountNumber = Swal.getPopup().querySelector('#account_number').value.trim();
                                    const routingNumber = Swal.getPopup().querySelector('#routing_number').value.trim();
                                    const accountType = Swal.getPopup().querySelector('#account_type').value.trim();
                                    const accountHolder = Swal.getPopup().querySelector('#account_holder').value.trim();
                
                                    if (!accountNumber || !routingNumber || !accountType || !accountHolder) {
                                        Swal.showValidationMessage(`Please fill out all required fields.`);
                                        return false;
                                    }
                
                                    return { accountNumber, routingNumber, accountType, accountHolder };
                                }
                            }).then((paymentResult) => {
                                if (paymentResult.isConfirmed) {
                                    const paymentData = paymentResult.value;
                
                                    // Combine shipping and payment data
                                    const formData = {
                                        ...shippingData,
                                        ...paymentData,
                                        amount: $('#amount').val(),
                                        quotationId: quotationId
                                    };
                
                                    // Perform the AJAX request
                                    $.ajax({
                                        type: 'POST',
                                        url: '/quotations/chargeEcheck',
                                        data: formData,
                                        success: function (response) {
                                            const { success, message } = response;
                
                                            Swal.fire({
                                                title: success ? 'Payment Successful!' : 'Payment Failed!',
                                                text: message,
                                                icon: success ? 'success' : 'error',
                                                willClose: () => {
                                                    window.location.href = "/quotations";
                                                }
                                            });
                                        },
                                        error: function (xhr) {
                                            const errorMessage = xhr.responseJSON?.message || 'An error occurred during the payment process. Please try again later.';
                
                                            Swal.fire({
                                                title: 'Payment Error!',
                                                text: errorMessage,
                                                icon: 'error',
                                            });
                                        }
                                    });
                                }
                            });
                        }
                    });
                });                                                                 
            },
            error: function () {
                console.error("Error fetching data");
            }
        });
    });

    function fetchData(search = '', year = '', month = '') {
        $.ajax({
            type: "GET",
            url: "/quotations/getData",
            data: { 
                search: search,
                year: year,
                month: month
            },
            success: function (response) {
                $("#card-columns").empty();
                if(response.length === 0) {
                    $("#noQuotationsMessage").show();
                } else {
                    $("#noQuotationsMessage").hide();
                    response.forEach(function (item) {
                        var cardHTML = `
                            <div class="col-xl-3 col-sm-6">
                                <div class="card card-statistics position-relative">
                                    <div class="delete-btn">
                                        <button class="btn btn-danger delete-quotation" data-id="${item.user_receive_quotation_response_id}">Delete</button>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex align-items-start">
                                            <div class="mr-3">
                                                <img src="../assets/img/file-icon/pdf.png" alt="png-img">
                                            </div>
                                            <div>
                                                <h4 class="mb-2">${item.quotation_name}</h4>
                                                <p class="mb-2"><span style="font-weight: bold;">Reference : ${item.reference_number}</span></p>
                                                <p class="mb-2"><span style="font-weight: bold; color: red;">Price: $${parseFloat(item.price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span></p>
                                                <p class="mb-2"><span style="font-weight: bold; color: blue;">Date :${new Date(item.quotationdate).toLocaleString('en-US', {
                                                    year: 'numeric',
                                                    month: 'short',
                                                    day: 'numeric',
                                                    hour: '2-digit',
                                                    minute: '2-digit',
                                                    hour12: true
                                                  })}</span></p>
                                                <a href="javascript:void(0)" class="btn btn-light quotationDetails" data-quotation-id="${item.quotation_id}" data-id="${item.quotation_response_id}" data-amount="${item.price}">Open</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        $("#card-columns").append(cardHTML);
                    });
                }
            },
            error: function () {
                console.error("Error fetching data");
            }
        });
    }
    
    // Function to trigger fetch with filters
    function applyFilters() {
        var search = $('#searchBox').val();
        var year = $('#yearFilter').val();
        var month = $('#monthFilter').val();
    
        fetchData(search, year, month);
    }
    
    // Event listeners for filter change
    $('#yearFilter').on('change', function() {
        applyFilters();
    });
    
    $('#monthFilter').on('change', function() {
        applyFilters();
    });
    
    // Search box keyup event listener to trigger search
    $('#searchBox').on('keyup', function() {
        applyFilters();
    });
    
    // Initial fetch without filters
    fetchData();    

    // Event listener for search box
    $('#searchBox').on('input', function() {
        const search = $(this).val();
        fetchData(search);
    });

    $(document).on('click', '.delete-quotation', function () {
        let id = $(this).data('id');

        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'No, cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/quotations/delete/' + id,
                    method: 'DELETE',
                    success: function (response) {
                        if (response.status === 'success') {
                            fetchData();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'Something went wrong!',
                            });
                        }
                    },
                    error: function () {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Something went wrong with the request!',
                        });
                    }
                });
            }
        });
    });
    $.fn.modal.Constructor.prototype._enforceFocus = function() {};
});