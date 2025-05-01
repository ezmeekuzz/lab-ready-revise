$(document).ready(function () {
    let table = $('#quotationmasterlist').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "/quotationmasterlist/getData",
            "type": "POST",
            "data": function (d) {
                d.year = $('#yearFilter').val();  // Pass the selected year
                d.month = $('#monthFilter').val(); // Pass the selected month
            }
        },
        "columns": [
            { 
                "data": "uid",
                "visible": false // Hide the User ID column
            },
            { "data": "fullname" },
            { "data": "email" },
            { "data": "reference_number" },
            { "data": "quotation_name" },
            {
                "data": "price",
                "render": function(data, type, row) {
                    if (type === 'display' || type === 'filter') {
                        return '$' + parseFloat(data).toLocaleString('en-US', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    }
                    return data;
                }
            },
            {
                "data": "response_date",
                "render": function (data, type, row) {
                    if (type === 'display' || type === 'filter') {
                        // Format the date for display
                        if (data) {
                            const date = new Date(data);
                            return date.toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'short',
                                day: 'numeric'
                            });
                        }
                        return '';
                    }
                    return data; // Return raw data for sorting and other operations
                }
            },
            {
                "data": "payment_status",
                "render": function (data, type, row) {
                    let statusText = data;
                    let statusClass = '';
            
                    if (row.delivery_date) {
                        statusText = 'Delivery Scheduled';
                        statusClass = 'badge badge-info';
                    } else if (data === 'Unpaid') {
                        statusClass = 'badge badge-warning';
                    } else if (data === 'Paid' || data === 'PO Submitted') {
                        statusClass = 'badge badge-success';
                    }
            
                    return `<span class="${statusClass}">${statusText}</span>`;
                }
            },
            {
                "data": null,
                "render": function (data, type, row) {
                    let buttons = `
                        <a href="#" 
                        title="Mark as Paid" 
                        class="paid-btn" 
                        data-id="${row.quotation_response_id}" 
                        style="color: blue;">
                        <i class="ti ti-money" style="font-size: 18px;"></i>
                        </a>`;

                        if (row.payment_status === 'Paid' || row.payment_status === 'PO Submitted') {
                            buttons += `
                                <a href="#" 
                                title="Update Shipment Status" 
                                class="shipment-btn" 
                                data-id="${row.quotation_id}" 
                                data-fullname="${row.fullname}" 
                                data-reference="${row.reference_number}" 
                                data-email="${row.email}" 
                                style="color: green;">
                                <i class="ti ti-truck" style="font-size: 18px;"></i>
                                </a>
                        
                                <a href="#" 
                                title="Schedule Delivery" 
                                class="schedule-delivery-btn" 
                                data-id="${row.quotation_response_id}" 
                                data-fullname="${row.fullname}" 
                                data-reference="${row.reference_number}" 
                                data-email="${row.email}" 
                                style="color: blue;">
                                <i class="ti ti-calendar" style="font-size: 18px;"></i>
                                </a>
                        
                                <a href="#" 
                                title="Send Invoice" 
                                class="send-invoice-btn" 
                                data-id="${row.quotation_response_id}" 
                                data-quotation-id="${row.quotation_id}"
                                data-reference="${row.reference_number}" 
                                data-email="${row.email}"
                                data-fullname="${row.fullname}"
                                style="color: #6f42c1;">
                                <i class="fa fa-file-text" style="font-size: 18px;"></i>
                                </a>`;
                        }

                    buttons += `
                        <a href="#" 
                        title="Delete Quotation" 
                        class="delete-btn" 
                        data-id="${row.quotation_response_id}" 
                        style="color: red;">
                        <i class="ti ti-trash" style="font-size: 18px;"></i>
                        </a>`;

                    return buttons;
                }
            }
        ],
        "createdRow": function (row, data, dataIndex) {
            $(row).attr('data-id', data.quotation_id);
        },
        "initComplete": function (settings, json) {
            $(this).trigger('dt-init-complete');
        }
    });
    
    // Apply filter when the filter button is clicked
    $('#filterBtn').on('click', function() {
        table.ajax.reload();  // Reload the table with the selected year and month
    });
    
    // Reset filters when the reset button is clicked
    $('#resetBtn').on('click', function() {
        $('#yearFilter').val('');   // Clear the year filter
        $('#monthFilter').val('');  // Clear the month filter
        table.ajax.reload();        // Reload the table with the default data
    });    

    $(document).on('click', '.delete-btn', function () {
        let id = $(this).data('id');
        let row = $(this).closest('tr');

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
                    url: '/quotationmasterlist/delete/' + id,
                    method: 'DELETE',
                    success: function (response) {
                        if (response.status === 'success') {
                            table.row(row).remove().draw(false);
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

    $(document).on('click', '.send-invoice-btn', function(e) {
        e.preventDefault();
        
        const quotationResponseId = $(this).data('id');
        const quotationId = $(this).data('quotation-id');
        const referenceNumber = $(this).data('reference');
        const customerEmail = $(this).data('email');
        const customerName = $(this).data('fullname');
        const invoiceNumber = `${referenceNumber}-INV`;
        
        Swal.fire({
            title: 'Send Invoice to Customer',
            html: `
                <div class="mb-3">
                    <p><strong>Invoice Number:</strong> ${invoiceNumber}</p>
                    <p><strong>Customer:</strong> ${customerName} (${customerEmail})</p>
                </div>
                <div class="mb-3">
                    <label class="form-label">Invoice File (PDF)</label>
                    <input type="file" id="invoiceFile" class="form-control" accept=".pdf" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Additional Message (Optional)</label>
                    <textarea id="invoiceMessage" class="form-control" rows="3" placeholder="Add any additional notes for the customer..."></textarea>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Send Invoice',
            preConfirm: () => {
                const fileInput = document.getElementById('invoiceFile');
                if (!fileInput.files.length) {
                    Swal.showValidationMessage('Please select an invoice PDF file');
                    return false;
                }
                return {
                    file: fileInput.files[0],
                    message: document.getElementById('invoiceMessage').value.trim()
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('invoiceFile', result.value.file);
                formData.append('quotationResponseId', quotationResponseId);
                formData.append('quotationId', quotationId);
                formData.append('referenceNumber', referenceNumber);
                formData.append('customerEmail', customerEmail);
                formData.append('customerName', customerName);
                formData.append('invoiceNumber', invoiceNumber);
                formData.append('message', result.value.message);
                
                Swal.fire({
                    title: 'Sending Invoice',
                    html: 'Please wait while we send the invoice...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
                
                $.ajax({
                    url: '/quotationmasterlist/sendInvoice',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        Swal.fire({
                            title: 'Invoice Sent!',
                            text: `Invoice ${invoiceNumber} has been sent to ${customerEmail}`,
                            icon: 'success'
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            title: 'Error!',
                            text: xhr.responseJSON?.message || 'Failed to send invoice',
                            icon: 'error'
                        });
                    }
                });
            }
        });
    });

    $(document).on('click', '.paid-btn', function () {
        let id = $(this).data('id');
        let row = $(this).closest('tr');

        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, It is already paid!',
            cancelButtonText: 'No, cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/quotationmasterlist/updateStatus/' + id,
                    method: 'POST',
                    success: function (response) {
                        if (response.status === 'success') {
                            table.row(row).draw(false);
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

    $(document).on('click', '.shipment-btn', function () {
        let id = $(this).data('id');
        let email = $(this).data('email');
        let fullname = $(this).data('fullname');
        let reference = $(this).data('reference');
    
        $.ajax({
            url: '/quotationmasterlist/getShipment/' + id,
            method: 'GET',
            success: function (response) {
                if (response.status === 'success') {
                    let data = response.data || {};
                    Swal.fire({
                        title: 'Update Shipment Status',
                        html: `
                            <input type="hidden" id="quotation_id" value="${id}">
                            <input type="hidden" id="fullname" value="${fullname}">
                            <input type="hidden" id="reference" value="${reference}">
                            <input type="hidden" id="email" value="${email}">
                            <div class="form-group">
                                <label for="shipment_link">Shipment Link</label>
                                <input type="text" id="shipment_link" class="form-control" value="${data.shipment_link || ''}" placeholder="Shipment Link">
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'Update',
                        cancelButtonText: 'Cancel',
                        preConfirm: () => {
                            Swal.showLoading();
                            return new Promise((resolve) => {
                                $.ajax({
                                    url: '/quotationmasterlist/updateShipment/' + id,
                                    method: 'POST',
                                    data: {
                                        quotation_id: document.getElementById('quotation_id').value,
                                        fullname: document.getElementById('fullname').value,
                                        reference: document.getElementById('reference').value,
                                        shipment_link: document.getElementById('shipment_link').value,
                                        email: document.getElementById('email').value
                                    },
                                    success: function (response) {
                                        if (response.status === 'success') {
                                            Swal.fire({
                                                icon: 'success',
                                                title: 'Updated!',
                                                text: 'Shipment status and delivery date have been updated.',
                                            });
                                            table.row($(`tr[data-id="${id}"]`)).draw(false);
                                        } else {
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Oops...',
                                                text: 'Something went wrong!',
                                            });
                                        }
                                        resolve();
                                    },
                                    error: function () {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Oops...',
                                            text: 'Something went wrong with the request!',
                                        });
                                        resolve();
                                    }
                                });
                            });
                        }
                    });
                } else {
                    console.error('Server response:', response);
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Failed to fetch shipment data!',
                    });
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Something went wrong with the request!',
                });
            }
        });
    });  

    $(document).on('click', '.schedule-delivery-btn', function () {
        const id = $(this).data('id');
        let email = $(this).data('email');
        let fullname = $(this).data('fullname');
        let reference = $(this).data('reference');
    
        Swal.fire({
            title: 'Schedule Delivery',
            html: `
                <input type="hidden" id="quotation_id" value="${id}">
                <div class="form-group text-start mt-2">
                    <label for="delivery_date">Delivery Date</label>
                    <input type="date" id="delivery_date" class="form-control">
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Submit',
            cancelButtonText: 'Cancel',
            focusConfirm: false,
            preConfirm: () => {
                const deliveryDate = document.getElementById('delivery_date').value;
    
                if (!deliveryDate) {
                    Swal.showValidationMessage('Please select a delivery date');
                    return false;
                }
    
                Swal.showLoading();
    
                return $.ajax({
                    url: '/quotationmasterlist/updateDeliveryDate/' + id,
                    method: 'POST',
                    data: {
                        quotation_id: id,
                        email: email,
                        fullname: fullname,
                        reference: reference,
                        delivery_date: deliveryDate
                    }
                }).then(response => {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Scheduled!',
                            text: 'Delivery date has been scheduled.',
                        });
                        table.row($(`tr[data-id="${id}"]`)).draw(false);
                    } else {
                        throw new Error(response.message || 'Update failed.');
                    }
                }).catch(error => {
                    Swal.showValidationMessage(`Request failed: ${error.message}`);
                });
            }
        });
    });    
});
