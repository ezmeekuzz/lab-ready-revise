$(document).ready(function () {
    let table = $('#requestquotationmasterlist').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "/requestquotationmasterlist/getData",
            "type": "POST",
            "data": function (d) {
                // Add year and month to the request
                d.year = $('#yearFilter').val();
                d.month = $('#monthFilter').val();
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
                "data": "status",
                "render": function (data) {
                    let statusClass = '';
                    if (data === 'Pending') {
                        statusClass = 'badge badge-warning';
                    } else if (data === 'Submitted') {
                        statusClass = 'badge badge-success';
                    } else {
                        statusClass = 'badge-info p-1 rounded';
                    }
                    return `<span class="${statusClass}">${data}</span>`;
                }
            },
            {
                "data": "created_at",
                "render": function (data, type, row) {
                    if (type === 'display' || type === 'filter') {
                        // Format the date for display
                        if (data) {
                            const date = new Date(data);
                            return date.toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'short',
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                        }
                        return '';
                    }
                    return data; // Return raw data for sorting and other operations
                }
            },
            {
                "data": null,
                "orderable": false,
                "render": function (data, type, row) {
                    return `
                        <a href="/download-files/${row.quotation_id}" download title="Download Excel File" style="color: green;">
                            <i class="ti ti-download" style="font-size: 18px;"></i>
                        </a>`;
                }
            }
        ],
        "createdRow": function (row, data) {
            $(row).attr('data-id', data.quotation_id);
            $(row).attr('data-reference', data.reference_name);
            $(row).attr('data-nickname', data.quotation_name);
    
            $('td', row).each(function (index) {
                if (index !== 7) { // Assuming the actions column is at index 5
                    $(this).attr('data-user-id', data.uid);
                    $(this).attr('data-reference', data.reference_name);
                    $(this).attr('data-nickname', data.quotation_name);
                }
            });
        },
        "initComplete": function () {
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

    $('#requestquotationmasterlist tbody').on('click', 'td', function () {
        let cell = table.cell(this);
        let cellIndex = cell.index().column;
    
        if (cellIndex === 7) { // If the cell index is the actions column, do nothing
            return;
        }
    
        let userId = $(this).data('user-id');
        let quotationId = $(this).closest('tr').data('id');
        let reference = $(this).closest('tr').data('reference');
        let nickname = $(this).closest('tr').data('nickname') || reference; // Use reference if nickname is null
    
        $('#user_id').val(userId);
        $('#quotation_id').val(quotationId);
        $('#nickname').val(nickname);
    
        $('#quotationModal').modal('show');
    });

    $('#sendquotation').submit(function (event) {
        event.preventDefault();

        let row = $(this).closest('tr');

        let nickName = $('#nickname').val();
        let price = $('#price').val();
        let invoiceFile = $('#invoicefile')[0].files[0];
        let userId = $('#user_id').val();
        let quotationId = $('#quotation_id').val();

        if (price.trim() === '' || !invoiceFile || !userId || !quotationId) {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Please fill in all the required fields!',
            });
            return;
        }

        let formData = new FormData();
        formData.append('nickname', nickName);
        formData.append('price', price);
        formData.append('invoicefile', invoiceFile);
        formData.append('userId', userId);
        formData.append('quotationId', quotationId);

        $.ajax({
            type: 'POST',
            url: '/requestquotationmasterlist/insert',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            beforeSend: function () {
                Swal.fire({
                    title: 'Sending...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            success: function (response) {
                if (response.success) {
                    $('#sendquotation')[0].reset();
                    $('#user_id').trigger('chosen:updated');
                    table.row(row).draw(false);
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: response.message,
                    });
                }
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'An error occurred while sending quotation. Please try again later.',
                });
                console.error(xhr.responseText);
            }
        });
    });

    $(document).on('click', '.update-status', function () {
        let id = $(this).data('id');
        let row = $(this).closest('tr');

        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Quotation already submitted!',
            cancelButtonText: 'No, cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/requestquotationmasterlist/updateStatus/' + id,
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
    $('#downloadAssembly').click(function(e) {
        e.preventDefault();

        var requestId = $(this).data('id');

        var downloadUrl = '/requestquotationlist/downloadAssemblyFiles/' + requestId;

        window.location.href = downloadUrl;
    });
});
