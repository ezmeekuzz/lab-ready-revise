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
                let poAllow = response.po_allow;
                let address = response.user_address || '';
                let city = response.user_city || '';
                let state = response.user_state || '';
                let zipcode = response.user_zipcode || '';
                let phonenumber = response.user_phonenumber || '';

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
                    
                    // PO Payment Button - shows different buttons based on approval status
                    if (poAllow == 1) {
                        htmlContent += '<button type="button" class="btn btn-warning p-3 w-100 mb-2" id="submitPO">';
                        htmlContent += '<i class="fa fa-file-text"></i> Submit PO';
                        htmlContent += '</button>';
                    } else {
                        htmlContent += '<button type="button" class="btn btn-secondary p-3 w-100 mb-2" id="requestPOApproval">';
                        htmlContent += '<i class="fa fa-question-circle"></i> NOT Approved for PO Payment - Click to Request';
                        htmlContent += '</button>';
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
                
                // PDF Viewer
                const pdfUrl = invoiceFile;
                const loadingTask = pdfjsLib.getDocument(pdfUrl);
                loadingTask.promise.then(function(pdf) {
                    pdf.getPage(1).then(function(page) {
                        const scale = 1.5;
                        const viewport = page.getViewport({ scale: scale });
                        const canvas = document.createElement('canvas');
                        const context = canvas.getContext('2d');
                        canvas.height = viewport.height;
                        canvas.width = viewport.width;
                        $("#pdfViewer").html(canvas);
                        const renderContext = {
                            canvasContext: context,
                            viewport: viewport
                        };
                        page.render(renderContext);
                    });
                });
                
                $("#productName").html('<h3><i class="fa fa-flag"></i> ' + productName + '</h3>');
                $("#quotationDetails").modal("show");

                // Event listener for PO submission button
                $(document).on('click', '#submitPO', function() {
                    Swal.fire({
                        title: 'Submit Purchase Order',
                        html: `
                            <div class="text-left mb-3">
                                <h5>Shipping Information</h5>
                                <label>Address:</label><input type="text" id="address" name="address" value="${address}" class="form-control" required><br>
                                <label>City:</label><input type="text" id="city" name="city" value="${city}" class="form-control" required><br>
                                <label>State:</label><input type="text" id="state" name="state" value="${state}" class="form-control" required><br>
                                <label>Zip Code:</label><input type="text" id="zipcode" name="zipcode" value="${zipcode}" class="form-control" required><br>
                                <label>Phone Number:</label><input type="text" id="phonenumber" name="phonenumber" value="${phonenumber}" class="form-control" required><br>
                            </div>
                            <div class="text-left">
                                <h5>PO Document</h5>
                                <p>Please upload your Purchase Order document (PDF preferred)</p>
                                <input type="file" id="poDocument" name="poDocument" class="form-control" accept=".pdf,.doc,.docx" required>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'Submit PO',
                        preConfirm: () => {
                            const address = Swal.getPopup().querySelector('#address').value.trim();
                            const city = Swal.getPopup().querySelector('#city').value.trim();
                            const state = Swal.getPopup().querySelector('#state').value.trim();
                            const zipcode = Swal.getPopup().querySelector('#zipcode').value.trim();
                            const phoneNumber = Swal.getPopup().querySelector('#phonenumber').value.trim();
                            const poDocument = Swal.getPopup().querySelector('#poDocument').files[0];
                            
                            if (!address || !city || !state || !zipcode || !phoneNumber || !poDocument) {
                                Swal.showValidationMessage('Please fill out all required fields and upload a PO document');
                                return false;
                            }
                            
                            return { address, city, state, zipcode, phoneNumber, poDocument };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const formData = new FormData();
                            formData.append('address', result.value.address);
                            formData.append('city', result.value.city);
                            formData.append('state', result.value.state);
                            formData.append('zipcode', result.value.zipcode);
                            formData.append('phoneNumber', result.value.phoneNumber);
                            formData.append('poDocument', result.value.poDocument);
                            formData.append('quotationId', quotationId);
                            formData.append('quotationReponseId', quotationReponseId);
                            
                            Swal.fire({
                                title: 'Processing PO Submission',
                                html: 'Please wait while we submit your purchase order...',
                                allowOutsideClick: false,
                                didOpen: () => { Swal.showLoading(); }
                            });
                            
                            $.ajax({
                                type: "POST",
                                url: "/quotations/submitPO",
                                data: formData,
                                processData: false,
                                contentType: false,
                                success: function(response) {
                                    Swal.fire({
                                        title: 'Success!',
                                        text: response.message || 'Your PO has been submitted successfully.',
                                        icon: 'success',
                                        willClose: () => { window.location.href = "/quotations"; }
                                    });
                                },
                                error: function(xhr) {
                                    const errorMessage = xhr.responseJSON?.message || 'An error occurred while submitting your PO.';
                                    Swal.fire({ title: 'Error!', text: errorMessage, icon: 'error' });
                                }
                            });
                        }
                    });
                });
                
                // Event listener for PO approval request button
                $(document).on('click', '#requestPOApproval', function() {
                    const quotationId = $(this).data('quotation-id');
                    const quotationReponseId = $(this).data('id');
                    
                    Swal.fire({
                        title: 'Request PO Payment Approval',
                        html: `
                            <div class="mb-3">
                                <p>Please provide details about your PO payment request:</p>
                                <textarea id="requestNotes" class="form-control" rows="4" 
                                    placeholder="Example: Our company requires PO payments. Our accounting department will process payment upon approval. Purchase order number will be provided once approved."></textarea>
                            </div>
                            <div class="alert alert-info mt-2">
                                <i class="fas fa-info-circle"></i> We typically respond to approval requests within 1-2 business days.
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'Submit Request',
                        cancelButtonText: 'Cancel',
                        focusConfirm: false,
                        preConfirm: () => {
                            const notes = $('#requestNotes').val().trim();
                            if (!notes) {
                                Swal.showValidationMessage('Please provide some details about your request');
                                return false;
                            }
                            return { notes };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            Swal.fire({
                                title: 'Submitting Request...',
                                allowOutsideClick: false,
                                didOpen: () => Swal.showLoading()
                            });
                            
                            $.ajax({
                                type: "POST",
                                url: "/quotations/requestPOApproval",
                                data: {
                                    quotationId: quotationId,
                                    quotationReponseId: quotationReponseId,
                                    notes: result.value.notes
                                },
                                success: function(response) {
                                    Swal.fire({
                                        title: 'Request Submitted!',
                                        icon: 'success',
                                        html: `
                                            <div class="text-center">
                                                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                                                <p>${response.message}</p>
                                                <p class="small text-muted mt-2">You'll receive an email once your request is processed.</p>
                                            </div>
                                        `,
                                        confirmButtonText: 'OK'
                                    });
                                },
                                error: function(xhr) {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: xhr.responseJSON?.message || 'An error occurred while submitting your request.',
                                        icon: 'error'
                                    });
                                }
                            });
                        }
                    });
                });

                // Credit Card Payment Handler
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
                
                            return { address, city, state, zipcode, phoneNumber };
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
                
                                    return { cardNumber, expirationDate, cvv };
                                }
                            }).then((paymentResult) => {
                                if (paymentResult.isConfirmed) {
                                    const paymentData = paymentResult.value;
                
                                    // Combine both shipping and payment data
                                    const formData = {
                                        ...shippingData,
                                        ...paymentData,
                                        amount: productAmount,
                                        quotationId: quotationId,
                                        quotationReponseId: quotationReponseId
                                    };
                
                                    // Send the data via AJAX
                                    Swal.fire({
                                        title: 'Processing Payment',
                                        html: 'Please wait while we process your payment...',
                                        allowOutsideClick: false,
                                        didOpen: () => { Swal.showLoading(); }
                                    });
                
                                    $.ajax({
                                        type: 'POST',
                                        url: '/quotations/chargeCreditCard',
                                        data: formData,
                                        success: function (response) {
                                            const { success, message } = response;
                
                                            Swal.fire({
                                                title: success ? 'Payment Successful!' : 'Payment Failed!',
                                                text: message,
                                                icon: success ? 'success' : 'error',
                                                willClose: () => { window.location.href = "/quotations"; }
                                            });
                                        },
                                        error: function (xhr) {
                                            const errorMessage = xhr.responseJSON?.message || 'An error occurred.';
                                            Swal.fire({ title: 'Payment Error!', text: errorMessage, icon: 'error' });
                                        }
                                    });
                                }
                            });
                        }
                    });
                });                
                
                // ACH Payment Handler
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
                                title: 'ACH Payment',
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
                                        amount: productAmount,
                                        quotationId: quotationId,
                                        quotationReponseId: quotationReponseId
                                    };
                
                                    // Show loading state
                                    Swal.fire({
                                        title: 'Processing Payment',
                                        html: 'Please wait while we process your payment...',
                                        allowOutsideClick: false,
                                        didOpen: () => { Swal.showLoading(); }
                                    });
                
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
                                                willClose: () => { window.location.href = "/quotations"; }
                                            });
                                        },
                                        error: function (xhr) {
                                            const errorMessage = xhr.responseJSON?.message || 'An error occurred.';
                                            Swal.fire({ title: 'Payment Error!', text: errorMessage, icon: 'error' });
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